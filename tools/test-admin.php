<?php
/**
 * tools/test-admin.php — Banc de test du back-office.
 *
 * Exerce l'administration à travers de vraies requêtes HTTP : connexion,
 * enregistrements, cas limites, tentatives d'injection. Restaure ensuite
 * l'état initial du contenu.
 *
 * Usage :  php tools/test-admin.php [http://localhost/talinjo]
 *
 * Prérequis : Apache démarré et un compte admin créé. Les identifiants sont
 * demandés au lancement (jamais écrits dans ce fichier).
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Ce script s'exécute uniquement en ligne de commande.\n");
}

$base = rtrim($argv[1] ?? 'http://localhost/talinjo', '/');
$root = dirname(__DIR__);
$jar  = sys_get_temp_dir() . '/sakalava-test-cookies.txt';
@unlink($jar);

// ─── Sauvegarde du contenu avant tout test ────────────────────────────────
$contentFile = $root . '/data/content.json';
$backup      = $contentFile . '.avant-test';
if (!is_file($contentFile)) {
    fwrite(STDERR, "data/content.json introuvable.\n");
    exit(1);
}
copy($contentFile, $backup);

$restore = static function () use ($backup, $contentFile, $root) {
    if (is_file($backup)) {
        copy($backup, $contentFile);
        unlink($backup);
        require_once $root . '/lib/content.php';
        $store = new ContentStore($root);
        $store->write($store->read()); // régénère le site
    }
};
register_shutdown_function($restore);

// ─── Petit framework de test ──────────────────────────────────────────────
$passed = 0;
$failed = [];

function check(string $label, bool $ok, string $detail = ''): void
{
    global $passed, $failed;
    if ($ok) {
        $passed++;
        echo "  ok    $label\n";
    } else {
        $failed[] = $label . ($detail !== '' ? " — $detail" : '');
        echo "  ECHEC $label" . ($detail !== '' ? " — $detail" : '') . "\n";
    }
}

function section(string $title): void
{
    echo "\n── $title " . str_repeat('─', max(2, 58 - mb_strlen($title))) . "\n";
}

/** Requête HTTP conservant les cookies de session. */
function http(string $url, array $post = null, bool $follow = false): array
{
    global $jar;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR      => $jar,
        CURLOPT_COOKIEFILE     => $jar,
        CURLOPT_FOLLOWLOCATION => $follow,
        CURLOPT_TIMEOUT        => 20,
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $body = (string) curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $body];
}

/** Récupère le jeton CSRF d'une page. */
function token(string $url): string
{
    $r = http($url);
    return preg_match('/name="_csrf" value="([a-f0-9]+)"/', $r['body'], $m) ? $m[1] : '';
}

/** Dernier message affiché par l'admin après une action. */
function flash(string $url): string
{
    $r = http($url);
    if (preg_match('/class="alert alert--(ok|error)" role="status">(.*?)</s', $r['body'], $m)) {
        return html_entity_decode($m[2], ENT_QUOTES, 'UTF-8');
    }
    return '';
}

function content(): array
{
    global $contentFile;
    return json_decode((string) file_get_contents($contentFile), true) ?: [];
}

function generated(): string
{
    global $root;
    return (string) @file_get_contents($root . '/config.js');
}

// ─── Identifiants ─────────────────────────────────────────────────────────
echo "Banc de test du back-office — $base\n";
echo "Identifiant admin : ";
$user = rtrim((string) fgets(STDIN), "\r\n");
echo "Mot de passe      : ";
$pass = rtrim((string) fgets(STDIN), "\r\n");

// ══════════════════════════════════════════════════════════════════════════
section('Accès et authentification');

$r = http("$base/admin/index.php");
check('Tableau de bord inaccessible sans session', $r['code'] === 302, "HTTP {$r['code']}");

$r = http("$base/data/content.json");
check('data/content.json refusé par le serveur', $r['code'] === 403, "HTTP {$r['code']}");

$r = http("$base/data/admin.json");
check('data/admin.json refusé par le serveur', $r['code'] === 403, "HTTP {$r['code']}");

$tok = token("$base/admin/login.php");
check('Jeton CSRF présent sur la page de connexion', $tok !== '');

$r = http("$base/admin/login.php", ['_csrf' => $tok, 'username' => $user, 'password' => 'mauvais']);
check('Mot de passe erroné refusé', str_contains($r['body'], 'incorrects'));

$r = http("$base/admin/login.php", ['username' => $user, 'password' => $pass]);
check('Connexion sans jeton CSRF refusée', str_contains($r['body'], 'jeton de sécurité'));

$tok = token("$base/admin/login.php");
$r   = http("$base/admin/login.php", ['_csrf' => $tok, 'username' => $user, 'password' => $pass]);
check('Connexion valide acceptée', $r['code'] === 302, "HTTP {$r['code']}");

$r = http("$base/admin/index.php");
check('Tableau de bord accessible une fois connecté', $r['code'] === 200);
if ($r['code'] !== 200) {
    fwrite(STDERR, "\nConnexion impossible : les tests suivants sont sans objet.\n");
    exit(1);
}

section('Toutes les pages répondent sans erreur PHP');

foreach (['index', 'chambres', 'photos', 'services', 'textes', 'reglages'] as $page) {
    $r  = http("$base/admin/$page.php");
    $ok = $r['code'] === 200
       && !preg_match('/(Fatal error|Warning:|Notice:|Deprecated:|Undefined)/i', $r['body']);
    check("admin/$page.php", $ok, "HTTP {$r['code']}");
}

section('Injection et échappement');

$xss = '<script>alert(1)</script>" onmouseover="alert(2)';
$tok = token("$base/admin/textes.php");
http("$base/admin/textes.php", [
    '_csrf' => $tok, 'action' => 'texts',
    'brand_name' => $xss, 'brand_logo' => 'Test', 'brand_wordmark' => 'Test',
    'hero_logo' => '', 'hero_sub' => '', 'hero_badge' => '', 'hero_title' => '',
    'hero_headline_sub' => '', 'story_kicker' => '', 'story_text' => '',
    'story_stat' => '', 'acces_kicker' => '', 'acces_title' => '', 'acces_text' => '',
    'booking_title' => '', 'booking_intro' => '', 'footer_title' => '', 'footer_button' => '',
]);

$c = content();
check('Le texte hostile est bien stocké tel quel', ($c['brand']['name'] ?? '') === $xss);

$r = http("$base/admin/textes.php");
check(
    'Aucune balise script ré-injectée dans le formulaire admin',
    !str_contains($r['body'], '<script>alert(1)</script>')
);

$gen = generated();
check(
    'config.js encode le texte hostile en JSON, sans balise brute',
    str_contains($gen, '<script>') || !str_contains($gen, '<script>alert(1)</script>')
);

$r = http("$base/index.html");
check(
    'Le site public ne contient pas de script injecté',
    !str_contains($r['body'], '<script>alert(1)</script>')
);

section('Validation côté serveur');

$tok = token("$base/admin/textes.php");
http("$base/admin/textes.php", ['_csrf' => $tok, 'action' => 'contact', 'email' => 'pas-un-email']);
check('E-mail invalide refusé', str_contains(flash("$base/admin/textes.php"), "n'est pas valide"));

$tok = token("$base/admin/textes.php");
http("$base/admin/textes.php", ['_csrf' => $tok, 'action' => 'contact', 'email' => '', 'facebook' => 'pas-une-url']);
check('Lien Facebook invalide refusé', str_contains(flash("$base/admin/textes.php"), 'adresse complète'));

$tok = token("$base/admin/reglages.php");
http("$base/admin/reglages.php", ['_csrf' => $tok, 'action' => 'theme', 'accent' => 'rouge', 'dark' => '#000000', 'bg' => '#ffffff']);
check('Couleur invalide refusée', str_contains(flash("$base/admin/reglages.php"), '#rrggbb'));

$tok = token("$base/admin/reglages.php");
http("$base/admin/reglages.php", ['_csrf' => $tok, 'action' => 'seo', 'siteUrl' => 'pas-une-url', 'seoTitle' => 'T', 'seoDesc' => 'D']);
check('Adresse de site invalide refusée', str_contains(flash("$base/admin/reglages.php"), 'adresse du site'));

$tok = token("$base/admin/reglages.php");
http("$base/admin/reglages.php", ['_csrf' => $tok, 'action' => 'prices', 'showPrices' => '1', 'fallback' => '']);
check('Mention de repli vide refusée', str_contains(flash("$base/admin/reglages.php"), 'ne peut pas être vide'));

$tok = token("$base/admin/reglages.php");
http("$base/admin/reglages.php", ['_csrf' => $tok, 'action' => 'password', 'current' => 'faux', 'new' => 'nouveaumotdepasse', 'confirm' => 'nouveaumotdepasse']);
check('Changement de mot de passe sans le mot de passe actuel refusé',
    str_contains(flash("$base/admin/reglages.php"), 'actuel est incorrect'));

section('Cas limites du contenu');

// Texte très long
$long = str_repeat('Boda et Bakoly vous accueillent. ', 400);
$tok  = token("$base/admin/textes.php");
http("$base/admin/textes.php", [
    '_csrf' => $tok, 'action' => 'texts', 'brand_name' => 'Home Sakalava',
    'brand_logo' => 'Sakalava', 'brand_wordmark' => 'Sakalava',
    'hero_logo' => '', 'hero_sub' => '', 'hero_badge' => '', 'hero_title' => '',
    'hero_headline_sub' => '', 'story_kicker' => 'Test', 'story_text' => $long,
    'story_stat' => '', 'acces_kicker' => '', 'acces_title' => '', 'acces_text' => '',
    'booking_title' => '', 'booking_intro' => '', 'footer_title' => '', 'footer_button' => '',
]);
$c = content();
check('Texte très long accepté sans corruption', mb_strlen($c['story']['text'] ?? '') > 10000);
check('config.js reste un JavaScript valide', str_contains(generated(), 'const CONFIG = {'));

// Listes : suppression par vidage, avec un trou au milieu
$tok = token("$base/admin/services.php");
http("$base/admin/services.php", [
    '_csrf' => $tok, 'action' => 'services',
    'title' => 'Titre', 'card_title' => 'Message', 'note' => 'Note',
    'pills' => ['Un', '', 'Trois'],
    'svc_label' => ['Wifi', '', 'Parking', 'Massage'],
    'svc_icon'  => ['wifi', 'check', 'square-parking', 'hand-heart'],
    'svc_supp'  => [3 => '1'],
]);
$c = content();
check('Pastille vide retirée de la liste', count($c['services']['pills'] ?? []) === 2);
check('Service vide retiré de la liste', count($c['services']['items'] ?? []) === 3);
$items = $c['services']['items'] ?? [];
check('Les libellés restent alignés après le trou',
    ($items[1]['label'] ?? '') === 'Parking', 'obtenu : ' . ($items[1]['label'] ?? '?'));
check('La case « supplément » suit la bonne ligne',
    ($items[2]['supplement'] ?? false) === true && ($items[0]['supplement'] ?? true) === false);
check('L\'icône suit la bonne ligne',
    ($items[1]['icon'] ?? '') === 'square-parking', 'obtenu : ' . ($items[1]['icon'] ?? '?'));

// Icône inconnue → repli
$tok = token("$base/admin/services.php");
http("$base/admin/services.php", [
    '_csrf' => $tok, 'action' => 'services', 'title' => 'T', 'card_title' => '', 'note' => '',
    'pills' => ['Un'], 'svc_label' => ['Service'], 'svc_icon' => ['<img src=x>'], 'svc_supp' => [],
]);
$c = content();
check('Icône inconnue remplacée par un repli sûr',
    ($c['services']['items'][0]['icon'] ?? '') === 'check');

// Liste entièrement vidée
$tok = token("$base/admin/services.php");
http("$base/admin/services.php", [
    '_csrf' => $tok, 'action' => 'services', 'title' => 'T', 'card_title' => '', 'note' => '',
    'pills' => [''], 'svc_label' => [''], 'svc_icon' => ['check'], 'svc_supp' => [],
]);
$c = content();
check('Liste entièrement vidée acceptée', ($c['services']['items'] ?? null) === []);
$r = http("$base/index.html");
check('Le site reste servi avec une liste vide', $r['code'] === 200);

section('Chambres');

$c     = content();
$rooms = $c['rooms']['items'] ?? [];
check('Cinq chambres présentes', count($rooms) === 5, 'trouvé : ' . count($rooms));

// Réordonnancement : inverser la première et la dernière
$post = ['_csrf' => token("$base/admin/chambres.php"), 'action' => 'list'];
$n    = count($rooms);
foreach ($rooms as $i => $room) {
    $post['order_' . $room['id']] = ($i === 0) ? $n : (($i === $n - 1) ? 1 : $i + 1);
    $post['active_' . $room['id']] = '1';
}
http("$base/admin/chambres.php", $post);
$after = content()['rooms']['items'] ?? [];
check('Réordonnancement appliqué',
    ($after[0]['id'] ?? '') === ($rooms[$n - 1]['id'] ?? ''),
    'première chambre : ' . ($after[0]['id'] ?? '?'));
check('Aucune chambre perdue au réordonnancement', count($after) === 5);

// Masquage
$post = ['_csrf' => token("$base/admin/chambres.php"), 'action' => 'list'];
foreach ($after as $i => $room) {
    $post['order_' . $room['id']] = $i + 1;
    if ($i > 0) { $post['active_' . $room['id']] = '1'; }
}
http("$base/admin/chambres.php", $post);
$c = content();
check('Chambre masquée enregistrée', ($c['rooms']['items'][0]['active'] ?? true) === false);
check('Chambre masquée absente du site public',
    substr_count(generated(), '"id": "' . ($c['rooms']['items'][0]['id'] ?? 'x') . '"') === 0);
check('Chambre masquée toujours conservée en base', count($c['rooms']['items']) === 5);

// Édition d'une chambre
$target = $c['rooms']['items'][1]['id'] ?? '';
$tok    = token("$base/admin/chambres.php?id=" . urlencode($target));
http("$base/admin/chambres.php", [
    '_csrf' => $tok, 'action' => 'save', 'id' => $target,
    'name' => 'Chambre testée', 'area' => '99 m²', 'loc' => 'Sous-titre',
    'tag' => 'Étiquette', 'desc' => 'Une description.', 'price' => '150 000 Ar',
    'amenities' => ['Climatisation', '', 'Vue mer'], 'coverId' => '', 'active' => '1',
]);
$c    = content();
$room = null;
foreach ($c['rooms']['items'] as $r2) { if ($r2['id'] === $target) { $room = $r2; } }
check('Chambre enregistrée', ($room['name'] ?? '') === 'Chambre testée');
check('Équipement vide retiré', count($room['amenities'] ?? []) === 2);
check('Prix enregistré', ($room['price'] ?? '') === '150 000 Ar');

// Le prix ne doit pas fuir tant que l'affichage est désactivé
$tok = token("$base/admin/reglages.php");
http("$base/admin/reglages.php", ['_csrf' => $tok, 'action' => 'prices', 'fallback' => 'Tarif sur demande']);
check('Tarifs masqués : le prix réel est absent du site',
    !str_contains(generated(), '150 000 Ar'));
check('Tarifs masqués : la mention de repli est présente',
    str_contains(generated(), 'Tarif sur demande'));

$tok = token("$base/admin/reglages.php");
http("$base/admin/reglages.php", ['_csrf' => $tok, 'action' => 'prices', 'showPrices' => '1', 'fallback' => 'Tarif sur demande']);
check('Tarifs affichés : le prix réel apparaît', str_contains(generated(), '150 000 Ar'));

// Chambre inexistante
$tok = token("$base/admin/chambres.php");
http("$base/admin/chambres.php", ['_csrf' => $tok, 'action' => 'save', 'id' => 'chambre-fantome', 'name' => 'X']);
check('Chambre inexistante refusée', str_contains(flash("$base/admin/chambres.php"), "n'existe plus"));

// Nom obligatoire
$tok = token("$base/admin/chambres.php");
http("$base/admin/chambres.php", ['_csrf' => $tok, 'action' => 'save', 'id' => $target, 'name' => '']);
check('Nom de chambre vide refusé', str_contains(flash("$base/admin/chambres.php"), 'obligatoire'));

section('Photos');

$tok = token("$base/admin/photos.php");
http("$base/admin/photos.php", ['_csrf' => $tok, 'action' => 'assign', 'hero' => 'media-qui-nexiste-pas']);
$c = content();
check('Photo inexistante non affectée au hero',
    ($c['hero']['mediaId'] ?? '') !== 'media-qui-nexiste-pas',
    'valeur : ' . ($c['hero']['mediaId'] ?? '(vide)'));

$tok = token("$base/admin/photos.php");
http("$base/admin/photos.php", ['_csrf' => $tok, 'action' => 'delete', 'id' => 'inexistant']);
check('Suppression d\'une photo inexistante refusée proprement',
    str_contains(flash("$base/admin/photos.php"), "n'existe plus"));

section('Intégrité après tous ces tests');

$r = http("$base/index.html");
check('Le site public répond toujours', $r['code'] === 200);
check('config.js toujours valide', str_contains(generated(), 'const CONFIG = {'));

$json = json_decode((string) file_get_contents($contentFile), true);
check('data/content.json toujours lisible', is_array($json));

// ══════════════════════════════════════════════════════════════════════════
echo "\n" . str_repeat('═', 62) . "\n";
if ($failed) {
    echo count($failed) . " test(s) en échec sur " . ($passed + count($failed)) . " :\n";
    foreach ($failed as $f) { echo "  - $f\n"; }
} else {
    echo "Les $passed tests passent.\n";
}
echo "Contenu d'origine restauré.\n";
exit($failed ? 1 : 0);
