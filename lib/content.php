<?php
/**
 * lib/content.php — Lecture/écriture des données + génération du site public.
 *
 * ContentStore : accès atomique et verrouillé à data/content.json.
 * SiteBuilder  : transforme ces données en config.js + blocs SEO + sitemap.
 *
 * Le back-office et les outils en ligne de commande passent tous les deux par
 * ici, pour qu'il n'existe qu'une seule définition de la forme des données.
 */

declare(strict_types=1);

require_once __DIR__ . '/images.php';

final class ContentStore
{
    private string $file;

    public function __construct(private string $root)
    {
        $this->file = $root . '/data/content.json';
    }

    public function path(): string
    {
        return $this->file;
    }

    /** @throws RuntimeException */
    public function read(): array
    {
        if (!is_file($this->file)) {
            throw new RuntimeException(
                "data/content.json est absent. Lancez : php tools/seed-content.php"
            );
        }
        $raw  = file_get_contents($this->file);
        $data = json_decode((string) $raw, true);
        if (!is_array($data)) {
            throw new RuntimeException("data/content.json est illisible (JSON invalide).");
        }
        return $data;
    }

    /**
     * Écrit les données puis régénère immédiatement le site public.
     * L'écriture passe par un fichier temporaire + rename : en cas de coupure,
     * content.json n'est jamais laissé à moitié écrit.
     */
    public function write(array $data): void
    {
        $data['updated_at'] = date('c');

        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        if ($json === false) {
            throw new RuntimeException("Encodage JSON impossible.");
        }

        $tmp = $this->file . '.tmp';
        if (file_put_contents($tmp, $json, LOCK_EX) === false) {
            throw new RuntimeException("Écriture impossible dans data/.");
        }
        if (!rename($tmp, $this->file)) {
            @unlink($tmp);
            throw new RuntimeException("Remplacement de content.json impossible.");
        }

        // Invalidation du cache public : on reconstruit config.js dans la foulée.
        (new SiteBuilder($this->root, $data))->buildAll();
    }
}


final class SiteBuilder
{
    public function __construct(private string $root, private array $c) {}

    /** @return array<string,int> fichier => octets écrits */
    public function buildAll(): array
    {
        return [
            'config.js'   => $this->writeFile('config.js', $this->buildConfigJs()),
            'index.html'  => $this->patchIndexHtml(),
            'sitemap.xml' => $this->writeFile('sitemap.xml', $this->buildSitemap()),
        ];
    }

    // ══ config.js ═══════════════════════════════════════════════════════════

    private function buildConfigJs(): string
    {
        $cfg = [
            'brand'       => $this->brand(),
            'theme'       => $this->c['theme'] ?? [],
            'settings'    => $this->c['settings'] ?? [],
            'nav'         => $this->c['nav'] ?? [],
            'hero'        => $this->hero(),
            'story'       => $this->c['story'] ?? [],
            'amazing'     => $this->spaces(),
            'ideal'       => $this->rooms(),
            'trusted'     => $this->services(),
            'restaurant'  => $this->restaurant(),
            'activities'  => $this->activities(),
            'reputation'  => $this->c['reputation'] ?? [],
            'access'      => $this->access(),
            'faq'         => $this->faq(),
            'booking'     => $this->booking(),
            'footer'      => $this->footer(),
        ];

        $json = json_encode(
            $cfg,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        $stamp = date('d/m/Y à H:i');

        return <<<JS
        // ═══════════════════════════════════════════════════════════════
        //  config.js — Home SAKALAVA · Nosy Be, Madagascar
        //
        //  ⚠ FICHIER GÉNÉRÉ AUTOMATIQUEMENT — NE PAS MODIFIER À LA MAIN.
        //  Toute modification ici sera écrasée au prochain enregistrement
        //  depuis le back-office.
        //
        //  Source des données : data/content.json
        //  Régénérer          : php tools/build.php
        //  Généré le          : {$stamp}
        // ═══════════════════════════════════════════════════════════════

        const CONFIG = {$json};

        JS;
    }

    // ── Blocs de contenu ───────────────────────────────────────────────────

    private function brand(): array
    {
        $b = $this->c['brand'] ?? [];
        return [
            'name'     => $b['name']     ?? '',
            'logoText' => $b['logoText'] ?? '',
            'tagline'  => $b['tagline']  ?? '',
        ];
    }

    private function hero(): array
    {
        $h     = $this->c['hero'] ?? [];
        $rooms = $this->activeRooms();

        return [
            'image'         => $this->img($h['mediaId'] ?? '', 'hero'),
            'gradient'      => 'linear-gradient(145deg, #0a1a1a 0%, #1d3f3d 100%)',
            'logoName'      => $h['logoName']      ?? '',
            'logoSub'       => $h['logoSub']       ?? '',
            'bookLabel'     => $h['bookLabel']     ?? '',
            'badge'         => $h['badge']         ?? '',
            'scrollHint'    => $h['scrollHint']    ?? '',
            'headlineTitle' => $h['headlineTitle'] ?? '',
            'headlineSub'   => $h['headlineSub']   ?? '',
            'features'      => $h['features']      ?? [],
            // Les deux flèches du hero pointent vers la première et la
            // dernière chambre publiée, plus de noms écrits en dur.
            'prevRoom'      => $rooms ? (string) end($rooms)['name']   : '',
            'nextRoom'      => $rooms ? (string) $rooms[0]['name']     : '',
        ];
    }

    private function spaces(): array
    {
        $s = $this->c['spaces'] ?? [];
        return [
            'kicker' => $s['kicker'] ?? '',
            'cta'    => $s['cta']    ?? ['label' => '', 'href' => '#ideal'],
            'items'  => array_map(fn(array $i) => [
                'name'     => $i['name'] ?? '',
                'meta'     => $i['meta'] ?? '',
                'image'    => $this->img($i['mediaId'] ?? '', 'card'),
                'gradient' => $i['gradient'] ?? '',
                'big'      => (bool) ($i['big'] ?? false),
            ], $s['items'] ?? []),
        ];
    }

    private function rooms(): array
    {
        $r        = $this->c['rooms'] ?? [];
        $settings = $this->c['settings'] ?? [];
        $show     = (bool) ($settings['showPrices'] ?? false);
        $fallback = (string) ($settings['priceFallback'] ?? 'Tarif sur demande');

        $items = array_map(function (array $i) use ($show, $fallback) {
            $price = trim((string) ($i['price'] ?? ''));
            return [
                'id'        => $i['id']   ?? '',
                'name'      => $i['name'] ?? '',
                'loc'       => $i['loc']  ?? '',
                'tag'       => $i['tag']  ?? '',
                'area'      => $i['area'] ?? '',
                'desc'      => $i['desc'] ?? '',
                // Un prix ne s'affiche que si le réglage global l'autorise ET
                // qu'il a été saisi. Sinon : mention de repli, jamais de vide.
                'price'     => ($show && $price !== '') ? $price : $fallback,
                'amenities' => array_values($i['amenities'] ?? []),
                'image'     => $this->img($i['coverId'] ?? '', 'card'),
                'gradient'  => $i['gradient'] ?? '',
            ];
        }, $this->activeRooms());

        return [
            'kicker' => $r['kicker'] ?? '',
            'intro'  => $r['intro']  ?? '',
            'shared' => array_values($r['shared'] ?? []),
            'items'  => $items,
        ];
    }

    private function services(): array
    {
        $s = $this->c['services'] ?? [];
        return [
            'title'     => $s['title'] ?? '',
            'cta'       => $s['cta']   ?? ['label' => '', 'href' => '#booking'],
            'amenities' => array_values($s['pills'] ?? []),
            'items'     => array_map(fn(array $i) => [
                'label'      => $i['label'] ?? '',
                'icon'       => $i['icon']  ?? 'check',
                'supplement' => (bool) ($i['supplement'] ?? false),
            ], $s['items'] ?? []),
            'note'      => $s['note'] ?? '',
            'floatCard' => [
                'title' => $s['card']['title'] ?? '',
                'email' => $this->contactEmail() ?: 'Adresse e-mail à renseigner',
            ],
            'floatImages' => array_map(fn(array $i) => [
                'image'    => $this->img($i['mediaId'] ?? '', 'thumb'),
                'gradient' => $i['gradient'] ?? '',
                'label'    => $i['label'] ?? '',
            ], $s['floatImages'] ?? []),
        ];
    }

    private function restaurant(): array
    {
        $r = $this->c['restaurant'] ?? [];
        return [
            'kicker'   => $r['kicker'] ?? '',
            'title'    => $r['title']  ?? '',
            'text'     => $r['text']   ?? '',
            'image'    => $this->img($r['mediaId'] ?? '', 'card'),
            'gradient' => $r['gradient'] ?? '',
            'items'    => array_map(fn(array $i) => [
                'icon'  => $i['icon']  ?? 'check',
                'label' => $i['label'] ?? '',
            ], $r['items'] ?? []),
            'note'     => $r['note'] ?? '',
        ];
    }

    private function activities(): array
    {
        $a = $this->c['activities'] ?? [];
        return [
            'kicker'    => $a['kicker'] ?? '',
            'title'     => $a['title']  ?? '',
            'text'      => $a['text']   ?? '',
            'items'     => array_map(fn(array $i) => [
                'icon'  => $i['icon']  ?? 'check',
                'label' => $i['label'] ?? '',
            ], $a['items'] ?? []),
            'note'      => $a['note'] ?? '',
            'distances' => array_values($a['distances'] ?? []),
        ];
    }

    private function access(): array
    {
        $a = $this->c['access']  ?? [];
        $k = $this->c['contact'] ?? [];

        // Chaque coordonnée manquante devient un placeholder visible : le
        // gérant voit tout de suite ce qu'il reste à renseigner, et aucun
        // numéro n'est inventé.
        $rows = [
            ['icon' => 'map-pin', 'label' => 'Adresse',
             'value' => implode(', ', array_slice($k['addressLines'] ?? [], 1)),
             'href'  => ''],
            ['icon' => 'phone', 'label' => 'Téléphone',
             'value' => $k['phone'] ?: '[À renseigner]',
             'href'  => $k['phone'] ? 'tel:' . preg_replace('/\s+/', '', $k['phone']) : ''],
            ['icon' => 'mail', 'label' => 'E-mail',
             'value' => $k['email'] ?: '[À renseigner]',
             'href'  => $k['email'] ? 'mailto:' . $k['email'] : ''],
            ['icon' => 'navigation', 'label' => 'Coordonnées GPS',
             'value' => $k['gps'] ?: '[À renseigner]',
             'href'  => ''],
            ['icon' => 'log-in', 'label' => 'Arrivée',
             'value' => trim(($k['checkinFrom'] ?? '') . ' – ' . ($k['checkinTo'] ?? ''), ' –'),
             'href'  => ''],
            ['icon' => 'log-out', 'label' => 'Départ',
             'value' => trim(($k['checkoutFrom'] ?? '') . ' – ' . ($k['checkoutTo'] ?? ''), ' –'),
             'href'  => ''],
            ['icon' => 'languages', 'label' => 'Langues parlées',
             'value' => $k['languages'] ?? '', 'href' => ''],
            ['icon' => 'wallet', 'label' => 'Paiement',
             'value' => $k['payment'] ?? '', 'href' => ''],
        ];

        return [
            'kicker'    => $a['kicker'] ?? '',
            'title'     => $a['title']  ?? '',
            'text'      => $a['text']   ?? '',
            'rows'      => array_values(array_filter($rows, fn($r) => $r['value'] !== '')),
            'facebook'  => $k['facebook'] ?? '',
        ];
    }

    private function faq(): array
    {
        $f = $this->c['faq'] ?? [];
        return [
            'kicker' => $f['kicker'] ?? '',
            'title'  => $f['title']  ?? '',
            'intro'  => $f['intro']  ?? '',
            'image'  => $this->img($f['mediaId'] ?? '', 'card'),
            'items'  => array_map(fn(array $i) => [
                'q' => $i['q'] ?? '',
                'a' => $i['a'] ?? '',
            ], $f['items'] ?? []),
        ];
    }

    private function booking(): array
    {
        $b = $this->c['booking'] ?? [];
        $k = $this->c['contact'] ?? [];

        $infoCard = [
            ['label' => 'Arrivée',  'value' => trim(($k['checkinFrom'] ?? '') . ' – ' . ($k['checkinTo'] ?? ''), ' –')],
            ['label' => 'Départ',   'value' => trim(($k['checkoutFrom'] ?? '') . ' – ' . ($k['checkoutTo'] ?? ''), ' –')],
            ['label' => 'Langues',  'value' => $k['languages'] ?? ''],
            ['label' => 'Paiement', 'value' => $k['payment'] ?? ''],
            ['label' => 'Téléphone','value' => $k['phone'] ?: '[À renseigner]'],
            ['label' => 'E-mail',   'value' => $k['email'] ?: '[À renseigner]'],
        ];

        return [
            'kicker'   => $b['kicker']   ?? '',
            'title'    => $b['title']    ?? '',
            'subtitle' => $b['subtitle'] ?? '',
            'intro'    => $b['intro']    ?? '',
            'infoCard' => array_values(array_filter($infoCard, fn($r) => $r['value'] !== '')),
            'rooms'    => array_map(fn(array $r) => $r['name'] ?? '', $this->activeRooms()),
            // Destination réelle du formulaire. Tant que l'e-mail n'est pas
            // renseigné, le site bascule sur Facebook et le dit clairement.
            'mailto'   => $this->contactEmail(),
            'facebook' => $k['facebook'] ?? '',
            'labels'   => [
                'firstName' => 'Prénom',
                'lastName'  => 'Nom',
                'email'     => 'E-mail',
                'phone'     => 'Téléphone',
                'checkIn'   => "Date d'arrivée",
                'checkOut'  => 'Date de départ',
                'roomType'  => 'Chambre souhaitée',
                'guests'    => 'Voyageurs',
                'message'   => 'Votre message',
                'submit'    => 'Préparer ma demande',
                'note'      => "Ce bouton ouvre votre messagerie avec le message déjà rédigé. "
                             . "Rien n'est envoyé automatiquement, vous relisez avant.",
                'noEmail'   => "L'adresse e-mail de la maison n'est pas encore en ligne. "
                             . "En attendant, écrivez-nous sur Facebook :",
                'fbLink'    => 'Ouvrir la page Facebook',
                'successTitle' => 'Votre message est prêt',
                'successText'  => "Votre messagerie vient de s'ouvrir avec la demande pré-remplie. "
                                . "Relisez-la et envoyez-la — nous répondons sous quelques jours.",
                'resetBtn'  => 'Recommencer',
                'ph' => [
                    'firstName' => 'Prénom',
                    'lastName'  => 'Nom',
                    'email'     => 'vous@exemple.com',
                    'phone'     => 'Indicatif compris',
                    'message'   => "Nombre de nuits, heure d'arrivée, navette aéroport, régime alimentaire…",
                ],
            ],
        ];
    }

    private function footer(): array
    {
        $f = $this->c['footer']  ?? [];
        $k = $this->c['contact'] ?? [];

        $address = $k['addressLines'] ?? [];
        if ($k['email'] ?? '') { $address[] = $k['email']; }
        if ($k['phone'] ?? '') { $address[] = $k['phone']; }

        $social = [];
        if ($k['facebook'] ?? '') {
            $social[] = ['label' => 'Facebook', 'href' => $k['facebook']];
        }

        return [
            'cta' => [
                'title'    => $f['ctaTitle']  ?? '',
                'button'   => $f['ctaButton'] ?? '',
                'href'     => $f['ctaHref']   ?? '#booking',
                'image'    => $this->img($f['ctaMediaId'] ?? '', 'card'),
                'gradient' => $f['ctaGradient'] ?? '',
            ],
            'wordmark'  => $this->c['brand']['wordmark'] ?? '',
            'address'   => array_values($address),
            'social'    => $social,
            'legal'     => array_values($f['legal'] ?? []),
            'copyright' => $f['copyright'] ?? '',
        ];
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /** Chambres publiées, dans l'ordre défini en admin. */
    private function activeRooms(): array
    {
        return array_values(array_filter(
            $this->c['rooms']['items'] ?? [],
            fn(array $r) => (bool) ($r['active'] ?? true)
        ));
    }

    private function contactEmail(): string
    {
        $e = trim((string) ($this->c['contact']['email'] ?? ''));
        return filter_var($e, FILTER_VALIDATE_EMAIL) ? $e : '';
    }

    /**
     * Transforme un identifiant de média en objet image prêt pour le front
     * (WebP + repli JPEG + srcset). Renvoie null si le média est absent :
     * js/render.js retombe alors sur le dégradé de la carte.
     *
     * @param string $usage 'hero' | 'card' | 'thumb' — pilote l'attribut sizes.
     */
    private function img(string $id, string $usage = 'card'): ?array
    {
        $m = $this->c['media'][$id] ?? null;
        if (!$m) {
            return null;
        }

        $webpSet = [];
        $jpgSet  = [];
        foreach ($m['variants'] ?? [] as $v) {
            $webpSet[] = $v['webp']     . ' ' . $v['w'] . 'w';
            $jpgSet[]  = $v['fallback'] . ' ' . $v['w'] . 'w';
        }

        $sizes = match ($usage) {
            'hero'  => '100vw',
            'thumb' => '(max-width: 639px) 45vw, 220px',
            default => '(max-width: 639px) 92vw, (max-width: 1023px) 48vw, 600px',
        };

        return [
            'src'    => $m['src'],
            'webp'   => $m['webp'],
            'srcset' => [
                'webp' => implode(', ', $webpSet),
                'jpg'  => implode(', ', $jpgSet),
            ],
            'sizes'  => $sizes,
            'alt'    => $m['alt'] ?? '',
            'width'  => $m['width']  ?? null,
            'height' => $m['height'] ?? null,
            // Le hero est l'image la plus visible au-dessus de la ligne de
            // flottaison : elle ne doit jamais être en chargement différé.
            'eager'  => $usage === 'hero',
        ];
    }

    // ══ index.html : blocs SEO ══════════════════════════════════════════════

    private function patchIndexHtml(): int
    {
        $path = $this->root . '/index.html';
        $html = (string) file_get_contents($path);

        $new = $this->replaceBlock($html, 'SEO', $this->buildSeoBlock());

        if ($new !== $html) {
            file_put_contents($path, $new);
        }
        return strlen($new);
    }

    /** Remplace le contenu entre <!-- BUILD:NOM --> et <!-- /BUILD:NOM -->. */
    private function replaceBlock(string $html, string $name, string $inner): string
    {
        $pattern = '/(<!-- BUILD:' . $name . ' -->)(.*?)(<!-- \/BUILD:' . $name . ' -->)/s';
        if (!preg_match($pattern, $html)) {
            fwrite(STDERR, "Repère BUILD:$name absent de index.html — bloc non mis à jour.\n");
            return $html;
        }
        return preg_replace($pattern, '$1' . "\n" . $inner . '  $3', $html) ?? $html;
    }

    private function buildSeoBlock(): string
    {
        $seo     = $this->c['seo'] ?? [];
        $contact = $this->c['contact'] ?? [];
        $brand   = $this->c['brand'] ?? [];

        $title = $seo['title']       ?? '';
        $desc  = $seo['description'] ?? '';
        $url   = rtrim((string) ($seo['siteUrl'] ?? ''), '/');
        $ogImg = $this->c['media'][$seo['ogImageId'] ?? ''] ?? null;
        $ogAbs = $ogImg ? ($url !== '' ? $url . '/' . $ogImg['src'] : $ogImg['src']) : '';

        $e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $lines = [];
        $lines[] = '  <title>' . $e($title) . '</title>';
        $lines[] = '  <meta name="description" content="' . $e($desc) . '">';
        $lines[] = '  <meta name="theme-color" content="' . $e($this->c['theme']['dark'] ?? '#0a1a1a') . '">';
        if ($url !== '') {
            $lines[] = '  <link rel="canonical" href="' . $e($url) . '/">';
        }
        $lines[] = '';
        $lines[] = '  <meta property="og:type" content="website">';
        $lines[] = '  <meta property="og:site_name" content="' . $e($brand['name'] ?? '') . '">';
        $lines[] = '  <meta property="og:title" content="' . $e($title) . '">';
        $lines[] = '  <meta property="og:description" content="' . $e($desc) . '">';
        $lines[] = '  <meta property="og:locale" content="' . $e($seo['locale'] ?? 'fr_FR') . '">';
        if ($ogAbs !== '') {
            $lines[] = '  <meta property="og:image" content="' . $e($ogAbs) . '">';
            $lines[] = '  <meta property="og:image:alt" content="' . $e($ogImg['alt'] ?? '') . '">';
        }
        if ($url !== '') {
            $lines[] = '  <meta property="og:url" content="' . $e($url) . '/">';
        }
        $lines[] = '  <meta name="twitter:card" content="summary_large_image">';
        $lines[] = '';
        $lines[] = '  <script type="application/ld+json">';
        $lines[] = '  ' . $this->buildJsonLd();
        $lines[] = '  </script>';

        return implode("\n", $lines) . "\n";
    }

    /** Données structurées LodgingBusiness — uniquement des faits connus. */
    private function buildJsonLd(): string
    {
        $k     = $this->c['contact'] ?? [];
        $brand = $this->c['brand']   ?? [];
        $seo   = $this->c['seo']     ?? [];
        $rep   = $this->c['reputation'] ?? [];
        $url   = rtrim((string) ($seo['siteUrl'] ?? ''), '/');

        $ld = [
            '@context' => 'https://schema.org',
            '@type'    => 'LodgingBusiness',
            'name'     => $brand['name'] ?? '',
            'description' => $seo['description'] ?? '',
            'address'  => [
                '@type'           => 'PostalAddress',
                'streetAddress'   => 'Ampasikely',
                'addressLocality' => 'Dzamandzar',
                'postalCode'      => '207',
                'addressRegion'   => 'Diana',
                'addressCountry'  => 'MG',
            ],
            'numberOfRooms'   => count($this->activeRooms()),
            'petsAllowed'     => true,
            'availableLanguage' => ['fr', 'en'],
            'amenityFeature'  => array_map(fn(array $s) => [
                '@type' => 'LocationFeatureSpecification',
                'name'  => $s['label'] ?? '',
                'value' => true,
            ], array_slice($this->c['services']['items'] ?? [], 0, 10)),
        ];

        if ($url !== '')            { $ld['url']       = $url . '/'; }
        if ($k['phone']  ?? '')     { $ld['telephone'] = $k['phone']; }
        if ($k['email']  ?? '')     { $ld['email']     = $k['email']; }
        if ($k['facebook'] ?? '')   { $ld['sameAs']    = [$k['facebook']]; }
        if ($k['checkinFrom'] ?? '') {
            $ld['checkinTime']  = $k['checkinFrom'];
            $ld['checkoutTime'] = $k['checkoutFrom'] ?? '';
        }

        // Note agrégée : reprise des chiffres Booking fournis dans about.txt.
        if (!empty($rep['scores'])) {
            $ld['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => '9.6',
                'bestRating'  => '10',
                'ratingCount' => 75,
            ];
        }

        // Une image absolue n'a de sens qu'une fois le domaine connu.
        $ogImg = $this->c['media'][$seo['ogImageId'] ?? ''] ?? null;
        if ($ogImg && $url !== '') {
            $ld['image'] = $url . '/' . $ogImg['src'];
        }

        return (string) json_encode(
            $ld,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }

    // ══ sitemap.xml ═════════════════════════════════════════════════════════

    private function buildSitemap(): string
    {
        $url = rtrim((string) ($this->c['seo']['siteUrl'] ?? ''), '/');
        $day = date('Y-m-d');

        if ($url === '') {
            // Sans domaine défini, un sitemap avec des URL relatives serait
            // invalide : on produit un fichier explicite plutôt qu'un faux.
            return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
                 . "<!-- Domaine non renseigné dans l'admin (Réglages → Site).\n"
                 . "     Renseignez-le puis régénérez : le sitemap sera complété. -->\n"
                 . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"></urlset>\n";
        }

        // Site une seule page : les sections sont des ancres, pas des URL.
        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
             . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n"
             . "  <url>\n"
             . "    <loc>{$url}/</loc>\n"
             . "    <lastmod>{$day}</lastmod>\n"
             . "    <changefreq>monthly</changefreq>\n"
             . "    <priority>1.0</priority>\n"
             . "  </url>\n"
             . "</urlset>\n";
    }

    private function writeFile(string $name, string $contents): int
    {
        $path = $this->root . '/' . $name;
        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException("Écriture impossible : $name");
        }
        return strlen($contents);
    }
}
