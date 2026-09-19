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
| **Chambres** | Tarif, nom, description, superficie, équipements, photo, ordre d'apparition, et afficher/masquer une chambre. |
| **Photos** | Ajouter des photos, écrire leur description, les supprimer, et choisir quelle photo va dans quelle section. |
| **Services** | La liste des services (wifi, parking, navette…), le restaurant et les activités. La mention « supplément » se coche par ligne. |
| **Textes** | Les textes de chaque section, les coordonnées (téléphone, e-mail, adresse, GPS, Facebook), les horaires et les questions fréquentes. |
| **Réglages** | Afficher ou masquer les tarifs, le référencement Google, les couleurs, le mot de passe. |

Chaque enregistrement met le site public à jour **immédiatement**. Vous pouvez
vérifier en cliquant sur « Voir le site » en haut à droite.

### Ajouter, modifier, supprimer une ligne dans une liste

Les listes (équipements, services, questions…) fonctionnent toutes pareil :

- **Ajouter** : des lignes vides sont déjà présentes en bas de chaque liste.
  Écrivez dedans, puis enregistrez.
- **Modifier** : changez le texte, puis enregistrez.
- **Supprimer** : **videz complètement** la ligne, puis enregistrez.

### Les photos

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

## 9. Avant la mise en ligne

Trois choses à faire quand le site partira sur un vrai hébergement :

1. Renseigner l'**adresse du site** dans **Réglages → Référencement**. Sans
   elle, le plan du site et l'aperçu de partage restent incomplets.
2. Vérifier que le site est servi en **HTTPS**. Les cookies de session
   passeront alors automatiquement en mode sécurisé.
3. Décommenter la ligne `Sitemap:` dans `robots.txt` avec la bonne adresse.
