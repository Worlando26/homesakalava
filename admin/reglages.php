<?php
/**
 * admin/reglages.php — Réglages globaux, référencement, mot de passe.
 */

declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';
Auth::require();

$content = $store->read();

if (is_post()) {
    Csrf::require();

    try {
        switch (post_str('action')) {

            // ── Affichage des tarifs ─────────────────────────────────────
            case 'prices':
                $fallback = post_str('fallback');
                if ($fallback === '') {
                    throw new RuntimeException(
                        'La mention de remplacement ne peut pas être vide : '
                        . 'elle occupe la place du prix sur les chambres sans tarif.'
                    );
                }
                $content['settings']['showPrices']    = post_bool('showPrices');
                $content['settings']['priceFallback'] = $fallback;
                $store->write($content);
                Flash::ok(
                    post_bool('showPrices')
                        ? 'Les tarifs sont maintenant visibles sur le site.'
                        : 'Les tarifs sont masqués sur le site.'
                );
                break;

            // ── Référencement ────────────────────────────────────────────
            case 'seo':
                $url = post_str('siteUrl');
                if ($url !== '') {
                    if (!filter_var($url, FILTER_VALIDATE_URL)
                        || !preg_match('#^https?://#i', $url)) {
                        throw new RuntimeException(
                            "L'adresse du site doit être complète, par exemple : "
                            . 'https://homesakalava.mg'
                        );
                    }
                    $url = rtrim($url, '/');
                }

                $title = post_str('seoTitle');
                $desc  = post_str('seoDesc');
                if (mb_strlen($desc) > 300) {
                    throw new RuntimeException('La description dépasse 300 caractères.');
                }

                // L'adresse du site vit dans config/site.php, comme les autres
                // réglages opérationnels : une seule source fait autorité.
                $conf = require APP_ROOT . '/config/site.php';
                $conf['site']['url'] = $url ?: Config::A_REMPLIR;
                Config::ecrire($conf);

                // Le titre et la description restent éditoriaux, donc traduits.
                $content['seo']['title']       = $title;
                $content['seo']['description'] = $desc;
                $store->write($content);

                Flash::ok('Référencement enregistré. Le plan du site a été régénéré.');
                break;

            // ── Envoi des e-mails ────────────────────────────────────────
            case 'smtp':
                $conf = require APP_ROOT . '/config/site.php';

                $expediteur = post_str('expediteur_email');
                if ($expediteur !== '' && !filter_var($expediteur, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException("L'adresse expéditrice n'est pas valide.");
                }

                $securite = post_str('securite');
                if (!in_array($securite, ['tls', 'ssl', ''], true)) {
                    throw new RuntimeException("Le chiffrement doit être « tls », « ssl » ou aucun.");
                }

                $port = (int) post_str('port');
                if ($port < 1 || $port > 65535) {
                    throw new RuntimeException("Le port doit être un nombre entre 1 et 65535.");
                }

                $conf['smtp']['hote']             = post_str('hote') ?: Config::A_REMPLIR;
                $conf['smtp']['port']             = $port;
                $conf['smtp']['securite']         = $securite;
                $conf['smtp']['utilisateur']      = post_str('utilisateur') ?: Config::A_REMPLIR;
                $conf['smtp']['expediteur_email'] = $expediteur ?: Config::A_REMPLIR;
                $conf['smtp']['expediteur_nom']   = post_str('expediteur_nom');

                /**
                 * Le mot de passe n'est jamais réaffiché. Un champ laissé vide
                 * signifie « ne change rien » : sans cela, enregistrer le
                 * formulaire pour corriger le port effacerait le mot de passe.
                 */
                $motdepasse = (string) ($_POST['motdepasse'] ?? '');
                if ($motdepasse !== '') {
                    $conf['smtp']['motdepasse'] = $motdepasse;
                }

                $conf['formulaire']['mode_test']     = post_bool('mode_test');
                $conf['formulaire']['max_par_heure'] = max(1, (int) post_str('max_par_heure'));
                $conf['formulaire']['copie_cachee']  = post_str('copie_cachee') ?: Config::A_REMPLIR;

                Config::ecrire($conf);

                // Le site est régénéré : l'état du formulaire en dépend.
                $store->write($store->read());

                Flash::ok(
                    Config::modeTest()
                        ? "Enregistré. Le MODE TEST est actif : aucun e-mail ne part, "
                          . "ils sont écrits dans data/emails-test.log."
                        : 'Enregistré. Les e-mails partent réellement.'
                );
                break;

            // ── Couleurs ─────────────────────────────────────────────────
            case 'theme':
                foreach (['accent', 'dark', 'bg'] as $key) {
                    $v = post_str($key);
                    if (!preg_match('/^#[0-9a-f]{6}$/i', $v)) {
                        throw new RuntimeException(
                            "La couleur « $key » doit être au format #rrggbb."
                        );
                    }
                    $content['theme'][$key] = strtolower($v);
                }
                $store->write($content);
                Flash::ok('Couleurs enregistrées.');
                break;

            // ── Mot de passe ─────────────────────────────────────────────
            case 'password':
                $current = (string) ($_POST['current'] ?? '');
                $new     = (string) ($_POST['new'] ?? '');
                $confirm = (string) ($_POST['confirm'] ?? '');

                if (!Auth::verifyCurrent($current)) {
                    throw new RuntimeException('Le mot de passe actuel est incorrect.');
                }
                if ($new !== $confirm) {
                    throw new RuntimeException('Les deux nouveaux mots de passe ne correspondent pas.');
                }
                // setPassword impose lui-même la longueur minimale.
                Auth::setPassword(Auth::username(), $new);
                Flash::ok('Mot de passe modifié.');
                break;

            // ── Régénération manuelle du site ────────────────────────────
            case 'rebuild':
                $store->write($content);
                Flash::ok('Site régénéré à partir du contenu enregistré.');
                break;
        }
    } catch (Throwable $ex) {
        Flash::error($ex->getMessage());
    }

    redirect('reglages.php');
}

$s   = $content['settings'] ?? [];
$seo = $content['seo'] ?? [];
$th  = $content['theme'] ?? [];

layout_head('Réglages');
?>

<!-- ═══ TARIFS ═══ -->
<form method="post" data-guard>
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="prices">

  <div class="card">
    <div class="card__head"><h2 class="card__title">Affichage des tarifs</h2></div>
    <p class="card__hint">
      Réglage valable pour tout le site. Vous pouvez saisir les prix des
      chambres sans les rendre visibles tout de suite.
    </p>

    <label class="switch">
      <input type="checkbox" name="showPrices" value="1"
             <?= !empty($s['showPrices']) ? 'checked' : '' ?>>
      Afficher les tarifs sur le site
    </label>

    <div class="field" style="margin-top:14px">
      <label for="fb">Mention affichée à la place d'un prix</label>
      <input id="fb" name="fallback" type="text" maxlength="60" required
             value="<?= e($s['priceFallback'] ?? '') ?>">
      <p class="field__hint">
        Utilisée quand les tarifs sont masqués, ou quand une chambre n'a pas
        de prix saisi.
      </p>
    </div>
  </div>

  <div class="savebar">
    <button type="submit" class="btn">Enregistrer</button>
  </div>
</form>

<!-- ═══ RÉFÉRENCEMENT ═══ -->
<form method="post" data-guard style="margin-top:30px">
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="seo">

  <div class="card">
    <div class="card__head"><h2 class="card__title">Référencement (Google)</h2></div>

    <div class="field">
      <label for="url">Adresse définitive du site</label>
      <input id="url" name="siteUrl" type="url" maxlength="160"
             placeholder="https://homesakalava.mg" value="<?= e(Config::val('site.url')) ?>">
      <p class="field__hint">
        <?php if (!Config::rempli('site.url')): ?>
          Tant que cette adresse est vide, le plan du site (sitemap) et
          l'aperçu de partage restent incomplets. À renseigner dès que le
          nom de domaine est acheté.
        <?php else: ?>
          Utilisée pour le plan du site et l'aperçu de partage.
        <?php endif; ?>
      </p>
    </div>

    <div class="field">
      <label for="st">Titre dans les résultats Google</label>
      <input id="st" name="seoTitle" type="text" maxlength="120"
             value="<?= e($seo['title'] ?? '') ?>">
      <p class="field__hint">
        Environ 60 caractères. Actuellement :
        <?= mb_strlen($seo['title'] ?? '') ?> caractères.
      </p>
    </div>

    <div class="field">
      <label for="sd">Description dans les résultats Google</label>
      <textarea id="sd" name="seoDesc" rows="4" maxlength="300"><?= e($seo['description'] ?? '') ?></textarea>
      <p class="field__hint">
        Entre 120 et 160 caractères. Actuellement :
        <?= mb_strlen($seo['description'] ?? '') ?> caractères.
      </p>
    </div>
  </div>

  <div class="savebar">
    <button type="submit" class="btn">Enregistrer le référencement</button>
  </div>
</form>

<!-- ═══ ENVOI DES E-MAILS ═══ -->
<form method="post" data-guard style="margin-top:30px">
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="smtp">

  <div class="card">
    <div class="card__head"><h2 class="card__title">Envoi des e-mails</h2></div>
    <p class="card__hint">
      Ces réglages vous sont donnés par votre hébergeur ou par le fournisseur
      de la boîte mail. Avec Gmail, il faut un « mot de passe d'application »,
      pas le mot de passe du compte.
    </p>

    <?php if (Config::modeTest()): ?>
      <p class="alert alert--warn">
        <strong>Mode test actif.</strong> Aucun e-mail ne part : chaque message
        est écrit dans <code>data/emails-test.log</code>. Pratique pour vérifier
        le formulaire sans SMTP. À décocher le jour de la mise en ligne.
      </p>
    <?php endif; ?>

    <label class="switch">
      <input type="checkbox" name="mode_test" value="1"
             <?= Config::modeTest() ? 'checked' : '' ?>>
      Mode test — n'envoyer aucun e-mail
    </label>

    <div class="grid-2" style="margin-top:14px">
      <div class="field">
        <label for="s-hote">Serveur d'envoi</label>
        <input id="s-hote" name="hote" type="text" maxlength="120"
               placeholder="ssl0.ovh.net" value="<?= e(Config::val('smtp.hote')) ?>">
      </div>
      <div class="field">
        <label for="s-port">Port</label>
        <input id="s-port" name="port" type="number" min="1" max="65535"
               value="<?= e((string) Config::get('smtp.port', 587)) ?>">
        <p class="field__hint">587 avec TLS, 465 avec SSL.</p>
      </div>
    </div>

    <div class="field">
      <label for="s-sec">Chiffrement</label>
      <select id="s-sec" name="securite">
        <?php foreach (['tls' => 'TLS (port 587)', 'ssl' => 'SSL (port 465)', '' => 'Aucun'] as $v => $lib): ?>
          <option value="<?= e($v) ?>"<?= (string) Config::get('smtp.securite', 'tls') === $v ? ' selected' : '' ?>>
            <?= e($lib) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="grid-2">
      <div class="field">
        <label for="s-user">Identifiant</label>
        <input id="s-user" name="utilisateur" type="text" maxlength="120"
               autocomplete="off" value="<?= e(Config::val('smtp.utilisateur')) ?>">
        <p class="field__hint">Souvent l'adresse e-mail complète.</p>
      </div>
      <div class="field">
        <label for="s-pass">Mot de passe</label>
        <input id="s-pass" name="motdepasse" type="password" autocomplete="new-password"
               placeholder="<?= Config::rempli('smtp.motdepasse') ? '••••••••  (enregistré)' : 'à renseigner' ?>">
        <p class="field__hint">
          Laissez vide pour conserver le mot de passe actuel.
        </p>
      </div>
    </div>

    <div class="grid-2">
      <div class="field">
        <label for="s-exp">Adresse expéditrice</label>
        <input id="s-exp" name="expediteur_email" type="email" maxlength="120"
               value="<?= e(Config::val('smtp.expediteur_email')) ?>">
        <p class="field__hint">
          Doit appartenir au même domaine que le compte, sinon les messages
          partiront en indésirables.
        </p>
      </div>
      <div class="field">
        <label for="s-expn">Nom affiché</label>
        <input id="s-expn" name="expediteur_nom" type="text" maxlength="80"
               value="<?= e((string) Config::get('smtp.expediteur_nom', '')) ?>">
      </div>
    </div>

    <div class="grid-2">
      <div class="field">
        <label for="s-max">Demandes acceptées par heure</label>
        <input id="s-max" name="max_par_heure" type="number" min="1" max="100"
               value="<?= e((string) Config::get('formulaire.max_par_heure', 5)) ?>">
        <p class="field__hint">Par visiteur. Protège d'un envoi en boucle.</p>
      </div>
      <div class="field">
        <label for="s-bcc">Copie cachée des demandes</label>
        <input id="s-bcc" name="copie_cachee" type="email" maxlength="120"
               value="<?= e(Config::val('formulaire.copie_cachee')) ?>">
        <p class="field__hint">Facultatif. Laissez vide pour n'envoyer aucune copie.</p>
      </div>
    </div>
  </div>

  <div class="savebar">
    <button type="submit" class="btn">Enregistrer l'envoi des e-mails</button>
  </div>
</form>
<!-- ═══ COULEURS ═══ -->
<form method="post" data-guard style="margin-top:30px">
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="theme">

  <div class="card">
    <div class="card__head"><h2 class="card__title">Couleurs du site</h2></div>
    <p class="card__hint">
      À modifier avec précaution : ces trois couleurs pilotent tout le site.
    </p>

    <div class="grid-2">
      <div class="field">
        <label for="c1">Couleur principale (boutons)</label>
        <input id="c1" name="accent" type="text" maxlength="7"
               value="<?= e($th['accent'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="c2">Couleur sombre</label>
        <input id="c2" name="dark" type="text" maxlength="7"
               value="<?= e($th['dark'] ?? '') ?>">
      </div>
    </div>
    <div class="field">
      <label for="c3">Couleur de fond</label>
      <input id="c3" name="bg" type="text" maxlength="7"
             value="<?= e($th['bg'] ?? '') ?>">
    </div>
  </div>

  <div class="savebar">
    <button type="submit" class="btn">Enregistrer les couleurs</button>
  </div>
</form>

<!-- ═══ MOT DE PASSE ═══ -->
<form method="post" style="margin-top:30px">
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="password">

  <div class="card">
    <div class="card__head"><h2 class="card__title">Mot de passe</h2></div>
    <p class="card__hint">
      Au moins 10 caractères. Trois ou quatre mots sans rapport font un
      excellent mot de passe, facile à retenir.
    </p>

    <div class="field">
      <label for="pc">Mot de passe actuel</label>
      <input id="pc" name="current" type="password" autocomplete="current-password" required>
    </div>
    <div class="grid-2">
      <div class="field">
        <label for="pn">Nouveau mot de passe</label>
        <input id="pn" name="new" type="password" autocomplete="new-password"
               minlength="10" required>
      </div>
      <div class="field">
        <label for="px">Confirmer</label>
        <input id="px" name="confirm" type="password" autocomplete="new-password"
               minlength="10" required>
      </div>
    </div>
  </div>

  <div class="savebar">
    <button type="submit" class="btn">Changer le mot de passe</button>
  </div>
</form>

<!-- ═══ MAINTENANCE ═══ -->
<form method="post" style="margin-top:30px">
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="rebuild">

  <div class="card">
    <div class="card__head"><h2 class="card__title">Régénérer le site</h2></div>
    <p class="card__hint">
      Le site se régénère tout seul à chaque enregistrement. Ce bouton ne sert
      que si quelque chose semble ne pas s'être mis à jour.
    </p>
    <button type="submit" class="btn btn--outline">Régénérer maintenant</button>
  </div>
</form>

<?php layout_foot(); ?>
