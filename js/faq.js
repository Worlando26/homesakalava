// ═══════════════════════════════════════════════════════════
//  faq.js — Accordéon FAQ (un seul ouvert, GSAP maxHeight)
// ═══════════════════════════════════════════════════════════

function initFaq() {
  const items = document.querySelectorAll('.faq-item');
  if (!items.length) return;

  // Mesurer et définir maxHeight initial pour l'item ouvert
  items.forEach(item => {
    const body = item.querySelector('.faq-item__body');
    if (item.classList.contains('is-open')) {
      body.style.maxHeight = body.scrollHeight + 'px';
    } else {
      body.style.maxHeight = '0px';
    }
  });

  items.forEach(item => {
    const trigger = item.querySelector('.faq-item__trigger');
    const body    = item.querySelector('.faq-item__body');
    const icon    = item.querySelector('.faq-item__icon');

    trigger.addEventListener('click', () => {
      const isOpen = item.classList.contains('is-open');

      // Fermer tous
      items.forEach(other => {
        if (other === item) return;
        const otherBody = other.querySelector('.faq-item__body');
        gsap.to(otherBody, { maxHeight: 0, duration: 0.35, ease: 'power2.inOut' });
        other.classList.remove('is-open');
        other.querySelector('.faq-item__trigger').setAttribute('aria-expanded', 'false');
      });

      if (isOpen) {
        // Fermer celui-ci
        gsap.to(body, { maxHeight: 0, duration: 0.35, ease: 'power2.inOut' });
        item.classList.remove('is-open');
        trigger.setAttribute('aria-expanded', 'false');
      } else {
        // Ouvrir
        const targetH = body.scrollHeight;
        gsap.to(body, { maxHeight: targetH, duration: 0.4, ease: 'power2.out' });
        item.classList.add('is-open');
        trigger.setAttribute('aria-expanded', 'true');
      }
    });
  });
}
