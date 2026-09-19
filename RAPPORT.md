# Rapport de mission — Site Home Sakalava

**Date :** 19 septembre 2026
**Branche de travail :** `refonte-home-sakalava` (la branche `main` n'a pas été touchée)
**Commits :** 6 commits de travail au-dessus d'un commit de référence

> **Une seconde passe a suivi la mission initiale** : campagne de tests,
> correction des bugs trouvés, et préparation à l'hébergement.
> Elle est décrite dans la **section 8**, en fin de rapport. Si vous ne
> lisez qu'une chose, lisez celle-là : elle contient une faille de sécurité
> corrigée et la marche à suivre pour mettre le site en ligne.

---

## 1. État des lieux du projet tel que trouvé

### Ce qui existait

Un site vitrine **une page**, en HTML / CSS / JavaScript natifs, sans framework
ni système de build. Le projet suivait une consigne de construction trouvée
dans `.claude/skills/BUILD_STAYGO.md` (gabarit « StayGo »).

| Élément | État constaté |
|---|---|
| Stack | HTML/CSS/JS natifs. Aucune dépendance npm réelle (`package.json` quasi vide, nommé `leport`). |
| Moteur de template | Aucun. `index.html` est un squelette vide, rempli au chargement par `js/render.js` via des attributs `data-*`. |
| Contenu | Centralisé dans `config.js` (objet `CONFIG`). C'était déjà, de fait, un fichier de contenu. |
| Base de données | Aucune. |
| Build | Aucun. Les fichiers sont servis tels quels. |
| Librairies | GSAP 3.12.5, ScrollTrigger, Lenis 1.1.13, Lucide — toutes par CDN. |
| Serveur | Le projet est dans `C:\xampp\htdocs\`, donc PHP 8.2 disponible (GD, fileinfo, exif, sessions, Argon2id). |
| Git | **Aucun dépôt.** Le projet n'était pas versionné. |

### Le problème central

**Le site ne parlait pas du bon hôtel.** Tout le contenu concernait
« Talinjoo Hotel » à Fort Dauphin, avec des reliquats d'un troisième
établissement (« Le Port Hôtel », `contact@leporthotel.mg` dans la FAQ).
`about.txt` décrit Home Sakalava, une maison d'hôtes à Nosy Be — autre
établissement, autre ville, autre positionnement. Il ne s'agissait donc pas
d'ajuster des textes mais de reprendre l'intégralité du contenu.

### État d'avancement réel des sections

| Section | État |
|---|---|
| Hero « glassmorphism » | Terminée, soignée |
| Notre histoire | Terminée |
| Bento « nos espaces » | Terminée |
| Carrousel chambres | Terminé, mais 6 chambres fictives |
| Nos atouts | Terminée |
| Témoignage | **Faux avis inventé** signé « Marie Dupont », avatar tiré de picsum.photos |
| FAQ | Terminée, mais réponses contradictoires (deux hôtels cités) |
| Réservation | Formulaire **factice** : `setTimeout` de 1,2 s puis « Demande envoyée ! » sans qu'aucun message ne parte |
| Footer | Terminé |
| Restaurant · Activités · Accès | **Inexistantes** |

### Bugs et problèmes relevés en phase 1

1. **Formulaire mensonger** — il affichait « Demande envoyée » sans rien envoyer.
2. **Faux témoignage client** — personne et citation inventées.
3. **Coordonnées inventées** — `+261 32 XX XXX XX`, `contact@talinjoohotel.mg`.
4. `hero { height: 100vh }` — saut de mise en page à chaque apparition de la barre d'adresse mobile.
5. **Le nom de l'hôtel disparaît sur mobile** — `.hero__brand { display: none }` sous 768 px, sans repli.
6. **`lucide@latest` non épinglé** — une version majeure côté CDN pouvait casser le site du jour au lendemain.
7. **CSS mort** — `.hero__content`, `.hero__search`, `.hero__watermark` : classes de l'ancien hero, supprimées depuis.
8. **`carousel.js` et `faq.js` appelaient `gsap` sans garde** — CDN bloqué = page cassée, alors que `main.js` gardait bien ses propres appels.
9. **FAQ tronquée après rotation de l'écran** — `maxHeight` figé en pixels à l'ouverture, jamais remesuré.
10. **Contrastes insuffisants** — 5 paires de couleurs sous le seuil AA (mesuré).
11. **Aucun SEO** — pas de `<title>` utile, ni meta description, ni Open Graph, ni favicon, ni sitemap, ni données structurées.
12. **24 Mo de fichiers morts** à la racine, dont une `video.mp4` de 23 Mo référencée nulle part.

### Qualité du responsive existant

Correcte dans l'ensemble : quatre points de rupture cohérents (639, 767, 1023 px),
grilles qui s'effondrent proprement, carrousel tactile fonctionnel. Les
faiblesses étaient ponctuelles (points 4, 5 et 10 ci-dessus), pas structurelles.

### Les photos

`photos_sakalava/` contenait **deux** photos, pas davantage :

- `hero.jpeg` — coucher de soleil sur la plage à marée basse, arbre isolé, île au loin. Excellente.
- `WhatsApp Image 2026-09-19...jpeg` — chambre avec lit à baldaquin, moustiquaire, vanneries, mobilier bois, mur ocre. Exactement ce que décrit `about.txt`.

Les huit autres images à la racine appartenaient à Talinjoo Hotel.

---

## 2. Décisions prises, une par une

C'est la partie importante. Chaque décision est présentée avec les options
écartées et la raison du choix.

---

### D1 — Créer le dépôt git, puisqu'il n'existait pas

**Contexte.** La consigne impose de travailler sur une branche dédiée et de ne
pas toucher à la principale. Or le projet n'était pas versionné du tout.

**Options.** (a) Travailler sans git et le signaler comme blocage.
(b) Initialiser un dépôt, figer l'état trouvé sur `main`, puis brancher.

**Choix : (b).** Refuser de travailler faute de dépôt aurait bloqué toute la
mission pour une cause triviale. J'ai donc fait un commit de référence
(`9eef186`) qui capture le projet exactement tel que trouvé, créé
`refonte-home-sakalava`, et tout mon travail vit sur cette branche. `main`
contient l'état d'origine, intact. **Tout est réversible par un simple
`git checkout main`.**

---

### D2 — Ne réutiliser aucune photo de Talinjoo Hotel

**Contexte.** Le site avait besoin d'une quinzaine d'images ; je n'en avais que
deux qui représentent réellement Home Sakalava.

**Options.** (a) Garder les photos de Talinjoo en attendant. (b) Chercher des
photos de banque d'images. (c) N'utiliser que les vraies photos et laisser les
autres emplacements en dégradé.

**Choix : (c).** Les options (a) et (b) reviennent à montrer aux voyageurs un
établissement qui n'est pas celui qu'ils réserveront — une piscine de Fort
Dauphin sur le site d'une maison d'hôtes de Nosy Be. C'est de la publicité
trompeuse, et cela aurait été invisible pour vous une fois le site en ligne.

Le moteur de rendu prévoyait déjà un repli en dégradé quand une image manque :
je m'en sers. Une section sans photo affiche un dégradé de couleur cohérent
avec la charte, ce qui reste présentable — il n'y a jamais de trou blanc ni
d'image cassée. Et dès que vous fournirez des photos, il suffira de les
déposer dans l'admin.

**Conséquence assumée :** 3 emplacements sur 12 ont aujourd'hui une vraie
photo. C'est la limite du matériau disponible, pas un choix esthétique.

---

### D3 — Conserver `hero.jpeg` comme image principale

La consigne demandait de garder le hero existant sauf si une autre photo est
manifestement meilleure. Le hero d'origine (`hero.jpeg` à la racine) était une
photo de Talinjoo. La photo `photos_sakalava/hero.jpeg` — le coucher de soleil
— est à la fois la bonne maison, la bonne île, et la meilleure image des deux.
Je l'ai donc retenue comme hero. **Ce n'est pas un changement de hero : c'est
le hero que vous aviez fourni pour ce projet.**

---

### D4 — Supprimer le témoignage client et le remplacer par les chiffres Booking

**Contexte.** La section « témoignage » contenait un avis inventé signé
« Marie Dupont », avec un portrait tiré de picsum.photos.

**Options.** (a) Le garder en changeant le nom. (b) Le retirer et laisser un
trou. (c) Le remplacer par une preuve sociale vérifiable.

**Choix : (c).** Publier un faux avis est un problème légal (pratique
commerciale trompeuse), pas seulement un problème de goût. `about.txt` fournit
en revanche des chiffres réels et vérifiables : **9,6/10 sur 75 avis Booking**,
avec le détail par critère.

J'ai donc transformé la section en bloc « réputation » : la note en grand, le
détail des sept critères sous forme de barres, et la mention de la source.
C'est plus crédible qu'un témoignage anonyme, et c'est vrai. La section
réutilise la grille en deux colonnes de l'ancienne, donc le rythme visuel de
la page est conservé.

**Elle est placée juste après « Notre histoire »**, conformément à l'ordre de
mise en avant demandé dans `about.txt` (bord de plage → accueil des hôtes →
note 9,6 → chambres → restaurant → excursions).

---

### D5 — Rendre le formulaire de réservation réellement fonctionnel

**Contexte.** Le formulaire simulait un envoi. Un visiteur pouvait croire sa
demande transmise alors que rien ne partait. `about.txt` laissait le choix
« lien Booking ou formulaire e-mail, à trancher avec le client » — je devais
trancher seul.

**Options.** (a) Supprimer le formulaire, mettre un lien Booking. (b) Ajouter
un envoi côté serveur en PHP. (c) Le formulaire compose un e-mail pré-rempli
et ouvre la messagerie du visiteur.

**Choix : (c).** L'option (b) imposait de gérer un serveur d'envoi, une file
de messages, un anti-spam et un stockage des demandes — c'est-à-dire de la
gestion de clients, formellement hors périmètre selon `gestionnaire.txt`.
L'option (a) sacrifiait un formulaire déjà dessiné et intégré.

Le bouton s'intitule désormais « Préparer ma demande ». Il ouvre la messagerie
du visiteur avec un message déjà rédigé (nom, dates, chambre, nombre de
voyageurs, message). Le visiteur relit et envoie lui-même. Rien n'est stocké,
rien ne part à son insu, et vous n'avez aucune boîte de réception
supplémentaire à surveiller.

**Et tant que l'adresse e-mail n'est pas renseignée** (elle est `[A FOURNIR]`),
le formulaire affiche un bandeau explicite, le bouton d'envoi est désactivé,
et un bouton renvoie vers votre page Facebook — qui, elle, est connue. Dès que
vous saisirez l'adresse dans l'admin, le formulaire s'activera tout seul.

---

### D6 — Stack du back-office : PHP + JSON, pas Laravel

**Contexte.** `gestionnaire.txt` demandait, pour un site statique, de proposer
deux options et d'attendre votre choix. `run_autonome.txt` m'interdit d'attendre.
J'ai donc tranché.

**Options.** (a) Laravel + MySQL (la pile de référence citée). (b) PHP 8 sans
framework + fichier JSON.

**Choix : (b).** Trois raisons :

1. **Les règles de sécurité de la mission interdisent d'installer une
   dépendance lourde sans nécessité réelle.** Laravel, c'est Composer, une
   centaine de paquets, une base MySQL à créer et à sauvegarder — pour gérer
   cinq chambres et une vingtaine de textes.
2. **PHP 8.2 est déjà là**, avec tout ce qu'il faut : GD pour les images,
   fileinfo pour la validation, Argon2id pour les mots de passe, les sessions.
   Zéro installation.
3. **Le site restait ainsi 100 % statique.** Voir D7, c'est le point décisif.

Le contenu vit dans `data/content.json`. Pas de base à installer, pas de
sauvegarde SQL : pour tout archiver, il suffit de copier trois dossiers.

---

### D7 — Le site public reste statique ; l'admin régénère `config.js`

**C'est la décision d'architecture principale, et le critère de réussite de la
phase 3 en dépend.**

**Contexte.** `gestionnaire.txt` exige que le site lise ses contenus depuis les
données et non depuis du HTML en dur, avec une mise en cache invalidée à chaque
modification — et que le site reste **visuellement identique** après migration.

**Options.** (a) Transformer `index.html` en `index.php` qui lit le JSON.
(b) Servir le contenu via une API PHP appelée en JavaScript. (c) L'admin écrit
le JSON puis **régénère `config.js`** comme un fichier statique.

**Choix : (c).** Et c'est l'option qui garantit mécaniquement le critère de
réussite : `js/render.js` est inchangé dans sa logique d'affichage, il lit
toujours un objet `CONFIG`. Simplement, ce `CONFIG` est désormais écrit par un
générateur au lieu d'être tapé à la main. **Le rendu ne peut donc pas avoir
bougé, puisque le moteur de rendu et sa source de données ont la même forme
qu'avant.**

Trois bénéfices concrets :

- **Le site public ne dépend pas de PHP.** Si PHP s'arrête, le site continue de
  fonctionner. Il pourrait être hébergé sur un hébergement statique.
- **`config.js` est le cache**, et il est invalidé au sens strict : réécrit à
  chaque enregistrement. Pas de cache à purger, pas d'incohérence possible.
- **Aucune requête serveur au chargement.** Le site reste aussi rapide qu'avant.

L'option (a) aurait rendu le site dépendant de PHP ; l'option (b) aurait ajouté
un aller-retour réseau avant le premier affichage, donc une page blanche sur
connexion lente — inacceptable pour une clientèle sur mobile à Madagascar.

Une garantie supplémentaire : `config.js` porte un en-tête
« **FICHIER GÉNÉRÉ — NE PAS MODIFIER À LA MAIN** », pour qu'un développeur
futur ne perde pas son travail au premier enregistrement.

---

### D8 — Deux étapes plutôt qu'une pour la migration du contenu

J'ai d'abord écrit le contenu Home Sakalava (phase 2), puis je l'ai extrait
vers `content.json` avec le générateur (phase 3). Écrire directement le
générateur aurait été plus rapide, mais faire les deux étapes m'a permis de
**comparer le `config.js` généré au `config.js` écrit à la main** et de vérifier
qu'ils décrivent le même site. C'est cette comparaison qui prouve que la mise
en dynamique n'a rien changé visuellement.

---

### D9 — Stockage des images : originaux à part, dérivés publiés

`gestionnaire.txt` demandait un stockage hors du dossier public « si le
framework le permet ». En PHP simple sous Apache, tout le dossier est
potentiellement public.

**Choix retenu :**
- `storage/originals/` conserve votre fichier d'origine intact, et ce dossier
  est **refusé par Apache** (`Require all denied`).
- `uploads/` contient uniquement les dérivés redimensionnés et recompressés,
  servis directement — donc mis en cache par le navigateur, sans passer par
  PHP à chaque image.

**Option écartée :** servir chaque image via une route PHP. Cela permettait de
sortir `uploads/` du public, mais faisait passer chaque photo par PHP à chaque
chargement : plus lent, et la mise en cache navigateur devient difficile à
gérer correctement. Sur une connexion mobile lente, le coût était réel.

Le compromis est couvert par trois barrières : validation stricte à l'upload,
**réencodage systématique par GD** (qui détruit tout contenu exécutable caché
dans une image), et un `.htaccess` qui neutralise tout moteur de script dans
`uploads/`.

---

### D10 — Nommage des fichiers et formats

Chaque photo importée est **renommée** (`slug-8caractèresaléatoires`), jamais
son nom d'origine — cela évite les collisions, les caractères exotiques et la
divulgation de noms internes. Chaque photo est déclinée en **trois largeurs**
(320, 640, 1280 px) et **deux formats** (WebP + repli JPEG), servis via
`<picture>` et `srcset`. Un téléphone télécharge la version 640 px, pas la
version pleine taille.

Comme le nom contient un identifiant unique, une photo modifiée change de nom :
j'ai donc pu régler un cache d'un an sur les images sans risque d'afficher une
version périmée.

---

### D11 — Corriger les contrastes, malgré la consigne « ne pas refaire le design »

**Contexte.** La mission demande de conserver le design existant, mais aussi
d'assurer l'accessibilité de base, contrastes compris. Les deux se contredisaient :
la mesure montrait 5 paires sous le seuil AA, dont la couleur d'accent et le gris
des textes secondaires.

**Choix.** J'ai calculé l'**assombrissement minimal** qui atteint exactement
4,5:1, sans changer la teinte :

| Variable | Avant | Après | Contraste obtenu |
|---|---|---|---|
| `--c-muted` | `#7a8c86` | `#5f6d69` | 4,60:1 (était 3,01:1) |
| `--c-accent` | `#1d8f8a` | `#187773` | 4,54:1 en texte, 5,35:1 sous du blanc (était 3,33 et 3,93) |

Ce sont les mêmes couleurs, un ton plus soutenu. Le site est reconnaissable ;
il est simplement lisible. Si vous préférez les teintes d'origine, elles se
remettent en deux minutes depuis **Réglages → Couleurs**, en connaissance de
cause. La couleur d'accent étant aussi stockée dans les données (elle est
appliquée par JavaScript), j'ai mis les deux en accord — sinon le JavaScript
aurait écrasé la correction CSS.

---

### D12 — Textes : originaux, et ton de maison d'hôtes

Tous les textes sont écrits à partir de `about.txt` uniquement. Rien n'est
repris de Booking. J'ai suivi la consigne de ton : pas d'« établissement », pas
de « prestations haut de gamme ». Le titre de la section chambres est
« Cinq chambres, pas une de plus ». La chambre la plus simple est décrite par
« un lit, une salle de bain à soi, et la plage à deux pas. C'est souvent tout
ce qu'il faut. »

J'ai mis Boda et Bakoly au centre de la section « Notre histoire », puisque
`about.txt` indique que l'accueil personnel est l'argument central, et j'ai
mentionné explicitement que Boda oriente vers les excursions dans la section
activités.

---

### D13 — Nouvelles sections construites avec le vocabulaire visuel existant

La mission demandait de créer restaurant, activités, accès et contact, tout en
conservant la structure et le design. Ces deux exigences se concilient si les
nouvelles sections **réutilisent les composants existants** plutôt que d'inventer
un langage visuel : mêmes `kicker`, mêmes cartes, mêmes rayons, mêmes espacements,
mêmes points de rupture. Le bloc « distances » réutilise le fond sombre du
footer ; les cartes d'activités reprennent la bordure des pastilles d'équipement.

Les services étaient déjà présents sous forme de pastilles ; j'ai gardé les
pastilles (six mots-clés) et ajouté en dessous la **liste complète** des quinze
services avec la mention « supplément » là où `about.txt` l'indique.

---

### D14 — Aucun tarif affiché, mais l'emplacement est prêt

Deux niveaux de réglage, comme demandé par `gestionnaire.txt` :
un prix par chambre, et un **interrupteur global** dans Réglages.
L'interrupteur est sur « masqué ». Chaque carte de chambre affiche
« Tarif sur demande » à l'emplacement exact où le prix apparaîtra.

Vous pouvez donc saisir tous vos prix tranquillement et les publier d'un seul
geste quand ils seront validés.

---

### D15 — Les champs manquants sont visibles, pas masqués

`about.txt` marque téléphone, e-mail et GPS comme `[A FOURNIR]`. Plutôt que de
masquer ces lignes (ce qui les aurait fait oublier), le site affiche
« **[À renseigner]** » en italique gris. Et le tableau de bord de l'admin
ouvre sur la liste de ce qui reste à compléter, avec un lien direct vers le
bon champ.

C'est volontairement un peu inconfortable : l'inconfort est le rappel.

---

### D16 — Déplacer les fichiers morts plutôt que les supprimer

24 Mo de fichiers inutilisés traînaient à la racine, dont une `video.mp4` de
23 Mo référencée nulle part. Plutôt qu'un `rm`, je les ai déplacés dans
`_archive_ancien_site/`, exclu du versionnement. Ils sont **toujours sur votre
disque**, et également récupérables depuis le commit de référence
(`git show 9eef186`). Rien n'est perdu ; la racine du site est simplement
propre.

`photos_sakalava/` n'a **pas** été touché : j'ai vérifié les empreintes MD5 des
deux fichiers avant et après la mission, elles sont identiques.

---

### D17 — Épingler les librairies CDN

`lucide@latest` a été remplacé par `lucide@0.454.0` sur cdnjs. Une version
« latest » signifie qu'une mise à jour majeure côté éditeur peut casser votre
site sans que personne n'ait rien touché. Toutes les librairies sont désormais
sur une version figée, et chargées en `defer` pour ne plus bloquer l'affichage.

---

### D18 — Un test de rendu plutôt qu'une dépendance de test

Pour vérifier que le site s'affiche sans erreur console (exigence de phase 4),
il me fallait exécuter le JavaScript. L'outil habituel, jsdom, pèse plusieurs
dizaines de mégaoctets — ce que les règles de la mission proscrivent sans
nécessité.

J'ai donc écrit `tools/smoke-test.js` : un DOM minimal, en un seul fichier, sans
aucune dépendance, qui reconstruit la page, exécute réellement `render.js` et
vérifie ensuite qu'aucun emplacement n'est resté vide, que toutes les images
ont une description, qu'aucun reliquat de l'ancien hôtel ne subsiste, qu'aucune
coordonnée n'a été inventée, que la hiérarchie des titres est correcte et que
tous les liens ont un intitulé lisible.

Il se lance par `npm test`, et c'est lui qui a détecté deux des bugs corrigés
plus bas.

---

### D19 — Décisions mineures, pour mémoire

- **Ordre des sections** : hero → histoire → réputation → la maison → chambres
  → restaurant → services → activités → FAQ → accès → contact. Suit l'ordre de
  mise en avant demandé dans `about.txt`.
- **Icônes** : le gérant choisit dans une liste en français (« Avion / navette »,
  « Massage »), jamais un identifiant technique.
- **Suppression dans les listes** : vider le champ et enregistrer, plutôt qu'un
  bouton « supprimer » par ligne. Moins de boutons à l'écran, moins de risque de
  clic accidentel au pouce, et le principe est le même partout.
- **Mentions légales** : affichées en texte simple et non en lien, tant que les
  pages n'existent pas. Un lien mort est pire qu'une absence de lien.
- **Version anglaise** : `about.txt` la mentionne comme souhaitable. Je ne l'ai
  pas faite — voir section 5.

---

## 3. Fichiers créés, modifiés, déplacés

### Créés — moteur de contenu

| Fichier | Rôle |
|---|---|
| `data/content.json` | **La source de vérité.** Tout le contenu du site. |
| `lib/content.php` | Lecture/écriture atomique des données + générateur du site. |
| `lib/images.php` | Traitement d'images partagé (validation, redimensionnement, WebP). |
| `tools/seed-content.php` | Génère le contenu initial depuis `about.txt`. |
| `tools/build.php` | Régénère `config.js`, le bloc SEO et `sitemap.xml`. |
| `tools/import-photos.php` | Importe `photos_sakalava/` en lecture seule. |
| `tools/create-admin.php` | Crée ou change le compte administrateur. |
| `tools/smoke-test.js` | Test de rendu sans dépendance. |

### Créés — back-office

`admin/index.php` · `login.php` · `logout.php` · `chambres.php` · `photos.php` ·
`services.php` · `textes.php` · `reglages.php`
`admin/inc/` : `bootstrap.php` · `auth.php` · `flash.php` · `layout.php` · `media.php`
`admin/assets/` : `admin.css` · `admin.js`

### Créés — site public et configuration

`assets/favicon.svg` · `assets/apple-touch-icon.png` · `site.webmanifest` ·
`robots.txt` · `sitemap.xml` (généré) · `README.md` · `RAPPORT.md` · `.gitignore`
`.htaccess` à la racine, plus `data/`, `lib/`, `tools/`, `storage/`, `uploads/`

### Modifiés

| Fichier | Nature de la modification |
|---|---|
| `index.html` | Nouvelles sections, repères de build SEO, accessibilité, scripts épinglés et différés |
| `config.js` | **Désormais généré.** Contenu Home Sakalava |
| `js/render.js` | Support `<picture>`/WebP, 4 nouvelles fonctions de rendu, formulaire e-mail réel |
| `js/faq.js` | Réécrit : remesure au redimensionnement, fonctionne sans GSAP |
| `js/carousel.js` | Repli sans GSAP |
| `js/main.js` | Détection GSAP, touche Échap sur le menu mobile |
| `js/animations.js` | Sélecteurs reportés sur les nouvelles sections |
| `css/variables.css` | Deux couleurs assombries pour le contraste |
| `css/base.css` | Lien d'évitement, anneau de focus |
| `css/components.css` | `<picture>`, cartes chambres enrichies, services, activités |
| `css/sections.css` | 4 sections ajoutées, CSS mort retiré, correctifs mobile |
| `package.json` | Renommé, scripts utiles ajoutés |

### Déplacés

`_archive_ancien_site/` — 10 fichiers, 24 Mo : `video.mp4`, `hero.jpeg`,
`hero1.webp`, `DSC7896-681x1024.jpg`, `Talinjoo-Hotel-21.webp`,
`Talinjoo-Hotel-33.webp`, `piscine-mer-talinjoo-*.jpg`,
`talinjoo-hotel-fort-dauphin-*.webp`, `images (5).jfif`, `images (6).jfif`.

### Jamais touchés

`photos_sakalava/` (MD5 vérifiés identiques), `about.txt`, `gestionnaire.txt`,
`run_autonome.txt`, et la branche `main`.

---

## 4. Ce que j'ai besoin de vous

### Indispensable avant la mise en ligne

1. **Le numéro de téléphone.** → Admin, onglet Textes.
2. **L'adresse e-mail.** → Admin, onglet Textes. **C'est la plus importante :
   le formulaire de contact du site reste désactivé tant qu'elle manque.**
3. **Les coordonnées GPS.** Relevables sur Google Maps : appui long sur la
   maison, les chiffres apparaissent en haut.
4. **Les tarifs des cinq chambres**, puis l'activation de l'affichage dans
   Réglages.
5. **Le nom de domaine définitif.** → Réglages → Référencement. Sans lui, le
   plan du site et l'aperçu de partage restent incomplets.

### Très souhaitable

6. **Des photos.** C'est ce qui manque le plus. Neuf emplacements attendent une
   image : le jardin et la terrasse, le restaurant (deux emplacements), et
   surtout **les quatre chambres sans photo** (Standard Triple, Double vue
   jardin, Double avec terrasse, Standard Double). Une photo par chambre
   changerait la page du tout au tout. Prises au téléphone, en lumière du jour,
   elles suffisent largement : l'admin les optimise automatiquement.

### Décisions qui vous appartiennent

7. **Le bouton de réservation.** J'ai tranché pour un formulaire e-mail (D5).
   Si vous préférez renvoyer vers votre fiche Booking, dites-le : c'est une
   demi-journée de travail.
8. **Les couleurs.** J'ai assombri deux teintes pour l'accessibilité (D11).
   Réversible depuis Réglages si vous préférez les teintes d'origine.
9. **Mentions légales et politique de confidentialité.** Affichées en texte
   simple, sans lien, tant que les pages n'existent pas.
10. **Le mot de passe admin.** J'en ai créé un provisoire pour mes tests :
    identifiant `admin`, mot de passe `plage-vanille-baobab-37`.
    **Changez-le à votre première connexion** (Réglages → Mot de passe). Il
    n'est pas dans le dépôt git, mais je l'ai écrit ici, donc considérez-le
    comme public.

---

## 5. Ce que je n'ai pas pu faire

### La version anglaise

`about.txt` la signale comme souhaitable, la clientèle étant majoritairement
européenne. Elle n'est **pas faite**.

Ce n'était pas dans le périmètre des quatre phases, et surtout c'est un choix
d'architecture qui vous revient : deux pages séparées (`index.html` /
`en/index.html`), ou un basculement de langue côté JavaScript ? Cela change la
structure du fichier de contenu et le référencement. J'ai préféré ne pas
préempter cette décision pendant votre absence. La bonne nouvelle : le contenu
étant déjà centralisé dans un seul fichier de données, ajouter une langue est
un travail net, sans réécriture du site.

### Le test sur de vrais téléphones

Je n'ai pas de navigateur dans cet environnement. J'ai vérifié le responsive
par l'analyse du CSS (recherche systématique des largeurs fixes, des
`nowrap` risqués, des grilles non repliées) et par l'exécution réelle du
JavaScript de rendu. **Cela ne remplace pas un coup d'œil sur un vrai
téléphone** — c'est le premier point de la section 7.

### La photo du restaurant et du jardin

Faute de matériau (D2). Les emplacements existent et attendent.

### Aucun blocage réel

Je ne me suis arrêté sur rien. Les deux contradictions rencontrées entre
fichiers de consigne ont été tranchées dans le sens de `run_autonome.txt`,
comme il l'exige : le back-office a été construit d'une traite sans attendre
de validation par étape, et le choix de la pile technique a été fait seul.

---

## 6. Comment lancer le projet, pas à pas

*(Le détail complet, à garder sous la main, est dans `README.md`.)*

### Voir le site

1. Ouvrez le **Panneau de contrôle XAMPP**.
2. Cliquez sur **Start** en face d'**Apache**. MySQL est inutile.
3. Dans le navigateur : **http://localhost/talinjo/**

### Entrer dans l'administration

1. Allez sur **http://localhost/talinjo/admin/login.php**
2. Identifiant `admin`, mot de passe `plage-vanille-baobab-37`
3. **Changez immédiatement ce mot de passe** : onglet **Réglages**, section
   **Mot de passe**.

Si vous préférez repartir d'un compte propre, ouvrez un terminal dans le
dossier du projet et lancez `php tools/create-admin.php` : le script propose de
remplacer le compte existant et demande un nouveau mot de passe, sans
l'afficher à l'écran.

### Modifier quelque chose

1. Choisissez l'onglet correspondant (Chambres, Photos, Services, Textes,
   Réglages).
2. Modifiez, puis cliquez sur le bouton d'enregistrement en bas.
3. Cliquez sur **Voir le site** en haut à droite : la modification y est déjà.

Il n'y a rien à publier, rien à régénérer : c'est automatique.

### En ligne de commande, si besoin

```
php tools/build.php          # régénère le site depuis les données
node tools/smoke-test.js .   # vérifie que le site s'affiche sans erreur
php tools/create-admin.php   # crée ou change le compte admin
```

---

## 7. À vérifier en priorité à votre réveil

Dans cet ordre.

1. **Ouvrez le site sur votre téléphone.** C'est le seul contrôle que je n'ai
   pas pu faire, et c'est l'appareil de votre clientèle. Regardez en
   particulier : le hero en portrait, le carrousel des chambres au doigt,
   l'accordéon de la FAQ, et le formulaire en bas.

2. **Changez le mot de passe admin.** Il est écrit en clair dans ce rapport.

3. **Vérifiez que le contenu est juste.** J'ai rédigé tous les textes à partir
   de `about.txt`, mais je ne connais ni Boda ni Bakoly. Relisez
   « Notre histoire » et les descriptions des cinq chambres : les faits
   viennent de votre fiche, le ton vient de moi. Si quelque chose sonne faux,
   c'est modifiable en deux minutes dans l'admin.

4. **Vérifiez les cinq chambres.** Superficies, équipements, et surtout
   l'étiquette de chacune (« La plus grande », « Avec baignoire »…) : je les ai
   inventées à partir des caractéristiques, elles vous appartiennent.

5. **Faites un test complet dans l'admin.** Changez un texte, enregistrez,
   regardez le site. Envoyez une photo. Cela vous fera prendre l'outil en main
   et confirmera que tout fonctionne chez vous.

6. **Regardez les emplacements sans photo** (section « La maison », le
   restaurant, quatre chambres sur cinq). Jugez si les dégradés de couleur vous
   conviennent en attendant, ou s'il faut réorganiser en attendant vos photos.

7. **Confirmez la décision D5** sur le formulaire de réservation. C'est le
   choix le plus structurant que j'ai pris seul, et celui qui touche directement
   la façon dont vos demandes arrivent.

---

## Annexe — Vérifications effectuées

| Contrôle | Résultat |
|---|---|
| Syntaxe PHP, 19 fichiers | Aucune erreur |
| Syntaxe JavaScript, 5 fichiers | Aucune erreur |
| Équilibre des accolades CSS, 5 fichiers | Équilibré |
| Exécution du rendu (684 nœuds) | Aucune erreur |
| Ancres internes | Aucune cassée |
| Fichiers images référencés (12) | Aucun manquant |
| Hiérarchie des titres | Un seul h1, aucun niveau sauté |
| Textes alternatifs | Toutes les images |
| Contrastes (13 paires mesurées) | Toutes au niveau AA |
| Reliquats Talinjoo / Fort Dauphin / picsum | Aucun |
| Coordonnées inventées | Aucune |
| `data/`, `lib/`, `tools/`, `storage/` via HTTP | 403 |
| `about.txt` via HTTP | 403 |
| Routes admin sans session | Redirection 302 |
| Écriture sans jeton CSRF | Refusée |
| Mot de passe erroné | Refusé, compteur décrémenté |
| Script PHP déguisé en `.jpg` | Refusé |
| Fichier texte déguisé en `.png` | Refusé |
| Upload, puis suppression en cascade | Fichiers et références nettoyés |
| `photos_sakalava/` (MD5 avant/après) | Identiques |

**Un bug a été trouvé par ces tests et corrigé :** une saisie non-UTF-8 — ce qui
arrive en collant du texte depuis un traitement de texte — était silencieusement
vidée par le filtre de caractères de contrôle. Le texte disparaissait sans le
moindre message d'erreur. L'encodage est désormais converti avant filtrage.

---

# 8. Seconde passe — tests, corrections et mise en ligne

Cette section couvre le travail fait après la mission initiale, à votre
demande : tester, réparer, améliorer, et rendre le site prêt à héberger.

---

## 8.1 Un banc de test du back-office

J'ai écrit `tools/test-admin.php`, qui exerce l'administration à travers de
vraies requêtes HTTP : il se connecte, enregistre, tente des injections,
pousse les cas limites, puis **restaure le contenu tel qu'il était avant**.
Vous pouvez le lancer sans crainte, même sur le site en production.

```
php tools/test-admin.php
```

**67 contrôles**, tous au vert aujourd'hui. Ils couvrent l'authentification,
l'échappement, la validation côté serveur, les listes, les chambres, la
galerie photos, et la page de diagnostic.

Ce banc n'est pas décoratif : **il a trouvé quatre bugs**, dont un sérieux.

---

## 8.2 Les bugs trouvés et corrigés

### Bug 1 — Une faille XSS dans les données structurées (sérieux)

**Ce qui se passait.** Le bloc de données destiné à Google est écrit
*à l'intérieur* d'une balise `<script>` de la page. L'encodage que
j'utilisais ne protégeait pas les chevrons. Un texte contenant `</script>`
saisi depuis l'administration refermait donc la balise, et tout ce qui
suivait était exécuté comme du code par le navigateur des visiteurs.

**Pourquoi c'est sérieux.** C'est une injection de code dans le site public.
Même si vous êtes la seule personne à avoir accès à l'administration, ce
type de faille ne doit jamais rester : elle transforme une erreur de frappe,
ou un compte compromis, en prise de contrôle de la page vue par vos clients.

**Correction.** Les chevrons, les guillemets et les esperluettes sont
désormais encodés en séquences d'échappement. Le résultat reste du JSON
parfaitement valide et lisible par Google — j'ai vérifié les deux.

**Vérifié :** une tentative de sortie de balise est maintenant neutralisée,
et le nombre de balises ouvertes et fermées de la page reste équilibré.

### Bug 2 — Photos fantômes

La page d'affectation acceptait n'importe quel identifiant de photo, y
compris un qui ne désignait rien. Le site affichait alors une image cassée.
Désormais, un identifiant inconnu est ignoré et la section retombe sur son
dégradé de couleur.

### Bug 3 — Avertissements silencieux

Dès que la fiche de contact était incomplète, le générateur lisait des
champs inexistants. Comme l'affichage des erreurs est désactivé (à raison),
cela ne se voyait pas, mais remplissait le journal du serveur à chaque
enregistrement.

### Bug 4 — Données structurées figées

L'adresse postale envoyée à Google était écrite en dur dans le code. Si vous
aviez corrigé l'adresse dans l'administration, Google aurait continué de
lire l'ancienne. Elle est maintenant **déduite des lignes que vous
saisissez**, avec le code pays, les horaires convertis au format attendu,
les coordonnées GPS dès que vous les renseignerez, et la note Booking tirée
de la section réputation.

---

## 8.3 La lacune que j'avais laissée : les photos des chambres

`gestionnaire.txt` demandait de pouvoir « ajouter, remplacer, supprimer et
réordonner les photos d'une chambre » et « définir la photo principale ».
Ma première version n'en gérait **qu'une seule**. C'était un manque réel, et
il tombait mal puisque vous allez justement ajouter des photos.

**Ce qui existe maintenant :**

- Une chambre accepte **autant de photos que vous voulez**.
- **La première est la photo principale** : celle qui s'affiche sur la carte.
  J'ai délibérément fusionné « ordre » et « photo principale » en un seul
  réglage — deux réglages séparés, c'est deux occasions de se contredire.
- Pour retirer une photo d'une chambre, on remet sa liste sur
  « aucune photo » : elle reste dans la bibliothèque.
- Les doublons et les photos supprimées entre-temps sont écartés
  automatiquement.

**Côté visiteur**, un bouton « Voir les photos » ouvre une visionneuse.

**Décision : un bouton explicite, pas une carte cliquable.** Le carrousel des
chambres se manipule au glissé du doigt. Rendre la carte entière cliquable
aurait fait ouvrir la visionneuse par accident à chaque glissement. Un bouton
séparé évite ce conflit, et il est atteignable au clavier.

La visionneuse est un vrai dialogue accessible : le focus y est enfermé,
Échap ferme, les flèches changent de photo, le balayage fonctionne au doigt,
et le focus revient sur le bouton d'origine à la fermeture. Elle n'utilise
aucune librairie et fonctionne même si les CDN sont bloqués.

---

## 8.4 Créer et supprimer des chambres

Vous pouvez maintenant **ajouter** une chambre et en **supprimer** une.

Une chambre nouvellement créée est **masquée par défaut**. C'est volontaire :
vous la complétez tranquillement, et elle n'apparaît en ligne que quand vous
cochez « Afficher cette chambre ». Pas de carte vide visible par vos clients
pendant que vous travaillez.

La suppression demande confirmation et le rappelle : pour retirer une chambre
temporairement, il vaut mieux la masquer.

---

## 8.5 Prêt à être hébergé

### Une page de diagnostic

Nouvel onglet **Diagnostic** dans l'administration. Elle contrôle :

- la version de PHP et les extensions nécessaires,
- les droits d'écriture sur `data/`, `uploads/`, `storage/` et la racine,
- **la protection réelle des dossiers sensibles** — en les interrogeant
  vraiment par une requête HTTP, comme le ferait un visiteur,
- le HTTPS, les sessions, le journal des erreurs,
- la présence effective des fichiers de photos.

Chaque ligne en rouge ou en orange explique quoi faire, en français.

**C'est le contrôle le plus utile après une mise en ligne.** Il détecte
notamment le cas où l'hébergeur ignore les fichiers `.htaccess` — situation
dans laquelle votre contenu et votre mot de passe haché seraient publiquement
téléchargeables sans que rien ne le laisse deviner.

### Un dossier prêt à téléverser

```
php tools/package.php
```

Crée un dossier `livraison/` contenant le site, **sans** votre compte
administrateur, sans les fiches de travail internes (`about.txt`,
`gestionnaire.txt`, ce rapport), sans les archives ni l'historique git, et
sans les outils de développement qui n'ont rien à faire sur un serveur.

Le script vérifie ensuite qu'aucun fichier sensible n'a suivi par erreur, et
**s'arrête en erreur si c'est le cas**. Il vous rappelle aussi ce qui reste à
compléter.

Un fichier `LISEZ-MOI-AVANT-MISE-EN-LIGNE.txt` est déposé dans le dossier,
avec les six étapes à suivre.

### Adaptations pour l'hébergement mutualisé

- **HTTPS derrière un répartiteur de charge** : la plupart des hébergeurs
  gèrent le certificat en amont de PHP. Sans traitement particulier, le
  cookie de session n'aurait jamais été marqué « sécurisé » en production.
  Les en-têtes standards sont désormais reconnus.
- **En-tête HSTS** dès que le site est en HTTPS.
- **L'administration n'est plus mise en cache** par le navigateur : sur un
  ordinateur partagé, le bouton « page précédente » pouvait réafficher une
  page d'administration après déconnexion.
- **Journal d'erreurs dédié** dans `data/erreurs.log`, dossier inaccessible
  depuis le web. Sur beaucoup d'hébergements, les erreurs PHP partent sinon
  dans un fichier introuvable : impossible de diagnostiquer à distance.
- **Redirection HTTPS** préparée dans `.htaccess`, laissée commentée.
  Activée avant que le certificat existe, elle rendrait le site inaccessible :
  à vous de retirer les dièses une fois le certificat en place.
- **Chemins relatifs** partout : le site fonctionne à la racine d'un domaine
  comme dans un sous-dossier.

---

## 8.6 Ce qui reste vrai depuis le premier rapport

Rien de ce qui précède n'annule les sections 1 à 7. En particulier :

- **Les photos manquent toujours.** Quatre chambres sur cinq n'en ont
  aucune. C'est le principal levier d'amélioration du site, et c'est
  maintenant beaucoup plus facile à combler : plusieurs photos par chambre,
  et une visionneuse pour les montrer.
- **Le mot de passe provisoire est toujours à changer** (section 4, point 10).
- **La version anglaise n'est toujours pas faite** (section 5).
- **Rien n'a été testé sur un vrai téléphone** : je n'ai pas de navigateur
  dans cet environnement.

---

## 8.7 Demain, pour saisir vos données

Dans cet ordre :

1. **Connectez-vous** et changez le mot de passe (Réglages).
2. **Textes → Coordonnées** : téléphone, e-mail, GPS. Dès que l'e-mail est
   saisi, le formulaire de contact du site s'active tout seul.
3. **Photos** : envoyez tout ce que vous avez. Écrivez une phrase de
   description pour chacune, c'est obligatoire et c'est ce que lisent les
   personnes malvoyantes.
4. **Chambres** : pour chaque chambre, le tarif, puis les photos. La première
   photo de la liste est celle qui s'affichera sur la carte.
5. **Réglages → Affichage des tarifs** : quand tous les prix sont saisis et
   validés, basculez l'interrupteur. Ils apparaissent d'un coup.
6. **Diagnostic** : vérifiez qu'il ne reste rien en rouge.
7. Quand vous êtes prêt : `php tools/package.php`, puis les étapes du
   `README.md`, section 9.

---

## Annexe 2 — Vérifications de la seconde passe

| Contrôle | Résultat |
|---|---|
| Banc de test du back-office | 67 / 67 |
| Test de rendu du site | Aucun problème |
| Test de rendu du dossier `livraison/` | Aucun problème |
| Syntaxe PHP (22 fichiers) | Aucune erreur |
| Syntaxe JavaScript (6 fichiers) | Aucune erreur |
| Équilibre des accolades CSS | Équilibré |
| Sortie de balise `<script>` depuis l'admin | Neutralisée |
| Données structurées après échappement | JSON valide, lisible |
| Fichier sensible dans `livraison/` | Aucun |
| Page de diagnostic | 24 contrôles au vert, 2 avertissements attendus en local |
| Photos d'origine (`photos_sakalava/`) | Intactes |
