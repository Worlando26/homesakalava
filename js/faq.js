// ═══════════════════════════════════════════════════════════
//  faq.js — Accordéon des questions fréquentes.
//
//  Un seul panneau ouvert à la fois. L'animation passe par GSAP quand il est
//  disponible, sinon par une transition CSS : si le CDN est bloqué (ce qui
//  arrive sur une connexion filtrée), l'accordéon reste utilisable.
// ═══════════════════════════════════════════════════════════

function initFaq() {
  const items = document.querySelectorAll('.faq-item');
  if (!items.length) return;

  const hasGsap = typeof gsap !== 'undefined';

  /** Hauteur réelle du contenu, panneau replié comme déplié. */
  function contentHeight(body) {
    return body.scrollHeight;
  }

  function open(item, animate) {
    const body    = item.querySelector('.faq-item__body');
    const trigger = item.querySelector('.faq-item__trigger');
    const target  = contentHeight(body);

    item.classList.add('is-open');
    trigger.setAttribute('aria-expanded', 'true');

    if (hasGsap && animate) {
      gsap.to(body, { maxHeight: target, duration: 0.4, ease: 'power2.out' });
    } else {
      body.style.maxHeight = target + 'px';
    }
  }

  function close(item, animate) {
    const body    = item.querySelector('.faq-item__body');
    const trigger = item.querySelector('.faq-item__trigger');

    item.classList.remove('is-open');
    trigger.setAttribute('aria-expanded', 'false');

    if (hasGsap && animate) {
      gsap.to(body, { maxHeight: 0, duration: 0.35, ease: 'power2.inOut' });
    } else {
      body.style.maxHeight = '0px';
    }
  }

  // État initial : le premier panneau est ouvert, les autres repliés.
  items.forEach(item => {
    if (item.classList.contains('is-open')) {
      open(item, false);
    } else {
      close(item, false);
    }
  });

  items.forEach(item => {
    const trigger = item.querySelector('.faq-item__trigger');

    trigger.addEventListener('click', () => {
      const wasOpen = item.classList.contains('is-open');

      items.forEach(other => {
        if (other !== item) close(other, true);
      });

      if (wasOpen) {
        close(item, true);
      } else {
        open(item, true);
      }
    });
  });

  /**
   * La hauteur maximale est figée en pixels au moment de l'ouverture. Si la
   * largeur change ensuite — rotation du téléphone, ouverture du clavier —
   * le texte se réorganise sur plus de lignes et se retrouvait coupé.
   * On remesure donc après chaque redimensionnement.
   */
  let resizeTimer = null;
  let lastWidth   = window.innerWidth;

  window.addEventListener('resize', () => {
    // Sur mobile, le simple défilement fait apparaître/disparaître la barre
    // d'adresse et déclenche un resize vertical : on ignore ce cas.
    if (window.innerWidth === lastWidth) return;
    lastWidth = window.innerWidth;

    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => {
      items.forEach(item => {
        if (!item.classList.contains('is-open')) return;
        const body = item.querySelector('.faq-item__body');
        if (typeof gsap !== 'undefined') gsap.killTweensOf(body);
        body.style.maxHeight = contentHeight(body) + 'px';
      });
    }, 150);
  });
}
