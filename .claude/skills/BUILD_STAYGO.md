# 🏝️ BUILD — Site Hôtelier type « StayGo »

> **À Claude Code :** Construis un site one-page hôtelier premium en reproduisant **exactement** la structure, le style et les **animations** décrites ici. Lis tout le fichier AVANT de coder. Respecte la stack, l'arborescence et la **config centralisée** (`config.js`) pour que le contenu/images/couleurs soient modifiables sans toucher au code.

---

## 0. Stack & règles non négociables

- **HTML / CSS / JS vanilla** (pas de framework) — un seul site statique déployable sur **GitHub Pages**.
- Animations : **GSAP 3** + **ScrollTrigger** + **SplitText** (ou split maison si SplitText payant) + **Lenis** (smooth scroll). CDN.
- Polices : **Google Fonts** uniquement (voir tokens).
- Icônes : **Lucide** (`lucide@latest` via CDN, `lucide.createIcons()`).
- **Mobile-first**, responsive complet (mobile / tablette / desktop).
- **Zéro contenu en dur dans le HTML** : tout texte, prix, image, lien vient de `config.js`. Le HTML est généré/rempli par `main.js`.
- Code commenté en français, propre, modulaire.
- Respecter `prefers-reduced-motion` : désactiver les grosses animations si l'utilisateur le demande.

---

## 1. Arborescence des fichiers

```
staygo/
├── index.html              # squelette + conteneurs vides (data-* hooks)
├── config.js               # ⭐ TOUS les paramètres éditables (LE fichier à modifier)
├── css/
│   ├── reset.css           # reset/normalize
│   ├── variables.css       # tokens (couleurs, fonts, espacements)
│   ├── base.css            # typo, conteneurs, utilitaires
│   ├── components.css      # boutons, cards, nav, pills, accordéon
│   └── sections.css        # styles par section (hero, story, bento...)
├── js/
│   ├── render.js           # injecte le contenu de config.js dans le DOM
│   ├── animations.js       # toutes les animations GSAP (1 fonction = 1 section)
│   ├── carousel.js         # logique carousel "Ideal Destination"
│   ├── faq.js              # logique accordéon FAQ
│   └── main.js             # init Lenis + render + animations + lucide
└── assets/
    └── images/             # voir convention de nommage §3
```

---

## 2. Design tokens (`css/variables.css`)

```css
:root {
  /* Couleurs */
  --c-bg:        #f4f3f1;   /* fond général beige/blanc cassé */
  --c-surface:   #ffffff;
  --c-ink:       #16181b;   /* texte principal quasi-noir */
  --c-muted:     #9a9a98;   /* texte gris (révélé en noir au scroll) */
  --c-accent:    #f59425;   /* orange boutons/CTA */
  --c-accent-2:  #e07d10;
  --c-dark:      #0c1015;   /* sections sombres (hero overlay, footer) */
  --c-line:      #e6e4e0;   /* bordures */

  /* Typo */
  --font-display: 'Bricolage Grotesque', 'Clash Display', sans-serif; /* titres */
  --font-body:    'Inter', 'Satoshi', system-ui, sans-serif;          /* corps */

  /* Rayons & ombres */
  --r-card: 18px;
  --r-pill: 999px;
  --r-frame: 22px;          /* le grand cadre arrondi des blocs (hero, etc.) */
  --shadow-soft: 0 20px 60px rgba(0,0,0,.10);

  /* Layout */
  --maxw: 1200px;
  --gap: clamp(16px, 3vw, 40px);
}
```

> Le **gros cadre arrondi noir** autour du hero (et de certains blocs) dans la vidéo = un conteneur `border-radius: var(--r-frame)` avec une fine bordure sombre / léger ombrage. Reproduis-le.

---

## 3. Convention de nommage des images (`assets/images/`)

> Objectif : le propriétaire de l'hôtel (non-technique) remplace une image **sans rien casser**, juste en gardant le même nom de fichier.

| Nom de fichier               | Usage                                  | Ratio conseillé |
|------------------------------|----------------------------------------|-----------------|
| `hero-main.jpg`              | Grande image du hero (maison/lac)      | 16:9 (1920×1080)|
| `dest-amazing-01.jpg`       | Bento — grande carte gauche            | 4:5             |
| `dest-amazing-02.jpg`       | Bento — carte haut droite              | 16:9            |
| `dest-amazing-03.jpg`       | Bento — carte bas droite               | 16:9            |
| `ideal-01.jpg` … `ideal-06.jpg` | Cartes du carousel (1 par logement) | 4:3             |
| `trusted-float-01.jpg` … `-04.jpg` | Cartes flottantes section "Trusted" | 3:4         |
| `brand-01.svg` … `brand-05.svg`   | Logos partenaires (Bvlgari, Hilton…)| transparent     |
| `faq-side.jpg`              | Image piscine à droite de la FAQ       | 4:3             |
| `footer-bg.jpg`             | Fond du bloc CTA final                 | 16:9            |
| `avatar-01.jpg`             | Photo témoignage                       | 1:1             |

**Règles :** minuscules, tirets, jamais d'espaces ni d'accents. Toujours `.jpg` pour les photos, `.svg` pour les logos. Chemins gérés via `config.js` (`IMG_BASE = "assets/images/"`).

---

## 4. Fichier de configuration (`config.js`) — placement des paramètres

> ⭐ **C'est le seul fichier que le client/toi modifiez au quotidien.** Tout le reste lit ici.

```js
const CONFIG = {
  brand: {
    name: "StayGo",
    logoText: "StayGo",        // wordmark texte (ou null si logo image)
  },

  // Couleurs override (sinon valeurs CSS par défaut)
  theme: {
    accent: "#f59425",
    dark:   "#0c1015",
    bg:     "#f4f3f1",
  },

  nav: {
    links: [
      { label: "Explore Hotels", href: "#destinations" },
      { label: "Deals & Offers", href: "#trusted" },
      { label: "Destinations",   href: "#ideal" },
    ],
    cta: { label: "Sign In", href: "#" },
  },

  hero: {
    watermark: "StayGo",        // gros texte fantôme derrière l'image
    title: "Find Your Perfect Stay\nat the Best Price",
    image: "hero-main.jpg",
    searchPlaceholder: "Search hotel",
    searchCta: "Search",
  },

  story: {
    kicker: "Our Story",
    // texte révélé mot par mot au scroll
    text: "From city escapes to beachside retreats, we connect you with hotels that fit your lifestyle wherever the journey takes you.",
    stat: "Trusted by over 10 million travelers worldwide…", // petit paragraphe gauche
    cta: { label: "Learn More", href: "#" },
  },

  amazing: {
    kicker: "Our most Amazing Destination",
    cta: { label: "Explore Now", href: "#" },
    items: [
      { name: "Vista Grand Suites", meta: "Phuket, Thailand · ★ 4.9", image: "dest-amazing-01.jpg", big: true },
      { name: "Mountain Peak Lodge", meta: "Swiss Alps · ★ 4.8",      image: "dest-amazing-02.jpg" },
      { name: "Ocean View Resort",  meta: "Maldives · ★ 5.0",         image: "dest-amazing-03.jpg" },
    ],
  },

  ideal: {
    kicker: "Discover Your Ideal Destination",
    items: [
      { name: "City Center Apartments", loc: "New York, USA",      price: "$150,000", image: "ideal-01.jpg" },
      { name: "Tropical Paradise Resort", loc: "Bali, Indonesia",  price: "$110,000", image: "ideal-02.jpg" },
      { name: "Pinecone Lodge", loc: "Aspen, Colorado",            price: "$85,000",  image: "ideal-03.jpg" },
      { name: "Lakeside Villa", loc: "Como, Italy",                price: "$120,000", image: "ideal-04.jpg" },
      { name: "Desert Oasis", loc: "Dubai, UAE",                   price: "$95,000",  image: "ideal-05.jpg" },
      { name: "Forest Cabin", loc: "Oregon, USA",                  price: "$70,000",  image: "ideal-06.jpg" },
    ],
  },

  trusted: {
    title: "Trusted Stays, Seamless\nBooking Explore Now!",
    cta: { label: "Explore Hotels", href: "#" },
    brands: ["brand-01.svg","brand-02.svg","brand-03.svg","brand-04.svg","brand-05.svg"],
    floatCard: { title: "Join our community of travelers and experience unforgettable stays.", email: "support@staygo.com" },
    floatImages: ["trusted-float-01.jpg","trusted-float-02.jpg","trusted-float-03.jpg","trusted-float-04.jpg"],
  },

  testimonial: {
    kicker: "Testimonial",
    intro: "Here's what people have to say about working together. Real moments, real experiences, real feedback.",
    title: "Exactly What We Needed — Smooth & Stress-Free!",
    body: "The booking process was incredibly smooth, and the hotel was exactly as shown. Clean, comfortable, and located right where we needed.",
    author: { name: "Sarah Ahmed", role: "Traveler", avatar: "avatar-01.jpg" },
  },

  faq: {
    kicker: "FAQs",
    title: "Got Questions?\nWe're Here to Help.",
    intro: "Find quick answers to common questions about booking, cancellations, payments, and more.",
    image: "faq-side.jpg",
    items: [
      { q: "How do I find the best deals on StayGo?", a: "We compare prices from hundreds of trusted hotels…" },
      { q: "Is it safe to book through StayGo?", a: "Yes, all payments are encrypted and secure…" },
      { q: "Can I cancel or change my reservation?", a: "Most bookings can be modified or cancelled free…" },
      { q: "Do I need an account to book?", a: "You can book as a guest, but an account saves time…" },
      { q: "Are taxes and fees included in the price shown?", a: "Final price with all fees is shown at checkout…" },
      { q: "How do I contact customer support?", a: "24/7 support via chat, email or phone…" },
    ],
  },

  footer: {
    cta: { title: "Discover Places You'll\nNever Want to Leave", button: "Plan Your Stay", href: "#" },
    wordmark: "StayGo",        // gros logo bas de page
    bg: "footer-bg.jpg",
    address: ["StayGo HQ", "23 Greenview Avenue", "New York, NY 10001, USA", "hello@staygo.com", "+1 (212) 555-0198"],
    social: [
      { label: "Facebook", href: "#" },
      { label: "Twitter",  href: "#" },
      { label: "LinkedIn", href: "#" },
    ],
    legal: ["Privacy Policy", "Terms of Use", "Legal Disclaimer", "Cookie Policy"],
    copyright: "©2026 StayGo. All Rights Reserved.",
  },
};
```

---

## 5. Sections — structure & layout (de haut en bas)

### 5.1 Navbar (sticky, transparente sur hero)
Logo `StayGo` à gauche · liens centrés (`nav.links`) · bouton orange pill `Sign In` à droite. Devient opaque (fond clair + ombre) après scroll de 80px.

### 5.2 HERO
- Grand **cadre arrondi** (`--r-frame`) contenant `hero-main.jpg` en plein cadre.
- **Watermark** `StayGo` géant en blanc semi-transparent (~`opacity:.12`) derrière/au-dessus de l'image, en haut.
- Overlay dégradé sombre en bas pour lisibilité.
- En bas-gauche : titre `hero.title` (display, blanc, ~clamp(40px,6vw,72px)).
- En bas-droite : **barre de recherche pill** sombre (`Search hotel` placeholder) + bouton orange `Search`.

### 5.3 OUR STORY
- Layout 2 colonnes : gauche petit paragraphe `story.stat` + bouton `Learn More` (pill avec flèche orange) ; droite **grand texte** `story.text` qui se **révèle mot par mot** au scroll (gris → noir).

### 5.4 OUR MOST AMAZING DESTINATION (Bento)
- Kicker à gauche + bouton `Explore Now` pill à droite.
- **Grille bento** : 1 grande carte à gauche (`big:true`) + 2 cartes empilées à droite. Image plein cadre, overlay dégradé bas, nom + meta + ★ en bas. Arrondi `--r-card`.

### 5.5 DISCOVER YOUR IDEAL DESTINATION (Carousel)
- Kicker à gauche + 2 boutons flèches ronds (◀ ▶) à droite (la flèche active = orange).
- **Carousel horizontal** draggable (`ideal.items`) : carte = image, nom, localisation (📍), prix. ~3,5 cartes visibles sur desktop, 1,2 sur mobile.

### 5.6 TRUSTED STAYS (fond clair, cartes flottantes)
- Titre centré `trusted.title` + bouton orange centré.
- Rangée de **logos partenaires** en gris (`trusted.brands`).
- Au centre : **carte sombre** `floatCard` (titre + champ email + bouton flèche).
- De part et d'autre : **4 images flottantes** (`floatImages`) qui **glissent depuis les côtés** en parallaxe au scroll.

### 5.7 TESTIMONIAL
- Layout 2 colonnes : gauche `kicker` + `intro` + grosses **guillemets** ; droite `title` (display) + `body` + bloc auteur (avatar + nom + ★).

### 5.8 FAQ
- 2 colonnes : gauche titre `faq.title` + **accordéon** (`faq.items`, un seul ouvert à la fois, chevron qui pivote) ; droite `faq-side.jpg` arrondie.

### 5.9 FOOTER CTA
- Bloc sombre arrondi avec `footer-bg.jpg` en fond assombri : titre `cta.title` centré + bouton orange `Plan Your Stay`.
- Sous le bloc : colonnes adresse + social + legal.
- **Wordmark géant** `StayGo` en bas, pleine largeur (`clamp(80px, 20vw, 240px)`), blanc.

---

## 6. ⭐ ANIMATIONS (le cœur — `js/animations.js`)

> Smooth scroll global via **Lenis** branché sur `ScrollTrigger.update`. Toutes les anims utilisent GSAP + ScrollTrigger. Une fonction par section.

### A. Intro Hero (au chargement, timeline GSAP)
1. `hero-main.jpg` démarre **flou + zoomé** : `filter: blur(20px); scale: 1.15` → anime vers `blur(0); scale: 1` sur ~1.4s `power3.out`. (Reproduit le « flou → net » de la vidéo.)
2. Watermark `StayGo` : `opacity 0 → .12`, léger `y: 30 → 0`.
3. Navbar : slide depuis le haut (`y: -100 → 0`, fade).
4. Titre hero : `clipPath`/mask reveal ligne par ligne (`y: 40 → 0`, `opacity 0 → 1`, stagger).
5. Barre de recherche : fade + `y: 20 → 0`, délai après le titre.

### B. Our Story — révélation de texte au scroll ⭐
- Split `story.text` en **mots** (SplitText ou wrap `<span>` maison).
- Chaque mot : couleur `--c-muted` → `--c-ink`, contrôlé par `scrub` lié au scroll de la section.
- `ScrollTrigger: { trigger, start:"top 70%", end:"bottom 60%", scrub:true }`, stagger sur les mots. (= effet « le paragraphe se noircit progressivement ».)

### C. Reveals génériques de section
- Kickers/titres : `y: 30 → 0`, `opacity 0 → 1`, `start:"top 80%"`.
- Cartes bento & carousel : **stagger** d'apparition (`y: 40 → 0`, `opacity`, `stagger:.08`).

### D. Trusted Stays — cartes flottantes parallaxe ⭐
- Images gauche : `x: -120 → 0`, `rotate: -6 → 0` ; images droite : `x: 120 → 0`, `rotate: 6 → 0`.
- `scrub:true` sur la traversée de section → elles « rentrent » depuis les bords pendant le scroll. Léger `y` parallaxe différentiel pour la profondeur.
- Logos partenaires : fade + `y` stagger.

### E. Carousel (`js/carousel.js`)
- Drag souris/tactile + boutons flèches. `transform: translateX` animé GSAP (`power2.out`), clamp aux bornes, snap optionnel.

### F. FAQ (`js/faq.js`)
- Accordéon : hauteur animée (`gsap.to(maxHeight)` ou grid-rows), chevron `rotate 0 → 180`, un seul ouvert.

### G. Footer wordmark
- `StayGo` géant : reveal par mask `y: 100% → 0` quand il entre dans le viewport ; léger parallaxe au scroll.

> **Tous** les `start/end` doivent être testés responsive. Si `prefers-reduced-motion: reduce` → remplacer toutes les anims par un simple fade court, et désactiver Lenis.

---

## 7. Responsive

- **Desktop** (>1024) : grilles complètes, bento 2 colonnes, carousel 3,5 cartes.
- **Tablette** (640–1024) : story passe en 1 colonne, bento empilé, carousel 2 cartes.
- **Mobile** (<640) : tout en colonne unique, navbar → menu burger, hero titre + recherche empilés, watermark réduit, wordmark footer plus petit, cartes flottantes Trusted réduites/désactivées si trop chargé.

---

## 8. Ordre de build conseillé

1. `index.html` (squelette + conteneurs `data-section`) + CSS variables/reset/base.
2. `config.js` complet.
3. `render.js` : injection du contenu → site statique correct **sans** animation.
4. `components.css` + `sections.css` : design fidèle aux captures (cadres arrondis, pills orange, bento).
5. `carousel.js` + `faq.js` (interactions).
6. Lenis + `animations.js` (A→G).
7. Responsive + `prefers-reduced-motion`.
8. Vérifier déploiement **GitHub Pages** (chemins relatifs, pas de `/`).

---

## 9. Checklist de fidélité (à valider à la fin)

- [ ] Hero démarre flou + zoomé puis devient net (intro timeline).
- [ ] Watermark `StayGo` visible et discret.
- [ ] Texte « Our Story » se noircit mot par mot au scroll.
- [ ] Bento 1 grande + 2 petites cartes.
- [ ] Carousel draggable + flèches fonctionnelles.
- [ ] Cartes flottantes Trusted entrent depuis les côtés en parallaxe.
- [ ] Accordéon FAQ (un seul ouvert, chevron pivote).
- [ ] Gros wordmark `StayGo` en footer avec reveal.
- [ ] 100% responsive + reduced-motion respecté.
- [ ] Tout le contenu vient de `config.js` (rien en dur).

---

**Note couleurs :** la vidéo montre une palette **beige/blanc + orange accent + sombre** (≠ ton beige/blanc/or habituel). Si tu veux l'adapter à ta charte hôtel (beige/blanc/or), change juste `theme` dans `config.js` et `--c-accent` → ton or.
