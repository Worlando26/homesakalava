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

                $content['seo']['siteUrl']     = $url;
                $content['seo']['title']       = $title;
                $content['seo']['description'] = $desc;
                $store->write($content);
                Flash::ok('Référencement enregistré. Le sitemap a été régénéré.');
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
             placeholder="https://homesakalava.mg" value="<?= e($seo['siteUrl'] ?? '') ?>">
      <p class="field__hint">
        <?php if (($seo['siteUrl'] ?? '') === ''): ?>
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
