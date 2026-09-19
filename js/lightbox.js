// ═══════════════════════════════════════════════════════════
//  lightbox.js — Visionneuse des photos d'une chambre.
//
//  Ouverte depuis le bouton « Voir les photos » d'une carte. Volontairement
//  autonome : aucune librairie, et elle fonctionne sans GSAP.
//
//  Accessibilité : la fenêtre est un vrai dialogue (role/aria-modal), le
//  focus y est enfermé tant qu'elle est ouverte, Échap la ferme, les flèches
//  changent de photo, et le focus revient sur le bouton d'origine.
// ═══════════════════════════════════════════════════════════

function initLightbox() {
  let overlay   = null;   // l'élément de la visionneuse
  let photos    = [];     // photos de la chambre en cours
  let index     = 0;
  let opener    = null;   // bouton à re-focaliser à la fermeture
  let scrollTop = 0;

  // ─── Construction (une seule fois, à la première ouverture) ──────────
  function build() {
    overlay = el('div', 'lightbox');
    overlay.setAttribute('role', 'dialog');
    overlay.setAttribute('aria-modal', 'true');
    overlay.hidden = true;

    const frame = el('div', 'lightbox__frame');

    const title = el('h2', 'lightbox__title');
    title.id = 'lightbox-title';
    overlay.setAttribute('aria-labelledby', title.id);

    const close = el('button', 'lightbox__close');
    close.type = 'button';
    close.setAttribute('aria-label', 'Fermer la visionneuse');
    close.innerHTML =
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" ' +
      'stroke-linecap="round" aria-hidden="true"><line x1="5" y1="5" x2="19" y2="19"/>' +
      '<line x1="19" y1="5" x2="5" y2="19"/></svg>';

    const stage = el('div', 'lightbox__stage');

    const prev = el('button', 'lightbox__nav lightbox__nav--prev');
    prev.type = 'button';
    prev.setAttribute('aria-label', 'Photo précédente');
    prev.innerHTML =
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" ' +
      'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
      '<polyline points="15 18 9 12 15 6"/></svg>';

    const next = el('button', 'lightbox__nav lightbox__nav--next');
    next.type = 'button';
    next.setAttribute('aria-label', 'Photo suivante');
    next.innerHTML =
      '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" ' +
      'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' +
      '<polyline points="9 18 15 12 9 6"/></svg>';

    const holder = el('div', 'lightbox__holder');

    // Le compteur est annoncé aux lecteurs d'écran à chaque changement.
    const count = el('p', 'lightbox__count');
    count.setAttribute('aria-live', 'polite');

    stage.appendChild(prev);
    stage.appendChild(holder);
    stage.appendChild(next);

    frame.appendChild(close);
    frame.appendChild(title);
    frame.appendChild(stage);
    frame.appendChild(count);
    overlay.appendChild(frame);
    document.body.appendChild(overlay);

    overlay._parts = { title, holder, count, prev, next, close, frame };

    // ── Interactions ──────────────────────────────────────────────────
    close.addEventListener('click', hide);
    prev.addEventListener('click', () => step(-1));
    next.addEventListener('click', () => step(1));

    // Un clic en dehors du cadre ferme la visionneuse.
    overlay.addEventListener('mousedown', e => {
      if (e.target === overlay) hide();
    });

    overlay.addEventListener('keydown', e => {
      if (e.key === 'Escape')     { e.preventDefault(); hide(); }
      if (e.key === 'ArrowLeft')  { e.preventDefault(); step(-1); }
      if (e.key === 'ArrowRight') { e.preventDefault(); step(1); }
      if (e.key === 'Tab')        trapFocus(e);
    });

    // Balayage horizontal au doigt.
    let touchX = null;
    holder.addEventListener('touchstart', e => { touchX = e.touches[0].clientX; }, { passive: true });
    holder.addEventListener('touchend', e => {
      if (touchX === null) return;
      const delta = e.changedTouches[0].clientX - touchX;
      if (Math.abs(delta) > 45) step(delta < 0 ? 1 : -1);
      touchX = null;
    });
  }

  /** Empêche le focus de sortir du dialogue à la tabulation. */
  function trapFocus(e) {
    const focusable = overlay.querySelectorAll('button:not([disabled])');
    if (!focusable.length) return;
    const first = focusable[0];
    const last  = focusable[focusable.length - 1];

    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  }

  /** Affiche la photo courante. */
  function paint() {
    const { holder, count, prev, next } = overlay._parts;
    holder.textContent = '';

    const picture = makePicture(photos[index], 'lightbox__pic');
    if (picture) holder.appendChild(picture);

    const many = photos.length > 1;
    prev.hidden = !many;
    next.hidden = !many;
    count.textContent = many ? (index + 1) + ' / ' + photos.length : '';
  }

  function step(delta) {
    if (photos.length < 2) return;
    index = (index + delta + photos.length) % photos.length;
    paint();
  }

  function show(room, button) {
    if (!overlay) build();

    photos = room.gallery || [];
    if (!photos.length) return;

    index  = 0;
    opener = button;

    overlay._parts.title.textContent = room.name || '';
    paint();

    // Le fond de page ne doit pas défiler derrière la visionneuse. On fige
    // la position plutôt que de masquer le débordement, sinon le navigateur
    // remonte la page en haut à la fermeture.
    scrollTop = window.scrollY;
    document.body.style.position = 'fixed';
    document.body.style.top      = (-scrollTop) + 'px';
    document.body.style.width    = '100%';

    overlay.hidden = false;
    if (window.__lenis && window.__lenis.stop) window.__lenis.stop();
    overlay._parts.close.focus();
  }

  function hide() {
    if (!overlay || overlay.hidden) return;

    overlay.hidden = true;
    document.body.style.position = '';
    document.body.style.top      = '';
    document.body.style.width    = '';
    window.scrollTo(0, scrollTop);

    if (window.__lenis && window.__lenis.start) window.__lenis.start();
    if (opener) opener.focus();
    opener = null;
  }

  // ─── Branchement sur les boutons des cartes ──────────────────────────
  document.querySelectorAll('[data-gallery-index]').forEach(button => {
    button.addEventListener('click', () => {
      const i    = parseInt(button.getAttribute('data-gallery-index'), 10);
      const room = CONFIG.ideal.items[i];
      if (room) show(room, button);
    });
  });
}
