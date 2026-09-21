<?php
/**
 * lib/mailer.php — Envoi d'e-mails par SMTP, sans dépendance.
 *
 * Deux classes :
 *   Mailer — compose le message (en-têtes, MIME, encodage) et l'envoie ;
 *   Smtp   — dialogue avec le serveur d'envoi.
 *
 * Pourquoi pas mail() : cette fonction remet le message au programme local
 * de l'hébergeur, sans authentification. Les messages partent alors le plus
 * souvent en indésirables, et rien ne permet de savoir s'ils sont arrivés.
 * Le SMTP authentifié dit, lui, si le serveur a accepté le message.
 *
 * MODE TEST : quand config/site.php a 'mode_test' => true, aucun message ne
 * part. Chaque e-mail est écrit en clair dans data/emails-test.log, tel
 * qu'il aurait été envoyé. Cela permet de vérifier tout le parcours du
 * formulaire sans serveur SMTP.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

final class Mailer
{
    /**
     * Envoie un message.
     *
     * @param array{
     *   a: array{email:string, nom?:string},
     *   sujet: string,
     *   html: string,
     *   texte: string,
     *   replyTo?: array{email:string, nom?:string},
     *   bcc?: string
     * } $msg
     * @return array{ok:bool, erreur:string, mode:string}
     */
    public static function envoyer(array $msg): array
    {
        $destEmail = trim((string) ($msg['a']['email'] ?? ''));
        if (!filter_var($destEmail, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'erreur' => 'Adresse destinataire invalide.', 'mode' => 'aucun'];
        }

        $expEmail = Config::val('smtp.expediteur_email') ?: Config::val('hotel.email');
        $expNom   = Config::val('smtp.expediteur_nom') ?: Config::val('hotel.nom');

        if (Config::modeTest()) {
            // En mode test l'expéditeur n'a pas besoin d'exister.
            $expEmail = $expEmail ?: 'test@localhost';
        } elseif (!filter_var($expEmail, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'erreur' => "Adresse expéditrice absente de config/site.php.", 'mode' => 'aucun'];
        }

        $brut = self::composer([
            'de'       => ['email' => $expEmail, 'nom' => $expNom],
            'a'        => ['email' => $destEmail, 'nom' => (string) ($msg['a']['nom'] ?? '')],
            'sujet'    => (string) ($msg['sujet'] ?? ''),
            'html'     => (string) ($msg['html'] ?? ''),
            'texte'    => (string) ($msg['texte'] ?? ''),
            'replyTo'  => $msg['replyTo'] ?? null,
        ]);

        // Destinataires réels : le destinataire visible, plus la copie cachée.
        $rcpt = [$destEmail];
        $bcc  = trim((string) ($msg['bcc'] ?? ''));
        if ($bcc !== '' && filter_var($bcc, FILTER_VALIDATE_EMAIL)) {
            $rcpt[] = $bcc;
        }

        if (Config::modeTest()) {
            self::journaliser($expEmail, $rcpt, $brut);
            return ['ok' => true, 'erreur' => '', 'mode' => 'test'];
        }

        try {
            $smtp = new Smtp(
                Config::val('smtp.hote'),
                (int) Config::get('smtp.port', 587),
                (string) Config::get('smtp.securite', 'tls'),
                Config::val('smtp.utilisateur'),
                (string) Config::get('smtp.motdepasse', '')
            );
            $smtp->envoyer($expEmail, $rcpt, $brut);
            return ['ok' => true, 'erreur' => '', 'mode' => 'smtp'];
        } catch (Throwable $e) {
            // Le détail technique va au journal, pas au visiteur.
            error_log('[mailer] ' . $e->getMessage());
            return [
                'ok'     => false,
                'erreur' => "Le message n'a pas pu être remis au serveur d'envoi.",
                'mode'   => 'smtp',
            ];
        }
    }

    // ══ Composition du message ════════════════════════════════════════

    /** Construit le message complet, en-têtes et corps MIME. */
    private static function composer(array $m): string
    {
        $limite = "=_sakalava_" . bin2hex(random_bytes(12));

        $entetes = [
            'Date'         => date('r'),
            'Message-ID'   => '<' . bin2hex(random_bytes(12)) . '@'
                            . (self::domaine($m['de']['email']) ?: 'localhost') . '>',
            'From'         => self::adresse($m['de']['email'], $m['de']['nom']),
            'To'           => self::adresse($m['a']['email'], $m['a']['nom']),
            'Subject'      => self::encoder($m['sujet']),
            'MIME-Version' => '1.0',
            'Content-Type' => 'multipart/alternative; boundary="' . $limite . '"',
            // Indique aux filtres que le message est déclenché par un
            // formulaire, et non envoyé en masse.
            'Auto-Submitted' => 'auto-generated',
        ];

        // Reply-To : c'est lui qui permet de répondre directement au client
        // depuis la boîte de la maison.
        if (!empty($m['replyTo']['email'])
            && filter_var($m['replyTo']['email'], FILTER_VALIDATE_EMAIL)) {
            $entetes['Reply-To'] = self::adresse(
                $m['replyTo']['email'],
                (string) ($m['replyTo']['nom'] ?? '')
            );
        }

        $out = '';
        foreach ($entetes as $cle => $valeur) {
            $out .= $cle . ': ' . self::nettoyer($valeur) . "\r\n";
        }
        $out .= "\r\n";

        // Partie texte d'abord : c'est l'ordre attendu, du plus simple au
        // plus riche. Les messageries affichent la dernière qu'elles savent
        // lire.
        $out .= '--' . $limite . "\r\n";
        $out .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $out .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $out .= chunk_split(base64_encode($m['texte']), 76, "\r\n");

        $out .= '--' . $limite . "\r\n";
        $out .= "Content-Type: text/html; charset=UTF-8\r\n";
        $out .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $out .= chunk_split(base64_encode($m['html']), 76, "\r\n");

        $out .= '--' . $limite . "--\r\n";

        return $out;
    }

    /** « Nom <adresse> », le nom encodé s'il sort de l'ASCII. */
    private static function adresse(string $email, string $nom = ''): string
    {
        $email = self::nettoyer($email);
        $nom   = trim(self::nettoyer($nom));
        return $nom === '' ? $email : self::encoder($nom) . ' <' . $email . '>';
    }

    /**
     * Encodage RFC 2047 des en-têtes non ASCII.
     * Sans lui, « Réservation » arrive illisible dans la plupart des
     * messageries.
     */
    private static function encoder(string $texte): string
    {
        $texte = self::nettoyer($texte);
        if (preg_match('/^[\x20-\x7E]*$/', $texte)) {
            return $texte;
        }
        return '=?UTF-8?B?' . base64_encode($texte) . '?=';
    }

    /**
     * Retire retours chariot et sauts de ligne d'une valeur d'en-tête.
     *
     * C'est la protection contre l'injection d'en-têtes : sans elle, un
     * visiteur qui écrirait un saut de ligne suivi de « Bcc: » dans le champ
     * « nom » ferait envoyer une copie du message à qui il veut.
     */
    private static function nettoyer(string $v): string
    {
        return trim(str_replace(["\r", "\n", "\0"], ' ', $v));
    }

    private static function domaine(string $email): string
    {
        $pos = strrpos($email, '@');
        return $pos === false ? '' : substr($email, $pos + 1);
    }

    /** Écrit le message dans le journal, en mode test. */
    private static function journaliser(string $de, array $rcpt, string $brut): void
    {
        $dossier = dirname(__DIR__) . '/data';
        if (!is_dir($dossier)) {
            return;
        }

        $sep = str_repeat('═', 72);
        $bloc = "\n$sep\n"
              . "  MODE TEST — ce message n'a PAS été envoyé\n"
              . '  ' . date('d/m/Y à H:i:s') . "\n"
              . "  De        : $de\n"
              . '  Vers      : ' . implode(', ', $rcpt) . "\n"
              . "$sep\n"
              . self::lisible($brut)
              . "\n";

        @file_put_contents($dossier . '/emails-test.log', $bloc, FILE_APPEND | LOCK_EX);
    }

    /**
     * Décode les parties base64 pour que le journal se lise à l'œil nu.
     * Un journal illisible ne sert à rien pour vérifier un formulaire.
     */
    private static function lisible(string $brut): string
    {
        return (string) preg_replace_callback(
            '/Content-Transfer-Encoding: base64\r\n\r\n([A-Za-z0-9+\/=\r\n]+)/',
            function (array $m): string {
                $decode = base64_decode(preg_replace('/\s+/', '', $m[1]) ?: '', true);
                return "Contenu :\n\n" . ($decode === false ? $m[1] : $decode) . "\n\n";
            },
            $brut
        );
    }
}


/**
 * Client SMTP minimal : connexion, STARTTLS, authentification, envoi.
 * Chaque étape vérifie le code de réponse du serveur et échoue fort.
 */
final class Smtp
{
    private const DELAI = 15;

    /** @var resource|null */
    private $flux = null;

    public function __construct(
        private string $hote,
        private int $port,
        private string $securite,
        private string $utilisateur,
        private string $motdepasse,
    ) {
        if ($this->hote === '') {
            throw new RuntimeException("Serveur SMTP absent de config/site.php.");
        }
    }

    /** @param string[] $destinataires */
    public function envoyer(string $de, array $destinataires, string $message): void
    {
        $this->connecter();

        try {
            $this->lire(220);
            $this->dialogue('EHLO ' . $this->nomClient(), 250);

            if (strtolower($this->securite) === 'tls') {
                $this->dialogue('STARTTLS', 220);
                $this->activerTls();
                // Après STARTTLS, la session repart de zéro : il faut
                // redire EHLO sur le canal désormais chiffré.
                $this->dialogue('EHLO ' . $this->nomClient(), 250);
            }

            if ($this->utilisateur !== '') {
                $this->authentifier();
            }

            $this->dialogue('MAIL FROM:<' . $de . '>', 250);
            foreach ($destinataires as $rcpt) {
                $this->dialogue('RCPT TO:<' . $rcpt . '>', [250, 251]);
            }

            $this->dialogue('DATA', 354);
            $this->ecrire($this->proteger($message) . "\r\n.\r\n");
            $this->lire(250);

            $this->dialogue('QUIT', 221);
        } finally {
            $this->fermer();
        }
    }

    private function connecter(): void
    {
        $prefixe = strtolower($this->securite) === 'ssl' ? 'ssl://' : 'tcp://';

        $contexte = stream_context_create([
            'ssl' => ['verify_peer' => true, 'verify_peer_name' => true],
        ]);

        $flux = @stream_socket_client(
            $prefixe . $this->hote . ':' . $this->port,
            $errno,
            $errstr,
            self::DELAI,
            STREAM_CLIENT_CONNECT,
            $contexte
        );

        if ($flux === false) {
            throw new RuntimeException(
                "Connexion à {$this->hote}:{$this->port} impossible ($errno $errstr)."
            );
        }

        stream_set_timeout($flux, self::DELAI);
        $this->flux = $flux;
    }

    private function activerTls(): void
    {
        $ok = @stream_socket_enable_crypto(
            $this->flux,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );
        if ($ok !== true) {
            throw new RuntimeException("Passage en TLS refusé par {$this->hote}.");
        }
    }

    /**
     * AUTH LOGIN d'abord, AUTH PLAIN en repli : entre les deux, presque
     * tous les hébergeurs sont couverts.
     */
    private function authentifier(): void
    {
        try {
            $this->dialogue('AUTH LOGIN', 334);
            $this->dialogue(base64_encode($this->utilisateur), 334);
            $this->dialogue(base64_encode($this->motdepasse), 235);
        } catch (RuntimeException $e) {
            $jeton = base64_encode("\0" . $this->utilisateur . "\0" . $this->motdepasse);
            $this->dialogue('AUTH PLAIN ' . $jeton, 235);
        }
    }

    /** @param int|int[] $attendu */
    private function dialogue(string $commande, int|array $attendu): string
    {
        $this->ecrire($commande . "\r\n");
        return $this->lire($attendu);
    }

    private function ecrire(string $donnees): void
    {
        if (@fwrite($this->flux, $donnees) === false) {
            throw new RuntimeException("Écriture impossible vers {$this->hote}.");
        }
    }

    /** @param int|int[] $attendu */
    private function lire(int|array $attendu): string
    {
        $attendus = is_array($attendu) ? $attendu : [$attendu];
        $reponse  = '';

        while (($ligne = @fgets($this->flux, 515)) !== false) {
            $reponse .= $ligne;
            // Une réponse multiligne a un tiret en quatrième caractère ;
            // la dernière ligne a une espace.
            if (strlen($ligne) < 4 || $ligne[3] !== '-') {
                break;
            }
        }

        $meta = stream_get_meta_data($this->flux);
        if (!empty($meta['timed_out'])) {
            throw new RuntimeException("Le serveur {$this->hote} ne répond plus.");
        }

        $code = (int) substr(trim($reponse), 0, 3);
        if (!in_array($code, $attendus, true)) {
            throw new RuntimeException(
                'Réponse inattendue du serveur : ' . trim($reponse)
                . ' (attendu ' . implode(' ou ', $attendus) . ')'
            );
        }

        return $reponse;
    }

    /**
     * Protection des points en début de ligne.
     * Une ligne réduite à « . » signale la fin du message : sans cette
     * transformation, un message contenant une telle ligne serait tronqué.
     */
    private function proteger(string $message): string
    {
        $message = str_replace(["\r\n", "\r", "\n"], "\r\n", $message);
        return preg_replace('/^\./m', '..', $message) ?? $message;
    }

    /** Nom annoncé au serveur : un nom de domaine, jamais une adresse IP nue. */
    private function nomClient(): string
    {
        $url = Config::siteUrl();
        $hote = $url !== '' ? (parse_url($url, PHP_URL_HOST) ?: '') : '';
        if ($hote === '') {
            $hote = (string) ($_SERVER['SERVER_NAME'] ?? 'localhost');
        }
        return preg_match('/^[A-Za-z0-9.\-]+$/', $hote) ? $hote : 'localhost';
    }

    private function fermer(): void
    {
        if (is_resource($this->flux)) {
            @fclose($this->flux);
        }
        $this->flux = null;
    }
}
