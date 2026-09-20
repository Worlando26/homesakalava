/**
 * smoke.js — Exécute render.js contre un DOM minimal reconstruit depuis
 * index.html, pour détecter toute erreur JS au rendu sans navigateur ni
 * dépendance externe.
 *
 * Ce n'est pas un moteur de rendu : il implémente exactement ce que
 * js/render.js utilise. Objectif : faire remonter les crashs, pas peindre.
 */
const fs = require('fs');
const path = require('path');
const ROOT = process.argv[2] || '.';

// ─── Mini DOM ──────────────────────────────────────────────────────────
class ClassList {
  constructor(node) { this.node = node; this._s = new Set(); }
  add(...c) { c.forEach(x => x && this._s.add(x)); }
  remove(...c) { c.forEach(x => this._s.delete(x)); }
  contains(c) { return this._s.has(c); }
  toggle(c, force) {
    const on = force === undefined ? !this._s.has(c) : force;
    on ? this._s.add(c) : this._s.delete(c);
    return on;
  }
  get value() { return [...this._s].join(' '); }
}

class Node {
  constructor(tag) {
    this.tagName = String(tag).toUpperCase();
    this.children = [];
    this.attrs = {};
    this.classList = new ClassList(this);
    this.style = new Proxy({ setProperty(){}, cssText:'' }, { set(t,k,v){ t[k]=v; return true; } });
    this.dataset = {};
    this._text = '';
    this.parentNode = null;
  }
  set className(v) { String(v).split(/\s+/).forEach(c => c && this.classList.add(c)); }
  get className() { return this.classList.value; }
  set textContent(v) { this._text = v == null ? '' : String(v); this.children = []; }
  get textContent() { return this._text + this.children.map(c => c.textContent).join(''); }
  set innerHTML(v) { this._html = v; }
  get innerHTML() { return this._html || ''; }
  appendChild(c) { if (!c) throw new TypeError('appendChild(null)'); c.parentNode = this; this.children.push(c); return c; }
  removeChild(c) { this.children = this.children.filter(x => x !== c); }
  remove() { if (this.parentNode) this.parentNode.removeChild(this); }
  setAttribute(k, v) {
    this.attrs[k] = String(v);
    if (k.startsWith('data-')) this.dataset[k.slice(5).replace(/-(\w)/g, (_, c) => c.toUpperCase())] = String(v);
  }
  getAttribute(k) { return this.attrs[k] ?? null; }
  hasAttribute(k) { return k in this.attrs; }
  addEventListener() {}
  focus() {}
  // Les propriétés IDL (img.alt, a.href…) doivent se refléter dans les
  // attributs, sinon les contrôles d'accessibilité passent à côté.
  get alt()      { return this.attrs.alt ?? ''; }
  set alt(v)     { this.setAttribute('alt', v); }
  get src()      { return this.attrs.src ?? ''; }
  set src(v)     { this.setAttribute('src', v); }
  get href()     { return this.attrs.href ?? ''; }
  set href(v)    { this.setAttribute('href', v); }
  get srcset()   { return this.attrs.srcset ?? ''; }
  set srcset(v)  { this.setAttribute('srcset', v); }
  get sizes()    { return this.attrs.sizes ?? ''; }
  set sizes(v)   { this.setAttribute('sizes', v); }
  get id()       { return this.attrs.id ?? ''; }
  set id(v)      { this.setAttribute('id', v); }
  get type()     { return this.attrs.type ?? ''; }
  set type(v)    { this.setAttribute('type', v); }
  get loading()  { return this.attrs.loading ?? ''; }
  set loading(v) { this.setAttribute('loading', v); }
  get decoding() { return this.attrs.decoding ?? ''; }
  set decoding(v){ this.setAttribute('decoding', v); }
  get width()    { return this.attrs.width ?? ''; }
  set width(v)   { this.setAttribute('width', v); }
  get height()   { return this.attrs.height ?? ''; }
  set height(v)  { this.setAttribute('height', v); }
  // Recherche descendante, suffisante pour les sélecteurs utilisés
  querySelector(sel) { return this.querySelectorAll(sel)[0] || null; }
  querySelectorAll(sel) {
    const out = [];
    const match = (n) => sel.split(',').some(s => matchOne(n, s.trim()));
    const walk = (n) => { n.children.forEach(c => { if (match(c)) out.push(c); walk(c); }); };
    walk(this);
    return out;
  }
}

function matchOne(node, sel) {
  let m;
  if ((m = sel.match(/^\[data-([a-z0-9-]+)\]$/))) return `data-${m[1]}` in node.attrs;
  if (sel.startsWith('.')) return node.classList.contains(sel.slice(1));
  if (sel.startsWith('#')) return node.attrs.id === sel.slice(1);
  return node.tagName === sel.toUpperCase();
}

// ─── Parse d'index.html : on ne retient que la structure et les data-* ──
function parseHtml(html) {
  const root = new Node('root');
  const stack = [root];
  const tagRe = /<(\/?)([a-zA-Z][\w-]*)((?:\s+[^\s=>/]+(?:\s*=\s*(?:"[^"]*"|'[^']*'|[^\s>]+))?)*)\s*(\/?)>/g;
  const voids = new Set(['meta','link','img','br','hr','input','source','path','polyline','line','circle','rect','use']);
  let m;
  let lastIndex = 0;
  while ((m = tagRe.exec(html))) {
    const [, close, tag, attrStr, selfClose] = m;

    // Texte situé entre deux balises : sans lui, les intitulés écrits en dur
    // dans index.html seraient invisibles pour les contrôles d'accessibilité.
    const between = html.slice(lastIndex, m.index).trim();
    if (between && !/^<!--/.test(between)) {
      const t = new Node('#text');
      t.textContent = between.replace(/\s+/g, ' ');
      stack[stack.length - 1].appendChild(t);
    }
    lastIndex = tagRe.lastIndex;

    if (close) { if (stack.length > 1) stack.pop(); continue; }
    const node = new Node(tag);
    const attrRe = /([^\s=]+)(?:\s*=\s*(?:"([^"]*)"|'([^']*)'|([^\s>]+)))?/g;
    let a;
    while ((a = attrRe.exec(attrStr))) {
      const k = a[1]; if (!k) continue;
      node.setAttribute(k, a[2] ?? a[3] ?? a[4] ?? '');
      if (k === 'class') node.className = a[2] ?? a[3] ?? a[4] ?? '';
    }
    stack[stack.length - 1].appendChild(node);
    if (!selfClose && !voids.has(tag.toLowerCase())) stack.push(node);
  }
  return root;
}

const PAGE = process.argv[3] || 'index.html';
const html = fs.readFileSync(path.join(ROOT, PAGE), 'utf8');
const tree = parseHtml(html);

const document = {
  documentElement: new Node('html'),
  createElement: (t) => new Node(t),
  createElementNS: (_, t) => new Node(t),
  createTextNode: (t) => { const n = new Node('#text'); n.textContent = t; return n; },
  querySelector: (s) => tree.querySelector(s),
  querySelectorAll: (s) => tree.querySelectorAll(s),
  getElementById: (id) => tree.querySelector('#' + id),
};

// rooms.js lit document.body.dataset.page pour savoir quelle page rendre.
document.body = tree.querySelectorAll('BODY')[0] || new Node('body');

global.document = document;
global.window = {
  matchMedia: () => ({ matches: false }),
  location: { hash: '' },
  addEventListener(){},
  requestAnimationFrame(){},
  scrollTo(){},
};
global.navigator = { userAgent: 'smoke' };
global.gsap = undefined;

// ─── Exécution ─────────────────────────────────────────────────────────
const warnings = [];
const origWarn = console.warn;
console.warn = (...a) => warnings.push(a.join(' '));

try {
  // Le config.js chargé est celui que la page désigne vraiment, résolu
  // depuis son propre dossier. Charger celui de la racine reviendrait à
  // tester toutes les langues avec le contenu français.
  const cfgRef = (html.match(/<script[^>]+src="([^"]*config\.js)"/) || [])[1] || 'config.js';
  const cfgPath = path.resolve(path.dirname(path.join(ROOT, PAGE)), cfgRef);

  const cfg = fs.readFileSync(cfgPath, 'utf8');
  const rnd = fs.readFileSync(path.join(ROOT, 'js/render.js'), 'utf8');
  const rms = fs.readFileSync(path.join(ROOT, 'js/rooms.js'), 'utf8');
  // eslint-disable-next-line no-eval
  eval(cfg + '\n' + rnd + '\n' + rms + '\nrenderAll();\nglobalThis.__CONFIG = CONFIG;');
} catch (e) {
  console.warn = origWarn;
  console.error('ÉCHEC DU RENDU :', e.message);
  console.error(e.stack.split('\n').slice(1, 5).join('\n'));
  process.exit(1);
}
console.warn = origWarn;

// ─── Contrôles de contenu ──────────────────────────────────────────────
const countNodes = (n) => n.children.reduce((a,c)=>a+1+countNodes(c),0);
const problems = [];

// 1. Aucun hook ne doit rester vide
tree.querySelectorAll('[data-nav-logo],[data-hero-headline-title],[data-story-text],[data-rep-title],[data-resto-title],[data-act-title],[data-acces-title],[data-footer-copyright],[data-trusted-title]')
  .forEach(n => {
    if (!n.textContent.trim()) {
      problems.push('Hook vide après rendu : ' + Object.keys(n.attrs).find(k => k.startsWith('data-')));
    }
  });

// 2. Toute image doit porter un alt non vide
let imgs = 0, noAlt = 0;
tree.querySelectorAll('IMG').forEach(n => {
  imgs++;
  if (!n.attrs.alt || !n.attrs.alt.trim()) noAlt++;
});
if (noAlt) problems.push(`${noAlt} image(s) sans attribut alt sur ${imgs}`);

// 3. Aucun reliquat de l'ancien hôtel
const rendered = tree.textContent;
['Talinjoo', 'Fort Dauphin', 'Le Port', 'leporthotel', 'Lorem', 'picsum', 'Marie Dupont']
  .forEach(bad => { if (rendered.includes(bad)) problems.push('Reliquat détecté dans le rendu : ' + bad); });

// 4. Aucun numéro ou e-mail inventé
const invented = rendered.match(/\+261[\d\sX]+|[a-z0-9._-]+@[a-z0-9.-]+\.[a-z]{2,}/gi) || [];
invented.filter(v => !v.includes('exemple.com')).forEach(v => problems.push('Coordonnée en dur dans le rendu : ' + v));

// 5. Hiérarchie des titres : un seul h1, et jamais de niveau saute
const headings = [];
(function collect(n) {
  n.children.forEach(c => {
    const m = /^H([1-6])$/.exec(c.tagName);
    if (m) headings.push({ level: +m[1], text: c.textContent.trim().slice(0, 40) });
    collect(c);
  });
})(tree);

const h1s = headings.filter(h => h.level === 1);
if (h1s.length !== 1) problems.push(`${h1s.length} balise(s) h1 (il en faut exactement une)`);

let previous = 1;
headings.forEach(h => {
  if (h.level > previous + 1) {
    problems.push(`Niveau de titre saute : h${previous} puis h${h.level} ("${h.text}")`);
  }
  previous = h.level;
});

// 6. Tout lien doit avoir un intitulé perceptible (texte ou aria-label)
let mute = 0;
tree.querySelectorAll('A').forEach(a => {
  const hasText  = a.textContent.trim().length > 0;
  const hasLabel = (a.attrs['aria-label'] || '').trim().length > 0;
  if (!hasText && !hasLabel) mute++;
});
if (mute) problems.push(`${mute} lien(s) sans intitulé lisible (ni texte, ni aria-label)`);

// 7. Tout bouton doit être de type explicite : sans type, un <button> dans
//    un formulaire le soumet, ce qui provoque des envois accidentels
let untyped = 0;
tree.querySelectorAll('BUTTON').forEach(b => { if (!b.attrs.type) untyped++; });
if (untyped) problems.push(`${untyped} bouton(s) sans attribut type`);

// 8. Chaque fichier local référencé doit exister, résolu depuis le dossier
//    de la page. C'est ce qui attrape une page de langue qui pointerait sur
//    un fichier de la racine, ou un chemin relatif oublié.
const pageDir = path.dirname(path.join(ROOT, PAGE));
const refs = [
  ...[...html.matchAll(/\ssrc="([^"]+)"/g)].map(m => m[1]),
  ...[...html.matchAll(/\shref="([^"]+)"/g)].map(m => m[1]),
];
const manquants = new Set();
refs.forEach(ref => {
  if (/^(https?:|mailto:|tel:|data:|#)/.test(ref)) return;
  const cible = path.resolve(pageDir, ref.split('#')[0].split('?')[0]);
  if (!fs.existsSync(cible)) manquants.add(ref);
});
manquants.forEach(r => problems.push(`Fichier référencé introuvable : ${r}`));

// 9. La page doit charger le config.js de SON dossier : chaque langue a le
//    sien. Un préfixe de chemin ici afficherait le site en français partout.
const cfgSrc = (html.match(/<script[^>]+src="([^"]*config\.js)"/) || [])[1];
if (cfgSrc && cfgSrc !== 'config.js') {
  problems.push(`config.js chargé depuis « ${cfgSrc} » au lieu du dossier de la page`);
}

// 10. La langue déclarée par <html lang> doit être celle du contenu chargé.
const htmlLang = (html.match(/<html lang="([^"]+)"/) || [])[1];
const cfgCharge = globalThis.__CONFIG;
if (htmlLang && cfgCharge && cfgCharge.lang && htmlLang !== cfgCharge.lang) {
  problems.push(`<html lang="${htmlLang}"> mais le contenu chargé est en « ${cfgCharge.lang} »`);
}

// 11. Les icônes demandées à Lucide doivent porter un nom connu.
//     Une icône mal nommée ne lève aucune erreur : elle laisse juste un vide.
const iconNames = new Set(
  [...html.matchAll(/data-lucide="([^"]+)"/g)].map(m => m[1])
);
tree.querySelectorAll('I').forEach(n => {
  if (n.attrs['data-lucide']) iconNames.add(n.attrs['data-lucide']);
});
const iconFile = path.join(ROOT, 'data', 'lucide-icons.txt');
if (fs.existsSync(iconFile)) {
  const connues = new Set(
    fs.readFileSync(iconFile, 'utf8').split(/\r?\n/).map(s => s.trim()).filter(Boolean)
  );
  const inconnues = [...iconNames].filter(n => !connues.has(n));
  inconnues.forEach(n => problems.push(`Icône inconnue de Lucide : « ${n} »`));
}

console.log(`Titres : ${headings.map(h => 'h' + h.level).join(' ')}`);

console.log(`${PAGE} — ${countNodes(tree)} nœuds, ${imgs} <img>.`);
if (warnings.length) console.log('Avertissements :', warnings.join(' | '));
if (problems.length) {
  console.log('\nPROBLÈMES :');
  problems.forEach(p => console.log('  ✗ ' + p));
  process.exit(1);
}
console.log('Aucun problème détecté.');
