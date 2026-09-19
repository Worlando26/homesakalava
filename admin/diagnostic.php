<?php
/**
 * admin/diagnostic.php — Contrôle de bonne santé de l'installation.
 *
 * À ouvrir après chaque mise en ligne ou changement d'hébergeur. La page
 * vérifie ce qui, en pratique, casse un site lors d'un déménagement :
 * version de PHP, extensions, droits d'écriture, protection des dossiers
 * sensibles, HTTPS.
 *
 * Elle ne modifie rien : elle observe et explique.
 */

declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';
Auth::require();

/** Un point de contrôle : statut, libellé, explication. */
function probe(string $label, string $status, string $detail, string $fix = ''): array
{
    return compact('label', 'status', 'detail', 'fix');
}

$checks  = [];
$root    = APP_ROOT;

// ─── Serveur ──────────────────────────────────────────────────────────────
$checks['Serveur'][] = PHP_VERSION_ID >= 80100
    ? probe('Version de PHP', 'ok', 'PHP ' . PHP_VERSION)
    : probe('Version de PHP', 'error', 'PHP ' . PHP_VERSION,
        'Ce site demande PHP 8.1 ou plus récent. Demandez à votre hébergeur '
        . 'de basculer le domaine sur une version récente de PHP.');

foreach ([
    'gd'       => "redimensionnement et compression des photos",
    'fileinfo' => "vérification du type réel des fichiers envoyés",
    'mbstring' => "gestion correcte des accents",
    'json'     => "lecture et écriture du fichier de contenu",
    'session'  => "connexion à l'administration",
] as $ext => $why) {
    $checks['Serveur'][] = extension_loaded($ext)
        ? probe("Extension $ext", 'ok', "Présente — $why")
        : probe("Extension $ext", 'error', "Absente — sans elle : $why",
            "Demandez à votre hébergeur d'activer l'extension PHP « $ext ».");
}

$checks['Serveur'][] = function_exists('imagewebp')
    ? probe('Conversion WebP', 'ok', 'Disponible — les photos sont allégées pour le mobile')
    : probe('Conversion WebP', 'warn', 'Indisponible',
        'Le site fonctionne, mais les photos ne seront servies qu\'en JPEG, '
        . 'donc un peu plus lourdes.');

$checks['Serveur'][] = defined('PASSWORD_ARGON2ID')
    ? probe('Hachage Argon2id', 'ok', 'Disponible — le mot de passe est protégé au mieux')
    : probe('Hachage Argon2id', 'warn', 'Indisponible sur ce serveur',
        'Le mot de passe reste haché, mais avec un algorithme plus ancien. '
        . 'Si vous changez de mot de passe ici, relancez '
        . 'php tools/create-admin.php pour le réenregistrer.');

$upload = min(
    (int) preg_replace('/\D/', '', (string) ini_get('upload_max_filesize')),
    (int) preg_replace('/\D/', '', (string) ini_get('post_max_size'))
);
$checks['Serveur'][] = $upload >= 8
    ? probe('Taille maximale des envois', 'ok', $upload . ' Mo — suffisant pour des photos')
    : probe('Taille maximale des envois', 'warn', $upload . ' Mo seulement',
        'Les photos de plus de ' . $upload . ' Mo seront refusées par le serveur. '
        . 'Réduisez-les avant envoi, ou demandez à votre hébergeur d\'augmenter '
        . 'upload_max_filesize et post_max_size à 8 Mo.');

// ─── Droits d'écriture ────────────────────────────────────────────────────
foreach ([
    'data'              => "l'enregistrement de vos modifications",
    'uploads'           => "l'envoi de photos",
    'storage/originals' => "la conservation de vos photos d'origine",
    '.'                 => "la régénération du site (config.js)",
] as $dir => $why) {
    $path  = $root . ($dir === '.' ? '' : '/' . $dir);
    $shown = $dir === '.' ? 'Dossier racine' : $dir . '/';

    if (!is_dir($path)) {
        $checks['Droits d\'écriture'][] = probe($shown, 'error', 'Dossier absent',
            "Créez le dossier « $dir » à la racine du site.");
    } elseif (!is_writable($path)) {
        $checks['Droits d\'écriture'][] = probe($shown, 'error', "Écriture impossible — $why ne fonctionnera pas",
            "Donnez les droits d'écriture à ce dossier (permissions 755, ou 775 "
            . "si votre hébergeur l'exige).");
    } else {
        $checks['Droits d\'écriture'][] = probe($shown, 'ok', "Accessible en écriture — $why");
    }
}

// ─── Protection des dossiers sensibles ────────────────────────────────────
// On interroge le serveur comme le ferait un visiteur.
$scheme = is_https() ? 'https' : 'http';
$host   = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
$dir    = rtrim(str_replace('\\', '/', dirname(dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '')))), '/');
$base   = "$scheme://$host$dir";

/** Renvoie le code HTTP d'une URL, ou 0 si la requête échoue. */
function http_status(string $url): int
{
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_NOBODY         => true,
            CURLOPT_TIMEOUT        => 6,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code;
    }
    $ctx = stream_context_create(['http' => ['method' => 'HEAD', 'timeout' => 6, 'ignore_errors' => true]]);
    @file_get_contents($url, false, $ctx);
    foreach ($http_response_header ?? [] as $h) {
        if (preg_match('#^HTTP/\S+\s+(\d{3})#', $h, $m)) {
            return (int) $m[1];
        }
    }
    return 0;
}

$sensitive = [
    'data/content.json'  => 'Le contenu du site',
    'data/admin.json'    => 'Votre mot de passe (haché)',
    'lib/content.php'    => 'Le code du générateur',
    'about.txt'          => 'La fiche interne de l\'établissement',
];

foreach ($sensitive as $path => $what) {
    $code = http_status("$base/$path");

    if ($code === 0) {
        $checks['Fichiers protégés'][] = probe($path, 'warn', 'Vérification impossible depuis le serveur',
            "Ouvrez $base/$path dans votre navigateur : vous devez obtenir une "
            . "erreur d'accès refusé, et non le contenu du fichier.");
    } elseif ($code === 403 || $code === 404) {
        $checks['Fichiers protégés'][] = probe($path, 'ok', "$what : inaccessible depuis le web (HTTP $code)");
    } else {
        $checks['Fichiers protégés'][] = probe($path, 'error', "$what est PUBLIC (HTTP $code)",
            "Le fichier .htaccess n'est pas pris en compte par votre serveur. "
            . "Demandez à votre hébergeur d'activer AllowOverride All, ou de "
            . "refuser l'accès aux dossiers data/, lib/, tools/ et storage/.");
    }
}

// ─── Connexion et sécurité ────────────────────────────────────────────────
$checks['Sécurité'][] = is_https()
    ? probe('Connexion chiffrée (HTTPS)', 'ok', 'Le site est servi en HTTPS')
    : probe('Connexion chiffrée (HTTPS)', 'warn', 'Le site est servi en HTTP',
        'En local, c\'est normal. En ligne, activez le certificat gratuit '
        . 'proposé par votre hébergeur : sans lui, votre mot de passe circule '
        . 'en clair sur le réseau.');

$checks['Sécurité'][] = ini_get('display_errors')
    ? probe('Affichage des erreurs', 'error', 'Activé',
        'Les messages d\'erreur révèlent les chemins de vos fichiers aux '
        . 'visiteurs. Demandez à votre hébergeur de mettre display_errors sur Off.')
    : probe('Affichage des erreurs', 'ok', 'Désactivé, comme il se doit');

$adminFile = $root . '/data/admin.json';
if (is_file($adminFile)) {
    $account = json_decode((string) file_get_contents($adminFile), true);
    $changed = strtotime((string) ($account['updated_at'] ?? '')) ?: filemtime($adminFile);
    $days    = (int) floor((time() - $changed) / 86400);

    $checks['Sécurité'][] = probe('Compte administrateur', 'ok',
        $days === 0
            ? "Créé, mot de passe modifié aujourd'hui"
            : "Créé, mot de passe modifié il y a $days jour(s)");
} else {
    $checks['Sécurité'][] = probe('Compte administrateur', 'error', 'Absent',
        'Lancez php tools/create-admin.php');
}

// ─── Contenu ──────────────────────────────────────────────────────────────
try {
    $content = $store->read();
    $checks['Contenu'][] = probe('Fichier de contenu', 'ok',
        count($content['rooms']['items'] ?? []) . ' chambre(s), '
        . count($content['media'] ?? []) . ' photo(s)');
} catch (Throwable $ex) {
    $content = [];
    $checks['Contenu'][] = probe('Fichier de contenu', 'error', $ex->getMessage());
}

$generated = $root . '/config.js';
if (is_file($generated) && is_file($root . '/data/content.json')) {
    $checks['Contenu'][] = filemtime($generated) >= filemtime($root . '/data/content.json')
        ? probe('Site régénéré', 'ok', 'Le site public reflète le contenu enregistré')
        : probe('Site régénéré', 'warn', 'Le site public est en retard sur le contenu',
            'Allez dans Réglages et cliquez sur « Régénérer maintenant ».');
}

// Photos réellement présentes sur le disque
$missing = 0;
foreach (($content['media'] ?? []) as $m) {
    foreach (($m['variants'] ?? []) as $v) {
        if (!is_file($root . '/' . ($v['fallback'] ?? ''))) { $missing++; }
    }
}
$checks['Contenu'][] = $missing === 0
    ? probe('Fichiers des photos', 'ok', 'Toutes les photos sont présentes sur le serveur')
    : probe('Fichiers des photos', 'error', "$missing fichier(s) manquant(s)",
        'Des photos ont été perdues lors du transfert. Renvoyez le dossier '
        . 'uploads/ complet, ou réimportez les photos concernées.');

$seoUrl = (string) ($content['seo']['siteUrl'] ?? '');
$checks['Contenu'][] = $seoUrl !== ''
    ? probe('Adresse du site', 'ok', $seoUrl)
    : probe('Adresse du site', 'warn', 'Non renseignée',
        'Renseignez-la dans Réglages → Référencement : le plan du site et '
        . 'l\'aperçu de partage en dépendent.');

// ─── Synthèse ─────────────────────────────────────────────────────────────
$errors = 0;
$warns  = 0;
foreach ($checks as $group) {
    foreach ($group as $c) {
        if ($c['status'] === 'error') { $errors++; }
        if ($c['status'] === 'warn')  { $warns++; }
    }
}

layout_head('Diagnostic');
?>

<p class="section-intro">
  Cette page vérifie que le serveur est correctement configuré. Ouvrez-la après
  chaque mise en ligne ou changement d'hébergeur.
</p>

<?php if ($errors === 0 && $warns === 0): ?>
  <p class="alert alert--ok">Tout est en ordre. L'installation est saine.</p>
<?php elseif ($errors === 0): ?>
  <p class="alert alert--warn">
    Aucun problème bloquant. <?= $warns ?> point(s) méritent votre attention,
    détaillés ci-dessous.
  </p>
<?php else: ?>
  <p class="alert alert--error">
    <?= $errors ?> problème(s) à corriger<?= $warns ? ", et $warns point(s) d'attention" : '' ?>.
    Chaque ligne en rouge indique la marche à suivre.
  </p>
<?php endif; ?>

<?php foreach ($checks as $group => $items): ?>
  <div class="card">
    <div class="card__head"><h2 class="card__title"><?= e($group) ?></h2></div>
    <ul class="stack">
      <?php foreach ($items as $c): ?>
        <li class="probe probe--<?= e($c['status']) ?>">
          <span class="probe__mark" aria-hidden="true">
            <?= $c['status'] === 'ok' ? '✓' : ($c['status'] === 'warn' ? '!' : '✕') ?>
          </span>
          <span class="probe__body">
            <strong><?= e($c['label']) ?></strong>
            <span class="probe__detail"><?= e($c['detail']) ?></span>
            <?php if ($c['fix'] !== ''): ?>
              <span class="probe__fix"><?= e($c['fix']) ?></span>
            <?php endif; ?>
          </span>
          <span class="sr-only">
            <?= $c['status'] === 'ok' ? 'Correct' : ($c['status'] === 'warn' ? 'À surveiller' : 'À corriger') ?>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endforeach; ?>

<p class="muted">
  Contrôle effectué le <?= e(date('d/m/Y \à H:i')) ?>.
  Rechargez la page après avoir corrigé quelque chose.
</p>

<?php layout_foot(); ?>
