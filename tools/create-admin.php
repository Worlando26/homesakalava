<?php
/**
 * tools/create-admin.php — Crée ou remplace le compte administrateur.
 *
 * À lancer depuis un terminal, à la racine du projet :
 *     php tools/create-admin.php
 *
 * Le mot de passe est demandé de façon interactive et n'apparaît ni dans
 * l'historique du terminal, ni dans un fichier versionné : il est haché en
 * Argon2id dans data/admin.json, qui est exclu de git.
 *
 * Usage non interactif (à éviter, le mot de passe reste dans l'historique) :
 *     php tools/create-admin.php --user=boda --password="..."
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Ce script s'exécute uniquement en ligne de commande.\n");
}

define('APP_ROOT', dirname(__DIR__));
require_once APP_ROOT . '/lib/content.php';

// Auth::require() et Csrf dépendent de la session web ; en CLI on ne charge
// que ce qui est nécessaire au hachage.
function e(?string $v): string { return (string) $v; }
function redirect(string $p): never { exit; }
require_once APP_ROOT . '/admin/inc/auth.php';

// ─── Arguments ────────────────────────────────────────────────────────────
$opts = getopt('', ['user::', 'password::']);

/** Lecture d'un mot de passe sans écho quand le terminal le permet. */
function promptHidden(string $label): string
{
    echo $label;

    // Sous Windows, on bascule l'écho via PowerShell ; ailleurs, via stty.
    if (DIRECTORY_SEPARATOR === '\\') {
        $cmd = 'powershell -NoProfile -Command '
             . '"$p = Read-Host -AsSecureString; '
             . '[Runtime.InteropServices.Marshal]::PtrToStringAuto('
             . '[Runtime.InteropServices.Marshal]::SecureStringToBSTR($p))"';
        $value = shell_exec($cmd);
        echo PHP_EOL;
        return rtrim((string) $value, "\r\n");
    }

    shell_exec('stty -echo 2>/dev/null');
    $value = rtrim((string) fgets(STDIN), "\r\n");
    shell_exec('stty echo 2>/dev/null');
    echo PHP_EOL;
    return $value;
}

function prompt(string $label, string $default = ''): string
{
    echo $label;
    $v = rtrim((string) fgets(STDIN), "\r\n");
    return $v !== '' ? $v : $default;
}

echo "\n";
echo "══════════════════════════════════════════════\n";
echo "  Compte administrateur — Home Sakalava\n";
echo "══════════════════════════════════════════════\n\n";

if (Auth::isConfigured()) {
    echo "Un compte existe déjà. Continuer remplacera son mot de passe.\n";
    $go = prompt("Continuer ? (o/N) : ", 'n');
    if (!in_array(strtolower($go), ['o', 'oui', 'y', 'yes'], true)) {
        echo "Annulé. Rien n'a été modifié.\n";
        exit(0);
    }
    echo "\n";
}

$user = $opts['user'] ?? prompt("Identifiant [admin] : ", 'admin');
if (!preg_match('/^[A-Za-z0-9._-]{3,32}$/', $user)) {
    fwrite(STDERR, "Identifiant invalide : 3 à 32 caractères, lettres, chiffres, . _ -\n");
    exit(1);
}

if (isset($opts['password'])) {
    $pass = (string) $opts['password'];
} else {
    echo "\nLe mot de passe doit faire au moins 10 caractères.\n";
    echo "Conseil : trois ou quatre mots sans rapport valent mieux qu'un mot\n";
    echo "compliqué — par exemple « plage-vanille-baobab-37 ».\n\n";

    $pass  = promptHidden("Mot de passe        : ");
    $again = promptHidden("Confirmer           : ");

    if ($pass !== $again) {
        fwrite(STDERR, "\nLes deux saisies ne correspondent pas. Rien n'a été modifié.\n");
        exit(1);
    }
}

try {
    Auth::setPassword($user, $pass);
} catch (Throwable $ex) {
    fwrite(STDERR, "\nÉchec : " . $ex->getMessage() . "\n");
    exit(1);
}

echo "\n";
echo "Compte enregistré.\n";
echo "  Identifiant : {$user}\n";
echo "  Fichier     : data/admin.json (haché en Argon2id, exclu de git)\n\n";
echo "Connexion : http://localhost/talinjo/admin/login.php\n\n";
