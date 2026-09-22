# Mise en ligne — Home Sakalava

Site statique généré, back-office PHP. **Ni base de données, ni Composer, ni
npm.** Un hébergement mutualisé avec PHP 8.1 ou plus suffit.

---

## 1. Le fichier à remplir

Un seul : **`config/site.php`**. Chaque valeur encore à `A_REMPLIR` est
affichée comme « à renseigner » sur le site — rien n'est jamais inventé.

Vous pouvez l'éditer à la main, ou passer par l'administration : c'est le
même fichier. Les onglets **Textes** et **Réglages** y écrivent.

### Déjà renseigné

| Champ | Valeur |
|---|---|
| `hotel.nom` | Home Sakalava |
| `hotel.telephone` | +261 32 21 709 93 |
| `hotel.whatsapp` | 261322170993 |
| `hotel.email` | home.sakalava27@gmail.com |
| `hotel.adresse` | Ampasikely · 207 Dzamandzar · Nosy Be, région Diana · Madagascar |
| `reseaux.facebook` | facebook.com/home.sakalava.nosybe |
| `smtp.hote` · `port` · `securite` | smtp.gmail.com · 587 · tls |
| `smtp.utilisateur` · `expediteur_email` | home.sakalava27@gmail.com |

### Reste à fournir

| Champ | Ce que c'est | Où le trouver |
|---|---|---|
| **`smtp.motdepasse`** | **Indispensable.** Mot de passe d'application Google. | Voir §2 ci-dessous. Ce n'est **pas** le mot de passe du compte Gmail. |
| **`site.url`** | **Indispensable.** Adresse définitive, sans barre finale. Ex. `https://homesakalava.mg` | Votre nom de domaine, une fois acheté. Sans lui : plan du site et aperçu de partage incomplets. |
| `hotel.gps` | Facultatif. `-13.3987, 48.2345` | Google Maps, appui long sur la maison. |
| `hotel.maps` | Facultatif. Lien vers le plan. | Google Maps → Partager → Copier le lien. Laissé vide, il est déduit du GPS. |
| `reseaux.instagram` · `tripadvisor` · `booking` | Facultatifs. | Adresse complète en `https://`. Vides = liens non affichés. |
| `formulaire.copie_cachee` | Facultatif. Copie de chaque demande. | Une autre adresse e-mail. |
| **`formulaire.mode_test`** | **À passer sur `false`** le jour de la mise en ligne. | Voir §5. |

---

## 2. Le mot de passe d'application Gmail

Gmail refuse le mot de passe du compte pour un envoi SMTP. Il faut un
**mot de passe d'application**, en 16 lettres.

1. La **validation en deux étapes** doit être activée sur le compte
   `home.sakalava27@gmail.com`.
   → myaccount.google.com → Sécurité → Validation en deux étapes.
2. Allez sur **myaccount.google.com/apppasswords**
3. Nommez-le « Site Home Sakalava », validez.
4. Google affiche 16 lettres, par groupes de quatre. **Copiez-les sans les
   espaces** dans `smtp.motdepasse`, ou dans Réglages → Envoi des e-mails.

> Gmail limite à environ 500 envois par jour. Largement suffisant : chaque
> demande en consomme deux.
>
> Si vous prenez plus tard une adresse à votre domaine (`contact@votre-site`),
> remplacez les quatre champs `smtp.*` par ceux de votre hébergeur.

---

## 3. Préparer le dossier à téléverser

Depuis un terminal ouvert dans le dossier du projet :

```
php tools/package.php
```

Cela crée **`livraison/`**, prêt à partir. Le script écarte votre compte
administrateur, les fiches de travail, les journaux, les sources de photos
et les outils de développement — puis vérifie qu'aucun fichier sensible n'a
suivi, et s'arrête en erreur si c'est le cas.

Il vous rappelle aussi, avant de finir, ce qui reste à renseigner.

---

## 4. Téléverser

Copiez **tout le contenu** de `livraison/` à la racine de votre hébergement —
le dossier nommé `www/`, `public_html/` ou `htdocs/` selon l'hébergeur.

Le site fonctionne à la racine d'un domaine comme dans un sous-dossier : tous
les chemins sont relatifs.

> **Si vous installez dans un sous-dossier**, ouvrez `.htaccess` et complétez
> la ligne `ErrorDocument 404 /404.html` avec votre dossier :
> `ErrorDocument 404 /mon-dossier/404.html`

### Droits d'écriture

Ces dossiers doivent être accessibles en écriture (permissions `755`, ou
`775` si votre hébergeur l'exige) :

```
data/    uploads/    storage/originals/    et la racine du site
```

### Compte administrateur

Avec un accès SSH :

```
php tools/create-admin.php
```

Sinon, créez-le sur votre ordinateur avec la même commande, puis téléversez
le seul fichier `data/admin.json` ainsi produit.

> Le mot de passe actuel est provisoire et connu : **changez-le**.

### HTTPS

Activez le certificat gratuit proposé par votre hébergeur. **Une fois qu'il
fonctionne**, ouvrez `.htaccess` et retirez les `#` du bloc « Forcer HTTPS »
en fin de fichier.

> Ne l'activez pas avant : le site deviendrait inaccessible.

---

## 5. Basculer le formulaire en envoi réel

Tant que `formulaire.mode_test` vaut `true`, **aucun e-mail ne part** : les
messages sont écrits dans `data/emails-test.log`. C'est fait pour vérifier le
formulaire sans SMTP.

Le jour de la mise en ligne, une fois le mot de passe d'application renseigné :

- soit dans `config/site.php` : `'mode_test' => false`
- soit dans **Réglages → Envoi des e-mails**, décochez « Mode test »

Puis faites le test du §6.

---

## 6. Vérifications après mise en ligne

Dans cet ordre. Comptez dix minutes.

### Le diagnostic, d'abord

Ouvrez **`/admin/diagnostic.php`**. La page contrôle la version de PHP, les
extensions, les droits d'écriture, les sessions, le HTTPS, et **interroge
réellement vos dossiers sensibles par requête HTTP**.

C'est ce qui détecte le cas où l'hébergeur ignore les fichiers `.htaccess` —
situation où votre contenu et votre mot de passe haché seraient publiquement
téléchargeables sans que rien ne le laisse deviner.

**Rien ne doit rester en rouge.**

### Le formulaire, ensuite

1. Allez sur **Accès & Contact**, remplissez le formulaire avec **votre
   propre adresse e-mail**, envoyez.
2. Vous devez voir le message de confirmation **sans que la page recharge**.
3. **Deux e-mails** doivent arriver :
   - sur `home.sakalava27@gmail.com` : la demande complète. **Répondez-y** :
     la réponse doit partir vers l'adresse du client, pas vers vous-même.
   - sur votre adresse : l'accusé de réception, avec le récapitulatif et les
     coordonnées de la maison.
4. Vérifiez qu'ils ne sont pas dans les indésirables. S'ils y sont,
   l'expéditeur ne correspond probablement pas au compte SMTP.

### Les erreurs volontaires

Dans le formulaire, vérifiez que chacune est refusée **avec un message sous
le champ concerné** :

- laisser le prénom vide
- saisir `bonjour` comme e-mail
- mettre une date d'arrivée dans le passé
- mettre un départ avant l'arrivée

### Sur téléphone

Ouvrez le site sur un vrai téléphone :

- [ ] Le menu s'ouvre et se ferme
- [ ] Les **boutons WhatsApp et appel** apparaissent en bas à droite, et
      ouvrent bien WhatsApp et le composeur
- [ ] Le carrousel des chambres défile au doigt, les flèches répondent
- [ ] Le formulaire est utilisable au pouce, sans zoom automatique
- [ ] **Aucun défilement horizontal** sur aucune page
- [ ] Le changement FR/EN fonctionne et garde la page courante

### Le reste

- [ ] Une adresse inexistante affiche la page 404 (essayez `/nimporte-quoi`)
- [ ] `/sitemap.xml` liste bien les 8 pages avec votre domaine
- [ ] Décommentez la ligne `Sitemap:` dans `robots.txt` avec votre adresse
- [ ] Déclarez le site dans Google Search Console et soumettez le sitemap

---

## 7. Sauvegarder

Trois dossiers suffisent à tout restaurer :

```
config/    les coordonnées et les réglages SMTP
data/      le contenu, le compte administrateur
uploads/   les photos publiées
storage/   les photos d'origine
```

Le reste se régénère avec `php tools/build.php`.

---

## 8. En cas de problème

**Le formulaire renvoie une erreur.**
Regardez `data/erreurs.log`. Si le message parle de SMTP, vérifiez le mot de
passe d'application et que la validation en deux étapes est bien active.

**Les e-mails partent en indésirables.**
L'adresse expéditrice doit appartenir au même compte que le SMTP. Avec Gmail,
`expediteur_email` doit être exactement `home.sakalava27@gmail.com`.

**Une modification n'apparaît pas sur le site.**
Réglages → Régénérer le site. Sinon, videz le cache du navigateur (Ctrl+F5).

**J'ai oublié le mot de passe administrateur.**
Relancez `php tools/create-admin.php` : il propose de le remplacer.

**Le site affiche « à renseigner ».**
Un champ de `config/site.php` est resté à `A_REMPLIR`. Le tableau de bord de
l'administration en donne la liste, avec un lien vers le bon champ.

---

## 9. Vérifier avant de livrer

```
php tools/build.php        # régénère les 10 pages
node tools/smoke-test.js . # le site s'affiche sans erreur
php tools/test-admin.php   # 89 contrôles sur l'administration
node tools/check-cdn.js    # les librairies externes répondent
```

`test-admin.php` exerce réellement l'administration et le formulaire, puis
**restaure le contenu tel qu'il était**. Vous pouvez le lancer sans crainte.
