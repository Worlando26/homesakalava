// ═══════════════════════════════════════════════════════════
//  render.js — Injecte le contenu de CONFIG dans le DOM.
//
//  Tout passe par textContent : aucun contenu n'est interprété comme du
//  HTML, ce qui rend l'injection sûre quel que soit ce que le gérant saisit
//  dans le back-office.
//
//  Images : <picture> WebP + repli JPEG + srcset quand le média existe,
//  dégradé CSS sinon. Une section sans photo reste donc présentable.
// ═══════════════════════════════════════════════════════════

/**
 * Texte d interface traduit. Le second argument est le francais, utilise
 * en repli : une traduction manquante affiche du francais, jamais du vide.
 */
function T(key, fallback) {
  const dict = (typeof CONFIG !== 'undefined' && CONFIG.t) || {};
  return dict[key] || fallback;
}

/* Crée un élément avec classes optionnelles */
function el(tag, classes, text) {
  const e = document.createElement(tag);
  if (classes) classes.split(' ').forEach(c => c && e.classList.add(c));
  if (text != null) e.textContent = text;
  return e;
}

/* Icône Lucide (remplacée par un <svg> au moment de lucide.createIcons) */
function icon(name) {
  const i = document.createElement('i');
  i.setAttribute('data-lucide', name || 'check');
  i.setAttribute('aria-hidden', 'true');
  return i;
}

/**
 * Écrit un texte dans un hook s'il existe sur la page courante.
 * Les pages intérieures reprennent certains titres dans leur en-tête : le
 * hook de section correspondant n'y figure alors plus.
 */
function setText(selector, value) {
  const node = document.querySelector(selector);
  if (node) node.textContent = value == null ? '' : value;
}

/* Applique un dégradé CSS en fond d'un élément */
function applyGradient(elm, gradient) {
  if (gradient) elm.style.background = gradient;
}

/**
 * Construit un <picture> responsive à partir d'un objet image de CONFIG.
 * Renvoie null si aucune image n'est définie — l'appelant retombe alors
 * sur le dégradé.
 */
function makePicture(image, className, altOverride) {
  if (!image || !image.src) return null;

  const picture = el('picture', className);

  if (image.webp) {
    const source = document.createElement('source');
    source.type = 'image/webp';
    source.srcset = (image.srcset && image.srcset.webp) || image.webp;
    if (image.sizes) source.sizes = image.sizes;
    picture.appendChild(source);
  }

  const img = el('img', 'media__img');
  img.src = image.src;
  if (image.srcset && image.srcset.jpg) img.srcset = image.srcset.jpg;
  if (image.sizes)  img.sizes  = image.sizes;
  if (image.width)  img.width  = image.width;
  if (image.height) img.height = image.height;
  img.alt = altOverride != null ? altOverride : (image.alt || '');
  // Le hero est au-dessus de la ligne de flottaison : chargement immédiat.
  img.loading  = image.eager ? 'eager' : 'lazy';
  img.decoding = image.eager ? 'sync'  : 'async';
  if (image.eager) img.setAttribute('fetchpriority', 'high');

  picture.appendChild(img);
  return picture;
}

/**
 * Fond d'une carte : photo si disponible, dégradé sinon.
 * Le dégradé est appliqué dans tous les cas — il sert de couleur d'attente
 * pendant le chargement de l'image.
 */
function applyCardImage(container, item, imgClass) {
  applyGradient(container, item.gradient || '#1a1a1a');
  const picture = makePicture(item.image, imgClass, item.image && item.image.alt
    ? item.image.alt
    : (item.name || ''));
  if (picture) container.appendChild(picture);
}

/**
 * Fond d'un bloc décoratif (float-card, image FAQ, bloc CTA du footer).
 * Ces éléments utilisent background-image en CSS : on garde ce mécanisme,
 * mais en pointant sur le WebP quand le navigateur le supporte.
 */
function applyBackdrop(elm, image, gradient) {
  applyGradient(elm, gradient);
  if (!image || !image.src) return;
  const url = supportsWebp() && image.webp ? image.webp : image.src;
  elm.style.backgroundImage    = `url("${url}")`;
  elm.style.backgroundSize     = 'cover';
  elm.style.backgroundPosition = 'center';
  if (image.alt) elm.setAttribute('role', 'img');
  if (image.alt) elm.setAttribute('aria-label', image.alt);
}

/* Détection WebP, calculée une seule fois. */
let _webp = null;
function supportsWebp() {
  if (_webp === null) {
    const c = document.createElement('canvas');
    _webp = c.toDataURL && c.toDataURL('image/webp').indexOf('data:image/webp') === 0;
  }
  return _webp;
}

// ─── Nav ─────────────────────────────────────────────────
function renderNav() {
  const { brand, nav } = CONFIG;

  // Le nom complet, et non le nom court : c est lui qui ancre la page.
  setText('[data-nav-logo]', brand.name || brand.logoText);
  setText('[data-nav-sub]', CONFIG.hero ? CONFIG.hero.logoSub : '');

  const navLinks  = document.querySelector('[data-nav-links]');
  const drawLinks = document.querySelector('[data-drawer-links]');

  // Page courante, pour signaler l onglet actif dans le menu.
  const page = (document.body && document.body.dataset.page) || 'index';

  nav.links.forEach(link => {
    const cible = link.href.split('#')[0].replace('.html', '') || 'index';
    const actif = cible === page;

    [navLinks, drawLinks].forEach(ul => {
      const li = el('li');
      const a  = el('a', actif ? 'is-current' : '', link.label);
      a.href   = link.href;
      if (actif) a.setAttribute('aria-current', 'page');
      li.appendChild(a);
      ul.appendChild(li);
    });
  });

  // Le meme appel a l action sert la barre et le tiroir mobile.
  document.querySelectorAll('[data-nav-cta], [data-drawer-cta]').forEach(a => {
    a.textContent = nav.cta.label;
    a.href        = nav.cta.href;
  });
}

// ─── Hero ─────────────────────────────────────────────────
function renderHero() {
  if (!document.querySelector('[data-hero-img]')) return;
  const { hero, brand } = CONFIG;

  const wrap = document.querySelector('[data-hero-img]');
  applyGradient(wrap, hero.gradient);
  const picture = makePicture(hero.image, 'hero__pic');
  if (picture) wrap.appendChild(picture);

  // Le nom de la maison vit desormais dans la barre de navigation : les
  // hooks correspondants n existent plus ici, d ou l ecriture tolerante.
  setText('[data-hero-badge]', hero.badge);
  setText('[data-hero-scroll-hint]', hero.scrollHint);
  setText('[data-hero-headline-title]', hero.headlineTitle);
  setText('[data-hero-headline-sub]', hero.headlineSub);
  setText('[data-hero-prev-name]', hero.prevRoom);
  setText('[data-hero-next-name]', hero.nextRoom);

  // Repli mobile : le nom de la maison doit rester lisible même quand la
  // barre du haut est masquée (voir css/sections.css, media query 767px).
  const fallback = document.querySelector('[data-hero-headline-sub]');
  if (fallback && brand && brand.name) fallback.setAttribute('data-brand', brand.name);

  const featuresEl = document.querySelector('[data-hero-features]');
  if (!featuresEl) return;
  (hero.features || []).forEach((feat, i) => {
    if (i > 0) featuresEl.appendChild(el('div', 'hero__feature-sep'));
    const item = el('div', 'hero__feature');
    item.appendChild(icon(feat.icon));
    item.appendChild(el('span', 'hero__feature-label', feat.label));
    featuresEl.appendChild(item);
  });
}

// ─── Story ────────────────────────────────────────────────
function renderStory() {
  if (!document.querySelector('[data-story-kicker]')) return;
  const { story } = CONFIG;
  document.querySelector('[data-story-kicker]').textContent = story.kicker;
  document.querySelector('[data-story-stat]').textContent   = story.stat;
  document.querySelector('[data-story-text]').textContent   = story.text;

  const ctaEl = document.querySelector('[data-story-cta]');
  ctaEl.textContent = story.cta.label;
  ctaEl.href        = story.cta.href;
}

// ─── Réputation ───────────────────────────────────────────
function renderReputation() {
  const r = CONFIG.reputation;
  if (!r || !document.querySelector('[data-rep-scores]')) return;

  document.querySelector('[data-rep-kicker]').textContent = r.kicker;
  document.querySelector('[data-rep-title]').textContent  = r.title;
  document.querySelector('[data-rep-label]').textContent  = r.label;
  document.querySelector('[data-rep-intro]').textContent  = r.intro;
  document.querySelector('[data-rep-badge]').textContent  = r.badge;
  document.querySelector('[data-rep-source]').textContent = r.source;

  const list = document.querySelector('[data-rep-scores]');
  (r.scores || []).forEach(s => {
    const li    = el('li', 'reputation__bar');
    const label = el('span', 'reputation__bar-label', s.label);
    const track = el('span', 'reputation__bar-track');
    const fill  = el('span', 'reputation__bar-fill');
    const value = el('span', 'reputation__bar-value', String(s.value).replace('.', ','));

    // La barre est décorative ; la note chiffrée juste à côté porte l'info.
    fill.style.width = Math.max(0, Math.min(100, (s.value / 10) * 100)) + '%';
    track.setAttribute('aria-hidden', 'true');
    track.appendChild(fill);

    li.appendChild(label);
    li.appendChild(track);
    li.appendChild(value);
    list.appendChild(li);
  });
}

// ─── La maison (bento) ────────────────────────────────────
function renderAmazing() {
  if (!document.querySelector('[data-amazing-bento]')) return;
  const { amazing } = CONFIG;
  document.querySelector('[data-amazing-kicker]').textContent = amazing.kicker;

  const ctaEl = document.querySelector('[data-amazing-cta]');
  ctaEl.textContent = amazing.cta.label;
  ctaEl.href        = amazing.cta.href;

  const bento = document.querySelector('[data-amazing-bento]');
  amazing.items.forEach(item => {
    const card = el('div', 'card' + (item.big ? ' card--big' : ''));
    applyCardImage(card, item, 'card__bg');

    const body = el('div', 'card__body');
    body.appendChild(el('div', 'card__name', item.name));
    body.appendChild(el('div', 'card__meta', item.meta));

    card.appendChild(el('div', 'card__overlay'));
    card.appendChild(body);
    bento.appendChild(card);
  });
}

// ─── Les chambres (carousel) ──────────────────────────────
function renderIdeal() {
  if (!document.querySelector('[data-carousel-track]')) return;
  const { ideal } = CONFIG;
  document.querySelector('[data-ideal-kicker]').textContent = ideal.kicker;
  document.querySelector('[data-ideal-intro]').textContent  = ideal.intro || '';

  const track = document.querySelector('[data-carousel-track]');
  ideal.items.forEach((item, roomIndex) => {
    const card = el('li', 'ideal-card');
    applyCardImage(card, item, 'ideal-card__bg');

    card.appendChild(el('div', 'ideal-card__overlay'));
    if (item.tag) card.appendChild(el('span', 'ideal-card__tag', item.tag));

    const body = el('div', 'ideal-card__body');
    body.appendChild(el('h3', 'ideal-card__name', item.name));

    const loc = el('div', 'ideal-card__loc');
    const pin = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    pin.setAttribute('width', '10'); pin.setAttribute('height', '12');
    pin.setAttribute('viewBox', '0 0 10 12'); pin.setAttribute('fill', 'none');
    pin.setAttribute('aria-hidden', 'true');
    pin.innerHTML = '<path d="M5 0C2.24 0 0 2.24 0 5c0 3.75 5 7 5 7s5-3.25 5-7c0-2.76-2.24-5-5-5zm0 6.5c-.83 0-1.5-.67-1.5-1.5S4.17 3.5 5 3.5 6.5 4.17 6.5 5 5.83 6.5 5 6.5z" fill="currentColor"/>';
    loc.appendChild(pin);
    loc.appendChild(document.createTextNode(item.loc));
    body.appendChild(loc);

    if (item.desc) body.appendChild(el('p', 'ideal-card__desc', item.desc));

    if (item.amenities && item.amenities.length) {
      const ul = el('ul', 'ideal-card__amenities');
      item.amenities.forEach(a => ul.appendChild(el('li', '', a)));
      body.appendChild(ul);
    }

    // Emplacement du tarif : toujours présent, même quand le prix n'est pas
    // encore connu (il affiche alors la mention de repli).
    if (item.price) body.appendChild(el('div', 'ideal-card__price', item.price));

    // Lien vers la fiche complete de la chambre. Un lien explicite plutôt
    // qu'une carte cliquable : le carrousel se manipule au glissé, et un
    // glissé ne doit jamais déclencher une navigation par accident.
    const t = CONFIG.t || {};
    const open = el('a', 'ideal-card__open');
    open.href = 'chambres.html#' + item.id;
    open.setAttribute(
      'aria-label',
      (t.seeRoom || 'Voir la chambre') + ' ' + (item.name || '')
    );
    open.appendChild(icon('arrow-right'));
    open.appendChild(el('span', '', t.seeRoom || 'Voir la chambre'));
    body.appendChild(open);

    card.appendChild(body);
    track.appendChild(card);
  });

  // Équipements communs à toutes les chambres
  const sharedEl = document.querySelector('[data-ideal-shared]');
  if (sharedEl && ideal.shared && ideal.shared.length) {
    sharedEl.appendChild(el('h3', 'ideal__shared-title', T('inEveryRoom', 'Dans toutes les chambres')));
    const ul = el('ul', 'ideal__shared-list');
    ideal.shared.forEach(s => {
      const li = el('li');
      li.appendChild(icon('check'));
      li.appendChild(el('span', '', s));
      ul.appendChild(li);
    });
    sharedEl.appendChild(ul);
  }
}

// ─── Le restaurant ────────────────────────────────────────
function renderRestaurant() {
  const r = CONFIG.restaurant;
  if (!r || !document.querySelector('[data-resto-list]')) return;

  applyBackdrop(document.querySelector('[data-resto-media]'), r.image, r.gradient);

  document.querySelector('[data-resto-kicker]').textContent = r.kicker;
  document.querySelector('[data-resto-title]').textContent  = r.title;
  document.querySelector('[data-resto-text]').textContent   = r.text;
  document.querySelector('[data-resto-note]').textContent   = r.note || '';

  const list = document.querySelector('[data-resto-list]');
  (r.items || []).forEach(i => {
    const li = el('li');
    li.appendChild(icon(i.icon));
    li.appendChild(el('span', '', i.label));
    list.appendChild(li);
  });
}

// ─── Services ─────────────────────────────────────────────
function renderTrusted() {
  if (!document.querySelector('[data-trusted-title]')) return;
  const { trusted } = CONFIG;

  document.querySelector('[data-trusted-title]').textContent = trusted.title;
  const ctaEl = document.querySelector('[data-trusted-cta]');
  ctaEl.textContent = trusted.cta.label;
  ctaEl.href        = trusted.cta.href;

  const amenitiesEl = document.querySelector('[data-trusted-amenities]');
  trusted.amenities.forEach(label => {
    amenitiesEl.appendChild(el('span', 'amenity-pill', label));
  });

  const floatLeft  = document.querySelector('[data-float-left]');
  const floatRight = document.querySelector('[data-float-right]');

  trusted.floatImages.forEach((imgData, i) => {
    const card = el('div', 'float-card');
    applyBackdrop(card, imgData.image, imgData.gradient);
    card.appendChild(el('span', 'float-card__label', imgData.label));
    (i < 2 ? floatLeft : floatRight).appendChild(card);
  });

  // Carte centrale sombre : message des hôtes + adresse de contact.
  const cardEl = document.querySelector('[data-trusted-card]');
  cardEl.appendChild(el('p', 'trusted__card-title', trusted.floatCard.title));
  const contact = el('div', 'trusted__card-form');
  contact.appendChild(el('span', 'trusted__card-email', trusted.floatCard.email));
  const link = el('a', 'trusted__card-btn');
  link.href = '#booking';
  link.setAttribute('aria-label', 'Aller au formulaire de contact');
  link.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
  contact.appendChild(link);
  cardEl.appendChild(contact);

  // Liste complète des services, avec mention « supplément » le cas échéant.
  const list = document.querySelector('[data-trusted-services]');
  (trusted.items || []).forEach(s => {
    const li = el('li', 'service-item');
    li.appendChild(icon(s.icon));
    li.appendChild(el('span', 'service-item__label', s.label));
    if (s.supplement) li.appendChild(el('span', 'service-item__badge', T('supplement', 'supplément')));
    list.appendChild(li);
  });
  document.querySelector('[data-trusted-note]').textContent = trusted.note || '';
}

// ─── Les activités ────────────────────────────────────────
function renderActivities() {
  const a = CONFIG.activities;
  if (!a || !document.querySelector('[data-act-grid]')) return;

  setText('[data-act-kicker]', a.kicker);
  setText('[data-act-title]',  a.title);
  setText('[data-act-text]',   a.text);
  setText('[data-act-note]',   a.note);

  const grid = document.querySelector('[data-act-grid]');
  (a.items || []).forEach(i => {
    const li = el('li', 'activite-card');
    li.appendChild(icon(i.icon));
    li.appendChild(el('span', 'activite-card__label', i.label));
    grid.appendChild(li);
  });

  const dist = document.querySelector('[data-act-distances]');
  (a.distances || []).forEach(d => {
    dist.appendChild(el('dt', 'activites__dist-label', d.label));
    dist.appendChild(el('dd', 'activites__dist-value', d.value));
  });
}

// ─── FAQ ──────────────────────────────────────────────────
function renderFaq() {
  if (!document.querySelector('[data-faq-list]')) return;
  const { faq } = CONFIG;

  document.querySelector('[data-faq-kicker]').textContent = faq.kicker;
  document.querySelector('[data-faq-title]').textContent  = faq.title;
  document.querySelector('[data-faq-intro]').textContent  = faq.intro;

  applyBackdrop(document.querySelector('[data-faq-image]'), faq.image, faq.imageGradient);

  const list = document.querySelector('[data-faq-list]');
  faq.items.forEach((item, i) => {
    const wrap = el('div', 'faq-item');
    if (i === 0) wrap.classList.add('is-open');

    const bodyId = 'faq-body-' + i;

    const trigger = el('button', 'faq-item__trigger');
    trigger.type = 'button';
    trigger.setAttribute('aria-expanded', i === 0 ? 'true' : 'false');
    trigger.setAttribute('aria-controls', bodyId);
    trigger.appendChild(el('span', 'faq-item__q', item.q));

    const chevron = icon('chevron-down');
    chevron.classList.add('faq-item__icon');
    trigger.appendChild(chevron);

    const body = el('div', 'faq-item__body');
    body.id = bodyId;
    body.appendChild(el('p', 'faq-item__a', item.a));

    wrap.appendChild(trigger);
    wrap.appendChild(body);
    list.appendChild(wrap);
  });
}

// ─── Accès & contact ──────────────────────────────────────
function renderAccess() {
  const a = CONFIG.access;
  if (!a || !document.querySelector('[data-acces-rows]')) return;

  setText('[data-acces-kicker]', a.kicker);
  setText('[data-acces-title]',  a.title);
  setText('[data-acces-text]',   a.text);

  const fb = document.querySelector('[data-acces-fb]');
  if (a.facebook) {
    fb.href = a.facebook;
  } else {
    fb.remove();
  }

  const rows = document.querySelector('[data-acces-rows]');
  (a.rows || []).forEach(r => {
    const dt = el('dt', 'acces__row-label');
    dt.appendChild(icon(r.icon));
    dt.appendChild(el('span', '', r.label));

    const dd = el('dd', 'acces__row-value');
    // Un champ non renseigné est signalé visuellement plutôt que masqué :
    // le gérant voit immédiatement ce qu'il lui reste à compléter.
    const missing = /^\[.*\]$/.test(r.value);
    if (missing) dd.classList.add('is-missing');

    if (r.href && !missing) {
      const a2 = el('a', '', r.value);
      a2.href = r.href;
      dd.appendChild(a2);
    } else {
      dd.textContent = r.value;
    }

    rows.appendChild(dt);
    rows.appendChild(dd);
  });
}

// ─── Demande de réservation ───────────────────────────────
// ─── Demande de réservation ───────────────────────────────
function renderBooking() {
  const { booking } = CONFIG;
  const left  = document.querySelector('[data-booking-left]');
  const right = document.querySelector('[data-booking-right]');
  if (!left || !right) return;

  const L  = booking.labels;
  const ph = L.ph;

  // ── Colonne gauche : texte + infos pratiques ──────────
  left.appendChild(el('p', 'kicker', booking.kicker));
  left.appendChild(el('span', 'booking__subtitle', booking.subtitle));

  const h2 = el('h2', 'booking__title', booking.title);
  h2.id = 'booking-title';
  left.appendChild(h2);
  left.appendChild(el('p', 'booking__intro', booking.intro));

  const infoCard = el('div', 'booking__info-card');
  booking.infoCard.forEach(({ label, value }) => {
    const row = el('div', 'booking__info-row');
    row.appendChild(el('span', 'booking__info-label', label));
    const v = el('span', 'booking__info-value', value);
    if (/^\[.*\]$/.test(value)) v.classList.add('is-missing');
    row.appendChild(v);
    infoCard.appendChild(row);
  });
  left.appendChild(infoCard);

  // ── Colonne droite : formulaire ───────────────────────
  const wrap = el('div', 'booking__form-wrap');

  // Tant que l'adresse de réception n'est pas configurée, on le dit
  // franchement et on renvoie vers Facebook, qui est connu.
  if (!booking.actif) {
    const notice = el('div', 'booking__notice');
    notice.appendChild(el('p', '', L.noEmail));
    if (booking.facebook) {
      const a = el('a', 'btn btn--pill btn--outline', L.fbLink);
      a.href   = booking.facebook;
      a.target = '_blank';
      a.rel    = 'noopener noreferrer';
      notice.appendChild(a);
    }
    wrap.appendChild(notice);
  }

  // Le formulaire est un vrai formulaire : sans JavaScript, il se soumet
  // normalement vers contact.php, qui répond alors une page complète.
  const form = el('form', 'booking__form');
  form.method = 'post';
  form.action = booking.endpoint || 'contact.php';
  form.setAttribute('novalidate', '');
  form.setAttribute('accept-charset', 'UTF-8');

  /** Champ caché, transmis tel quel au serveur. */
  function cache(nom, valeur) {
    const i = el('input');
    i.type  = 'hidden';
    i.name  = nom;
    i.value = valeur;
    return i;
  }

  form.appendChild(cache('lang', booking.lang || 'fr'));
  // Horodatage d'ouverture : un envoi en moins de trois secondes vient
  // d'un automate, pas d'un visiteur.
  form.appendChild(cache('_t', String(Math.floor(Date.now() / 1000))));

  /**
   * Piège à robots. Invisible à l'écran et retiré du parcours clavier,
   * mais rempli par les automates qui remplissent tous les champs.
   * Masqué en CSS plutôt qu'en type="hidden" : les robots ignorent les
   * champs cachés mais pas ceux qui sont simplement déplacés hors écran.
   */
  const piege = el('div', 'booking__hp');
  piege.setAttribute('aria-hidden', 'true');
  const piegeInput = el('input');
  piegeInput.type = 'text';
  piegeInput.name = 'site_web';
  piegeInput.tabIndex = -1;
  piegeInput.autocomplete = 'off';
  const piegeLabel = el('label', '', 'Ne remplissez pas ce champ');
  piegeLabel.setAttribute('for', 'bk-hp');
  piegeInput.id = 'bk-hp';
  piege.appendChild(piegeLabel);
  piege.appendChild(piegeInput);
  form.appendChild(piege);

  let fieldIdx = 0;
  function field(labelText, inputEl, fullWidth) {
    const id = 'bk-' + (fieldIdx++);
    inputEl.id = id;
    const w   = el('div', 'booking__field' + (fullWidth ? ' booking__field--full' : ''));
    const lbl = el('label', 'booking__label', labelText);
    lbl.setAttribute('for', id);
    // Emplacement du message d'erreur propre à ce champ.
    const err = el('p', 'booking__field-error');
    err.id = id + '-err';
    err.hidden = true;
    w.appendChild(lbl);
    w.appendChild(inputEl);
    w.appendChild(err);
    inputEl.setAttribute('aria-describedby', err.id);
    return w;
  }

  function inp(type, name, placeholder, required, autocomplete) {
    const i = el('input', 'booking__input');
    i.type = type;
    i.name = name;
    if (placeholder)  i.placeholder = placeholder;
    if (required)   { i.required = true; i.setAttribute('aria-required', 'true'); }
    if (autocomplete) i.autocomplete = autocomplete;
    return i;
  }

  function sel(name, options) {
    const s = el('select', 'booking__select');
    s.name = name;
    options.forEach(([val, txt]) => {
      const o = el('option', '', txt);
      o.value = val;
      s.appendChild(o);
    });
    return s;
  }

  const today = new Date().toISOString().split('T')[0];

  const iFirst = inp('text',  'prenom',    ph.firstName, true, 'given-name');
  const iLast  = inp('text',  'nom',       ph.lastName,  true, 'family-name');
  const iMail  = inp('email', 'email',     ph.email,     true, 'email');
  const iPhone = inp('tel',   'telephone', ph.phone,     false, 'tel');
  const iIn    = inp('date',  'arrivee',   '', false);  iIn.min  = today;
  const iOut   = inp('date',  'depart',    '', false);  iOut.min = today;

  const sRoom = sel('chambre', [['', L.select || '— Sélectionner —'],
    ...booking.rooms.map(r => [r, r])]);
  const sGuests = sel('voyageurs',
    ['1','2','3','4','5','6'].map(n => [n, n + ' ' + (n === '1' ? L.guestOne : L.guestMany)])
  );
  const iMsg = el('textarea', 'booking__textarea');
  iMsg.name = 'message';
  iMsg.placeholder = ph.message;
  iMsg.rows = 4;

  const champs = {
    prenom:    field(L.firstName, iFirst),
    nom:       field(L.lastName,  iLast),
    email:     field(L.email,     iMail),
    telephone: field(L.phone,     iPhone),
    arrivee:   field(L.checkIn,   iIn),
    depart:    field(L.checkOut,  iOut),
    chambre:   field(L.roomType,  sRoom),
    voyageurs: field(L.guests,    sGuests),
    message:   field(L.message,   iMsg, true),
  };
  Object.values(champs).forEach(f => form.appendChild(f));

  // Message global, annoncé aux lecteurs d'écran dès qu'il apparaît.
  const avis = el('p', 'booking__error');
  avis.setAttribute('role', 'alert');
  avis.hidden = true;
  form.appendChild(avis);

  const submitRow = el('div', 'booking__submit-row');
  const submitBtn = el('button', 'btn btn--accent btn--pill', L.submit);
  submitBtn.type = 'submit';
  if (!booking.actif) submitBtn.disabled = true;
  submitRow.appendChild(submitBtn);
  submitRow.appendChild(el('p', 'booking__note', L.note));
  form.appendChild(submitRow);

  // État « demande envoyée »
  const success = el('div', 'booking__success');
  const iconWrap = el('div', 'booking__success-icon');
  iconWrap.innerHTML =
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
    '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>' +
    '<polyline points="22 4 12 14.01 9 11.01"/></svg>';
  const successTitre = el('div', 'booking__success-title', L.successTitle);
  const successTexte = el('p',   'booking__success-text',  L.successText);
  const resetBtn = el('button', 'btn btn--outline btn--pill', L.resetBtn);
  resetBtn.type = 'button';
  success.appendChild(iconWrap);
  success.appendChild(successTitre);
  success.appendChild(successTexte);
  success.appendChild(resetBtn);

  wrap.appendChild(form);
  wrap.appendChild(success);
  right.appendChild(wrap);

  // ── Validation et envoi ───────────────────────────────

  /** Affiche ou efface l'erreur d'un champ. */
  function marquer(nom, message) {
    const bloc = champs[nom];
    if (!bloc) return;
    const saisie = bloc.querySelector('input, select, textarea');
    const err    = bloc.querySelector('.booking__field-error');
    if (!err) return;

    if (message) {
      bloc.classList.add('has-error');
      err.textContent = message;
      err.hidden = false;
      if (saisie) saisie.setAttribute('aria-invalid', 'true');
    } else {
      bloc.classList.remove('has-error');
      err.textContent = '';
      err.hidden = true;
      if (saisie) saisie.removeAttribute('aria-invalid');
    }
  }

  function effacerErreurs() {
    Object.keys(champs).forEach(n => marquer(n, ''));
    avis.hidden = true;
  }

  /**
   * Contrôles côté navigateur. Ils font gagner un aller-retour, mais ne
   * remplacent jamais ceux du serveur : contact.php revalide tout.
   */
  function verifier() {
    const erreurs = {};

    if (!iFirst.value.trim()) erreurs.prenom = L.required;
    if (!iLast.value.trim())  erreurs.nom    = L.required;

    const mail = iMail.value.trim();
    if (!mail) {
      erreurs.email = L.required;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(mail)) {
      erreurs.email = L.badEmail;
    }

    const auj = new Date(); auj.setHours(0, 0, 0, 0);
    const din = iIn.value  ? new Date(iIn.value  + 'T00:00:00') : null;
    const dout = iOut.value ? new Date(iOut.value + 'T00:00:00') : null;

    if (din && din < auj)  erreurs.arrivee = L.pastDate;
    if (dout && dout < auj) erreurs.depart  = L.pastDate;
    if (din && dout && dout <= din) erreurs.depart = L.badDates;

    return erreurs;
  }

  // La correction d'un champ efface son erreur immédiatement.
  Object.entries(champs).forEach(([nom, bloc]) => {
    const saisie = bloc.querySelector('input, select, textarea');
    if (saisie) saisie.addEventListener('input', () => marquer(nom, ''));
  });

  iIn.addEventListener('change', () => {
    if (iIn.value) {
      iOut.min = iIn.value;
      if (iOut.value && iOut.value <= iIn.value) iOut.value = '';
    }
  });

  function afficherAvis(texte, estErreur) {
    avis.textContent = texte;
    avis.classList.toggle('booking__error--ok', !estErreur);
    avis.hidden = false;
  }

  form.addEventListener('submit', e => {
    if (!booking.actif) { e.preventDefault(); return; }

    const erreurs = verifier();
    if (Object.keys(erreurs).length) {
      e.preventDefault();
      effacerErreurs();
      Object.entries(erreurs).forEach(([nom, msg]) => marquer(nom, msg));
      afficherAvis(L.error, true);
      const premier = form.querySelector('.has-error input, .has-error select, .has-error textarea');
      if (premier) premier.focus();
      return;
    }

    // Sans fetch, on laisse le formulaire se soumettre normalement :
    // contact.php répondra une page complète.
    if (typeof window.fetch !== 'function') return;

    e.preventDefault();
    effacerErreurs();

    submitBtn.disabled = true;
    submitBtn.textContent = L.sending;
    form.classList.add('is-sending');

    fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'fetch' },
    })
      .then(r => r.json().then(j => ({ status: r.status, body: j })))
      .then(({ body }) => {
        if (body && body.ok) {
          form.hidden = true;
          success.classList.add('is-visible');
          successTexte.textContent = body.message || L.successText;
          if (typeof gsap !== 'undefined') {
            gsap.fromTo(success, { y: 24, opacity: 0 },
              { y: 0, opacity: 1, duration: 0.7, ease: 'power2.out' });
          }
          // Le focus suit l'information : sans cela, un lecteur d'écran
          // resterait sur un bouton qui n'existe plus à l'écran.
          successTitre.setAttribute('tabindex', '-1');
          successTitre.focus();
          return;
        }

        if (body && body.champs) {
          Object.entries(body.champs).forEach(([nom, msg]) => marquer(nom, String(msg)));
          const premier = form.querySelector('.has-error input, .has-error select, .has-error textarea');
          if (premier) premier.focus();
        }
        afficherAvis((body && body.message) || L.offline, true);
      })
      .catch(() => afficherAvis(L.offline, true))
      .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = L.submit;
        form.classList.remove('is-sending');
      });
  });

  resetBtn.addEventListener('click', () => {
    form.reset();
    effacerErreurs();
    form.hidden = false;
    success.classList.remove('is-visible');
    iFirst.focus();
  });
}

// ─── Footer ───────────────────────────────────────────────
function renderFooter() {
  if (!document.querySelector('[data-footer-cta-block]')) return;
  const { footer, brand } = CONFIG;

  const ctaBlock = document.querySelector('[data-footer-cta-block]');
  applyBackdrop(ctaBlock, footer.cta.image, footer.cta.gradient);

  ctaBlock.appendChild(el('h2', 'footer__cta-title', footer.cta.title));
  const ctaBtn = el('a', 'btn btn--accent btn--pill footer__cta-btn', footer.cta.button);
  ctaBtn.href  = footer.cta.href;
  ctaBlock.appendChild(ctaBtn);

  const addrEl = document.querySelector('[data-footer-address]');
  addrEl.appendChild(el('div', 'footer__brand-name', brand.name));
  footer.address.forEach(line => {
    if (line.includes('@')) {
      const a = el('a', '', line);
      a.href = 'mailto:' + line;
      addrEl.appendChild(a);
    } else {
      addrEl.appendChild(el('p', '', line));
    }
  });

  const socialEl = document.querySelector('[data-footer-social]');
  socialEl.appendChild(el('h3', '', T('follow', 'Suivez-nous')));
  const socialUl = el('ul');
  footer.social.forEach(s => {
    const li = el('li');
    const a  = el('a', '', s.label);
    a.href   = s.href;
    a.target = '_blank';
    a.rel    = 'noopener noreferrer';
    li.appendChild(a);
    socialUl.appendChild(li);
  });
  socialEl.appendChild(socialUl);

  const legalEl = document.querySelector('[data-footer-legal]');
  legalEl.appendChild(el('h3', '', T('info', 'Informations')));
  const legalUl = el('ul');
  footer.legal.forEach(l => {
    const li = el('li');
    // Pages non rédigées à ce stade : texte simple plutôt qu'un lien mort.
    li.appendChild(el('span', '', l));
    legalUl.appendChild(li);
  });
  legalEl.appendChild(legalUl);

  document.querySelector('[data-footer-wordmark]').textContent  = footer.wordmark;
  document.querySelector('[data-footer-copyright]').textContent = footer.copyright;
}

// ─── Thème ────────────────────────────────────────────────
function applyTheme() {
  const { theme } = CONFIG;
  if (!theme) return;
  const r = document.documentElement.style;
  if (theme.accent) r.setProperty('--c-accent', theme.accent);
  if (theme.dark)   r.setProperty('--c-dark',   theme.dark);
  if (theme.bg)     r.setProperty('--c-bg',     theme.bg);
}

// ─── Export principal ─────────────────────────────────────
function renderAll() {
  applyTheme();
  renderNav();
  renderPageHead();
  renderHero();
  renderStory();
  renderReputation();
  renderAmazing();
  renderIdeal();
  renderRestaurant();
  renderTrusted();
  renderActivities();
  renderFaq();
  renderAccess();
  renderRoomsPage();
  renderBooking();
  renderFooter();
  renderJoindre();
  renderNotFound();
}

// ─── Bouton d'appel flottant (mobile) ─────────────────────
/**
 * Affiche WhatsApp et l'appel direct en bas d'écran, sur téléphone.
 * Les deux liens viennent de config/site.php : sans numéro renseigné,
 * le bloc reste vide et masqué plutôt que d'offrir un lien mort.
 */
function renderJoindre() {
  const bloc = document.querySelector('[data-joindre]');
  if (!bloc) return;

  const a = CONFIG.access || {};
  const liens = [];

  if (a.whatsapp) {
    liens.push({
      href:  a.whatsapp,
      cls:   'joindre__btn--wa',
      label: 'WhatsApp',
      externe: true,
      // Logo WhatsApp officiel, en chemin unique.
      svg: '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">'
         + '<path d="M17.47 14.38c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15'
         + '-.2.3-.77.96-.94 1.16-.17.2-.35.22-.65.07-.3-.15-1.25-.46-2.38-1.47'
         + '-.88-.78-1.48-1.75-1.65-2.05-.17-.3-.02-.46.13-.61.14-.13.3-.35.45-.52'
         + '.15-.18.2-.3.3-.5.1-.2.05-.38-.02-.53-.08-.15-.67-1.62-.92-2.22'
         + '-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.38-.27.3-1.04 1.02-1.04 2.48'
         + '0 1.46 1.07 2.88 1.22 3.08.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.69.63'
         + '.71.22 1.36.19 1.87.12.57-.09 1.75-.72 2-1.41.25-.69.25-1.28.17-1.4'
         + '-.07-.13-.27-.2-.57-.35z"/>'
         + '<path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95'
         + 'L2 22l5.25-1.38a9.87 9.87 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91'
         + 'C21.96 6.45 17.5 2 12.04 2zm0 18.15h-.01a8.2 8.2 0 0 1-4.19-1.15l-.3-.18'
         + '-3.12.82.83-3.04-.2-.31a8.22 8.22 0 0 1-1.26-4.38c0-4.54 3.7-8.23 8.25-8.23'
         + 'a8.23 8.23 0 0 1 8.24 8.24c0 4.54-3.7 8.23-8.24 8.23z"/></svg>',
    });
  }

  if (a.telLink) {
    liens.push({
      href:  a.telLink,
      cls:   'joindre__btn--tel',
      label: T('phone', 'Téléphone'),
      externe: false,
      svg: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"'
         + ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'
         + '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07'
         + ' 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3'
         + 'a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91'
         + 'a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7'
         + 'A2 2 0 0 1 22 16.92z"/></svg>',
    });
  }

  if (!liens.length) return;

  liens.forEach(l => {
    const a2 = el('a', 'joindre__btn ' + l.cls);
    a2.href = l.href;
    a2.setAttribute('aria-label', l.label);
    a2.title = l.label;
    if (l.externe) { a2.target = '_blank'; a2.rel = 'noopener noreferrer'; }
    a2.innerHTML = l.svg;
    bloc.appendChild(a2);
  });

  bloc.hidden = false;
}

// ─── Page introuvable ─────────────────────────────────────
/**
 * Propose les pages du site à un visiteur égaré. Les liens viennent de la
 * navigation : ils restent justes si le menu change.
 */
function renderNotFound() {
  const liste = document.querySelector('[data-e404-liens]');
  if (!liste) return;

  const liens = [{ label: T('e404Home', "Retour à l'accueil"), href: 'index.html' }]
    .concat(CONFIG.nav && CONFIG.nav.links ? CONFIG.nav.links : []);

  liens.forEach(l => {
    const li = el('li');
    const a  = el('a', 'e404__lien', l.label);
    a.href   = l.href;
    li.appendChild(a);
    liste.appendChild(li);
  });
}
