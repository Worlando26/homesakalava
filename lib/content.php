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
require_once __DIR__ . '/pages.php';
require_once __DIR__ . '/i18n.php';
require_once __DIR__ . '/config.php';

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
    /** Contenu français d'origine, jamais modifié : base de chaque traduction. */
    private array $source;

    private I18n $i18n;

    public function __construct(private string $root, private array $c)
    {
        $this->source = $c;
        $this->i18n   = new I18n($root, I18n::SOURCE);
    }

    /** Raccourci vers un texte d'interface dans la langue en cours de génération. */
    private function t(string $cle, string $repli = ''): string
    {
        return $this->i18n->t($cle, $repli);
    }

    /**
     * Coordonnées de la maison, composées de deux sources :
     *
     *   - config/site.php pour tout ce qui est opérationnel — téléphone,
     *     e-mail, WhatsApp, GPS, adresse, réseaux, lien Maps. C'est le
     *     fichier unique que le client remplit ;
     *   - data/content.json pour ce qui est éditorial et dépend de la
     *     langue — horaires d'arrivée, langues parlées, moyens de paiement.
     *
     * Un champ resté à « A_REMPLIR » revient vide : l'affichage décide
     * ensuite de masquer le lien ou d'écrire « à renseigner ».
     */
    private function contact(): array
    {
        $editorial = $this->c['contact'] ?? [];

        $adresse = Config::liste('hotel.adresse');
        if (!$adresse) {
            $adresse = array_values(array_filter($editorial['addressLines'] ?? []));
        }
        // Le nom de la maison ouvre toujours l'adresse postale.
        $nom = Config::val('hotel.nom') ?: ($this->c['brand']['name'] ?? '');
        if ($nom !== '' && (!$adresse || strcasecmp($adresse[0], $nom) !== 0)) {
            array_unshift($adresse, $nom);
        }

        return [
            'phone'        => Config::val('hotel.telephone'),
            'email'        => Config::val('hotel.email'),
            'whatsapp'     => Config::whatsappLien(),
            'gps'          => Config::val('hotel.gps'),
            'maps'         => Config::mapsLien(),
            'addressLines' => $adresse,
            'reseaux'      => Config::reseaux(),

            // Liens prêts à l'emploi, fabriqués une seule fois ici.
            'telLink'      => Config::telLien(),
            'mailLink'     => Config::mailtoLien(),

            // Partie éditoriale, traduite avec le reste du contenu.
            'checkinFrom'  => $editorial['checkinFrom']  ?? '',
            'checkinTo'    => $editorial['checkinTo']    ?? '',
            'checkoutFrom' => $editorial['checkoutFrom'] ?? '',
            'checkoutTo'   => $editorial['checkoutTo']   ?? '',
            'languages'    => $editorial['languages']    ?? '',
            'payment'      => $editorial['payment']      ?? '',
        ];
    }

    /**
     * Fourchette de prix, déduite des tarifs réellement publiés.
     *
     * Renvoie une chaîne vide si les tarifs sont masqués ou absents :
     * annoncer une fourchette inventée serait pire que ne rien annoncer.
     */
    private function fourchettePrix(): string
    {
        if (empty($this->c['settings']['showPrices'])) {
            return '';
        }

        $montants = [];
        foreach ($this->activeRooms() as $r) {
            // On retient le premier nombre du tarif : « À partir de 56 € la
            // nuit » donne 56.
            if (preg_match('/(\d[\d\s]*)/u', (string) ($r['price'] ?? ''), $m)) {
                $n = (int) preg_replace('/\D/', '', $m[1]);
                if ($n > 0) {
                    $montants[] = $n;
                }
            }
        }

        if (!$montants) {
            return '';
        }

        // La devise est celle écrite dans les tarifs ; à défaut, rien.
        $devise = '';
        foreach ($this->activeRooms() as $r) {
            if (preg_match('/([€$£]|\bAr\b|\bMGA\b|\bEUR\b|\bUSD\b)/u',
                           (string) ($r['price'] ?? ''), $m)) {
                $devise = $m[1];
                break;
            }
        }

        $min = min($montants);
        $max = max($montants);

        return $min === $max
            ? trim($min . ' ' . $devise)
            : trim($min . '–' . $max . ' ' . $devise);
    }

    /** Adresse du site : le fichier de configuration fait autorité. */
    private function siteUrl(): string
    {
        return Config::siteUrl()
            ?: rtrim((string) ($this->c['seo']['siteUrl'] ?? ''), '/');
    }

    /** @return array<string,int> fichier => octets écrits */
    public function buildAll(): array
    {
        $ecrits = [];

        // Une passe complète par langue. Le français sort à la racine, les
        // autres dans leur propre dossier : chaque langue a ainsi sa propre
        // adresse, ce qu'exige le référencement.
        foreach (array_keys(I18n::LANGUES) as $lang) {
            $this->i18n = new I18n($this->root, $lang);
            $this->c    = $this->i18n->traduire($this->source);

            $dossier = $this->i18n->dossier();
            $prefixe = $this->i18n->prefixe();

            if ($dossier !== '') {
                $chemin = rtrim($this->root . '/' . $dossier, '/');
                if (!is_dir($chemin) && !mkdir($chemin, 0775, true) && !is_dir($chemin)) {
                    throw new RuntimeException("Création impossible : $chemin");
                }
            }

            $ecrits[$dossier . 'config.js'] =
                $this->writeFile($dossier . 'config.js', $this->buildConfigJs());

            // La page introuvable est generee avec les autres, mais elle
            // reste hors du menu et hors du plan du site.
            foreach (array_merge(PageTemplates::PAGES, ['404']) as $page) {
                $fichier = $dossier . $page . '.html';
                $ecrits[$fichier] = $this->writeFile($fichier, PageTemplates::render(
                    $page,
                    $prefixe,
                    $lang,
                    $this->buildSeoBlock($page) . $this->i18n->scriptLangue($page),
                    $this->i18n->selecteur($page),
                    $this->i18n->ui()
                ));
            }
        }

        // Le contenu français est rétabli pour les appels ultérieurs.
        $this->i18n = new I18n($this->root, I18n::SOURCE);
        $this->c    = $this->source;

        $ecrits['sitemap.xml'] = $this->writeFile('sitemap.xml', $this->buildSitemap());

        return $ecrits;
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
            'pages'       => $this->pages(),
            't'           => $this->i18n->ui(),
            'lang'        => $this->i18n->lang(),
        ];

        $json = json_encode(
            $cfg,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );

        // config.js est un fichier externe : « </script> » y est inoffensif.
        // En revanche U+2028 et U+2029, laissés bruts par JSON_UNESCAPED_UNICODE,
        // cassent l'analyse du script sur les moteurs JavaScript anciens.
        $json = str_replace(["\u{2028}", "\u{2029}"], [' ', ' '], (string) $json);

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

            // Galerie : les photos de la chambre, dans l'ordre défini en
            // admin, débarrassées de celles qui auraient été supprimées.
            $gallery = [];
            foreach ($i['mediaIds'] ?? [] as $id) {
                if ($img = $this->img((string) $id, 'gallery')) {
                    $gallery[] = $img;
                }
            }

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
                'gallery'   => $gallery,
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
        $k = $this->contact();

        // Chaque coordonnée manquante devient un placeholder visible : le
        // gérant voit tout de suite ce qu'il reste à renseigner, et aucun
        // numéro n'est inventé.
        $rows = [
            ['icon' => 'map-pin', 'label' => $this->t('address'),
             'value' => implode(', ', array_slice($k['addressLines'] ?? [], 1)),
             'href'  => ''],
            ['icon' => 'phone', 'label' => $this->t('phone'),
             'value' => ($k['phone'] ?? '') ?: $this->t('toFill'),
             'href'  => $k['telLink'] ?? ''],
            ['icon' => 'message-circle', 'label' => 'WhatsApp',
             'value' => ($k['whatsapp'] ?? '') ? ($k['phone'] ?: 'WhatsApp') : '',
             'href'  => $k['whatsapp'] ?? ''],
            ['icon' => 'mail', 'label' => $this->t('email'),
             'value' => ($k['email'] ?? '') ?: $this->t('toFill'),
             'href'  => $k['mailLink'] ?? ''],
            // La ligne GPS mène au plan : c'est ce qu'on attend en cliquant
            // sur des coordonnées, bien plus qu'un texte à recopier.
            ['icon' => 'navigation', 'label' => $this->t('gps'),
             'value' => ($k['gps'] ?? '') ?: $this->t('toFill'),
             'href'  => ($k['gps'] ?? '') ? ($k['maps'] ?? '') : ''],
            ['icon' => 'log-in', 'label' => $this->t('checkin'),
             'value' => trim(($k['checkinFrom'] ?? '') . ' – ' . ($k['checkinTo'] ?? ''), ' –'),
             'href'  => ''],
            ['icon' => 'log-out', 'label' => $this->t('checkout'),
             'value' => trim(($k['checkoutFrom'] ?? '') . ' – ' . ($k['checkoutTo'] ?? ''), ' –'),
             'href'  => ''],
            ['icon' => 'languages', 'label' => $this->t('languages'),
             'value' => $k['languages'] ?? '', 'href' => ''],
            ['icon' => 'wallet', 'label' => $this->t('payment'),
             'value' => $k['payment'] ?? '', 'href' => ''],
        ];

        return [
            'kicker'    => $a['kicker'] ?? '',
            'title'     => $a['title']  ?? '',
            'text'      => $a['text']   ?? '',
            'rows'      => array_values(array_filter($rows, fn($r) => $r['value'] !== '')),

            // Liens exploités ailleurs sur la page : bouton d'appel flottant,
            // lien vers le plan, réseaux sociaux du pied de page.
            'phone'     => $k['phone']    ?? '',
            'telLink'   => $k['telLink']  ?? '',
            'whatsapp'  => $k['whatsapp'] ?? '',
            'mailLink'  => $k['mailLink'] ?? '',
            'maps'      => $k['maps']     ?? '',
            'reseaux'   => $k['reseaux']  ?? [],

            // Conservé pour compatibilité : le premier réseau social connu.
            'facebook'  => (function () use ($k) {
                foreach ($k['reseaux'] ?? [] as $r) {
                    if (($r['cle'] ?? '') === 'facebook') {
                        return $r['href'];
                    }
                }
                return '';
            })(),
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
        $k = $this->contact();

        $infoCard = [
            ['label' => $this->t('checkin'),  'value' => trim(($k['checkinFrom'] ?? '') . ' – ' . ($k['checkinTo'] ?? ''), ' –')],
            ['label' => $this->t('checkout'),   'value' => trim(($k['checkoutFrom'] ?? '') . ' – ' . ($k['checkoutTo'] ?? ''), ' –')],
            ['label' => $this->t('languagesShort'),  'value' => $k['languages'] ?? ''],
            ['label' => $this->t('payment'), 'value' => $k['payment'] ?? ''],
            ['label' => $this->t('phone'),'value' => ($k['phone'] ?? '') ?: $this->t('toFill')],
            ['label' => $this->t('email'),   'value' => ($k['email'] ?? '') ?: $this->t('toFill')],
        ];

        return [
            'kicker'   => $b['kicker']   ?? '',
            'title'    => $b['title']    ?? '',
            'subtitle' => $b['subtitle'] ?? '',
            'intro'    => $b['intro']    ?? '',
            'infoCard' => array_values(array_filter($infoCard, fn($r) => $r['value'] !== '')),
            'rooms'    => array_map(fn(array $r) => $r['name'] ?? '', $this->activeRooms()),

            /**
             * Destination du formulaire. Le point d'entrée est à la racine :
             * les pages anglaises, qui vivent dans en/, doivent le préfixer.
             */
            'endpoint' => $this->i18n->prefixe() . 'contact.php',
            'lang'     => $this->i18n->lang(),

            /**
             * Le formulaire n'est proposé que s'il peut réellement aboutir :
             * adresse de réception renseignée, et SMTP configuré hors mode
             * test. Sinon le site le dit et renvoie vers Facebook.
             */
            'actif'    => Config::formulaireActif(),
            'facebook' => $k['facebook'] ?? '',
            'labels'   => [
                'firstName' => $this->t('formFirstName'),
                'lastName'  => $this->t('formLastName'),
                'email'     => $this->t('formEmail'),
                'phone'     => $this->t('formPhone'),
                'checkIn'   => $this->t('formCheckIn'),
                'checkOut'  => $this->t('formCheckOut'),
                'roomType'  => $this->t('formRoomType'),
                'guests'    => $this->t('formGuests'),
                'message'   => $this->t('formMessage'),
                'submit'    => $this->t('formSubmit'),
                'note'         => $this->t('formNote'),
                'noEmail'      => $this->t('formNoEmail'),
                'fbLink'       => $this->t('formFbLink'),
                'successTitle' => $this->t('formSuccessTitle'),
                'successText'  => $this->t('formSuccessText'),
                'resetBtn'     => $this->t('formReset'),

                // Messages de validation et d'état, affichés sans recharger.
                'error'    => $this->t('formError'),
                'sending'  => $this->t('formSending'),
                'offline'  => $this->t('formOffline'),
                'required' => $this->t('formRequired'),
                'badEmail' => $this->t('formBadEmail'),
                'badDates' => $this->t('formBadDates'),
                'pastDate' => $this->t('formPastDate'),
                'select'   => $this->t('formSelect'),
                // Singulier et pluriel du menu deroulant. La cle « guests »
                // sert deja au libelle du champ : on ne la reutilise pas.
                'guestOne'  => $this->t('guest'),
                'guestMany' => $this->t('guests'),
                'ph' => [
                    'firstName' => $this->t('phFirstName'),
                    'lastName'  => $this->t('phLastName'),
                    'email'     => $this->t('phEmail'),
                    'phone'     => $this->t('phPhone'),
                    'message'   => $this->t('phMessage'),
                ],
            ],
        ];
    }

    /**
     * En-tete de chaque page interieure. Le titre de page est distinct du
     * titre de section : « Nos chambres » en tete de page, « Cinq chambres,
     * pas une de plus » a l interieur.
     */
    private function pages(): array
    {
        $p = $this->c['pages'] ?? [];
        $mk = fn(string $k, string $kicker, string $title, string $intro) => [
            'kicker' => $p[$k]['kicker'] ?? $kicker,
            'title'  => $p[$k]['title']  ?? $title,
            'intro'  => $p[$k]['intro']  ?? $intro,
        ];

        return [
            'chambres'  => $mk('chambres',
                (string) ($this->c['rooms']['kicker'] ?? ''),
                $this->t('pageRooms'),
                (string) ($this->c['rooms']['intro'] ?? '')),
            'activites' => $mk('activites',
                (string) ($this->c['activities']['kicker'] ?? ''),
                (string) ($this->c['activities']['title'] ?? ''),
                (string) ($this->c['activities']['text'] ?? '')),
            'acces'     => $mk('acces',
                (string) ($this->c['access']['kicker'] ?? ''),
                (string) ($this->c['access']['title'] ?? ''),
                (string) ($this->c['access']['text'] ?? '')),
        ];
    }

    private function footer(): array
    {
        $f = $this->c['footer']  ?? [];
        $k = $this->contact();

        $address = $k['addressLines'] ?? [];
        if ($k['email'] ?? '') { $address[] = $k['email']; }
        if ($k['phone'] ?? '') { $address[] = $k['phone']; }

        // Tous les réseaux renseignés dans config/site.php, dans l'ordre.
        $social = array_map(
            fn(array $r) => ['label' => $r['label'], 'href' => $r['href']],
            $k['reseaux'] ?? []
        );

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
        $e = Config::val('hotel.email');
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

        // Les pages d une langue vivent dans leur propre dossier : leurs
        // chemins d images doivent remonter d un cran.
        $base = $this->i18n->prefixe();

        $webpSet = [];
        $jpgSet  = [];
        foreach ($m['variants'] ?? [] as $v) {
            $webpSet[] = $base . $v['webp']     . ' ' . $v['w'] . 'w';
            $jpgSet[]  = $base . $v['fallback'] . ' ' . $v['w'] . 'w';
        }

        $sizes = match ($usage) {
            'hero'    => '100vw',
            'thumb'   => '(max-width: 639px) 45vw, 220px',
            // La visionneuse affiche la photo au plus grand format utile.
            'gallery' => '(max-width: 900px) 94vw, 880px',
            default   => '(max-width: 639px) 92vw, (max-width: 1023px) 48vw, 600px',
        };

        return [
            'src'    => $base . $m['src'],
            'webp'   => $base . $m['webp'],
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

    // ══ Blocs SEO par page ══════════════════════════════════════════════════

    /**
     * Titre et description propres à chaque page. Une page qui reprendrait
     * le titre de l'accueil serait considérée comme un doublon par Google.
     *
     * @return array{0:string,1:string} [titre, description]
     */
    private function pageSeo(string $page): array
    {
        $seo   = $this->c['seo'] ?? [];
        $brand = (string) ($this->c['brand']['name'] ?? '');

        $meta = $this->c['pages'][$page] ?? [];
        if (($meta['seoTitle'] ?? '') !== '' && ($meta['seoDescription'] ?? '') !== '') {
            return [$meta['seoTitle'], $meta['seoDescription']];
        }

        // Repli construit à partir du contenu de la page, pour qu'un titre
        // existe même si le gérant n'a rien saisi.
        return match ($page) {
            'chambres' => [
                $this->t('pageRooms') . ' — ' . $brand,
                (string) ($this->c['rooms']['intro'] ?? ''),
            ],
            'activites' => [
                (string) ($this->c['activities']['title'] ?: $this->t('pageActivities')) . ' — ' . $brand,
                (string) ($this->c['activities']['text'] ?? ''),
            ],
            'acces' => [
                $this->t('pageAccess') . ' — ' . $brand,
                (string) ($this->c['access']['text'] ?? ''),
            ],
            default => [
                (string) ($seo['title'] ?? ''),
                (string) ($seo['description'] ?? ''),
            ],
        };
    }

    private function buildSeoBlock(string $page = 'index'): string
    {
        $seo   = $this->c['seo'] ?? [];
        $brand = $this->c['brand'] ?? [];

        [$title, $desc] = $this->pageSeo($page);
        $url  = $this->siteUrl();
        $file = $page === 'index' ? '' : $page . '.html';
        $ogImg = $this->c['media'][$seo['ogImageId'] ?? ''] ?? null;
        $ogAbs = $ogImg ? ($url !== '' ? $url . '/' . $ogImg['src'] : $ogImg['src']) : '';

        $e = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $lines = [];
        $lines[] = '  <title>' . $e($title) . '</title>';
        $lines[] = '  <meta name="description" content="' . $e($desc) . '">';
        $lines[] = '  <meta name="theme-color" content="' . $e($this->c['theme']['dark'] ?? '#0a1a1a') . '">';
        if ($page === '404') {
            // Une page d erreur n a rien a faire dans un index.
            $lines[] = '  <meta name="robots" content="noindex, follow">';

            /**
             * Le serveur sert cette page en gardant l adresse demandee :
             * un visiteur egare sur /chambres/vue-mer verrait les chemins
             * relatifs se resoudre depuis /chambres/, donc casser. La balise
             * base les reancre a la racine du site.
             */
            $racine = '/';
            if ($url !== '') {
                $chemin = trim((string) parse_url($url, PHP_URL_PATH), '/');
                $racine = '/' . ($chemin !== '' ? $chemin . '/' : '');
            }
            // La base pointe sur le dossier de la page elle-meme, pour que
            // les chemins relatifs du gabarit se resolvent sans detour.
            $lines[] = '  <base href="' . $e($racine . $this->i18n->dossier()) . '">';
        }
        if ($url !== '') {
            $lines[] = '  <link rel="canonical" href="' . $e($url . '/' . $file) . '">';
        }
        $lines[] = '';
        $lines[] = '  <meta property="og:type" content="website">';
        $lines[] = '  <meta property="og:site_name" content="' . $e($brand['name'] ?? '') . '">';
        $lines[] = '  <meta property="og:title" content="' . $e($title) . '">';
        $lines[] = '  <meta property="og:description" content="' . $e($desc) . '">';
        $lines[] = '  <meta property="og:locale" content="' . $e($this->i18n->locale()) . '">';
        if ($ogAbs !== '') {
            $lines[] = '  <meta property="og:image" content="' . $e($ogAbs) . '">';
            $lines[] = '  <meta property="og:image:alt" content="' . $e($ogImg['alt'] ?? '') . '">';
        }
        if ($url !== '') {
            $lines[] = '  <meta property="og:url" content="' . $e($url . '/' . $file) . '">';
        }
        $lines[] = '  <meta name="twitter:card" content="summary_large_image">';

        $alt = $this->i18n->hreflang($page, $url);
        if ($alt !== '') { $lines[] = ''; $lines[] = $alt; }
        $lines[] = '';
        // Le bloc decrit l etablissement : il a sa place sur chaque page,
        // et non sur la seule page d accueil.
        $lines[] = '';
        $lines[] = '  <script type="application/ld+json">';
        $lines[] = '  ' . $this->buildJsonLd($page);
        $lines[] = '  </script>';

        return implode("\n", $lines) . "\n";
    }

    /**
     * Adresse postale structurée, déduite des lignes saisies en admin.
     *
     * Convention retenue : la première ligne est le nom de la maison, la
     * dernière le pays, l'avant-dernière la région. Ce qui reste au milieu
     * forme la rue et la localité. Un code postal en tête de ligne est
     * reconnu et isolé.
     */
    private function postalAddress(): array
    {
        $lines = array_values(array_filter(
            array_map('trim', $this->contact()['addressLines'] ?? [])
        ));

        // La première ligne répète le nom de l'établissement : on l'écarte.
        $brand = trim((string) ($this->c['brand']['name'] ?? ''));
        if ($lines && $brand !== '' && strcasecmp($lines[0], $brand) === 0) {
            array_shift($lines);
        }

        $address = ['@type' => 'PostalAddress'];

        if ($lines) {
            $country = array_pop($lines);
            $address['addressCountry'] = $this->countryCode($country);
        }
        if ($lines) {
            // « Nosy Be, région Diana » → localité + région
            $last = array_pop($lines);
            if (str_contains($last, ',')) {
                [$locality, $region] = array_map('trim', explode(',', $last, 2));
                $address['addressLocality'] = $locality;
                $address['addressRegion']   = preg_replace('/^r[ée]gion\s+/iu', '', $region);
            } else {
                $address['addressRegion'] = $last;
            }
        }
        if ($lines) {
            // « 207 Dzamandzar » → code postal + localité
            $line = array_pop($lines);
            if (preg_match('/^(\d{3,5})\s+(.+)$/', $line, $m)) {
                $address['postalCode'] = $m[1];
                $address['addressLocality'] ??= $m[2];
            } elseif (!isset($address['addressLocality'])) {
                $address['addressLocality'] = $line;
            } else {
                array_unshift($lines, $line);
            }
        }
        if ($lines) {
            $address['streetAddress'] = implode(', ', $lines);
        }

        return $address;
    }

    /** « 12h00 », « 12 h 00 », « 12:00 » → « 12:00 ». Vide si non reconnu. */
    private function isoTime(string $value): string
    {
        if (preg_match('/(\d{1,2})\s*[h:]\s*(\d{2})?/i', $value, $m)) {
            return sprintf('%02d:%02d', (int) $m[1], (int) ($m[2] ?? 0));
        }
        return '';
    }

    /**
     * « -13.3987, 48.2345 » → GeoCoordinates.
     * Renvoie null si la saisie n'est pas un couple de coordonnées plausible :
     * mieux vaut pas de donnée qu'une donnée fausse.
     */
    private function geoPoint(string $value): ?array
    {
        if (!preg_match('/(-?\d{1,3}[.,]\d+)\s*[,;]\s*(-?\d{1,3}[.,]\d+)/', $value, $m)) {
            return null;
        }
        $lat = (float) str_replace(',', '.', $m[1]);
        $lon = (float) str_replace(',', '.', $m[2]);

        if (abs($lat) > 90 || abs($lon) > 180) {
            return null;
        }

        return ['@type' => 'GeoCoordinates', 'latitude' => $lat, 'longitude' => $lon];
    }

    /**
     * Note agrégée déduite de la section réputation : la note vient du titre
     * (« 9,6 sur 10 »), le nombre d'avis de la mention de source
     * (« Source : Booking.com · 75 avis »). Si l'un des deux manque, on
     * n'écrit rien plutôt que d'inventer un chiffre.
     */
    private function aggregateRating(array $rep): ?array
    {
        if (empty($rep['scores'])) {
            return null;
        }
        if (!preg_match('/(\d+(?:[.,]\d+)?)/', (string) ($rep['title'] ?? ''), $v)) {
            return null;
        }
        if (!preg_match('/(\d+)\s*avis/iu', (string) ($rep['source'] ?? ''), $c)) {
            return null;
        }

        return [
            '@type'       => 'AggregateRating',
            'ratingValue' => str_replace(',', '.', $v[1]),
            'bestRating'  => '10',
            'ratingCount' => (int) $c[1],
        ];
    }

    /** Quelques pays fréquents en code ISO ; sinon le libellé tel quel. */
    private function countryCode(string $name): string
    {
        return match (mb_strtolower(trim($name))) {
            'madagascar'                     => 'MG',
            'france'                         => 'FR',
            'belgique'                       => 'BE',
            'suisse'                         => 'CH',
            'la réunion', 'réunion'          => 'RE',
            'maurice', 'île maurice'         => 'MU',
            default                          => $name,
        };
    }

    /** Données structurées LodgingBusiness — uniquement des faits connus. */
    private function buildJsonLd(string $page = 'index'): string
    {
        $k     = $this->contact();
        $brand = $this->c['brand']   ?? [];
        $seo   = $this->c['seo']     ?? [];
        $rep   = $this->c['reputation'] ?? [];
        $url   = $this->siteUrl();

        $ld = [
            '@context' => 'https://schema.org',
            '@type'    => 'Hotel',
            'name'     => $brand['name'] ?? '',
            'description' => $seo['description'] ?? '',
            // L'adresse suit celle saisie en admin. Si le gérant la corrige,
            // les données lues par Google suivent : rien n'est figé ici.
            'address'  => $this->postalAddress(),
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

        // Les horaires sont saisis en français (« 12h00 ») ; schema.org attend
        // un format ISO. On convertit, sans quoi Google ignore le champ.
        if ($in = $this->isoTime((string) ($k['checkinFrom'] ?? ''))) {
            $ld['checkinTime'] = $in;
        }
        if ($out = $this->isoTime((string) ($k['checkoutTo'] ?? $k['checkoutFrom'] ?? ''))) {
            $ld['checkoutTime'] = $out;
        }

        // Coordonnées géographiques, dès que le gérant les a relevées.
        if ($geo = $this->geoPoint((string) ($k['gps'] ?? ''))) {
            $ld['geo'] = $geo;
        }

        // Note agrégée : déduite des données de la section réputation, pour
        // qu'une correction en admin se répercute ici aussi.
        if ($rating = $this->aggregateRating($rep)) {
            $ld['aggregateRating'] = $rating;
        }

        // Une image absolue n'a de sens qu'une fois le domaine connu.
        $ogImg = $this->c['media'][$seo['ogImageId'] ?? ''] ?? null;
        if ($ogImg && $url !== '') {
            $ld['image'] = $url . '/' . $ogImg['src'];
        }

        // Lien vers le plan : Google l'exploite pour l'itinéraire.
        if ($maps = ($k['maps'] ?? '')) {
            $ld['hasMap'] = $maps;
        }

        // Fourchette de prix, déduite des tarifs réellement publiés.
        if ($fourchette = $this->fourchettePrix()) {
            $ld['priceRange'] = $fourchette;
        }

        /**
         * Sur la page des chambres, on décrit chaque chambre. C'est ce qui
         * permet à Google de comprendre l'offre, plutôt que de ne voir
         * qu'une page de texte.
         */
        if ($page === 'chambres') {
            $chambres = [];
            foreach ($this->activeRooms() as $r) {
                $chambre = [
                    '@type' => 'HotelRoom',
                    'name'  => $r['name'] ?? '',
                ];
                if (!empty($r['desc'])) {
                    $chambre['description'] = $r['desc'];
                }
                // « 30 m² » → valeur numérique exploitable.
                if (preg_match('/(\d+)/', (string) ($r['area'] ?? ''), $m)) {
                    $chambre['floorSize'] = [
                        '@type'    => 'QuantitativeValue',
                        'value'    => (int) $m[1],
                        'unitCode' => 'MTK',   // mètre carré
                    ];
                }
                if (!empty($r['amenities'])) {
                    $chambre['amenityFeature'] = array_map(
                        fn($a) => ['@type' => 'LocationFeatureSpecification',
                                   'name' => $a, 'value' => true],
                        array_slice($r['amenities'], 0, 8)
                    );
                }
                $chambres[] = $chambre;
            }
            if ($chambres) {
                $ld['containsPlace'] = $chambres;
            }
        }

        // JSON_HEX_TAG est indispensable : ce JSON est écrit DANS une balise
        // <script> de la page. Sans lui, un texte contenant « </script> »
        // saisi en admin refermerait la balise et permettrait d'injecter du
        // code dans le site public. Les chevrons deviennent < / >,
        // ce qui reste du JSON valide et parfaitement lisible par Google.
        return (string) json_encode(
            $ld,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
            | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );
    }

    // ══ sitemap.xml ═════════════════════════════════════════════════════════

    private function buildSitemap(): string
    {
        $url = $this->siteUrl();
        $day = date('Y-m-d');

        if ($url === '') {
            // Sans domaine défini, un sitemap avec des URL relatives serait
            // invalide : on produit un fichier explicite plutôt qu'un faux.
            return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
                 . "<!-- Domaine non renseigné dans l'admin (Réglages → Site).\n"
                 . "     Renseignez-le puis régénérez : le sitemap sera complété. -->\n"
                 . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"></urlset>\n";
        }

        // Quatre pages × quatre langues. Chaque version a sa propre adresse
        // et déclare les autres en alternative : c'est ce qui permet à Google
        // de servir la bonne langue sans considérer les pages comme dupliquées.
        $priorites = [
            'index'     => '1.0',
            'chambres'  => '0.9',
            'activites' => '0.7',
            'acces'     => '0.7',
        ];

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
             . "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\"\n"
             . "        xmlns:xhtml=\"http://www.w3.org/1999/xhtml\">\n";

        foreach (PageTemplates::PAGES as $page) {
            $fichier = $page === 'index' ? '' : $page . '.html';

            foreach (array_keys(I18n::LANGUES) as $lg) {
                $dossier = $lg === I18n::SOURCE ? '' : $lg . '/';
                $loc     = $url . '/' . $dossier . $fichier;

                $xml .= "  <url>\n"
                      . "    <loc>{$loc}</loc>\n"
                      . "    <lastmod>{$day}</lastmod>\n"
                      . "    <changefreq>monthly</changefreq>\n"
                      . "    <priority>{$priorites[$page]}</priority>\n";

                foreach (array_keys(I18n::LANGUES) as $autre) {
                    $d2 = $autre === I18n::SOURCE ? '' : $autre . '/';
                    $xml .= "    <xhtml:link rel=\"alternate\" hreflang=\"{$autre}\""
                          . " href=\"{$url}/{$d2}{$fichier}\"/>\n";
                }

                $xml .= "  </url>\n";
            }
        }

        return $xml . "</urlset>\n";
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
