/**
 * tools/sync-lucide-icons.js — Récupère la liste des icônes disponibles.
 *
 * Une icône mal nommée ne provoque aucune erreur : Lucide laisse simplement
 * un vide à la place. Un rond de bouton sans flèche dedans, par exemple.
 * Ce script télécharge le paquet réellement chargé par le site et en extrait
 * tous les noms, pour que tools/smoke-test.js puisse les vérifier.
 *
 * À relancer si la version de Lucide change dans lib/pages.php.
 *
 * Usage :  node tools/sync-lucide-icons.js
 */

const fs = require('fs');
const path = require('path');
const https = require('https');

const ROOT = path.resolve(__dirname, '..');

/** L'URL est lue dans le gabarit : une seule source de vérité pour la version. */
function urlDuPaquet() {
  const gabarit = fs.readFileSync(path.join(ROOT, 'lib', 'pages.php'), 'utf8');
  const m = gabarit.match(/src="(https:\/\/[^"]*lucide[^"]*\.js)"/);
  if (!m) {
    throw new Error("URL de Lucide introuvable dans lib/pages.php.");
  }
  return m[1];
}

function telecharger(url, redirections = 0) {
  return new Promise((resolve, reject) => {
    if (redirections > 5) return reject(new Error('Trop de redirections.'));

    https.get(url, res => {
      if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
        res.resume();
        return resolve(telecharger(res.headers.location, redirections + 1));
      }
      if (res.statusCode !== 200) {
        res.resume();
        return reject(new Error(`HTTP ${res.statusCode} sur ${url}`));
      }
      let data = '';
      res.setEncoding('utf8');
      res.on('data', c => { data += c; });
      res.on('end', () => resolve(data));
    }).on('error', reject);
  });
}

/**
 * « ChevronLeft » → « chevron-left », « Maximize2 » → « maximize-2 ».
 * C'est la convention que Lucide applique en sens inverse pour retrouver
 * une icône à partir de l'attribut data-lucide.
 */
function versKebab(pascal) {
  return pascal
    .replace(/([a-z0-9])([A-Z])/g, '$1-$2')
    .replace(/([A-Z]+)([A-Z][a-z])/g, '$1-$2')
    .replace(/([a-zA-Z])(\d)/g, '$1-$2')
    .toLowerCase();
}

(async () => {
  const url = urlDuPaquet();
  console.log('Téléchargement de ' + url);

  let source;
  try {
    source = await telecharger(url);
  } catch (e) {
    console.error('Échec : ' + e.message);
    console.error("La liste existante est conservée telle quelle.");
    process.exit(1);
  }

  // Les noms exportés apparaissent dans une longue liste « Nom:ref, ».
  const noms = new Set();
  for (const m of source.matchAll(/([A-Z][A-Za-z0-9]{2,}):[A-Za-z_$][A-Za-z0-9_$]{0,3},/g)) {
    noms.add(versKebab(m[1]));
  }

  if (noms.size < 500) {
    console.error(
      `Seulement ${noms.size} noms extraits : le format du paquet a changé. ` +
      `La liste existante est conservée.`
    );
    process.exit(1);
  }

  const liste = [...noms].sort();
  const sortie = path.join(ROOT, 'data', 'lucide-icons.txt');
  fs.writeFileSync(sortie, liste.join('\n') + '\n');

  console.log(`${liste.length} noms d'icônes écrits dans data/lucide-icons.txt`);
})();
