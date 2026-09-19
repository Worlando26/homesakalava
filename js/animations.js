// ═══════════════════════════════════════════════════════════
//  animations.js — Toutes les animations GSAP + ScrollTrigger
//  A. Hero intro   B. Story word-reveal   C. Section reveals
//  D. Trusted parallax   E. (carousel.js)   F. (faq.js)
//  G. Footer wordmark
// ═══════════════════════════════════════════════════════════

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// ─── A. HERO INTRO — révélation fluide ──────────────────────
function animateHeroIntro() {
  const heroImg   = document.querySelector('.hero__img-wrap img');
  const topbar    = document.querySelector('.hero__topbar');
  const badge     = document.querySelector('.hero__badge');
  const hint      = document.querySelector('.hero__scroll-hint');
  const headline  = document.querySelector('.hero__headline');
  const bottombar = document.querySelector('.hero__bottombar');

  if (prefersReducedMotion) {
    [topbar, badge, hint, headline, bottombar].forEach(e => {
      if (e) { e.style.opacity = '1'; e.style.transform = ''; }
    });
    return;
  }

  // Découpe le titre en mots pour la révélation mot par mot
  const titleEl = headline  ? headline.querySelector('[data-hero-headline-title]') : null;
  const subEl   = headline  ? headline.querySelector('[data-hero-headline-sub]')   : null;

  if (titleEl && titleEl.textContent.trim()) {
    const words = titleEl.textContent.trim().split(/\s+/);
    titleEl.innerHTML = words.map(w =>
      `<span class="word-wrap"><span class="word-inner">${w}</span></span>`
    ).join('&nbsp;');
  }

  const tl = gsap.timeline({ defaults: { ease: 'expo.out' } });

  // Image : flou → net, puis Ken Burns lent
  if (heroImg) {
    gsap.set(heroImg, { filter: 'blur(20px)', scale: 1.08 });
    tl.to(heroImg, {
      filter: 'blur(0px)', scale: 0.90, duration: 2.0,
      ease: 'power2.out',
      onComplete() {
        gsap.set(heroImg, { clearProps: 'filter' });
        gsap.to(heroImg, { scale: 0.96, duration: 22, ease: 'none' });
      },
    }, 0);
  }

  // Top bar — fondu pur, très doux
  if (topbar) {
    gsap.set(topbar, { opacity: 0 });
    tl.to(topbar, { opacity: 1, duration: 1.1 }, 0.5);
  }

  // Badge — fondu légèrement décalé
  if (badge) {
    gsap.set(badge, { opacity: 0 });
    tl.to(badge, { opacity: 1, duration: 0.9 }, 0.75);
  }

  // Sous-titre headline — fondu
  if (subEl) {
    gsap.set(subEl, { opacity: 0 });
    tl.to(subEl, { opacity: 1, duration: 1.0 }, 0.85);
  }

  // Titre — chaque mot monte depuis le bas (clip-path overflow hidden)
  if (titleEl) {
    const inners = titleEl.querySelectorAll('.word-inner');
    gsap.set(inners, { y: '105%' });
    tl.to(inners, {
      y: '0%',
      duration: 1.1,
      ease: 'expo.out',
      stagger: 0.09,
    }, 1.0);
  }

  // Barre bas — monte doucement
  if (bottombar) {
    gsap.set(bottombar, { y: 28, opacity: 0 });
    tl.to(bottombar, { y: 0, opacity: 1, duration: 1.0 }, 1.3);
  }

  // Texte vertical — fade discret + pulse
  if (hint) {
    gsap.set(hint, { opacity: 0 });
    tl.to(hint, { opacity: 0.38, duration: 0.8 }, 1.6);
    gsap.to(hint, {
      opacity: 0.1, duration: 2.2, ease: 'sine.inOut',
      yoyo: true, repeat: -1, delay: 2.8,
    });
  }
}

// ─── NAV scroll solid ──────────────────────────────────────
// La nav fixe reste masquée tant que le hero est visible (topbar interne = nav du hero).
// Elle apparaît uniquement quand l'utilisateur scroll au-delà du hero.
function animateNavScroll() {
  const nav = document.querySelector('.nav');
  if (!nav) return;

  // Cachée par défaut (le hero a son propre topbar)
  gsap.set(nav, { opacity: 0, y: -10, pointerEvents: 'none' });

  ScrollTrigger.create({
    trigger: '.hero',
    start: 'bottom 82%',
    onEnter: () => {
      gsap.to(nav, { opacity: 1, y: 0, duration: 0.4, ease: 'power2.out' });
      nav.style.pointerEvents = '';
      nav.classList.add('is-solid');
    },
    onLeaveBack: () => {
      gsap.to(nav, { opacity: 0, y: -10, duration: 0.3, ease: 'power2.in' });
      nav.style.pointerEvents = 'none';
      nav.classList.remove('is-solid');
    },
  });
}

// ─── B. STORY — révélation mot par mot ──────────────────────
function animateStoryText() {
  const textEl = document.querySelector('[data-story-text]');
  if (!textEl) return;

  if (prefersReducedMotion) {
    textEl.style.color = 'var(--c-ink)';
    return;
  }

  // Split en mots via des <span class="word">
  const rawText = textEl.textContent.trim();
  const words   = rawText.split(/\s+/);
  textEl.innerHTML = words.map(w => `<span class="word">${w}</span>`).join(' ');

  const wordEls = textEl.querySelectorAll('.word');
  gsap.set(wordEls, { color: 'var(--c-muted)' });

  const tl = gsap.timeline({
    scrollTrigger: {
      trigger: '.story',
      start: 'top 65%',
      end:   'bottom 55%',
      scrub: true,
    },
  });
  tl.to(wordEls, {
    color: 'var(--c-ink)',
    stagger: 0.08,
    ease: 'none',
  });
}

// ─── C. SECTION REVEALS ────────────────────────────────────
function animateSectionReveals() {
  if (prefersReducedMotion) return;

  // Kickers & titres de section
  const revealEls = document.querySelectorAll(
    '.kicker, .amazing__bento, .faq__title, .faq__intro, ' +
    '.reputation__score, .reputation__intro, .trusted__title, ' +
    '.resto__title, .activites__title, .acces__title'
  );
  revealEls.forEach(el => {
    gsap.fromTo(el,
      { y: 28, opacity: 0 },
      {
        y: 0, opacity: 1, duration: 0.7, ease: 'power2.out',
        scrollTrigger: { trigger: el, start: 'top 88%', toggleActions: 'play none none reverse' },
      }
    );
  });

  // Cartes bento — stagger depuis le bas
  const bentoCards = document.querySelectorAll('.amazing__bento .card');
  if (bentoCards.length) {
    gsap.fromTo(bentoCards,
      { y: 50, opacity: 0, scale: 0.97 },
      {
        y: 0, opacity: 1, scale: 1, duration: 0.75, stagger: 0.1, ease: 'power2.out',
        scrollTrigger: { trigger: '.amazing__bento', start: 'top 82%', toggleActions: 'play none none reverse' },
      }
    );
  }

  // Cartes carousel
  const idealCards = document.querySelectorAll('.ideal-card');
  if (idealCards.length) {
    gsap.fromTo(idealCards,
      { y: 40, opacity: 0 },
      {
        y: 0, opacity: 1, duration: 0.6, stagger: 0.08, ease: 'power2.out',
        scrollTrigger: { trigger: '.carousel__viewport', start: 'top 85%', toggleActions: 'play none none reverse' },
      }
    );
  }

  // Auteur témoignage
  const authorEl = document.querySelector('.reputation__bars');
  if (authorEl) {
    gsap.fromTo(authorEl,
      { y: 20, opacity: 0 },
      {
        y: 0, opacity: 1, duration: 0.6, ease: 'power2.out',
        scrollTrigger: { trigger: authorEl, start: 'top 88%', toggleActions: 'play none none reverse' },
      }
    );
  }

  // Items FAQ
  const faqItems = document.querySelectorAll('.faq-item');
  if (faqItems.length) {
    gsap.fromTo(faqItems,
      { y: 20, opacity: 0 },
      {
        y: 0, opacity: 1, duration: 0.5, stagger: 0.06, ease: 'power2.out',
        scrollTrigger: { trigger: '.faq__list', start: 'top 85%', toggleActions: 'play none none reverse' },
      }
    );
  }
}

// ─── D. TRUSTED — cartes flottantes parallaxe ──────────────
function animateTrustedFloats() {
  if (prefersReducedMotion) return;

  const floatsLeft  = document.querySelectorAll('.trusted__floats--left  .float-card');
  const floatsRight = document.querySelectorAll('.trusted__floats--right .float-card');
  const triggerEl   = document.querySelector('.trusted');

  if (!triggerEl) return;

  if (floatsLeft.length) {
    gsap.fromTo(floatsLeft,
      { x: -130, rotate: -7, opacity: 0 },
      {
        x: 0, rotate: 0, opacity: 1,
        stagger: 0.08,
        ease: 'power2.out',
        scrollTrigger: { trigger: triggerEl, start: 'top 75%', end: 'center 50%', scrub: 1 },
      }
    );
  }

  if (floatsRight.length) {
    gsap.fromTo(floatsRight,
      { x: 130, rotate: 7, opacity: 0 },
      {
        x: 0, rotate: 0, opacity: 1,
        stagger: 0.08,
        ease: 'power2.out',
        scrollTrigger: { trigger: triggerEl, start: 'top 75%', end: 'center 50%', scrub: 1 },
      }
    );
  }

  // Léger parallaxe Y sur la carte centrale
  const centerCard = document.querySelector('.trusted__card');
  if (centerCard) {
    gsap.fromTo(centerCard,
      { y: 40, opacity: 0 },
      {
        y: 0, opacity: 1, duration: 0.8, ease: 'power2.out',
        scrollTrigger: { trigger: triggerEl, start: 'top 70%', toggleActions: 'play none none reverse' },
      }
    );
  }

  // Amenity pills
  const pills = document.querySelectorAll('.amenity-pill');
  if (pills.length) {
    gsap.fromTo(pills,
      { y: 14, opacity: 0 },
      {
        y: 0, opacity: 1, stagger: 0.05, duration: 0.5, ease: 'power2.out',
        scrollTrigger: { trigger: '.trusted__amenities', start: 'top 88%', toggleActions: 'play none none reverse' },
      }
    );
  }
}

// ─── G. FOOTER WORDMARK reveal ──────────────────────────────
function animateFooterWordmark() {
  const wordmark = document.querySelector('.footer__wordmark');
  const wrap     = document.querySelector('.footer__wordmark-wrap');
  const ctaBlock = document.querySelector('.footer__cta-block');

  if (prefersReducedMotion) {
    if (wordmark) gsap.set(wordmark, { y: 0 });
    return;
  }

  if (wordmark && wrap) {
    // Reveal : masque y:110%→0 quand l'élément entre dans le viewport
    gsap.to(wordmark, {
      y: 0,
      duration: 1.1,
      ease: 'power3.out',
      scrollTrigger: {
        trigger: wrap,
        start: 'top bottom',
        toggleActions: 'play none none reverse',
      },
    });

    // Parallaxe : appliqué sur le WRAPPER (évite conflit de propriété y)
    gsap.to(wrap, {
      yPercent: -8,
      ease: 'none',
      scrollTrigger: {
        trigger: wrap,
        start: 'top bottom',
        end: 'bottom top',
        scrub: true,
      },
    });
  }

  // Footer CTA block reveal
  if (ctaBlock) {
    gsap.fromTo(ctaBlock,
      { y: 40, opacity: 0, scale: 0.97 },
      {
        y: 0, opacity: 1, scale: 1, duration: 0.8, ease: 'power2.out',
        scrollTrigger: { trigger: ctaBlock, start: 'top 88%', toggleActions: 'play none none reverse' },
      }
    );
  }
}

// ─── Hero image parallaxe au scroll ───────────────────────
// L'img-wrap est plus grand que le hero (top/bottom: -15%) pour absorber le déplacement.
function animateHeroParallax() {
  if (prefersReducedMotion) return;
  const imgWrap = document.querySelector('.hero__img-wrap');
  if (!imgWrap) return;
  gsap.to(imgWrap, {
    yPercent: 18,
    ease: 'none',
    scrollTrigger: {
      trigger: '.hero',
      start: 'top top',
      end: 'bottom top',
      scrub: true,
    },
  });
}

// ─── H. BOOKING — révélation du formulaire ─────────────────
function animateBookingSection() {
  if (prefersReducedMotion) return;

  const left     = document.querySelector('.booking__left');
  const formWrap = document.querySelector('.booking__form-wrap');
  const fields   = document.querySelectorAll('.booking__field');
  const infoCard = document.querySelector('.booking__info-card');

  if (!left) return;

  // Éléments texte gauche — stagger reveal
  const textEls = left.querySelectorAll('.kicker, .booking__subtitle, .booking__title, .booking__intro');
  if (textEls.length) {
    gsap.fromTo(textEls,
      { y: 28, opacity: 0 },
      {
        y: 0, opacity: 1, duration: 0.7, stagger: 0.1, ease: 'power2.out',
        scrollTrigger: { trigger: left, start: 'top 84%', toggleActions: 'play none none reverse' },
      }
    );
  }

  // Info card dark
  if (infoCard) {
    gsap.fromTo(infoCard,
      { y: 32, opacity: 0 },
      {
        y: 0, opacity: 1, duration: 0.75, ease: 'power2.out',
        scrollTrigger: { trigger: infoCard, start: 'top 88%', toggleActions: 'play none none reverse' },
      }
    );
  }

  // Carte formulaire
  if (formWrap) {
    gsap.fromTo(formWrap,
      { y: 44, opacity: 0, scale: 0.985 },
      {
        y: 0, opacity: 1, scale: 1, duration: 0.85, ease: 'power2.out',
        scrollTrigger: { trigger: formWrap, start: 'top 86%', toggleActions: 'play none none reverse' },
      }
    );
  }

  // Champs formulaire — stagger
  if (fields.length) {
    gsap.fromTo(fields,
      { y: 16, opacity: 0 },
      {
        y: 0, opacity: 1, duration: 0.4, stagger: 0.055, ease: 'power2.out',
        scrollTrigger: { trigger: formWrap, start: 'top 76%', toggleActions: 'play none none reverse' },
      }
    );
  }
}

// ─── Export principal ─────────────────────────────────────
function initAnimations() {
  gsap.registerPlugin(ScrollTrigger);

  animateHeroIntro();
  animateNavScroll();
  animateStoryText();
  animateSectionReveals();
  animateTrustedFloats();
  animateFooterWordmark();
  animateHeroParallax();
  animateBookingSection();
}
