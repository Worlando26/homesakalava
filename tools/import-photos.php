<?php
/**
 * tools/import-photos.php — Importe les photos d'origine dans la médiathèque.
 *
 * Lit photos_sakalava/ en LECTURE SEULE : les fichiers d'origine ne sont ni
 * déplacés, ni renommés, ni supprimés. Chaque photo est archivée dans
 * storage/originals/ puis déclinée en WebP + JPEG à plusieurs largeurs.
 *
 * Usage :  php tools/import-photos.php
 * Le script affiche les identifiants de média à reporter dans data/content.json.
 */

declare(strict_types=1);
require __DIR__ . '/../lib/images.php';

$root   = dirname(__DIR__);
$source = $root . '/photos_sakalava';

/**
 * Association nom de fichier d'origine → identifiant lisible + texte alternatif.
 * Le texte alternatif est rédigé à la main : c'est de l'accessibilité, pas du
 * remplissage automatique.
 */
$plan = [
    'hero.jpeg' => [
        'name' => 'plage-coucher-soleil',
        'alt'  => "Coucher de soleil sur la plage à marée basse, un arbre isolé "
                . "se reflétant dans l'eau, à quelques pas de Home Sakalava",
    ],
    'WhatsApp Image 2026-09-19 at 03.57.40.jpeg' => [
        'name' => 'chambre-lit-baldaquin',
        'alt'  => "Chambre avec lit à baldaquin et moustiquaire, mobilier en bois "
                . "local, suspensions en vannerie et ouverture sur le jardin",
    ],
];

if (!is_dir($source)) {
    fwrite(STDERR, "Dossier introuvable : $source\n");
    exit(1);
}

$media = [];
foreach ($plan as $file => $meta) {
    $path = $source . '/' . $file;
    if (!is_file($path)) {
        fwrite(STDERR, "IGNORÉ (absent) : $file\n");
        continue;
    }

    try {
        $record = ImageService::import($path, $meta['name'], $meta['alt']);
    } catch (Throwable $e) {
        fwrite(STDERR, "ÉCHEC $file : " . $e->getMessage() . "\n");
        continue;
    }

    $media[$record['id']] = $record;
    echo "OK  {$file}\n";
    echo "    id       : {$record['id']}\n";
    echo "    variantes: " . implode(', ', array_keys($record['variants'])) . "\n";
}

// Photos restées dans photos_sakalava/ sans plan d'import : on le signale.
foreach (glob($source . '/*') as $f) {
    if (is_file($f) && !isset($plan[basename($f)])) {
        echo "NON IMPORTÉ (pas au plan) : " . basename($f) . "\n";
    }
}

file_put_contents(
    $root . '/data/media.generated.json',
    json_encode($media, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
);

echo "\n" . count($media) . " média(s) importé(s) → data/media.generated.json\n";
echo "Originaux intacts dans photos_sakalava/.\n";
