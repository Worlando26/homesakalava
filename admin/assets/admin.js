/**
 * admin.js — Le strict nécessaire, sans librairie.
 *
 * Trois comportements : confirmation avant suppression, aperçu de l'image
 * choisie avant envoi, et garde-fou si on quitte une page sans enregistrer.
 */
(function () {
  'use strict';

  // ── 1. Confirmation avant toute suppression ─────────────────────────
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-confirm]');
    if (!btn) return;
    if (!window.confirm(btn.getAttribute('data-confirm'))) {
      e.preventDefault();
      e.stopPropagation();
    } else {
      // La suppression est acceptée : on laisse partir le formulaire sans
      // déclencher l'avertissement « modifications non enregistrées ».
      dirty = false;
    }
  });

  // ── 2. Aperçu de l'image avant envoi ────────────────────────────────
  document.querySelectorAll('input[type="file"][data-preview]').forEach(function (input) {
    const target = document.getElementById(input.getAttribute('data-preview'));
    if (!target) return;

    input.addEventListener('change', function () {
      const file = input.files && input.files[0];
      if (!file) return;

      if (!/^image\/(jpeg|png|webp)$/.test(file.type)) {
        alert('Format non accepté. Choisissez une image JPEG, PNG ou WebP.');
        input.value = '';
        return;
      }
      if (file.size > 8 * 1024 * 1024) {
        alert('Cette image dépasse 8 Mo. Elle sera refusée : choisissez-en une plus légère.');
        input.value = '';
        return;
      }

      const url = URL.createObjectURL(file);
      target.src = url;
      target.hidden = false;
      target.addEventListener('load', function () { URL.revokeObjectURL(url); }, { once: true });
    });
  });

  // ── 3. Avertissement si on quitte sans enregistrer ──────────────────
  let dirty = false;
  document.querySelectorAll('form[data-guard]').forEach(function (form) {
    form.addEventListener('input',  function () { dirty = true; });
    form.addEventListener('change', function () { dirty = true; });
    form.addEventListener('submit', function () { dirty = false; });
  });

  window.addEventListener('beforeunload', function (e) {
    if (!dirty) return;
    e.preventDefault();
    e.returnValue = '';
  });

  // ── 4. Aperçu de la photo sélectionnée dans un menu déroulant ───────
  document.querySelectorAll('select[data-picker]').forEach(function (select) {
    const img = document.getElementById(select.getAttribute('data-picker'));
    if (!img) return;

    function sync() {
      const opt = select.selectedOptions[0];
      const src = opt && opt.getAttribute('data-thumb');
      if (src) {
        img.src = src;
        img.hidden = false;
      } else {
        img.removeAttribute('src');
        img.hidden = true;
      }
    }
    select.addEventListener('change', sync);
    sync();
  });
})();
