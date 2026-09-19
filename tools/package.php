<?php
/**
 * tools/package.php — Prépare le dossier à téléverser chez l'hébergeur.
 *
 * Copie le site dans un dossier « livraison/ », en laissant de côté tout ce
 * qui ne doit pas se retrouver en ligne : outils de développement, fiches
 * internes, archives, historique git, et surtout le compte administrateur.
 *
 * Usage :  php tools/package.php [dossier-de-sortie]
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Ce script s'exécute uniquement en ligne de commande.\n");
}

$root = dirname(__DIR__);
$dest = $argv[1] ?? ($root . '/livraison');

require_once $root . '/lib/content.php';

/**
 * Ce qui ne part JAMAIS en ligne.
 * Les chemins sont relatifs à la racine du projet.
 */
const EXCLUS = [
    // Identifiants — la règle absolue.
    'data/admin.json',

    // Fiches de travail internes.
    'about.txt',
    'gestionnaire.txt',
    'run_autonome.txt',
    'RAPPORT.md',

    // Outils de développement : inutiles en ligne, et autant de surface
    // d'attaque en moins. On conserve en revanche create-admin.php et
    // build.php, qui servent réellement sur le serveur.
    'tools/test-admin.php',
    'tools/smoke-test.js',
    'tools/package.php',
    'tools/import-photos.php',
    'tools/seed-content.php',

    'photos_sakalava',
    '_archive_ancien_site',
    'livraison',

    // Environnement de développement.
    '.git',
    '.gitignore',
    '.claude',
    'node_modules',
    'package.json',
    'package-lock.json',
];

/** Fichiers temporaires reconnaissables à leur nom. */
function estTemporaire(string $name): bool
{
    return $name === '.'
        || $name === '..'
        || str_ends_with($name, '.tmp')
        || str_ends_with($name, '.bak')
        || str_ends_with($name, '.avant-test')
        || $name === 'Thumbs.db'
        || $name === '.DS_Store';
}

$copies = 0;
$octets = 0;

function copier(string $from, string $to, string $relatif = ''): void
{
    global $copies, $octets;

    foreach (scandir($from) ?: [] as $name) {
        if (estTemporaire($name)) {
            continue;
        }

        $rel = $relatif === '' ? $name : "$relatif/$name";
        if (in_array($rel, EXCLUS, true)) {
            continue;
        }

        $src = "$from/$name";
        $dst = "$to/$name";

        if (is_dir($src)) {
            if (!is_dir($dst) && !mkdir($dst, 0775, true) && !is_dir($dst)) {
                throw new RuntimeException("Création impossible : $dst");
            }
            copier($src, $dst, $rel);
        } else {
            if (!copy($src, $dst)) {
                throw new RuntimeException("Copie impossible : $rel");
            }
            $copies++;
            $octets += (int) filesize($src);
        }
    }
}

// ─── Contrôles avant emballage ────────────────────────────────────────────
echo "\n══════════════════════════════════════════════\n";
echo "  Préparation du dossier à mettre en ligne\n";
echo "══════════════════════════════════════════════\n\n";

$avertissements = [];

try {
    $store   = new ContentStore($root);
    $content = $store->read();
} catch (Throwable $ex) {
    fwrite(STDERR, "Contenu illisible : " . $ex->getMessage() . "\n");
    exit(1);
}

// Le site doit être à jour avant d'être copié.
echo "Régénération du site…\n";
$store->write($content);

if (($content['seo']['siteUrl'] ?? '') === '') {
    $avertissements[] = "L'adresse définitive du site n'est pas renseignée "
        . "(Réglages → Référencement). Le plan du site et l'aperçu de partage "
        . "resteront incomplets.";
}
if (($content['contact']['email'] ?? '') === '') {
    $avertissements[] = "L'adresse e-mail n'est pas renseignée : le formulaire "
        . "de contact du site restera désactivé.";
}
if (($content['contact']['phone'] ?? '') === '') {
    $avertissements[] = "Le numéro de téléphone n'est pas renseigné : le site "
        . "affichera « à renseigner ».";
}

$sansPhoto = 0;
foreach ($content['rooms']['items'] ?? [] as $room) {
    if (!empty($room['active']) && empty($room['mediaIds'])) {
        $sansPhoto++;
    }
}
if ($sansPhoto > 0) {
    $avertissements[] = "$sansPhoto chambre(s) publiée(s) sans photo : elles "
        . "s'afficheront avec un dégradé de couleur.";
}

// ─── Copie ────────────────────────────────────────────────────────────────
if (is_dir($dest)) {
    echo "Le dossier de sortie existe déjà : $dest\n";
    echo "Son contenu va être remplacé. Continuer ? (o/N) : ";
    $reponse = strtolower(rtrim((string) fgets(STDIN), "\r\n"));
    if (!in_array($reponse, ['o', 'oui', 'y', 'yes'], true)) {
        echo "Annulé. Rien n'a été modifié.\n";
        exit(0);
    }
    // Vidage du dossier de sortie uniquement — jamais rien en dehors.
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dest, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        $f->isDir() ? @rmdir($f->getPathname()) : @unlink($f->getPathname());
    }
} elseif (!mkdir($dest, 0775, true) && !is_dir($dest)) {
    fwrite(STDERR, "Création impossible : $dest\n");
    exit(1);
}

echo "Copie des fichiers…\n";
try {
    copier($root, $dest);
} catch (Throwable $ex) {
    fwrite(STDERR, "Échec : " . $ex->getMessage() . "\n");
    exit(1);
}

// Vérification finale : aucun fichier sensible n'a suivi.
$fuites = [];
foreach (['data/admin.json', 'about.txt', 'gestionnaire.txt', 'RAPPORT.md'] as $secret) {
    if (file_exists("$dest/$secret")) {
        $fuites[] = $secret;
    }
}

// Note explicative déposée dans le dossier livré.
$date = date('d/m/Y à H:i');

file_put_contents($dest . '/LISEZ-MOI-AVANT-MISE-EN-LIGNE.txt', <<<TXT
DOSSIER PRÊT À ÊTRE MIS EN LIGNE — Home Sakalava
Préparé le {$date}

1. Téléversez TOUT le contenu de ce dossier à la racine de votre hébergement
   (souvent un dossier nommé www/, public_html/ ou htdocs/).

2. Créez le compte administrateur. Si votre hébergeur donne un accès SSH :
       php tools/create-admin.php
   Sinon, créez-le sur votre ordinateur avec la même commande, puis
   téléversez le seul fichier data/admin.json qui vient d'être créé.

3. Vérifiez que ces dossiers sont accessibles en écriture (permissions 755) :
       data/   uploads/   storage/originals/   et la racine du site

4. Activez le certificat HTTPS gratuit proposé par votre hébergeur.

5. Ouvrez /admin/diagnostic.php : la page contrôle toute l'installation et
   indique quoi corriger, point par point.

6. Renseignez l'adresse définitive du site dans Réglages → Référencement,
   puis décommentez la ligne Sitemap: dans robots.txt.

CE QUI N'EST PAS DANS CE DOSSIER, VOLONTAIREMENT :
  - data/admin.json    votre mot de passe (à créer sur le serveur)
  - tools/ (partiel)    seuls create-admin.php et build.php sont fournis
  - about.txt, gestionnaire.txt, RAPPORT.md   documents de travail internes
  - photos_sakalava/   vos photos d'origine (déjà importées dans uploads/)

SAUVEGARDE : pour tout archiver, copiez data/, uploads/ et storage/.
TXT
);

// ─── Bilan ────────────────────────────────────────────────────────────────
echo "\n";
echo "Dossier prêt : $dest\n";
echo "  $copies fichiers, " . number_format($octets / 1048576, 1, ',', ' ') . " Mo\n\n";

if ($fuites) {
    echo "ARRÊT : des fichiers sensibles ont été copiés par erreur :\n";
    foreach ($fuites as $f) { echo "  - $f\n"; }
    echo "Supprimez-les avant toute mise en ligne.\n";
    exit(1);
}
echo "Aucun fichier sensible dans le dossier (compte admin, fiches internes,\n";
echo "outils de développement : tous laissés de côté).\n\n";

if ($avertissements) {
    echo "À savoir avant de mettre en ligne :\n";
    foreach ($avertissements as $a) { echo "  · $a\n"; }
    echo "\nCes points ne bloquent pas la mise en ligne et se corrigent depuis\n";
    echo "l'administration, une fois le site en place.\n\n";
}

echo "Étapes suivantes : voir LISEZ-MOI-AVANT-MISE-EN-LIGNE.txt dans le dossier.\n\n";
