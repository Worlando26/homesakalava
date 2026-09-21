<?php
/**
 * lib/config.php — Lecture de config/site.php.
 *
 * Point d'entrée unique vers les coordonnées et les réglages d'envoi.
 * Fabrique aussi les liens tel:, mailto: et WhatsApp à partir des valeurs
 * saisies : aucun lien n'est écrit en dur ailleurs dans le projet.
 *
 * Règle de fonctionnement : une valeur restée à « A_REMPLIR », vide, ou
 * absente est traitée comme non renseignée. Le site affiche alors une
 * mention explicite plutôt qu'un lien mort ou une information inventée.
 */

declare(strict_types=1);

final class Config
{
    /** Marqueur des valeurs que le client doit encore saisir. */
    public const A_REMPLIR = 'A_REMPLIR';

    private static ?array $data = null;
    private static string $racine = '';

    /** Charge le fichier une seule fois par requête. */
    private static function data(): array
    {
        if (self::$data !== null) {
            return self::$data;
        }

        self::$racine = dirname(__DIR__);
        $fichier = self::$racine . '/config/site.php';

        if (!is_file($fichier)) {
            // Absence de configuration : le site doit rester debout et le
            // dire, pas s'effondrer.
            self::$data = [];
            return self::$data;
        }

        $data = require $fichier;
        self::$data = is_array($data) ? $data : [];
        return self::$data;
    }

    /** Rechargement forcé — utilisé après une écriture depuis l'admin. */
    public static function recharger(): void
    {
        self::$data = null;
    }

    /**
     * Valeur brute, par chemin pointé : Config::get('smtp.hote').
     * Renvoie le repli si la clé est absente.
     */
    public static function get(string $chemin, mixed $repli = null): mixed
    {
        $noeud = self::data();
        foreach (explode('.', $chemin) as $cle) {
            if (!is_array($noeud) || !array_key_exists($cle, $noeud)) {
                return $repli;
            }
            $noeud = $noeud[$cle];
        }
        return $noeud;
    }

    /**
     * Valeur utilisable, ou chaîne vide.
     * C'est la méthode à employer partout : elle filtre « A_REMPLIR ».
     */
    public static function val(string $chemin): string
    {
        $v = self::get($chemin);
        if (!is_string($v)) {
            return '';
        }
        $v = trim($v);
        return ($v === '' || $v === self::A_REMPLIR) ? '' : $v;
    }

    /** Une valeur a-t-elle été renseignée ? */
    public static function rempli(string $chemin): bool
    {
        return self::val($chemin) !== '';
    }

    /** Liste nettoyée (adresse postale, par exemple). */
    public static function liste(string $chemin): array
    {
        $v = self::get($chemin, []);
        if (!is_array($v)) {
            return [];
        }
        return array_values(array_filter(
            array_map('trim', $v),
            fn($l) => $l !== '' && $l !== self::A_REMPLIR
        ));
    }

    // ══ Liens fabriqués ═══════════════════════════════════════════════

    /**
     * Lien d'appel. Les espaces et séparateurs sont retirés, le « + »
     * conservé : c'est ce qu'attendent les téléphones.
     */
    public static function telLien(): string
    {
        $tel = self::val('hotel.telephone');
        if ($tel === '') {
            return '';
        }
        $propre = preg_replace('/[^\d+]/', '', $tel) ?? '';
        return $propre === '' ? '' : 'tel:' . $propre;
    }

    /**
     * Lien WhatsApp. Le numéro doit être international sans « + » ni
     * zéro initial ; on le nettoie ici pour pardonner les deux erreurs
     * de saisie les plus fréquentes.
     */
    public static function whatsappLien(string $message = ''): string
    {
        $num = self::val('hotel.whatsapp');
        if ($num === '') {
            // À défaut, on tente le numéro de téléphone principal.
            $num = self::val('hotel.telephone');
        }
        if ($num === '') {
            return '';
        }

        $num = preg_replace('/\D/', '', $num) ?? '';
        $num = ltrim($num, '0');
        if (strlen($num) < 8) {
            return '';
        }

        $url = 'https://wa.me/' . $num;
        if ($message !== '') {
            $url .= '?text=' . rawurlencode($message);
        }
        return $url;
    }

    /** Lien e-mail, sujet optionnel. */
    public static function mailtoLien(string $sujet = ''): string
    {
        $mail = self::val('hotel.email');
        if ($mail === '' || !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
            return '';
        }
        return 'mailto:' . $mail . ($sujet !== '' ? '?subject=' . rawurlencode($sujet) : '');
    }

    /**
     * Lien vers le plan d'accès. Le lien saisi a la priorité ; sinon on
     * en fabrique un à partir des coordonnées GPS ; sinon à partir de
     * l'adresse postale.
     */
    public static function mapsLien(): string
    {
        if ($lien = self::val('hotel.maps')) {
            return $lien;
        }

        if ($gps = self::val('hotel.gps')) {
            if (preg_match('/(-?\d{1,3}[.,]\d+)\s*[,;]\s*(-?\d{1,3}[.,]\d+)/', $gps, $m)) {
                $lat = str_replace(',', '.', $m[1]);
                $lon = str_replace(',', '.', $m[2]);
                return 'https://www.google.com/maps/search/?api=1&query=' . $lat . ',' . $lon;
            }
        }

        $adresse = self::liste('hotel.adresse');
        if ($adresse) {
            $requete = self::val('hotel.nom') . ', ' . implode(', ', $adresse);
            return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($requete);
        }

        return '';
    }

    /** Réseaux sociaux effectivement renseignés, prêts à afficher. */
    public static function reseaux(): array
    {
        $noms = [
            'facebook'    => 'Facebook',
            'instagram'   => 'Instagram',
            'tripadvisor' => 'TripAdvisor',
            'booking'     => 'Booking.com',
        ];

        $out = [];
        foreach ($noms as $cle => $libelle) {
            $url = self::val('reseaux.' . $cle);
            if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
                $out[] = ['cle' => $cle, 'label' => $libelle, 'href' => $url];
            }
        }
        return $out;
    }

    /** Adresse du site, sans barre oblique finale. */
    public static function siteUrl(): string
    {
        $url = self::val('site.url');
        return $url === '' ? '' : rtrim($url, '/');
    }

    // ══ Réglages du formulaire ════════════════════════════════════════

    public static function modeTest(): bool
    {
        // Par prudence, l'absence de réglage vaut mode test : on préfère
        // ne rien envoyer plutôt qu'envoyer à tort.
        return (bool) self::get('formulaire.mode_test', true);
    }

    public static function maxParHeure(): int
    {
        return max(1, (int) self::get('formulaire.max_par_heure', 5));
    }

    /** Le formulaire peut-il réellement fonctionner ? */
    public static function formulaireActif(): bool
    {
        if (!self::rempli('hotel.email')) {
            return false;
        }
        // En mode test, le SMTP n'a pas besoin d'être configuré.
        if (self::modeTest()) {
            return true;
        }
        return self::rempli('smtp.hote')
            && self::rempli('smtp.utilisateur')
            && self::rempli('smtp.expediteur_email');
    }

    /**
     * Ce qui reste à renseigner, en clair.
     * Alimente la page de diagnostic et l'outil de livraison.
     *
     * @return array<int, array{cle:string, libelle:string, bloquant:bool}>
     */
    public static function manquants(): array
    {
        $champs = [
            ['hotel.telephone',       'Le numéro de téléphone',                 true],
            ['hotel.email',           "L'adresse e-mail qui reçoit les demandes", true],
            ['hotel.whatsapp',        'Le numéro WhatsApp',                     false],
            ['hotel.gps',             'Les coordonnées GPS',                    false],
            ['hotel.maps',            'Le lien Google Maps',                    false],
            ['site.url',              "L'adresse définitive du site",           true],
            ['smtp.hote',             "Le serveur d'envoi (SMTP)",              true],
            ['smtp.utilisateur',      "L'identifiant SMTP",                     true],
            ['smtp.motdepasse',       'Le mot de passe SMTP',                   true],
            ['smtp.expediteur_email', "L'adresse expéditrice",                  true],
        ];

        $out = [];
        foreach ($champs as [$cle, $libelle, $bloquant]) {
            if (!self::rempli($cle)) {
                $out[] = ['cle' => $cle, 'libelle' => $libelle, 'bloquant' => $bloquant];
            }
        }
        return $out;
    }

    /**
     * Écrit le fichier de configuration.
     *
     * Utilisé par l'onglet Coordonnées du back-office. On réécrit le
     * tableau en PHP littéral plutôt que d'éditer le texte du fichier :
     * aucune saisie n'est jamais interprétée comme du code.
     *
     * @throws RuntimeException
     */
    public static function ecrire(array $data): void
    {
        $racine  = dirname(__DIR__);
        $fichier = $racine . '/config/site.php';

        $entete = <<<'PHP'
            <?php
            /**
             * config/site.php — LE SEUL FICHIER À REMPLIR
             *
             * Ce fichier a été mis à jour depuis le back-office.
             * Vous pouvez aussi le modifier à la main : le site le relit à
             * chaud, sans rien à régénérer.
             *
             * Une valeur laissée à « A_REMPLIR » n'est jamais inventée : le
             * site affiche « à renseigner » et masque le lien correspondant.
             *
             * CE FICHIER CONTIENT UN MOT DE PASSE une fois rempli. Il est
             * refusé au navigateur par config/.htaccess et exclu du dossier
             * de livraison. Ne le publiez pas sur un dépôt git ouvert.
             */

            declare(strict_types=1);

            return
            PHP;

        $corps = self::exporter($data, 0);
        $php   = $entete . ' ' . $corps . ";\n";

        // Écriture atomique : une coupure ne doit pas laisser un fichier
        // de configuration à moitié écrit, qui casserait tout le site.
        $tmp = $fichier . '.tmp';
        if (file_put_contents($tmp, $php, LOCK_EX) === false) {
            throw new RuntimeException("Écriture impossible dans config/.");
        }

        // On vérifie que le fichier produit est du PHP valide AVANT de
        // remplacer l'original.
        $verif = @include $tmp;
        if (!is_array($verif)) {
            @unlink($tmp);
            throw new RuntimeException("Le fichier produit est invalide, l'ancien est conservé.");
        }

        if (!rename($tmp, $fichier)) {
            @unlink($tmp);
            throw new RuntimeException("Remplacement de config/site.php impossible.");
        }

        @chmod($fichier, 0640);
        self::recharger();
    }

    /** Export récursif en PHP littéral, indenté et lisible. */
    private static function exporter(mixed $v, int $niveau): string
    {
        $pad  = str_repeat('    ', $niveau + 1);
        $fin  = str_repeat('    ', $niveau);

        if (is_array($v)) {
            $liste = array_is_list($v);
            $out = "[\n";
            foreach ($v as $k => $item) {
                $out .= $pad;
                if (!$liste) {
                    $out .= var_export((string) $k, true) . ' => ';
                }
                $out .= self::exporter($item, $niveau + 1) . ",\n";
            }
            return $out . $fin . ']';
        }

        if (is_bool($v))  { return $v ? 'true' : 'false'; }
        if (is_int($v))   { return (string) $v; }
        if ($v === null)  { return "''"; }

        return var_export((string) $v, true);
    }
}
