<?php
/**
 * tools/build.php — Régénère le site public à partir de data/content.json.
 *
 * C'est le mécanisme de cache du projet : le site public ne lit jamais la base
 * de données ni de PHP, il lit config.js, un fichier statique. Chaque
 * enregistrement dans le back-office rappelle ce build, ce qui invalide et
 * reconstruit le cache. Conséquence : le site reste 100 % statique et rapide,
 * et continue de fonctionner même si PHP est arrêté.
 *
 * Produit :
 *   - config.js            contenu injecté dans le DOM par js/render.js
 *   - index.html           blocs SEO (title, meta, Open Graph, JSON-LD)
 *   - sitemap.xml          plan du site
 *
 * Usage :  php tools/build.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../lib/content.php';

$root = dirname(__DIR__);

try {
    $store   = new ContentStore($root);
    $content = $store->read();
} catch (Throwable $e) {
    fwrite(STDERR, "Lecture de data/content.json impossible : " . $e->getMessage() . "\n");
    exit(1);
}

$builder = new SiteBuilder($root, $content);
$written = $builder->buildAll();

foreach ($written as $file => $bytes) {
    printf("  %-14s %7s o\n", $file, number_format($bytes, 0, ',', ' '));
}
echo "Site régénéré depuis data/content.json.\n";
