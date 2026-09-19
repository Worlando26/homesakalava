// ═══════════════════════════════════════════════════════════
//  main.js — Point d'entrée : init Lenis + render + modules
// ═══════════════════════════════════════════════════════════

(function () {
  'use strict';

  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let lenis = null;

  // ─── Helper scroll fluide (utilisé partout) ───────────────
  function smoothScrollTo(target) {
    if (!target) return;
    if (lenis) {
      lenis.scrollTo(target, { offset: -72, duration: 1.4 });
    } else {
      window.scrollTo({ top: target.offsetTop - 72, behavior: 'smooth' });
    }
  }

  // Si GSAP n a pas pu se charger (CDN bloque), le CSS prend le relais
  // pour les quelques animations indispensables.
  if (typeof gsap === 'undefined') {
    document.documentElement.classList.add('no-gsap');
  }

  // ─── 1. Injecter le contenu (config → DOM) ────────────────
  renderAll();

  // ─── 2. data-section → id (ancres) ───────────────────────
  document.querySelectorAll('[data-section]').forEach(section => {
    if (!section.id) section.id = section.dataset.section;
  });

  // ─── 3. Initialiser les icônes Lucide ─────────────────────
  if (typeof lucide !== 'undefined') {
    lucide.createIcons();
  }

  // ─── 4. Smooth scroll Lenis ──────────────────────────────
  if (!reducedMotion && typeof Lenis !== 'undefined' && typeof gsap !== 'undefined') {
    lenis = new Lenis({
      duration: 1.2,
      easing: t => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
      smoothWheel: true,
    });

    // Connecter Lenis → ScrollTrigger
    lenis.on('scroll', () => {
      if (typeof ScrollTrigger !== 'undefined') ScrollTrigger.update();
    });

    // Piloter Lenis via GSAP ticker (pas de RAF séparé)
    gsap.ticker.add(time => lenis.raf(time * 1000));
    gsap.ticker.lagSmoothing(0);

    // Smooth scroll sur les ancres
    document.querySelectorAll('a[href^="#"]').forEach(a => {
      a.addEventListener('click', e => {
        const targetId = a.getAttribute('href').slice(1);
        if (!targetId) return;
        const target = document.getElementById(targetId);
        if (target) {
          e.preventDefault();
          smoothScrollTo(target);
        }
      });
    });
  }

  // ─── 5. Animations GSAP ──────────────────────────────────
  if (typeof gsap !== 'undefined') {
    initAnimations();
  }

  // ─── 6. Carousel ─────────────────────────────────────────
  initCarousel();

  // ─── 7. FAQ accordéon ────────────────────────────────────
  initFaq();

  // ─── 8. Burger menu mobile ───────────────────────────────
  (function initBurger() {
    const burger = document.querySelector('[data-burger]');
    const drawer = document.querySelector('[data-drawer]');
    const nav    = document.querySelector('.nav');
    if (!burger || !drawer) return;

    burger.addEventListener('click', () => {
      const isOpen = drawer.classList.toggle('is-open');
      burger.classList.toggle('is-open', isOpen);
      burger.setAttribute('aria-expanded', String(isOpen));
      if (isOpen && nav) nav.classList.add('is-solid');
    });

    // Echap referme le menu et redonne le focus au bouton : sans cela,
    // un utilisateur au clavier reste piege dans le tiroir.
    document.addEventListener('keydown', e => {
      if (e.key === 'Escape' && drawer.classList.contains('is-open')) {
        drawer.classList.remove('is-open');
        burger.classList.remove('is-open');
        burger.setAttribute('aria-expanded', 'false');
        burger.focus();
      }
    });

    drawer.querySelectorAll('a').forEach(a => {
      a.addEventListener('click', () => {
        drawer.classList.remove('is-open');
        burger.classList.remove('is-open');
        burger.setAttribute('aria-expanded', 'false');
      });
    });
  })();

  // ─── 9. Bouton RÉSERVER du hero → #booking ───────────────
  (function initHeroBookBtn() {
    const btn = document.querySelector('[data-hero-book]');
    if (!btn) return;
    btn.addEventListener('click', () => {
      smoothScrollTo(document.getElementById('booking'));
    });
  })();

})();
