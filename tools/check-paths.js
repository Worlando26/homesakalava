/**
 * tools/check-paths.js — Contrôle des chemins pour un hébergement statique.
 *
 * XAMPP sous Windows ne distingue pas les majuscules des minuscules ; GitHub
 * Pages et la plupart des serveurs Linux, si. Un fichier appelé « Photo.JPG »
 * et référencé « photo.jpg » marche en local et renvoie une erreur 404 en
 * ligne. Ce script compare chaque référence au nom réel du fichier.
 *
 * Il signale aussi les chemins commençant par « / » : ils pointent sur la
 * racine du domaine, ce qui casse dès que le site vit dans un sous-dossier —
 * le cas de GitHub Pages, qui sert sous /nom-du-depot/.
 *
 * Usage :  node tools/check-paths.js
 */

const fs = require('fs');
const path = require('path');

const ROOT = path.resolve(__dirname, '..');
process.chdir(ROOT);

const IGNORES = new Set([
  '.git', 'node_modules', '_archive_ancien_site',
  'add_img', 'photos_sakalava', 'livraison',
]);

/** Index de tous les fichiers réels, indexés en minuscules. */
const reels = new Map();
(function scan(dir) {
  for (const e of fs.readdirSync(dir, { withFileTypes: true })) {
    if (IGNORES.has(e.name)) continue;
    const p = path.join(dir, e.name);
    if (e.isDirectory()) {
      scan(p);
    } else {
      const rel = path.relative(ROOT, p).split(path.sep).join('/');
      reels.set(rel.toLowerCase(), rel);
    }
  }
})(ROOT);

const PAGES = [
  'index.html', 'chambres.html', 'activites.html', 'acces.html', '404.html',
  'en/index.html', 'en/chambres.html', 'en/activites.html', 'en/acces.html', 'en/404.html',
];

const absolus   = new Set();
const casse     = new Set();
const manquants = new Set();
let verifiees   = 0;

/** Contrôle une référence, résolue depuis le dossier de la page. */
function verifier(source, ref, dossier) {
  if (/^(https?:|mailto:|tel:|data:|#|javascript:)/i.test(ref)) return;

  if (ref.startsWith('/')) {
    absolus.add(`${source} → ${ref}`);
    return;
  }

  verifiees++;
  const propre = ref.split('#')[0].split('?')[0];
  if (propre === '') return;

  const cible = path.normalize(path.join(dossier, propre)).split(path.sep).join('/');

  if (!fs.existsSync(path.join(ROOT, cible))) {
    manquants.add(`${source} → ${ref}`);
    return;
  }

  const vrai = reels.get(cible.toLowerCase());
  if (vrai && vrai !== cible) {
    casse.add(`${source} → ${ref}\n       nom réel : ${vrai}`);
  }
}

// ─── Pages HTML ───────────────────────────────────────────────────────
for (const page of PAGES) {
  if (!fs.existsSync(page)) continue;
  const html = fs.readFileSync(page, 'utf8');
  const dossier = path.dirname(page);

  // La balise <base> porte une URL, pas un chemin de fichier.
  const sansBase = html.replace(/<base\b[^>]*>/gi, '');

  for (const m of sansBase.matchAll(/(?:src|href)="([^"]+)"/g)) {
    verifier(page, m[1], dossier);
  }
  for (const m of sansBase.matchAll(/srcset="([^"]+)"/g)) {
    for (const part of m[1].split(',')) {
      verifier(page, part.trim().split(/\s+/)[0], dossier);
    }
  }
}

// ─── Images citées dans les fichiers de contenu ───────────────────────
for (const f of ['config.js', 'en/config.js']) {
  if (!fs.existsSync(f)) continue;
  const src = fs.readFileSync(f, 'utf8');
  const dossier = path.dirname(f);

  for (const m of src.matchAll(/"((?:\.\.\/)?(?:uploads|assets)\/[^"]+)"/g)) {
    // Une valeur de srcset contient plusieurs chemins séparés par des virgules.
    for (const part of m[1].split(',')) {
      verifier(f, part.trim().split(/\s+/)[0], dossier);
    }
  }
}

// ─── url() des feuilles de style ──────────────────────────────────────
for (const f of fs.readdirSync('css')) {
  if (!f.endsWith('.css')) continue;
  const src = fs.readFileSync(path.join('css', f), 'utf8');
  for (const m of src.matchAll(/url\(\s*['"]?([^'")]+)['"]?\s*\)/g)) {
    verifier('css/' + f, m[1], 'css');
  }
}

// ─── Rapport ──────────────────────────────────────────────────────────
console.log(`\n${verifiees} référence(s) locale(s) vérifiée(s)\n`);

let souci = 0;

function bloc(titre, ensemble, explication) {
  if (ensemble.size === 0) {
    console.log(`  ok    ${titre} : aucun`);
    return;
  }
  souci += ensemble.size;
  console.log(`  ÉCHEC ${titre} : ${ensemble.size}`);
  console.log(`        ${explication}`);
  for (const l of ensemble) console.log('     ' + l);
}

bloc('Chemins absolus', absolus,
  'Un chemin commençant par « / » vise la racine du domaine. '
  + 'Il casse dès que le site vit dans un sous-dossier.');

bloc('Erreurs de casse', casse,
  'Fonctionne sous Windows, renvoie une erreur 404 sur un serveur Linux.');

bloc('Fichiers manquants', manquants,
  'La référence ne correspond à aucun fichier présent.');

console.log('');
if (souci === 0) {
  console.log('Tous les chemins sont relatifs et correspondent à des fichiers existants.');
} else {
  console.log(`${souci} problème(s) à corriger avant une mise en ligne statique.`);
  process.exit(1);
}
