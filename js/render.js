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

  document.querySelector('[data-nav-logo]').textContent = brand.logoText;

  const navLinks  = document.querySelector('[data-nav-links]');
  const drawLinks = document.querySelector('[data-drawer-links]');

  nav.links.forEach(link => {
    [navLinks, drawLinks].forEach(ul => {
      const li = el('li');
      const a  = el('a', '', link.label);
      a.href   = link.href;
      li.appendChild(a);
      ul.appendChild(li);
    });
  });

  const cta = document.querySelector('[data-nav-cta]');
  cta.textContent = nav.cta.label;
  cta.href        = nav.cta.href;
}

// ─── Hero ─────────────────────────────────────────────────
function renderHero() {
  const { hero, brand } = CONFIG;

  const wrap = document.querySelector('[data-hero-img]');
  applyGradient(wrap, hero.gradient);
  const picture = makePicture(hero.image, 'hero__pic');
  if (picture) wrap.appendChild(picture);

  document.querySelector('[data-hero-logo]').textContent     = hero.logoName;
  document.querySelector('[data-hero-logo-sub]').textContent = hero.logoSub;
  document.querySelector('[data-hero-cta]').textContent      = hero.bookLabel;
  document.querySelector('[data-hero-badge]').textContent    = hero.badge;
  document.querySelector('[data-hero-scroll-hint]').textContent = hero.scrollHint;

  document.querySelector('[data-hero-headline-title]').textContent = hero.headlineTitle;
  document.querySelector('[data-hero-headline-sub]').textContent   = hero.headlineSub;

  document.querySelector('[data-hero-prev-name]').textContent = hero.prevRoom;
  document.querySelector('[data-hero-next-name]').textContent = hero.nextRoom;

  // Repli mobile : le nom de la maison doit rester lisible même quand la
  // barre du haut est masquée (voir css/sections.css, media query 767px).
  const fallback = document.querySelector('[data-hero-headline-sub]');
  if (fallback && brand && brand.name) fallback.setAttribute('data-brand', brand.name);

  const featuresEl = document.querySelector('[data-hero-features]');
  hero.features.forEach((feat, i) => {
    if (i > 0) featuresEl.appendChild(el('div', 'hero__feature-sep'));
    const item = el('div', 'hero__feature');
    item.appendChild(icon(feat.icon));
    item.appendChild(el('span', 'hero__feature-label', feat.label));
    featuresEl.appendChild(item);
  });
}

// ─── Story ────────────────────────────────────────────────
function renderStory() {
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
  if (!r) return;

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

    // Bouton d'ouverture de la visionneuse. Un bouton explicite plutôt qu'une
    // carte cliquable : le carrousel se manipule au glissé, et un glissé ne
    // doit jamais ouvrir une fenêtre par accident.
    if (item.gallery && item.gallery.length) {
      const open = el('button', 'ideal-card__open');
      open.type = 'button';
      open.setAttribute('data-gallery-index', String(roomIndex));
      open.setAttribute(
        'aria-label',
        'Voir les photos de la chambre ' + (item.name || '')
      );
      open.appendChild(icon('image'));
      open.appendChild(el('span', '',
        item.gallery.length > 1
          ? 'Voir les ' + item.gallery.length + ' photos'
          : 'Voir la photo'
      ));
      body.appendChild(open);
    }

    card.appendChild(body);
    track.appendChild(card);
  });

  // Équipements communs à toutes les chambres
  const sharedEl = document.querySelector('[data-ideal-shared]');
  if (sharedEl && ideal.shared && ideal.shared.length) {
    sharedEl.appendChild(el('h3', 'ideal__shared-title', 'Dans toutes les chambres'));
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
  if (!r) return;

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
    if (s.supplement) li.appendChild(el('span', 'service-item__badge', 'supplément'));
    list.appendChild(li);
  });
  document.querySelector('[data-trusted-note]').textContent = trusted.note || '';
}

// ─── Les activités ────────────────────────────────────────
function renderActivities() {
  const a = CONFIG.activities;
  if (!a) return;

  document.querySelector('[data-act-kicker]').textContent = a.kicker;
  document.querySelector('[data-act-title]').textContent  = a.title;
  document.querySelector('[data-act-text]').textContent   = a.text;
  document.querySelector('[data-act-note]').textContent   = a.note || '';

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
  if (!a) return;

  document.querySelector('[data-acces-kicker]').textContent = a.kicker;
  document.querySelector('[data-acces-title]').textContent  = a.title;
  document.querySelector('[data-acces-text]').textContent   = a.text;

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

  // Tant que l'adresse e-mail n'est pas renseignée en admin, on le dit
  // franchement et on renvoie vers Facebook, qui est connu.
  if (!booking.mailto) {
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

  const form = el('form', 'booking__form');
  form.setAttribute('novalidate', '');

  let fieldIdx = 0;
  function field(labelText, inputEl, fullWidth) {
    const id = 'bk-' + (fieldIdx++);
    inputEl.id = id;
    const w   = el('div', 'booking__field' + (fullWidth ? ' booking__field--full' : ''));
    const lbl = el('label', 'booking__label', labelText);
    lbl.setAttribute('for', id);
    w.appendChild(lbl);
    w.appendChild(inputEl);
    return w;
  }

  function inp(type, placeholder, required, autocomplete) {
    const i = el('input', 'booking__input');
    i.type = type;
    if (placeholder)  i.placeholder = placeholder;
    if (required)     i.required = true;
    if (autocomplete) i.autocomplete = autocomplete;
    return i;
  }

  function sel(options) {
    const s = el('select', 'booking__select');
    options.forEach(([val, txt]) => {
      const o = el('option', '', txt);
      o.value = val;
      s.appendChild(o);
    });
    return s;
  }

  const today = new Date().toISOString().split('T')[0];

  const iFirst = inp('text',  ph.firstName, true, 'given-name');
  const iLast  = inp('text',  ph.lastName,  true, 'family-name');
  const iMail  = inp('email', ph.email,     true, 'email');
  const iPhone = inp('tel',   ph.phone,     false, 'tel');
  const iIn    = inp('date', '', true);  iIn.min  = today;
  const iOut   = inp('date', '', true);  iOut.min = today;

  const sRoom = sel([['', '— Sélectionner —'], ...booking.rooms.map(r => [r, r])]);
  const sGuests = sel(
    ['1','2','3','4','5','6'].map(n => [n, n + (n === '1' ? ' voyageur' : ' voyageurs')])
  );
  const iMsg = el('textarea', 'booking__textarea');
  iMsg.placeholder = ph.message;
  iMsg.rows = 4;

  form.appendChild(field(L.firstName, iFirst));
  form.appendChild(field(L.lastName,  iLast));
  form.appendChild(field(L.email,     iMail));
  form.appendChild(field(L.phone,     iPhone));
  form.appendChild(field(L.checkIn,   iIn));
  form.appendChild(field(L.checkOut,  iOut));
  form.appendChild(field(L.roomType,  sRoom));
  form.appendChild(field(L.guests,    sGuests));
  form.appendChild(field(L.message,   iMsg, true));

  const errorEl = el('p', 'booking__error');
  errorEl.setAttribute('role', 'alert');
  errorEl.hidden = true;
  form.appendChild(errorEl);

  const submitRow = el('div', 'booking__submit-row');
  const submitBtn = el('button', 'btn btn--accent btn--pill', L.submit);
  submitBtn.type = 'submit';
  if (!booking.mailto) submitBtn.disabled = true;
  submitRow.appendChild(submitBtn);
  submitRow.appendChild(el('p', 'booking__note', L.note));
  form.appendChild(submitRow);

  // État « message prêt »
  const success = el('div', 'booking__success');
  const iconWrap = el('div', 'booking__success-icon');
  iconWrap.innerHTML =
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
    '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>' +
    '<polyline points="22 4 12 14.01 9 11.01"/></svg>';
  const resetBtn = el('button', 'btn btn--outline btn--pill', L.resetBtn);
  resetBtn.type = 'button';
  success.appendChild(iconWrap);
  success.appendChild(el('div', 'booking__success-title', L.successTitle));
  success.appendChild(el('p',   'booking__success-text',  L.successText));
  success.appendChild(resetBtn);

  wrap.appendChild(form);
  wrap.appendChild(success);
  right.appendChild(wrap);

  // ── Logique ───────────────────────────────────────────
  iIn.addEventListener('change', () => {
    if (iIn.value) {
      iOut.min = iIn.value;
      if (iOut.value && iOut.value <= iIn.value) iOut.value = '';
    }
  });

  /**
   * Le formulaire ne parle à aucun serveur : il compose un e-mail et ouvre
   * la messagerie du visiteur. Rien n'est stocké, rien n'est envoyé à notre
   * insu, et la maison n'a pas de boîte de réception à surveiller ailleurs
   * que dans sa propre messagerie.
   */
  form.addEventListener('submit', e => {
    e.preventDefault();

    if (!booking.mailto) return;

    if (!form.checkValidity()) {
      errorEl.textContent = 'Merci de compléter les champs obligatoires.';
      errorEl.hidden = false;
      const firstInvalid = form.querySelector(':invalid');
      if (firstInvalid) firstInvalid.focus();
      return;
    }
    errorEl.hidden = true;

    const nights = [iIn.value, iOut.value].filter(Boolean).join(' → ');
    const lines = [
      'Bonjour Boda et Bakoly,',
      '',
      "Je souhaite réserver une chambre à Home Sakalava.",
      '',
      'Nom : '        + iFirst.value + ' ' + iLast.value,
      'E-mail : '     + iMail.value,
      'Téléphone : '  + (iPhone.value || '—'),
      'Dates : '      + (nights || '—'),
      'Chambre : '    + (sRoom.value || 'à conseiller'),
      'Voyageurs : '  + sGuests.value,
      '',
      'Message :',
      iMsg.value || '—',
      '',
      'Merci d\'avance,',
      iFirst.value + ' ' + iLast.value,
    ];

    const subject = 'Demande de réservation — ' + (nights || 'dates à définir');
    const href = 'mailto:' + booking.mailto
      + '?subject=' + encodeURIComponent(subject)
      + '&body='    + encodeURIComponent(lines.join('\n'));

    window.location.href = href;

    form.style.display = 'none';
    success.classList.add('is-visible');
    if (typeof gsap !== 'undefined') {
      gsap.fromTo(success, { y: 24, opacity: 0 }, { y: 0, opacity: 1, duration: 0.7, ease: 'power2.out' });
    }
  });

  resetBtn.addEventListener('click', () => {
    form.reset();
    form.style.display = '';
    success.classList.remove('is-visible');
    iFirst.focus();
  });
}

// ─── Footer ───────────────────────────────────────────────
function renderFooter() {
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
  socialEl.appendChild(el('h3', '', 'Suivez-nous'));
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
  legalEl.appendChild(el('h3', '', 'Informations'));
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
  renderBooking();
  renderFooter();
}
