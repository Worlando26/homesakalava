<?php
/**
 * lib/pages.php — Gabarits des pages du site public.
 *
 * Le site compte quatre pages (accueil, chambres, activités, accès) et, à
 * terme, quatre langues : seize fichiers HTML. Les tenir à jour à la main
 * serait intenable, et la moindre correction de navigation devrait être
 * reportée seize fois. Le HTML est donc produit ici, à partir d'un seul
 * jeu de gabarits.
 *
 * Ces gabarits ne contiennent que la structure : aucun texte n'y figure.
 * Le contenu reste injecté au chargement par js/render.js, depuis config.js.
 */

declare(strict_types=1);

final class PageTemplates
{
    /**
     * Les pages du site.
     * 'nav' : entrée de navigation correspondante (null = pas dans le menu).
     */
    public const PAGES = ['index', 'chambres', 'activites', 'acces'];

    /**
     * @param string $page   index | chambres | activites | acces
     * @param string $prefix chemin relatif vers la racine ('' ou '../')
     * @param string $lang   code langue de la page (fr, en, de, it)
     * @param string $head   blocs SEO déjà rendus
     * @param string $langNav sélecteur de langue rendu
     */
    public static function render(
        string $page,
        string $prefix,
        string $lang,
        string $head,
        string $langNav,
        array $ui = []
    ): string {
        $body = match ($page) {
            'chambres'  => self::roomsPage(),
            'activites' => self::activitiesPage(),
            'acces'     => self::accessPage(),
            default     => self::homePage(),
        };

        $html = self::shell($page, $prefix, $lang, $head, $langNav, $body);

        return $ui ? self::traduire($html, $ui) : $html;
    }

    /**
     * Traduit les quelques textes écrits directement dans les gabarits.
     *
     * Ils sont marqués data-t (contenu) ou data-t-aria (intitulé pour les
     * lecteurs d'écran). La traduction est appliquée à la génération, et non
     * dans le navigateur : le texte est ainsi correct avant même que le
     * JavaScript s'exécute, et lisible par les moteurs de recherche.
     */
    private static function traduire(string $html, array $ui): string
    {
        // Contenu : <tag data-t="cle">texte</tag>
        $html = preg_replace_callback(
            '/(<(\w+)[^>]*\bdata-t="([a-zA-Z]+)"[^>]*>)([^<]*)(<\/\2>)/',
            static function (array $m) use ($ui): string {
                $texte = $ui[$m[3]] ?? $m[4];
                return $m[1] . htmlspecialchars($texte, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . $m[5];
            },
            $html
        ) ?? $html;

        // Intitulé accessible : aria-label="…" data-t-aria="cle"
        $html = preg_replace_callback(
            '/aria-label="[^"]*"(\s*)data-t-aria="([a-zA-Z]+)"/',
            static function (array $m) use ($ui): string {
                $texte = $ui[$m[2]] ?? '';
                if ($texte === '') {
                    return $m[0];
                }
                return 'aria-label="' . htmlspecialchars($texte, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                     . '"' . $m[1] . 'data-t-aria="' . $m[2] . '"';
            },
            $html
        ) ?? $html;

        // Intitulé du bouton de menu, commun à toutes les pages.
        if (!empty($ui['openMenu'])) {
            $html = str_replace(
                'aria-label="Ouvrir le menu"',
                'aria-label="' . htmlspecialchars($ui['openMenu'], ENT_QUOTES) . '"',
                $html
            );
        }

        return $html;
    }

    // ══ Enveloppe commune ═══════════════════════════════════════════════

    private static function shell(
        string $page,
        string $p,
        string $lang,
        string $head,
        string $langNav,
        string $body
    ): string {
        // La page d'accueil porte le hero plein écran ; les autres ouvrent
        // sur un en-tête compact, donc la barre de navigation y est opaque
        // dès le chargement.
        $navClass = $page === 'index' ? 'nav' : 'nav nav--solid';

        return <<<HTML
        <!DOCTYPE html>
        <html lang="{$lang}">
        <head>
          <meta charset="UTF-8">
          <meta name="viewport" content="width=device-width, initial-scale=1.0">

        {$head}
          <link rel="icon" href="{$p}assets/favicon.svg" type="image/svg+xml">
          <link rel="apple-touch-icon" href="{$p}assets/apple-touch-icon.png">
          <link rel="manifest" href="{$p}site.webmanifest">

          <link rel="preconnect" href="https://fonts.googleapis.com">
          <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
          <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,400;12..96,600;12..96,700;12..96,800&family=Cormorant+Garamond:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

          <link rel="stylesheet" href="{$p}css/reset.css">
          <link rel="stylesheet" href="{$p}css/variables.css">
          <link rel="stylesheet" href="{$p}css/base.css">
          <link rel="stylesheet" href="{$p}css/components.css">
          <link rel="stylesheet" href="{$p}css/sections.css">
        </head>
        <body data-page="{$page}">

          <a class="skip-link" href="#contenu" data-t="skip">Aller au contenu</a>

          <!-- Barre de navigation en trois colonnes, disposition courante des
               sites hôteliers : liens à gauche, nom de la maison au centre,
               langue et réservation à droite. Le nom reste optiquement centré
               quelle que soit la longueur des deux côtés. -->
          <nav class="{$navClass}" data-nav aria-label="Navigation principale">
            <div class="nav__inner container">

              <div class="nav__side nav__side--left">
                <button class="nav__burger" data-burger type="button"
                        aria-label="Ouvrir le menu" aria-expanded="false" aria-controls="nav-drawer">
                  <span></span><span></span><span></span>
                </button>
                <ul class="nav__links" data-nav-links></ul>
              </div>

              <a class="nav__brand" href="{$p}index.html">
                <span class="nav__brand-name" data-nav-logo></span>
                <span class="nav__brand-sub" data-nav-sub></span>
              </a>

              <div class="nav__side nav__side--right">
                {$langNav}
                <a class="nav__cta" data-nav-cta href="#booking"></a>
              </div>

            </div>
            <div class="nav__drawer" data-drawer id="nav-drawer">
              <ul data-drawer-links></ul>
              <a class="nav__drawer-cta" data-drawer-cta href="#booking"></a>
            </div>
          </nav>

          <main id="contenu">
        {$body}
          </main>

          <footer class="footer" data-section="footer">
            <div class="container">
              <div class="footer__cta-block" data-footer-cta-block></div>
              <div class="footer__meta">
                <div class="footer__address" data-footer-address></div>
                <div class="footer__social" data-footer-social></div>
                <div class="footer__legal" data-footer-legal></div>
              </div>
              <div class="footer__wordmark-wrap">
                <span class="footer__wordmark" data-footer-wordmark aria-hidden="true"></span>
              </div>
              <p class="footer__copyright" data-footer-copyright></p>
            </div>
          </footer>

          <!-- Appel et WhatsApp, sur mobile uniquement. Rempli par js/render.js
               depuis config/site.php : sans numero renseigne, rien ne s affiche. -->
          <div class="joindre" data-joindre hidden></div>

          <script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
          <script defer src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
          <script defer src="https://cdn.jsdelivr.net/npm/lenis@1.1.13/dist/lenis.min.js"></script>
          <!-- Lucide n'est pas hébergé par cdnjs : jsDelivr le sert depuis npm. -->
          <script defer src="https://cdn.jsdelivr.net/npm/lucide@0.454.0/dist/umd/lucide.min.js"></script>

          <!-- config.js n'est PAS préfixé : chaque langue a le sien, dans son
               propre dossier. Le préfixer ferait charger le français partout. -->
          <script defer src="config.js"></script>
          <script defer src="{$p}js/render.js"></script>
          <script defer src="{$p}js/rooms.js"></script>
          <script defer src="{$p}js/carousel.js"></script>
          <script defer src="{$p}js/faq.js"></script>
          <script defer src="{$p}js/lightbox.js"></script>
          <script defer src="{$p}js/animations.js"></script>
          <script defer src="{$p}js/main.js"></script>
        </body>
        </html>

        HTML;
    }

    // ══ Accueil ═════════════════════════════════════════════════════════

    private static function homePage(): string
    {
        return <<<'HTML'

          <!-- ═══ HERO ═══ -->
          <section class="hero" data-section="hero" aria-label="Présentation">

            <div class="hero__img-wrap" data-hero-img></div>
            <div class="hero__overlay"></div>

            <!-- Le hero portait autrefois sa propre barre, avec le nom de la
                 maison et un bouton de réservation. Elle faisait doublon avec
                 la barre de navigation, qui devait être masquée pour éviter la
                 superposition : on ne pouvait donc pas naviguer depuis le haut
                 de la page. Une seule barre, transparente ici puis opaque au
                 défilement, comme sur les sites hôteliers. -->

            <div class="glass-pill hero__badge">
              <span data-hero-badge></span>
              <a class="glass-circle hero__badge-arrow" href="acces.html"
                 aria-label="Accès et contact" data-t-aria="accessTitle">
                <i data-lucide="arrow-up-right" aria-hidden="true"></i>
              </a>
            </div>

            <p class="hero__scroll-hint" data-hero-scroll-hint></p>

            <div class="hero__headline">
              <p class="hero__headline-sub"   data-hero-headline-sub></p>
              <h1 class="hero__headline-title" data-hero-headline-title></h1>
            </div>

            <div class="hero__bottombar">
              <a class="glass-panel hero__nav-prev" data-hero-prev href="chambres.html">
                <i data-lucide="arrow-left" class="hero__nav-icon" aria-hidden="true"></i>
                <span class="hero__nav-info">
                  <span class="hero__nav-label" data-t="room">Chambre</span>
                  <span class="hero__nav-room" data-hero-prev-name></span>
                </span>
              </a>
              <div class="glass-panel hero__features-panel" data-hero-features></div>
              <a class="glass-panel hero__nav-next" data-hero-next href="chambres.html">
                <span class="hero__nav-info">
                  <span class="hero__nav-label" data-t="room">Chambre</span>
                  <span class="hero__nav-room" data-hero-next-name></span>
                </span>
                <i data-lucide="arrow-right" class="hero__nav-icon" aria-hidden="true"></i>
              </a>
            </div>

          </section>

          <!-- ═══ NOTRE HISTOIRE ═══ -->
          <section class="story section" data-section="story" aria-labelledby="story-kicker">
            <div class="container story__inner">
              <div class="story__left">
                <h2 class="kicker" id="story-kicker" data-story-kicker></h2>
                <p class="story__stat" data-story-stat></p>
                <a class="btn btn--pill btn--outline story__cta" data-story-cta href="#amazing"></a>
              </div>
              <div class="story__right">
                <p class="story__text" data-story-text></p>
              </div>
            </div>
          </section>

          <!-- ═══ RÉPUTATION ═══ -->
          <section class="reputation section" data-section="reputation" aria-labelledby="rep-kicker">
            <div class="container reputation__inner">
              <div class="reputation__left">
                <h2 class="kicker" id="rep-kicker" data-rep-kicker></h2>
                <div class="reputation__score">
                  <span class="reputation__score-value" data-rep-title></span>
                  <span class="reputation__score-label" data-rep-label></span>
                </div>
                <p class="reputation__intro" data-rep-intro></p>
                <span class="reputation__badge" data-rep-badge></span>
              </div>
              <div class="reputation__right">
                <ul class="reputation__bars" data-rep-scores></ul>
                <p class="reputation__source" data-rep-source></p>
              </div>
            </div>
          </section>

          <!-- ═══ LA MAISON (BENTO) ═══ -->
          <section class="amazing section" data-section="amazing" aria-labelledby="amazing-kicker">
            <div class="container">
              <div class="section-header">
                <h2 class="kicker" id="amazing-kicker" data-amazing-kicker></h2>
                <a class="btn btn--pill btn--outline" data-amazing-cta href="chambres.html"></a>
              </div>
              <div class="amazing__bento" data-amazing-bento></div>
            </div>
          </section>

          <!-- ═══ APERÇU DES CHAMBRES ═══ -->
          <section class="ideal section" data-section="ideal" aria-labelledby="ideal-kicker">
            <div class="container">
              <div class="section-header">
                <div class="section-header__text">
                  <h2 class="kicker" id="ideal-kicker" data-ideal-kicker></h2>
                  <p class="section-header__intro" data-ideal-intro></p>
                </div>
                <div class="carousel__arrows">
                  <button class="carousel-arrow carousel-arrow--prev" data-prev type="button"
                          aria-label="Chambre précédente" data-t-aria="prevRoom">
                    <i data-lucide="arrow-left" aria-hidden="true"></i>
                  </button>
                  <button class="carousel-arrow carousel-arrow--next is-active" data-next type="button"
                          aria-label="Chambre suivante" data-t-aria="nextRoom">
                    <i data-lucide="arrow-right" aria-hidden="true"></i>
                  </button>
                </div>
              </div>
              <!-- Flèches posées sur le carrousel lui-même : celles de
                   l'en-tête passent inaperçues, surtout sur téléphone. -->
              <div class="carousel__stage">
                <button class="carousel-float carousel-float--prev" data-prev type="button"
                        aria-label="Chambre précédente" data-t-aria="prevRoom">
                  <i data-lucide="chevron-left" aria-hidden="true"></i>
                </button>

                <div class="carousel__viewport" data-carousel-viewport>
                  <ul class="carousel__track" data-carousel-track></ul>
                </div>

                <button class="carousel-float carousel-float--next is-active" data-next type="button"
                        aria-label="Chambre suivante" data-t-aria="nextRoom">
                  <i data-lucide="chevron-right" aria-hidden="true"></i>
                </button>
              </div>
              <div class="ideal__shared" data-ideal-shared></div>
              <p class="ideal__more">
                <a class="btn btn--accent btn--pill" href="chambres.html" data-t="allRooms">Voir les 5 chambres en détail</a>
              </p>
            </div>
          </section>

          <!-- ═══ LE RESTAURANT ═══ -->
          <section class="resto section" data-section="restaurant" aria-labelledby="resto-kicker">
            <div class="container resto__inner">
              <div class="resto__media" data-resto-media></div>
              <div class="resto__body">
                <p class="kicker" data-resto-kicker></p>
                <h2 class="resto__title" id="resto-kicker" data-resto-title></h2>
                <p class="resto__text" data-resto-text></p>
                <ul class="resto__list" data-resto-list></ul>
                <p class="resto__note" data-resto-note></p>
              </div>
            </div>
          </section>

          <!-- ═══ NOS SERVICES ═══ -->
          <section class="trusted section" data-section="trusted" aria-labelledby="trusted-title">
            <div class="container trusted__container">
              <h2 class="trusted__title" id="trusted-title" data-trusted-title></h2>
              <a class="btn btn--accent btn--pill trusted__btn" data-trusted-cta href="#booking"></a>
              <div class="trusted__amenities" data-trusted-amenities></div>
              <div class="trusted__stage">
                <div class="trusted__floats trusted__floats--left" data-float-left></div>
                <div class="trusted__card" data-trusted-card></div>
                <div class="trusted__floats trusted__floats--right" data-float-right></div>
              </div>
              <ul class="trusted__services" data-trusted-services></ul>
              <p class="trusted__note" data-trusted-note></p>
            </div>
          </section>

          <!-- ═══ FAQ ═══ -->
          <section class="faq section" data-section="faq" aria-labelledby="faq-title">
            <div class="container faq__inner">
              <div class="faq__left">
                <p class="kicker" data-faq-kicker></p>
                <h2 class="faq__title" id="faq-title" data-faq-title></h2>
                <p class="faq__intro" data-faq-intro></p>
                <div class="faq__list" data-faq-list></div>
              </div>
              <div class="faq__right">
                <div class="faq__image" data-faq-image></div>
              </div>
            </div>
          </section>

          <!-- ═══ DEMANDE DE RÉSERVATION ═══ -->
          <section class="booking section" data-section="booking" aria-labelledby="booking-title">
            <div class="container booking__inner">
              <div class="booking__left"  data-booking-left></div>
              <div class="booking__right" data-booking-right></div>
            </div>
          </section>

        HTML;
    }

    // ══ Nos chambres ════════════════════════════════════════════════════

    /**
     * Reprend la mise en page des maquettes fournies dans add_img/ :
     * grande photo à gauche avec sa bande de vignettes, panneau de détails
     * à droite (superficie, description, puis équipements regroupés).
     */
    private static function roomsPage(): string
    {
        return <<<'HTML'

          <header class="pagehead section" data-section="pagehead">
            <div class="container">
              <p class="kicker" data-page-kicker></p>
              <h1 class="pagehead__title" data-page-title></h1>
              <p class="pagehead__intro" data-page-intro></p>
              <nav class="pagehead__jump" data-rooms-jump aria-label="Aller à une chambre"></nav>
            </div>
          </header>

          <div class="rooms" data-rooms-list></div>

          <section class="booking section" data-section="booking" aria-labelledby="booking-title">
            <div class="container booking__inner">
              <div class="booking__left"  data-booking-left></div>
              <div class="booking__right" data-booking-right></div>
            </div>
          </section>

        HTML;
    }

    // ══ Activités ═══════════════════════════════════════════════════════

    private static function activitiesPage(): string
    {
        return <<<'HTML'

          <header class="pagehead section" data-section="pagehead">
            <div class="container">
              <p class="kicker" data-page-kicker></p>
              <h1 class="pagehead__title" data-page-title></h1>
              <p class="pagehead__intro" data-page-intro></p>
            </div>
          </header>

          <section class="activites section" data-section="activites">
            <div class="container">
              <ul class="activites__grid" data-act-grid></ul>
              <p class="activites__note" data-act-note></p>
              <div class="activites__distances">
                <h2 class="activites__dist-title" data-t="around">Aux alentours</h2>
                <dl class="activites__dist-list" data-act-distances></dl>
              </div>
            </div>
          </section>

        HTML;
    }

    // ══ Accès & contact ═════════════════════════════════════════════════

    private static function accessPage(): string
    {
        return <<<'HTML'

          <header class="pagehead section" data-section="pagehead">
            <div class="container">
              <p class="kicker" data-page-kicker></p>
              <h1 class="pagehead__title" data-page-title></h1>
              <p class="pagehead__intro" data-page-intro></p>
            </div>
          </header>

          <section class="acces section" data-section="acces">
            <div class="container acces__inner">
              <div class="acces__body">
                <div class="acces__map" data-acces-map></div>
                <a class="btn btn--pill btn--outline acces__fb" data-acces-fb
                   target="_blank" rel="noopener noreferrer">
                  <i data-lucide="facebook" aria-hidden="true"></i>
                  <span data-t="facebookPage">Notre page Facebook</span>
                </a>
              </div>
              <dl class="acces__rows" data-acces-rows></dl>
            </div>
          </section>

          <section class="booking section" data-section="booking" aria-labelledby="booking-title">
            <div class="container booking__inner">
              <div class="booking__left"  data-booking-left></div>
              <div class="booking__right" data-booking-right></div>
            </div>
          </section>

        HTML;
    }
}
