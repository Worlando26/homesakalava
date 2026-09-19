// ═══════════════════════════════════════════════════════════
//  render.js — Injecte le contenu de CONFIG dans le DOM.
//  Images : <img class="card__bg"> quand disponible, sinon gradient CSS.
// ═══════════════════════════════════════════════════════════

/* Applique un dégradé CSS en fond d'un élément */
function applyGradient(el, gradient) {
  el.style.background = gradient;
}

/* Crée un <img> */
function makeImg(src, alt) {
  const img = document.createElement('img');
  img.src    = src;
  img.alt    = alt || '';
  img.loading = 'lazy';
  return img;
}

/* Injecte l'image d'une carte : <img> si disponible, sinon gradient */
function applyCardImage(container, item, imgClass) {
  if (item.image) {
    applyGradient(container, item.gradient || '#1a1a1a'); // couleur pendant le chargement
    const img = makeImg(item.image, item.name || '');
    img.classList.add(imgClass);
    container.appendChild(img);
  } else {
    applyGradient(container, item.gradient);
  }
}

/* Crée un élément avec classes optionnelles */
function el(tag, classes, text) {
  const e = document.createElement(tag);
  if (classes) classes.split(' ').forEach(c => c && e.classList.add(c));
  if (text != null) e.textContent = text;
  return e;
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

// ─── Hero (glassmorphism) ─────────────────────────────────
function renderHero() {
  const { hero } = CONFIG;

  // Image plein écran
  document.querySelector('[data-hero-img]').appendChild(
    makeImg(hero.image, 'Talinjoo Hotel — Fort Dauphin')
  );

  // Top bar : logo + bouton réserver
  document.querySelector('[data-hero-logo]').textContent     = hero.logoName;
  document.querySelector('[data-hero-logo-sub]').textContent = hero.logoSub;
  document.querySelector('[data-hero-cta]').textContent      = hero.bookLabel;

  // Badge
  document.querySelector('[data-hero-badge]').textContent = hero.badge;

  // Scroll hint vertical
  document.querySelector('[data-hero-scroll-hint]').textContent = hero.scrollHint;

  // Headline
  document.querySelector('[data-hero-headline-title]').textContent = hero.headlineTitle;
  document.querySelector('[data-hero-headline-sub]').textContent   = hero.headlineSub;

  // Nav prev / next
  document.querySelector('[data-hero-prev-name]').textContent = hero.prevRoom;
  document.querySelector('[data-hero-next-name]').textContent = hero.nextRoom;

  // Features (icônes Lucide + label)
  const featuresEl = document.querySelector('[data-hero-features]');
  hero.features.forEach((feat, i) => {
    if (i > 0) featuresEl.appendChild(el('div', 'hero__feature-sep'));
    const item  = el('div', 'hero__feature');
    const icon  = document.createElement('i');
    icon.setAttribute('data-lucide', feat.icon);
    const label = el('span', 'hero__feature-label', feat.label);
    item.appendChild(icon);
    item.appendChild(label);
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

// ─── Amazing / Nos Espaces (bento) ────────────────────────
function renderAmazing() {
  const { amazing } = CONFIG;
  document.querySelector('[data-amazing-kicker]').textContent = amazing.kicker;

  const ctaEl = document.querySelector('[data-amazing-cta]');
  ctaEl.textContent = amazing.cta.label;
  ctaEl.href        = amazing.cta.href;

  const bento = document.querySelector('[data-amazing-bento]');
  amazing.items.forEach(item => {
    const card = el('div', 'card' + (item.big ? ' card--big' : ''));

    // Image (réelle via picsum) ou dégradé fallback
    applyCardImage(card, item, 'card__bg');

    const overlay = el('div', 'card__overlay');
    const body    = el('div', 'card__body');
    const name    = el('div', 'card__name', item.name);
    const meta    = el('div', 'card__meta', item.meta);

    body.appendChild(name);
    body.appendChild(meta);
    card.appendChild(overlay);
    card.appendChild(body);
    bento.appendChild(card);
  });
}

// ─── Ideal / Nos Chambres (carousel) ─────────────────────
function renderIdeal() {
  const { ideal } = CONFIG;
  document.querySelector('[data-ideal-kicker]').textContent = ideal.kicker;

  const track = document.querySelector('[data-carousel-track]');
  ideal.items.forEach(item => {
    const card = el('div', 'ideal-card');

    // Image (réelle via picsum) ou dégradé fallback
    applyCardImage(card, item, 'ideal-card__bg');

    const overlay = el('div', 'ideal-card__overlay');
    const tag     = el('span', 'ideal-card__tag', item.tag);
    const body    = el('div', 'ideal-card__body');
    const name    = el('div', 'ideal-card__name', item.name);
    const loc     = el('div', 'ideal-card__loc');

    // Icône pin SVG
    const pin = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    pin.setAttribute('width', '10'); pin.setAttribute('height', '12');
    pin.setAttribute('viewBox', '0 0 10 12'); pin.setAttribute('fill', 'none');
    pin.innerHTML = '<path d="M5 0C2.24 0 0 2.24 0 5c0 3.75 5 7 5 7s5-3.25 5-7c0-2.76-2.24-5-5-5zm0 6.5c-.83 0-1.5-.67-1.5-1.5S4.17 3.5 5 3.5 6.5 4.17 6.5 5 5.83 6.5 5 6.5z" fill="currentColor"/>';
    loc.appendChild(pin);
    loc.appendChild(document.createTextNode(item.loc));

    body.appendChild(name);
    body.appendChild(loc);
    card.appendChild(overlay);
    card.appendChild(tag);
    card.appendChild(body);
    track.appendChild(card);
  });
}

// ─── Trusted / Nos Atouts ─────────────────────────────────
function renderTrusted() {
  const { trusted } = CONFIG;

  document.querySelector('[data-trusted-title]').textContent = trusted.title;
  const ctaEl = document.querySelector('[data-trusted-cta]');
  ctaEl.textContent = trusted.cta.label;
  ctaEl.href        = trusted.cta.href;

  // Amenity pills
  const amenitiesEl = document.querySelector('[data-trusted-amenities]');
  trusted.amenities.forEach(label => {
    amenitiesEl.appendChild(el('span', 'amenity-pill', label));
  });

  // Cartes flottantes gauche (0,1) / droite (2,3)
  const floatLeft  = document.querySelector('[data-float-left]');
  const floatRight = document.querySelector('[data-float-right]');

  trusted.floatImages.forEach((imgData, i) => {
    const card = el('div', 'float-card');

    // Image de fond via background-image pour les float cards
    if (imgData.image) {
      card.style.backgroundImage    = `url(${imgData.image})`;
      card.style.backgroundSize     = 'cover';
      card.style.backgroundPosition = 'center';
      // fallback couleur pendant le chargement
      card.style.backgroundColor    = imgData.gradient ? '#1a2a3a' : '#333';
    } else {
      applyGradient(card, imgData.gradient);
    }

    // Label en bas
    const labelEl = el('span', 'float-card__label', imgData.label);
    card.appendChild(labelEl);

    (i < 2 ? floatLeft : floatRight).appendChild(card);
  });

  // Carte centrale sombre
  const cardEl = document.querySelector('[data-trusted-card]');
  const title  = el('p', 'trusted__card-title', trusted.floatCard.title);
  const form   = el('div', 'trusted__card-form');
  const input  = el('input', 'trusted__card-input');
  input.type        = 'email';
  input.placeholder = trusted.floatCard.email;
  input.readOnly    = true;
  const btn = el('button', 'trusted__card-btn');
  btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
  form.appendChild(input);
  form.appendChild(btn);
  cardEl.appendChild(title);
  cardEl.appendChild(form);
}

// ─── Testimonial ──────────────────────────────────────────
function renderTestimonial() {
  const { testimonial } = CONFIG;

  document.querySelector('[data-testi-kicker]').textContent = testimonial.kicker;
  document.querySelector('[data-testi-intro]').textContent  = testimonial.intro;
  document.querySelector('[data-testi-title]').textContent  = testimonial.title;
  document.querySelector('[data-testi-body]').textContent   = testimonial.body;

  const authorEl = document.querySelector('[data-testi-author]');
  const avatar   = el('div', 'testimonial__avatar');

  if (testimonial.author.image) {
    const img = makeImg(testimonial.author.image, testimonial.author.name);
    img.style.cssText = 'width:100%;height:100%;object-fit:cover;border-radius:50%;display:block;';
    avatar.appendChild(img);
  } else {
    applyGradient(avatar, testimonial.author.gradient);
  }

  const info = el('div');
  info.appendChild(el('div', 'testimonial__author-name', testimonial.author.name));
  info.appendChild(el('div', 'testimonial__author-role', testimonial.author.role));

  authorEl.appendChild(avatar);
  authorEl.appendChild(info);
}

// ─── FAQ ──────────────────────────────────────────────────
function renderFaq() {
  const { faq } = CONFIG;

  document.querySelector('[data-faq-kicker]').textContent = faq.kicker;
  document.querySelector('[data-faq-title]').textContent  = faq.title;
  document.querySelector('[data-faq-intro]').textContent  = faq.intro;

  // Image latérale
  const imgEl = document.querySelector('[data-faq-image]');
  if (faq.image) {
    imgEl.style.backgroundImage    = `url(${faq.image})`;
    imgEl.style.backgroundSize     = 'cover';
    imgEl.style.backgroundPosition = 'center';
  } else {
    applyGradient(imgEl, faq.imageGradient);
  }

  // Accordéon
  const list = document.querySelector('[data-faq-list]');
  faq.items.forEach((item, i) => {
    const wrap    = el('div', 'faq-item');
    if (i === 0) wrap.classList.add('is-open');

    const trigger = el('button', 'faq-item__trigger');
    trigger.setAttribute('aria-expanded', i === 0 ? 'true' : 'false');

    const q    = el('span', 'faq-item__q', item.q);
    const icon = document.createElement('i');
    icon.setAttribute('data-lucide', 'chevron-down');
    icon.classList.add('faq-item__icon');

    trigger.appendChild(q);
    trigger.appendChild(icon);

    const body = el('div', 'faq-item__body');
    body.appendChild(el('p', 'faq-item__a', item.a));

    if (i === 0) body.style.maxHeight = '500px'; // initFaq() recalculera

    wrap.appendChild(trigger);
    wrap.appendChild(body);
    list.appendChild(wrap);
  });
}

// ─── Booking / Réservation ────────────────────────────────
function renderBooking() {
  const { booking } = CONFIG;
  const left  = document.querySelector('[data-booking-left]');
  const right = document.querySelector('[data-booking-right]');
  if (!left || !right) return;

  // ── LEFT — texte + info card ──────────────────────────
  left.appendChild(el('span', 'kicker',            booking.kicker));
  left.appendChild(el('span', 'booking__subtitle', booking.subtitle));
  left.appendChild(el('h2',   'booking__title',    booking.title));
  left.appendChild(el('p',    'booking__intro',    booking.intro));

  const infoCard = el('div', 'booking__info-card');
  booking.infoCard.forEach(({ label, value }) => {
    const row = el('div', 'booking__info-row');
    row.appendChild(el('span', 'booking__info-label', label));
    row.appendChild(el('span', 'booking__info-value', value));
    infoCard.appendChild(row);
  });
  left.appendChild(infoCard);

  // ── RIGHT — formulaire ────────────────────────────────
  const wrap = el('div', 'booking__form-wrap');
  const form = el('form', 'booking__form');
  form.setAttribute('novalidate', '');
  const L = booking.labels;
  const ph = L.ph;

  // Helper : champ label + input avec association implicite
  let fieldIdx = 0;
  function field(labelText, inputEl, fullWidth) {
    const id  = 'bk-' + (fieldIdx++);
    inputEl.id = id;
    const wrap = el('div', 'booking__field' + (fullWidth ? ' booking__field--full' : ''));
    const lbl  = el('label', 'booking__label', labelText);
    lbl.setAttribute('for', id);
    wrap.appendChild(lbl);
    wrap.appendChild(inputEl);
    return wrap;
  }

  function inp(type, placeholder, required) {
    const i = el('input', 'booking__input');
    i.type = type;
    if (placeholder) i.placeholder = placeholder;
    if (required)    i.required = true;
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

  // Date min = aujourd'hui
  const today = new Date().toISOString().split('T')[0];

  const iCheckIn  = inp('date', '', true);  iCheckIn.min = today;
  const iCheckOut = inp('date', '', true);  iCheckOut.min = today;

  const sRoom = sel([
    ['', '— Sélectionner —'],
    ...booking.rooms.map(r => [r, r]),
  ]);
  const sGuests = sel(
    ['1','2','3','4','5','6'].map(n => [n, n + (n === '1' ? ' voyageur' : ' voyageurs')])
  );
  const iMsg = el('textarea', 'booking__textarea');
  iMsg.placeholder = ph.message;

  form.appendChild(field(L.firstName, inp('text',  ph.firstName, true)));
  form.appendChild(field(L.lastName,  inp('text',  ph.lastName,  true)));
  form.appendChild(field(L.email,     inp('email', ph.email,     true)));
  form.appendChild(field(L.phone,     inp('tel',   ph.phone,     false)));
  form.appendChild(field(L.checkIn,   iCheckIn));
  form.appendChild(field(L.checkOut,  iCheckOut));
  form.appendChild(field(L.roomType,  sRoom));
  form.appendChild(field(L.guests,    sGuests));
  form.appendChild(field(L.message,   iMsg, true));

  // Bouton + note
  const submitRow = el('div', 'booking__submit-row');
  const submitBtn = el('button', 'btn btn--accent btn--pill', L.submit);
  submitBtn.type = 'submit';
  submitRow.appendChild(submitBtn);
  submitRow.appendChild(el('p', 'booking__note', L.note));
  form.appendChild(submitRow);

  // État succès
  const success = el('div', 'booking__success');
  const iconWrap = el('div', 'booking__success-icon');
  iconWrap.innerHTML =
    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">' +
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

  // ── LOGIQUE FORMULAIRE ────────────────────────────────
  // Dépendance check-in → check-out
  iCheckIn.addEventListener('change', () => {
    if (iCheckIn.value) {
      iCheckOut.min = iCheckIn.value;
      if (iCheckOut.value && iCheckOut.value <= iCheckIn.value) iCheckOut.value = '';
    }
  });

  // Soumission (simulation — pas de backend)
  form.addEventListener('submit', e => {
    e.preventDefault();
    submitBtn.textContent = 'Envoi en cours…';
    submitBtn.disabled    = true;
    setTimeout(() => {
      form.style.display = 'none';
      success.classList.add('is-visible');
      if (typeof gsap !== 'undefined') {
        gsap.fromTo(success, { y: 24, opacity: 0 }, { y: 0, opacity: 1, duration: 0.7, ease: 'power2.out' });
      }
    }, 1200);
  });

  // Réinitialisation
  resetBtn.addEventListener('click', () => {
    form.reset();
    form.style.display = '';
    success.classList.remove('is-visible');
    submitBtn.textContent = L.submit;
    submitBtn.disabled    = false;
  });
}

// ─── Footer ───────────────────────────────────────────────
function renderFooter() {
  const { footer, brand } = CONFIG;

  // Bloc CTA avec image de fond
  const ctaBlock = document.querySelector('[data-footer-cta-block]');
  if (footer.cta.image) {
    ctaBlock.style.backgroundImage    = `url(${footer.cta.image})`;
    ctaBlock.style.backgroundSize     = 'cover';
    ctaBlock.style.backgroundPosition = 'center';
  } else {
    applyGradient(ctaBlock, footer.cta.gradient);
  }

  const ctaTitle = el('h2', 'footer__cta-title', footer.cta.title);
  const ctaBtn   = el('a', 'btn btn--accent btn--pill footer__cta-btn', footer.cta.button);
  ctaBtn.href    = footer.cta.href;
  ctaBlock.appendChild(ctaTitle);
  ctaBlock.appendChild(ctaBtn);

  // Adresse
  const addrEl = document.querySelector('[data-footer-address]');
  addrEl.appendChild(el('div', 'footer__brand-name', brand.name));
  footer.address.forEach(line => {
    const p = line.includes('@') ? el('a', '', line) : el('p', '', line);
    if (line.includes('@')) p.href = 'mailto:' + line;
    addrEl.appendChild(p);
  });

  // Social
  const socialEl = document.querySelector('[data-footer-social]');
  socialEl.appendChild(el('h4', '', 'Suivez-nous'));
  const socialUl = el('ul');
  footer.social.forEach(s => {
    const li = el('li');
    const a  = el('a', '', s.label);
    a.href   = s.href;
    li.appendChild(a);
    socialUl.appendChild(li);
  });
  socialEl.appendChild(socialUl);

  // Legal
  const legalEl = document.querySelector('[data-footer-legal]');
  legalEl.appendChild(el('h4', '', 'Informations'));
  const legalUl = el('ul');
  footer.legal.forEach(l => {
    const li = el('li');
    const a  = el('a', '', l);
    a.href   = '#';
    li.appendChild(a);
    legalUl.appendChild(li);
  });
  legalEl.appendChild(legalUl);

  // Wordmark & copyright
  document.querySelector('[data-footer-wordmark]').textContent  = footer.wordmark;
  document.querySelector('[data-footer-copyright]').textContent = footer.copyright;
}

// ─── Init couleurs thème ──────────────────────────────────
function applyTheme() {
  const { theme } = CONFIG;
  const r = document.documentElement.style;
  if (theme.accent) r.setProperty('--c-accent', theme.accent);
  if (theme.dark)   r.setProperty('--c-dark',   theme.dark);
  if (theme.bg)     r.setProperty('--c-bg',      theme.bg);
}

// ─── Export principal ─────────────────────────────────────
function renderAll() {
  applyTheme();
  renderNav();
  renderHero();
  renderStory();
  renderAmazing();
  renderIdeal();
  renderTrusted();
  renderTestimonial();
  renderFaq();
  renderBooking();
  renderFooter();
}
