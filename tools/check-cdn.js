/**
 * tools/check-cdn.js — Vérifie que les librairies externes répondent.
 *
 * Le site charge quatre librairies depuis des CDN. Si l'une d'elles renvoie
 * une erreur, rien ne casse visiblement : les animations ou les icônes
 * disparaissent simplement, sans le moindre message. C'est exactement ce qui
 * s'est produit avec Lucide, dont l'URL épinglée pointait vers un fichier
 * inexistant — tous les boutons ronds se retrouvaient vides.
 *
 * À lancer après toute modification des URL dans lib/pages.php, et avant
 * chaque mise en ligne.
 *
 * Usage :  node tools/check-cdn.js
 */

const fs = require('fs');
const path = require('path');
const https = require('https');

const ROOT = path.resolve(__dirname, '..');

/** Les URL sont lues dans le gabarit : une seule source de vérité. */
function urlsExternes() {
  const gabarit = fs.readFileSync(path.join(ROOT, 'lib', 'pages.php'), 'utf8');
  const urls = [...gabarit.matchAll(/src="(https:\/\/[^"]+)"/g)].map(m => m[1]);
  return [...new Set(urls)];
}

function tester(url, redirections = 0) {
  return new Promise(resolve => {
    if (redirections > 5) {
      return resolve({ url, code: 0, taille: 0, erreur: 'trop de redirections' });
    }

    const req = https.get(url, res => {
      if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
        res.resume();
        return resolve(tester(res.headers.location, redirections + 1));
      }

      let taille = 0;
      res.on('data', c => { taille += c.length; });
      res.on('end', () => resolve({ url, code: res.statusCode, taille }));
    });

    req.setTimeout(20000, () => {
      req.destroy();
      resolve({ url, code: 0, taille: 0, erreur: 'délai dépassé' });
    });
    req.on('error', e => resolve({ url, code: 0, taille: 0, erreur: e.message }));
  });
}

(async () => {
  const urls = urlsExternes();
  if (!urls.length) {
    console.log('Aucune librairie externe référencée.');
    return;
  }

  console.log(`Vérification de ${urls.length} librairie(s) externe(s)…\n`);

  const resultats = await Promise.all(urls.map(u => tester(u)));
  let echecs = 0;

  for (const r of resultats) {
    const nom = r.url.replace(/^https:\/\//, '');
    // Une librairie qui répond 200 mais ne pèse presque rien est en réalité
    // une page d'erreur déguisée.
    const ok = r.code === 200 && r.taille > 1000;
    if (!ok) echecs++;

    const etat = r.erreur
      ? 'ERREUR : ' + r.erreur
      : (ok ? 'OK' : `HTTP ${r.code}, ${r.taille} octets`);

    console.log(`  ${ok ? 'ok   ' : 'ÉCHEC'} ${nom}`);
    if (!ok) console.log(`        ${etat}`);
    else console.log(`        ${Math.round(r.taille / 1024)} Ko`);
  }

  console.log('');
  if (echecs) {
    console.log(
      `${echecs} librairie(s) injoignable(s). Corrigez les URL dans ` +
      `lib/pages.php, puis relancez php tools/build.php.`
    );
    process.exit(1);
  }
  console.log('Toutes les librairies externes répondent.');
})();
