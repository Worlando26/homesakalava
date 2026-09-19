<?php
/**
 * admin/inc/bootstrap.php — Socle commun à toutes les pages d'administration.
 *
 * Inclus en tout premier par chaque page admin. Il met en place la session,
 * l'accès aux données, la protection CSRF et les messages flash.
 */

declare(strict_types=1);

// Les erreurs ne doivent jamais s'afficher à l'écran en production : elles
// révèlent des chemins de fichiers. On les journalise à la place.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

define('APP_ROOT', dirname(__DIR__, 2));

require_once APP_ROOT . '/lib/content.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/flash.php';

/**
 * La connexion est-elle chiffrée ?
 *
 * Sur la plupart des hébergements mutualisés, le certificat est géré par un
 * répartiteur placé devant PHP : $_SERVER['HTTPS'] n'est alors pas renseigné
 * alors que le visiteur est bien en HTTPS. Sans ce contrôle élargi, le cookie
 * de session ne serait jamais marqué « secure » en production.
 */
function is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if (($_SERVER['SERVER_PORT'] ?? '') === '443') {
        return true;
    }
    // En-têtes posés par les répartiteurs de charge et les CDN.
    $forwarded = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if ($forwarded !== '') {
        // La valeur peut être une liste : « https, http ». Le premier segment
        // est celui vu par le client.
        return str_starts_with(trim(explode(',', $forwarded)[0]), 'https');
    }
    if (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? '')) === 'on') {
        return true;
    }
    return false;
}

// ─── Session durcie ───────────────────────────────────────────────────────
if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = is_https();

    session_set_cookie_params([
        'lifetime' => 0,          // cookie de session
        'path'     => '/',
        'httponly' => true,       // inaccessible au JavaScript
        'secure'   => $https,     // passe à true automatiquement en HTTPS
        'samesite' => 'Lax',      // bloque les requêtes inter-sites
    ]);
    session_name('sakalava_admin');
    session_start();
}

// ─── En-têtes de sécurité ─────────────────────────────────────────────────
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

// L'administration ne doit jamais être mise en cache : un navigateur partagé
// pourrait sinon réafficher une page depuis l'historique après déconnexion.
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');

// En HTTPS, on demande au navigateur de ne plus jamais revenir en clair.
if (is_https()) {
    header('Strict-Transport-Security: max-age=31536000');
}
// L'admin n'utilise ni CDN ni script externe : tout est servi localement.
header(
    "Content-Security-Policy: default-src 'self'; img-src 'self' data:; "
    . "style-src 'self'; script-src 'self'; form-action 'self'; "
    . "frame-ancestors 'none'; base-uri 'self'"
);

// ─── Accès aux données ────────────────────────────────────────────────────
$store = new ContentStore(APP_ROOT);

/** Échappement HTML — à utiliser sur TOUTE valeur affichée. */
function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Redirige puis stoppe le script (jamais de code après une redirection). */
function redirect(string $path): never
{
    header('Location: ' . $path);
    exit;
}

/**
 * Nettoie une chaîne saisie : encodage garanti UTF-8, caractères de contrôle
 * retirés, espaces de bord supprimés.
 *
 * L'étape d'encodage n'est pas de la coquetterie : un texte collé depuis un
 * traitement de texte peut arriver en Windows-1252. Sans conversion, le
 * filtre ci-dessous renverrait null et la saisie du gérant disparaîtrait
 * sans le moindre message — on préfère récupérer le texte.
 */
function clean_input(string $value): string
{
    if (!mb_check_encoding($value, 'UTF-8')) {
        $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
    }

    // Caractères de contrôle, sauf tabulation et retours à la ligne, qui
    // sont légitimes dans les textes longs.
    $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);

    // Ceinture et bretelles : si le filtre échoue malgré tout, on conserve
    // la saisie d'origine plutôt que de la perdre.
    return trim($cleaned ?? $value);
}

/** Valeur POST nettoyée : chaîne, espaces en trop retirés. */
function post_str(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $default;
    return is_string($v) ? clean_input($v) : $default;
}

/** Liste POST nettoyée, entrées vides retirées. */
function post_list(string $key): array
{
    $v = $_POST[$key] ?? [];
    if (!is_array($v)) {
        return [];
    }
    $out = [];
    foreach ($v as $item) {
        if (!is_string($item)) {
            continue;
        }
        $item = clean_input($item);
        if ($item !== '') {
            $out[] = $item;
        }
    }
    return $out;
}

function post_bool(string $key): bool
{
    return !empty($_POST[$key]);
}

/** Vrai si la requête courante est une soumission de formulaire. */
function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}
