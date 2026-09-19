<?php
/**
 * tools/import-add-img.php — Reprise des captures du dossier add_img/.
 *
 * Ces fichiers ne sont pas des photos d'appareil : ce sont des captures
 * d'écran de la fiche Booking.com de l'établissement. L'interface du site
 * (flèches du carrousel) est incrustée dans l'image et doit être retirée
 * avant toute publication.
 *
 * Ce script :
 *   1. écarte les captures de la fiche complète (panneau de texte, vignettes,
 *      barre d'enregistrement d'écran) — elles n'apportent aucune photo qui
 *      ne soit déjà dans les autres captures ;
 *   2. recadre systématiquement les bords gauche et droit, où se trouvent les
 *      flèches du carrousel. Le recadrage est appliqué sans détection : une
 *      détection ratée laisserait passer une flèche en ligne ;
 *   3. décline chaque image en WebP + JPEG à plusieurs largeurs, via la même
 *      chaîne que l'envoi depuis le back-office.
 *
 * Usage :
 *   php tools/import-add-img.php              aperçu, n'écrit que des exemples
 *   php tools/import-add-img.php --apply      importe réellement dans le site
 *
 * Le dossier add_img/ n'est jamais modifié ni supprimé par ce script.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Ce script s'exécute uniquement en ligne de commande.\n");
}

$root = dirname(__DIR__);
require_once $root . '/lib/content.php';

$apply  = in_array('--apply', $argv ?? [], true);
$source = $root . '/add_img';
$stage  = $root . '/data/apercu-add-img';

/** Largeur des bandes retirées à gauche et à droite (flèches du carrousel). */
const MARGE_FLECHES = 54;

/** Au-delà de cette largeur, la capture montre la fiche entière, pas une photo. */
const LARGEUR_FICHE = 1200;

/**
 * Correspondance dossier → chambre du site.
 * Établie à partir des superficies lues sur les captures Booking, qui
 * concordent toutes avec celles de about.txt.
 */
const CORRESPONDANCE = [
    'Chambre Triple Confort'          => 'comfort-triple',
    'Chambre Triple Standard'         => 'standard-triple',
    'Chambre Double - Vue sur Jardin' => 'double-jardin',
    'Chambre Double avec Terrasse'    => 'double-terrasse',
    'Standart double'                 => 'standard-double',
];

/** Dossiers présents mais qui ne contiennent pas de photos de chambre. */
const HORS_PHOTOS = ['price'];

/**
 * Textes alternatifs. Rédigés à la main d'après ce que montre réellement
 * chaque image : c'est de l'accessibilité, pas du remplissage automatique.
 * Clé = nom de fichier d'origine.
 */
const ALTS = [
    // Chambre Double - Vue sur Jardin
    '19.09.2026_20.05.07_REC.png' => "Lit à baldaquin avec moustiquaire et tête de lit en bois brut, ventilateur et ouverture sur la cour",
    '19.09.2026_20.05.25_REC.png' => "La chambre vue depuis l'entrée, lit à baldaquin et murs blancs",
    '19.09.2026_20.07.57_REC.png' => "Salle de bain de la chambre, vasque en pierre et mur de pierre apparente",
    '19.09.2026_20.09.02_REC.png' => "Baignoire taillée dans un tronc, face à la douche à l'italienne",
    '19.09.2026_20.09.17_REC.png' => "Coin toilette avec miroir encadré de bois flotté",

    // Chambre Double avec Terrasse
    '19.09.2026_20.11.41_REC.png' => "Terrasse privée meublée d'un fauteuil en rotin et d'une table en bois, suspensions en vannerie",
    '19.09.2026_20.12.14_REC.png' => "La chambre et son lit double sous moustiquaire",
    '19.09.2026_20.13.00_REC.png' => "Vue de la chambre depuis la terrasse, baie vitrée ouverte",
    '19.09.2026_20.13.27_REC.png' => "Salle de bain privative avec douche à l'italienne",
    '19.09.2026_20.13.47_REC.png' => "Détail du mobilier en bois local de la chambre",

    // Chambre Triple Confort
    '19.09.2026_20.22.46_REC.png' => "Lit à baldaquin et coussins bleus brodés, banc en bois brut au pied du lit",
    '19.09.2026_20.23.23_REC.png' => "La chambre triple vue d'ensemble, lits sous moustiquaire",
    '19.09.2026_20.23.40_REC.png' => "Salle de bain avec baignoire balnéo et double vasque en pierre",
    '19.09.2026_20.23.56_REC.png' => "Chapeau de paille et gousses de vanille posés sur le lit",
    '19.09.2026_20.24.19_REC.png' => "Coin salon de la chambre, mobilier en bois local",

    // Chambre Triple Standard
    '19.09.2026_20.18.00_REC.png' => "Les deux lits de la chambre triple, murs en enduit ocre",
    '19.09.2026_20.19.32_REC.png' => "Lits jumeaux et coussins, suspension en vannerie et volets en bois",
    '19.09.2026_20.19.58_REC.png' => "La chambre vue depuis la porte, décoration artisanale malgache",
    '19.09.2026_20.20.44_REC.png' => "Salle de bain privative de la chambre triple standard",

    // Standart double
    '19.09.2026_20.26.06_REC.png' => "Chambre double avec mur ocre et décoration en vannerie",
    '19.09.2026_20.26.34_REC.png' => "Le lit double de la chambre, linge blanc et jeté de lit coloré",
    '19.09.2026_20.27.07_REC.png' => "Salle de bain privative, vasque en pierre et mur de pierre apparente",

    // Capture restée à la racine d'add_img
    '19.09.2026_20.25.43_REC.png' => "Chambre au mur ocre, vanneries murales et coffre en bois sculpté",
];

// ══════════════════════════════════════════════════════════════════════════

if (!is_dir($source)) {
    fwrite(STDERR, "Dossier add_img/ introuvable.\n");
    exit(1);
}

$store   = new ContentStore($root);
$content = $store->read();

$rooms = [];
foreach ($content['rooms']['items'] ?? [] as $r) {
    $rooms[$r['id']] = $r['name'] ?? $r['id'];
}

echo "\n══════════════════════════════════════════════════════════\n";
echo "  Reprise des captures add_img/ — "
   . ($apply ? "IMPORT RÉEL" : "APERÇU (rien n'est publié)") . "\n";
echo "══════════════════════════════════════════════════════════\n\n";

// ─── 1. Correspondance des dossiers ───────────────────────────────────────
$dossiers = [];
foreach (glob($source . '/*', GLOB_ONLYDIR) as $d) {
    $dossiers[] = basename($d);
}

echo "CORRESPONDANCE DOSSIERS → CHAMBRES\n";
$orphelins = [];
foreach ($dossiers as $d) {
    if (in_array($d, HORS_PHOTOS, true)) {
        printf("  %-34s → (pas des photos de chambre)\n", $d);
        continue;
    }
    $id = CORRESPONDANCE[$d] ?? null;
    if ($id === null || !isset($rooms[$id])) {
        $orphelins[] = $d;
        printf("  %-34s → AUCUNE CHAMBRE CORRESPONDANTE\n", $d);
    } else {
        printf("  %-34s → %s\n", $d, $rooms[$id]);
    }
}

$couvertes = array_values(array_intersect(array_values(CORRESPONDANCE), array_keys($rooms)));
$sansDossier = array_diff(array_keys($rooms), $couvertes);
if ($sansDossier) {
    echo "\n  Chambres sans dossier d'images :\n";
    foreach ($sansDossier as $id) { echo "    - " . $rooms[$id] . "\n"; }
} else {
    echo "\n  Toutes les chambres du site ont un dossier.\n";
}

$racine = glob($source . '/*.png');
if ($racine) {
    echo "\n  Fichier(s) à la racine d'add_img, rattaché(s) à aucune chambre :\n";
    foreach ($racine as $f) { echo "    - " . basename($f) . "\n"; }
}

// ─── 2. Traitement ────────────────────────────────────────────────────────
if (!$apply) {
    if (!is_dir($stage) && !mkdir($stage, 0775, true) && !is_dir($stage)) {
        fwrite(STDERR, "Création impossible : $stage\n");
        exit(1);
    }
    array_map('unlink', glob($stage . '/*') ?: []);
}

echo "\nTRAITEMENT DES IMAGES\n";

$bilan   = [];
$importe = 0;
$ecarte  = 0;

foreach (CORRESPONDANCE as $dossier => $roomId) {
    $chemin = $source . '/' . $dossier;
    if (!is_dir($chemin) || !isset($rooms[$roomId])) {
        continue;
    }

    $fichiers = glob($chemin . '/*.png') ?: [];
    sort($fichiers);

    $retenues = [];
    $rang     = 0;

    foreach ($fichiers as $f) {
        $nom  = basename($f);
        $info = getimagesize($f);
        if ($info === false) {
            continue;
        }
        [$w, $h] = $info;

        // Capture de la fiche entière : panneau de texte, vignettes, barre
        // d'enregistrement. Les photos qu'elle contient figurent déjà dans
        // les autres captures du même dossier.
        if ($w >= LARGEUR_FICHE) {
            $bilan[$dossier][] = [$nom, "{$w}×{$h}", 'écartée — fiche Booking complète'];
            $ecarte++;
            continue;
        }

        $rang++;
        $slug = $roomId . '-' . str_pad((string) $rang, 2, '0', STR_PAD_LEFT);

        // Recadrage des bandes latérales, où se trouvent les flèches.
        // Les images étroites (moins de 450 px) sont des recadrages déjà
        // propres : on n'y touche pas.
        $marge   = $w >= 450 ? MARGE_FLECHES : 0;
        $largeur = $w - 2 * $marge;

        $src = imagecreatefrompng($f);
        $dst = imagecreatetruecolor($largeur, $h);
        imagecopy($dst, $src, 0, 0, $marge, 0, $largeur, $h);
        imagedestroy($src);

        $temp = sys_get_temp_dir() . '/' . $slug . '.png';
        imagepng($dst, $temp);
        imagedestroy($dst);

        $alt = ALTS[$nom] ?? '';

        if ($apply) {
            try {
                $record = ImageService::import($temp, $slug, $alt);
                $content['media'][$record['id']] = $record;
                $retenues[] = $record['id'];
                $bilan[$dossier][] = [$nom, "{$largeur}×{$h}", 'importée → ' . $record['id']];
                $importe++;
            } catch (Throwable $ex) {
                $bilan[$dossier][] = [$nom, "{$largeur}×{$h}", 'ÉCHEC — ' . $ex->getMessage()];
            }
        } else {
            copy($temp, $stage . '/' . $slug . '.png');
            $bilan[$dossier][] = [$nom, "{$largeur}×{$h}", 'recadrée → apercu/' . $slug . '.png'];
            $importe++;
        }

        @unlink($temp);
    }

    if ($apply && $retenues) {
        // Les photos s'ajoutent à celles déjà présentes, sans les écraser.
        foreach ($content['rooms']['items'] as $i => $room) {
            if (($room['id'] ?? '') !== $roomId) {
                continue;
            }
            $existant = array_values(array_filter(
                $room['mediaIds'] ?? [],
                fn($id) => isset($content['media'][$id])
            ));
            $fusion = array_values(array_unique(array_merge($existant, $retenues)));
            $content['rooms']['items'][$i]['mediaIds'] = $fusion;
            $content['rooms']['items'][$i]['coverId']  = $fusion[0] ?? '';
        }
    }
}

foreach ($bilan as $dossier => $lignes) {
    echo "\n  " . $dossier . "\n";
    foreach ($lignes as [$nom, $taille, $etat]) {
        printf("    %-30s %-10s %s\n", $nom, $taille, $etat);
    }
}

if ($apply) {
    $store->write($content);
    echo "\n$importe image(s) importée(s), $ecarte écartée(s).\n";
    echo "Chambres mises à jour, site régénéré.\n";
} else {
    echo "\n$importe image(s) recadrée(s) en aperçu, $ecarte écartée(s).\n";
    echo "Aperçus dans : data/apercu-add-img/\n";
    echo "Rien n'a été publié. Pour importer réellement :\n";
    echo "    php tools/import-add-img.php --apply\n";
}

echo "\nLe dossier add_img/ est intact.\n\n";
