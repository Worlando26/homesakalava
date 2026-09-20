// ═══════════════════════════════════════════════════════════
//  carousel.js — Carousel draggable avec flèches (GSAP)
// ═══════════════════════════════════════════════════════════

function initCarousel() {
  const viewport = document.querySelector('[data-carousel-viewport]');
  const track    = document.querySelector('[data-carousel-track]');
  const btnsPrev = Array.from(document.querySelectorAll('[data-prev]'));
  const btnsNext = Array.from(document.querySelectorAll('[data-next]'));

  if (!viewport || !track) return;

  // Le carrousel doit rester manipulable meme si GSAP ne s'est pas charge
  // (CDN bloque, connexion coupee) : on bascule alors sur des transformations
  // CSS directes, sans inertie.
  const hasGsap = typeof gsap !== 'undefined';
  const setX  = (x) => { track.style.transform = 'translate3d(' + x + 'px,0,0)'; };
  const killT = () => { if (hasGsap) gsap.killTweensOf(track); };

  const CARD_GAP = 16;
  let currentX   = 0;
  let isDragging = false;
  let startX     = 0;
  let startXCur  = 0;
  let velX       = 0;
  let lastX      = 0;
  let lastT      = 0;

  function getMaxX() {
    return -(track.scrollWidth - viewport.clientWidth);
  }

  function clamp(val) {
    return Math.max(getMaxX(), Math.min(0, val));
  }

  function getCardWidth() {
    const first = track.firstElementChild;
    if (!first) return 280 + CARD_GAP;
    return first.offsetWidth + CARD_GAP;
  }

  function moveTo(x, duration) {
    currentX = clamp(x);
    if (hasGsap) {
      gsap.to(track, {
        x: currentX,
        duration: duration !== undefined ? duration : 0.55,
        ease: 'power3.out',
      });
    } else {
      setX(currentX);
    }
  }

  function snapToNearest() {
    const cw    = getCardWidth();
    const index = Math.round(-currentX / cw);
    moveTo(-index * cw);
  }

  function updateArrows() {
    const versLaGauche = currentX < -10;              // on peut revenir en arriere
    const versLaDroite = currentX > getMaxX() + 10;   // il reste des chambres a droite

    btnsPrev.forEach(b => {
      b.classList.toggle('is-active', versLaGauche);
      b.disabled = !versLaGauche;
    });
    btnsNext.forEach(b => {
      b.classList.toggle('is-active', versLaDroite);
      b.disabled = !versLaDroite;
    });
  }

  // ── Flèches ──────────────────────────────────────────────
  btnsPrev.forEach(b => b.addEventListener('click', () => {
    moveTo(currentX + getCardWidth());
    updateArrows();
  }));
  btnsNext.forEach(b => b.addEventListener('click', () => {
    moveTo(currentX - getCardWidth());
    updateArrows();
  }));

  // ── Drag souris ───────────────────────────────────────────
  viewport.addEventListener('mousedown', e => {
    if (e.button !== 0) return;
    isDragging = true;
    startX     = e.clientX;
    startXCur  = currentX;
    lastX      = e.clientX;
    lastT      = Date.now();
    velX       = 0;
    viewport.style.cursor = 'grabbing';
    killT();
  });

  window.addEventListener('mousemove', e => {
    if (!isDragging) return;
    const now   = Date.now();
    const delta = e.clientX - startX;
    velX = (e.clientX - lastX) / Math.max(1, now - lastT);
    lastX = e.clientX;
    lastT = now;
    if (hasGsap) { gsap.set(track, { x: clamp(startXCur + delta) }); } else { setX(clamp(startXCur + delta)); }
    currentX = clamp(startXCur + delta);
  });

  window.addEventListener('mouseup', () => {
    if (!isDragging) return;
    isDragging = false;
    viewport.style.cursor = 'grab';
    if (Math.abs(velX) > 0.4) {
      moveTo(currentX + velX * 200);
    }
    snapToNearest();
    updateArrows();
  });

  // ── Drag tactile ──────────────────────────────────────────
  viewport.addEventListener('touchstart', e => {
    startX    = e.touches[0].clientX;
    startXCur = currentX;
    lastX     = startX;
    lastT     = Date.now();
    velX      = 0;
    killT();
  }, { passive: true });

  viewport.addEventListener('touchmove', e => {
    const now   = Date.now();
    const delta = e.touches[0].clientX - startX;
    velX  = (e.touches[0].clientX - lastX) / Math.max(1, now - lastT);
    lastX = e.touches[0].clientX;
    lastT = now;
    if (hasGsap) { gsap.set(track, { x: clamp(startXCur + delta) }); } else { setX(clamp(startXCur + delta)); }
    currentX = clamp(startXCur + delta);
  }, { passive: true });

  viewport.addEventListener('touchend', () => {
    if (Math.abs(velX) > 0.3) {
      moveTo(currentX + velX * 180);
    }
    snapToNearest();
    updateArrows();
  });

  // ── Resize ────────────────────────────────────────────────
  window.addEventListener('resize', () => {
    currentX = clamp(currentX);
    if (hasGsap) { gsap.set(track, { x: currentX }); } else { setX(currentX); }
    updateArrows();
  });

  updateArrows();
}
