<?php
/**
 * admin/inc/auth.php — Authentification administrateur.
 *
 * Un seul compte, un seul rôle. Pas d'inscription publique, pas de
 * réinitialisation par e-mail (la maison n'a pas encore d'adresse) : le mot
 * de passe se change en ligne de commande ou depuis la page Réglages.
 *
 * Le fichier d'identifiants (data/admin.json) n'est jamais versionné.
 */

declare(strict_types=1);

final class Auth
{
    /** Déconnexion après 30 minutes d'inactivité. */
    private const IDLE_TIMEOUT = 1800;

    /** Session non renouvelable au-delà de 12 heures, même active. */
    private const ABSOLUTE_TIMEOUT = 43200;

    /** Blocage après 5 échecs, pendant 15 minutes. */
    private const MAX_ATTEMPTS  = 5;
    private const LOCKOUT_TIME  = 900;

    private static function file(): string
    {
        return APP_ROOT . '/data/admin.json';
    }

    /** Le compte administrateur a-t-il déjà été créé ? */
    public static function isConfigured(): bool
    {
        return is_file(self::file());
    }

    private static function load(): array
    {
        if (!self::isConfigured()) {
            return [];
        }
        $data = json_decode((string) file_get_contents(self::file()), true);
        return is_array($data) ? $data : [];
    }

    private static function save(array $data): void
    {
        $dir = dirname(self::file());
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents(
            self::file(),
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
            LOCK_EX
        );
        @chmod(self::file(), 0600);
    }

    /**
     * Crée ou remplace le compte administrateur.
     * Le mot de passe en clair ne quitte jamais cette méthode.
     */
    public static function setPassword(string $username, string $password): void
    {
        if (strlen($password) < 10) {
            throw new InvalidArgumentException(
                'Le mot de passe doit faire au moins 10 caractères.'
            );
        }

        $existing = self::load();

        self::save([
            'username'   => $username,
            // Argon2id : résistant aux attaques par GPU, disponible sur ce PHP.
            'hash'       => password_hash($password, PASSWORD_ARGON2ID),
            'created_at' => $existing['created_at'] ?? date('c'),
            'updated_at' => date('c'),
            'attempts'   => [],
        ]);
    }

    /** Durée de blocage restante en secondes, 0 si la connexion est permise. */
    public static function lockoutRemaining(): int
    {
        $data  = self::load();
        $fails = array_filter(
            $data['attempts'] ?? [],
            fn($t) => $t > time() - self::LOCKOUT_TIME
        );

        if (count($fails) < self::MAX_ATTEMPTS) {
            return 0;
        }
        return max(0, (int) min($fails) + self::LOCKOUT_TIME - time());
    }

    /**
     * Tente une connexion.
     *
     * @return string Chaîne vide si la connexion réussit, message d'erreur sinon.
     */
    public static function attempt(string $username, string $password): string
    {
        if (!self::isConfigured()) {
            return "Aucun compte administrateur n'existe encore. "
                 . "Lancez : php tools/create-admin.php";
        }

        $wait = self::lockoutRemaining();
        if ($wait > 0) {
            return 'Trop de tentatives. Réessayez dans '
                 . (int) ceil($wait / 60) . ' minute(s).';
        }

        $data = self::load();

        // hash_equals + password_verify : comparaison à temps constant des
        // deux côtés, pour ne pas révéler si c'est le nom ou le mot de passe
        // qui est faux.
        $userOk = hash_equals((string) ($data['username'] ?? ''), $username);
        $passOk = password_verify($password, (string) ($data['hash'] ?? ''));

        if (!$userOk || !$passOk) {
            $data['attempts'][] = time();
            // On ne conserve que la fenêtre utile.
            $data['attempts'] = array_values(array_filter(
                $data['attempts'],
                fn($t) => $t > time() - self::LOCKOUT_TIME
            ));
            self::save($data);

            $left = self::MAX_ATTEMPTS - count($data['attempts']);
            return $left > 0
                ? "Identifiants incorrects. Il vous reste $left tentative(s)."
                : 'Trop de tentatives. Compte bloqué pendant 15 minutes.';
        }

        // Rehachage si les paramètres d'Argon2 ont changé depuis la création.
        if (password_needs_rehash($data['hash'], PASSWORD_ARGON2ID)) {
            $data['hash'] = password_hash($password, PASSWORD_ARGON2ID);
        }

        $data['attempts']   = [];
        $data['last_login'] = date('c');
        self::save($data);

        // Nouvelle identité de session : protège contre la fixation de session.
        session_regenerate_id(true);
        $_SESSION['admin']      = $username;
        $_SESSION['login_time'] = time();
        $_SESSION['last_seen']  = time();

        return '';
    }

    /** Vérifie le mot de passe courant (changement de mot de passe). */
    public static function verifyCurrent(string $password): bool
    {
        $data = self::load();
        return password_verify($password, (string) ($data['hash'] ?? ''));
    }

    public static function username(): string
    {
        return (string) ($_SESSION['admin'] ?? '');
    }

    /** Session valide ? Applique les deux expirations. */
    public static function check(): bool
    {
        if (empty($_SESSION['admin'])) {
            return false;
        }

        $now = time();
        $idle     = $now - (int) ($_SESSION['last_seen']  ?? 0);
        $lifetime = $now - (int) ($_SESSION['login_time'] ?? 0);

        if ($idle > self::IDLE_TIMEOUT || $lifetime > self::ABSOLUTE_TIMEOUT) {
            self::logout();
            return false;
        }

        $_SESSION['last_seen'] = $now;
        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires'  => time() - 42000,
                'path'     => $p['path'],
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }
        session_destroy();
    }

    /**
     * Garde-barrière : à appeler en haut de CHAQUE page admin protégée.
     * La protection est ici, jamais dans le simple fait de ne pas afficher
     * un lien.
     */
    public static function require(): void
    {
        if (!self::check()) {
            $target = $_SERVER['REQUEST_URI'] ?? '';
            redirect('login.php' . ($target ? '?suite=' . urlencode($target) : ''));
        }
    }
}


/**
 * Jeton CSRF : un par session, vérifié sur toutes les écritures.
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf'];
    }

    /** Champ caché à inclure dans chaque formulaire. */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function valid(): bool
    {
        $sent = $_POST['_csrf'] ?? '';
        return is_string($sent)
            && !empty($_SESSION['csrf'])
            && hash_equals($_SESSION['csrf'], $sent);
    }

    /**
     * Rejette la requête si le jeton est absent ou invalide.
     * Appelée systématiquement avant toute écriture.
     */
    public static function require(): void
    {
        if (!self::valid()) {
            http_response_code(400);
            exit('Requête refusée : jeton de sécurité invalide ou expiré. '
               . 'Revenez en arrière et rechargez la page.');
        }
    }
}
