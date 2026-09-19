// ═══════════════════════════════════════════════════════════
//  rooms.js — En-tête des pages intérieures et page « Nos chambres ».
//
//  La mise en page reprend les maquettes fournies dans add_img/ : grande
//  photo à gauche avec sa bande de vignettes, panneau de détails à droite
//  (superficie, description, tarif, puis équipements).
// ═══════════════════════════════════════════════════════════

/** Page courante, telle que posée par le générateur sur <body>. */
function currentPage() {
  return (document.body && document.body.dataset.page) || 'index';
}

// ─── En-tête des pages intérieures ────────────────────────
function renderPageHead() {
  const host = document.querySelector('[data-page-title]');
  if (!host) return;

  const meta = (CONFIG.pages || {})[currentPage()];
  if (!meta) return;

  const set = (sel, value) => {
    const n = document.querySelector(sel);
    if (n) n.textContent = value || '';
  };

  set('[data-page-kicker]', meta.kicker);
  set('[data-page-title]',  meta.title);
  set('[data-page-intro]',  meta.intro);

  // Le titre de la page devient aussi celui de l'onglet quand le
  // générateur n'en a pas fourni de plus précis.
  if (!document.title && meta.title) {
    document.title = meta.title + ' — ' + (CONFIG.brand.name || '');
  }
}

// ─── Page « Nos chambres » ────────────────────────────────
function renderRoomsPage() {
  const host = document.querySelector('[data-rooms-list]');
  if (!host) return;

  const rooms = (CONFIG.ideal && CONFIG.ideal.items) || [];
  const shared = (CONFIG.ideal && CONFIG.ideal.shared) || [];
  const t = CONFIG.t || {};

  // ── Raccourcis vers chaque chambre, en haut de page ────
  const jump = document.querySelector('[data-rooms-jump]');
  if (jump) {
    rooms.forEach(room => {
      const a = el('a', 'pagehead__jump-item', room.name);
      a.href = '#' + room.id;
      jump.appendChild(a);
    });
  }

  rooms.forEach((room, index) => {
    const article = el('article', 'room');
    article.id = room.id;
    // Les blocs alternent pour éviter l'effet de colonne monotone.
    if (index % 2 === 1) article.classList.add('room--reverse');

    const inner = el('div', 'container room__inner');

    // ── Colonne photos ───────────────────────────────────
    const gallery = el('div', 'room__gallery');
    const photos  = room.gallery || [];

    const main = el('div', 'room__main');
    applyGradient(main, room.gradient);

    if (photos.length) {
      const openBtn = el('button', 'room__main-btn');
      openBtn.type = 'button';
      openBtn.setAttribute('data-gallery-index', String(index));
      openBtn.setAttribute(
        'aria-label',
        (t.viewPhotosOf || 'Voir les photos de la chambre') + ' ' + room.name
      );

      const picture = makePicture(photos[0], 'room__pic');
      if (picture) openBtn.appendChild(picture);

      const loupe = el('span', 'room__zoom');
      loupe.appendChild(icon('maximize-2'));
      openBtn.appendChild(loupe);
      main.appendChild(openBtn);
    }
    gallery.appendChild(main);

    // Bande de vignettes : change la photo affichée sans quitter la page.
    if (photos.length > 1) {
      const thumbs = el('ul', 'room__thumbs');
      photos.forEach((photo, i) => {
        const li = el('li');
        const b  = el('button', 'room__thumb' + (i === 0 ? ' is-active' : ''));
        b.type = 'button';
        b.setAttribute('aria-label', (t.photo || 'Photo') + ' ' + (i + 1));

        const img = el('img');
        img.src = (photo.srcset && photo.srcset.jpg)
          ? photo.src.replace(/-(small|med|large)\.jpg$/, '-thumb.jpg')
          : photo.src;
        img.alt = photo.alt || '';
        img.loading = 'lazy';
        img.decoding = 'async';
        b.appendChild(img);

        b.addEventListener('click', () => {
          const current = main.querySelector('picture');
          const next = makePicture(photo, 'room__pic');
          if (current && next) main.querySelector('button').replaceChild(next, current);
          thumbs.querySelectorAll('.room__thumb').forEach(x => x.classList.remove('is-active'));
          b.classList.add('is-active');
        });

        li.appendChild(b);
        thumbs.appendChild(li);
      });
      gallery.appendChild(thumbs);
    }

    // ── Colonne détails ──────────────────────────────────
    const detail = el('div', 'room__detail');

    const title = el('h2', 'room__name', room.name);
    detail.appendChild(title);

    // Pastilles : superficie puis équipements propres à la chambre.
    const chips = el('ul', 'room__chips');
    if (room.area) {
      const li = el('li', 'room__chip room__chip--area');
      li.appendChild(icon('maximize'));
      li.appendChild(el('span', '', room.area));
      chips.appendChild(li);
    }
    (room.amenities || []).forEach(a => {
      const li = el('li', 'room__chip');
      li.appendChild(icon('check'));
      li.appendChild(el('span', '', a));
      chips.appendChild(li);
    });
    if (chips.children.length) detail.appendChild(chips);

    if (room.desc) detail.appendChild(el('p', 'room__desc', room.desc));

    // Tarif : toujours présent, même quand il n'est pas encore connu.
    if (room.price) {
      const price = el('div', 'room__price');
      price.appendChild(el('span', 'room__price-label', t.from || 'Tarif'));
      price.appendChild(el('span', 'room__price-value', room.price));
      detail.appendChild(price);
    }

    // Équipements communs, rappelés sur chaque chambre : le visiteur qui
    // arrive directement sur une ancre ne les manquerait sinon.
    if (shared.length) {
      detail.appendChild(el('h3', 'room__subtitle', t.inEveryRoom || 'Dans toutes les chambres'));
      const ul = el('ul', 'room__shared');
      shared.forEach(s => {
        const li = el('li');
        li.appendChild(icon('check'));
        li.appendChild(el('span', '', s));
        ul.appendChild(li);
      });
      detail.appendChild(ul);
    }

    const cta = el('a', 'btn btn--accent btn--pill room__cta',
      t.askThisRoom || 'Demander cette chambre');
    cta.href = 'acces.html#booking';
    detail.appendChild(cta);

    inner.appendChild(gallery);
    inner.appendChild(detail);
    article.appendChild(inner);
    host.appendChild(article);
  });

  // Arrivée directe sur une ancre : le navigateur saute avant que les
  // blocs n'existent, il faut donc repositionner après le rendu.
  if (window.location.hash) {
    const target = document.getElementById(window.location.hash.slice(1));
    if (target) {
      window.requestAnimationFrame(() => {
        target.scrollIntoView({ behavior: 'auto', block: 'start' });
      });
    }
  }
}
