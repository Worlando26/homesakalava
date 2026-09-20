# Home Sakalava — site et espace de gestion

Ce dossier contient le site de la maison d'hôtes et l'espace d'administration
qui permet d'en modifier le contenu sans toucher au code.

---

## 1. Ce qu'il faut savoir en une minute

- Le site est un **site statique** : du HTML, du CSS et du JavaScript. Il est
  rapide et fonctionne même si PHP est arrêté.
- Tout le contenu (textes, photos, tarifs, coordonnées) vit dans un seul
  fichier de données : `data/content.json`.
- L'espace d'administration est en PHP. Il modifie ce fichier de données puis
  **régénère automatiquement** le site public.
- Il n'y a **pas de base de données** à installer, et **aucune dépendance** à
  télécharger. Un serveur avec PHP 8 suffit.

---

## 2. Lancer le site sur votre ordinateur

### Avec XAMPP (ce qui est installé ici)

1. Ouvrez le **Panneau de contrôle XAMPP**.
2. Cliquez sur **Start** en face d'**Apache**. (MySQL est inutile.)
3. Ouvrez votre navigateur à l'adresse :

   **http://localhost/talinjo/**

### Sans XAMPP, avec PHP seul

Depuis un terminal ouvert dans ce dossier :

```
php -S localhost:8000
```

Puis ouvrez **http://localhost:8000/**

---

## 3. Se connecter à l'administration

### La toute première fois : créer le compte

Ouvrez un terminal dans ce dossier et lancez :

```
php tools/create-admin.php
```

Le script vous demande un identifiant puis un mot de passe (au moins
10 caractères, saisi deux fois, sans affichage à l'écran).

> **Conseil pour le mot de passe** : trois ou quatre mots sans rapport entre
> eux valent mieux qu'un mot compliqué. Par exemple `plage-vanille-baobab-37`.
> C'est plus solide et plus facile à retenir.

Le mot de passe n'est jamais enregistré en clair : il est haché en Argon2id
dans `data/admin.json`, un fichier volontairement exclu de la sauvegarde git.

### Ensuite : se connecter

**http://localhost/talinjo/admin/login.php**

Après 5 tentatives échouées, la connexion se bloque 15 minutes. La session se
ferme toute seule après 30 minutes sans activité.

### Changer le mot de passe

Depuis l'administration : onglet **Réglages → Mot de passe**.
Ou en ligne de commande, en relançant `php tools/create-admin.php`.

---

## 4. Que peut-on modifier, et où ?

| Onglet | Ce que vous y faites |
|---|---|
| **Accueil** | La liste de ce qu'il reste à compléter, et les raccourcis. |
| **Chambres** | Tarif, nom, description, superficie, équipements, photos, ordre d'apparition, afficher/masquer une chambre. Vous pouvez aussi en créer et en supprimer. |
| **Photos** | Ajouter des photos, écrire leur description, les supprimer, et choisir quelle photo va dans quelle section. |
| **Services** | La liste des services (wifi, parking, navette…), le restaurant et les activités. La mention « supplément » se coche par ligne. |
| **Textes** | Les textes de chaque section, les coordonnées (téléphone, e-mail, adresse, GPS, Facebook), les horaires et les questions fréquentes. |
| **Réglages** | Afficher ou masquer les tarifs, le référencement Google, les couleurs, le mot de passe. |
| **Diagnostic** | Vérifie que le serveur est bien configuré. À ouvrir après chaque mise en ligne. |

### Les deux langues

Le site existe en **français** (à la racine) et en **anglais** (dossier `en/`).

- Le français est la langue source : c'est lui que vous modifiez dans l'admin.
- L'anglais vit dans `data/i18n/en.json`. **Quand vous changez un texte en
  français, pensez à le reporter dans ce fichier**, sinon la version anglaise
  garde l'ancien texte.
- Un texte absent du fichier anglais s'affiche en français : le site n'est
  jamais vide, même si une traduction manque.

Le visiteur arrive dans sa langue : à sa première visite sur la page d'accueil,
le site suit la langue de son navigateur. S'il choisit une langue dans le
menu, ce choix est retenu et prime sur tout le reste.

L'allemand et l'italien ont été traduits puis retirés. Les fichiers sont
conservés dans `data/i18n/desactive/`, avec la marche à suivre pour les
remettre en ligne.

Chaque enregistrement met le site public à jour **immédiatement**. Vous pouvez
vérifier en cliquant sur « Voir le site » en haut à droite.

### Ajouter, modifier, supprimer une ligne dans une liste

Les listes (équipements, services, questions…) fonctionnent toutes pareil :

- **Ajouter** : des lignes vides sont déjà présentes en bas de chaque liste.
  Écrivez dedans, puis enregistrez.
- **Modifier** : changez le texte, puis enregistrez.
- **Supprimer** : **videz complètement** la ligne, puis enregistrez.

### Les photos d'une chambre

Une chambre peut avoir **plusieurs photos**. Dans l'écran d'une chambre, vous
trouvez une série de listes déroulantes :

- **La première photo est la photo principale.** C'est elle qui s'affiche sur
  la carte de la chambre.
- Les suivantes apparaissent quand le visiteur clique sur
  « Voir les photos » : elles s'ouvrent en grand, l'une après l'autre.
- Pour **changer l'ordre**, changez les photos choisies dans les listes.
- Pour **retirer** une photo de la chambre, remettez sa liste sur
  « aucune photo ». La photo reste dans la bibliothèque.

Deux emplacements vides sont toujours proposés en bas, pour en ajouter.

### Les photos, en général

- Formats acceptés : **JPEG, PNG, WebP**, jusqu'à **8 Mo**.
- Chaque photo envoyée est automatiquement réduite et compressée en plusieurs
  tailles, pour que le site reste rapide sur un téléphone en 3G.
- Votre fichier d'origine est conservé intact de son côté, dans
  `storage/originals/`.
- La **description** de la photo est obligatoire. Elle est lue à voix haute
  aux personnes malvoyantes et s'affiche si l'image ne se charge pas.
- Une section sans photo n'est pas cassée : elle s'affiche avec un dégradé de
  couleur, ce qui reste présentable.

### Les tarifs

Les tarifs ont deux niveaux de réglage :

1. Le prix de chaque chambre, saisi dans **Chambres**.
2. Un interrupteur général dans **Réglages → Affichage des tarifs**.

Tant que l'interrupteur est sur « masqué », le site affiche
« Tarif sur demande » partout, même si les prix sont saisis. Vous pouvez donc
préparer tous vos prix tranquillement et les publier d'un seul geste.

---

## 5. Ce qui n'est volontairement pas dans l'administration

Le site est une **vitrine**. Il n'y a donc, délibérément :

- pas de calendrier de disponibilités,
- pas de moteur de réservation en ligne,
- pas de gestion de clients ni de paiements,
- pas de synchronisation avec Booking.

Les demandes de réservation continuent d'arriver par Booking, par Facebook,
ou par le formulaire du site, qui prépare simplement un e-mail.

---

## 6. Organisation des fichiers

```
talinjo/
├── index.html              Le site public (une seule page)
├── config.js               ⚠ GÉNÉRÉ — ne pas modifier à la main
├── sitemap.xml             ⚠ GÉNÉRÉ
├── css/ · js/              Styles et scripts du site public
├── assets/                 Favicon et icônes
├── uploads/                Photos publiées (générées, plusieurs tailles)
│
├── admin/                  L'espace de gestion (PHP)
├── lib/                    Code partagé : données, images
├── tools/                  Outils en ligne de commande
│   ├── build.php           régénère le site
│   ├── create-admin.php    crée ou change le compte admin
│   ├── package.php         prépare le dossier à mettre en ligne
│   ├── test-admin.php      banc de test du back-office
│   └── smoke-test.js       vérifie l'affichage du site
│
├── data/
│   ├── content.json        ★ TOUT le contenu du site
│   └── admin.json          Compte administrateur (jamais sauvegardé sur git)
├── storage/originals/      Vos photos d'origine, intactes
└── photos_sakalava/        Les photos que vous avez fournies, jamais modifiées
```

Les fichiers marqués **GÉNÉRÉ** sont réécrits à chaque enregistrement : toute
modification manuelle y serait perdue. Pour changer le site, passez par
l'administration.

---

## 7. Sauvegarder le site

Pour tout sauvegarder, copiez ces trois dossiers :

- `data/` — le contenu et le compte
- `uploads/` — les photos publiées
- `storage/originals/` — les photos d'origine

Le reste se régénère à partir de là.

---

## 8. En cas de problème

**Le site ne reflète pas ma modification.**
Allez dans **Réglages → Régénérer le site** et cliquez sur le bouton. Si cela
ne suffit pas, videz le cache du navigateur (Ctrl+F5).

**J'ai oublié mon mot de passe.**
Relancez `php tools/create-admin.php` depuis un terminal : le script propose
de remplacer le mot de passe existant.

**Je suis bloqué après trop de tentatives.**
Attendez 15 minutes. Ou supprimez le fichier `data/admin.json` et recréez le
compte — attention, cela efface aussi l'identifiant.

**Une page d'administration affiche une erreur blanche.**
Regardez le journal d'erreurs PHP (`C:\xampp\php\logs\php_error_log` sous
XAMPP). Les erreurs ne s'affichent pas à l'écran, volontairement : elles
révéleraient des chemins de fichiers à un visiteur.

**Régénérer le site en ligne de commande :**

```
php tools/build.php
```

**Vérifier que le site s'affiche sans erreur :**

```
node tools/smoke-test.js .
```

---

## 9. Mettre le site en ligne

### Étape 1 — Préparer le dossier à téléverser

Depuis un terminal ouvert dans ce dossier :

```
php tools/package.php
```

Le script crée un dossier **`livraison/`** contenant le site prêt à partir.
Il laisse volontairement de côté :

- `data/admin.json` — votre mot de passe,
- `about.txt`, `gestionnaire.txt`, `RAPPORT.md` — documents de travail,
- `photos_sakalava/`, `_archive_ancien_site/` — sources et archives,
- les outils de développement et l'historique git.

Il affiche aussi, avant de finir, ce qui reste à compléter (adresse du site,
e-mail, chambres sans photo). Ces points ne bloquent pas la mise en ligne.

### Étape 2 — Téléverser

Copiez **tout le contenu** de `livraison/` à la racine de votre hébergement,
c'est-à-dire le dossier que votre hébergeur appelle `www/`, `public_html/`
ou `htdocs/`.

Le site fonctionne aussi bien à la racine d'un domaine que dans un
sous-dossier : tous les chemins sont relatifs.

### Étape 3 — Créer le compte administrateur sur le serveur

Si votre hébergeur vous donne un accès SSH :

```
php tools/create-admin.php
```

Sinon, créez le compte sur votre ordinateur avec la même commande, puis
téléversez le seul fichier `data/admin.json` qui vient d'être créé.

### Étape 4 — Vérifier les droits d'écriture

Ces dossiers doivent être accessibles en écriture (permissions `755`, ou
`775` si votre hébergeur l'exige) :

```
data/    uploads/    storage/originals/    et la racine du site
```

### Étape 5 — Activer HTTPS

Activez le certificat gratuit proposé par votre hébergeur. Puis, **une fois
qu'il fonctionne**, ouvrez `.htaccess` et retirez les `#` du bloc
« Forcer HTTPS » à la fin du fichier.

> Ne l'activez pas avant que le certificat existe : le site deviendrait
> inaccessible.

### Étape 6 — Passer le diagnostic

Ouvrez **`/admin/diagnostic.php`**. Cette page contrôle la version de PHP,
les extensions, les droits d'écriture, la protection des dossiers sensibles
et le HTTPS. Chaque ligne en rouge ou en orange explique quoi faire.

C'est le contrôle le plus utile après un déménagement : il détecte notamment
le cas où l'hébergeur ignore les fichiers `.htaccess`, qui laisserait vos
données accessibles publiquement.

### Étape 7 — Finaliser le référencement

1. Renseignez l'**adresse du site** dans **Réglages → Référencement**.
2. Décommentez la ligne `Sitemap:` dans `robots.txt` avec cette adresse.

---

## 10. Vérifier que tout fonctionne

Deux outils, à lancer depuis un terminal dans le dossier du projet :

```
node tools/smoke-test.js .     # le site s'affiche-t-il sans erreur ?
php tools/test-admin.php       # 67 contrôles sur l'administration
```

Le second demande vos identifiants, exerce réellement l'administration
(connexion, enregistrements, tentatives d'injection, cas limites), puis
**restaure le contenu tel qu'il était avant**. Vous pouvez le lancer sans
crainte sur un site en production.
