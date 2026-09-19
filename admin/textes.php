<?php
/**
 * admin/textes.php — Textes des sections, coordonnées et questions fréquentes.
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

            // ── Accueil, histoire, accès, réservation ────────────────────
            case 'texts':
                $content['brand']['name']     = post_str('brand_name');
                $content['brand']['logoText'] = post_str('brand_logo');
                $content['brand']['wordmark'] = post_str('brand_wordmark');

                $content['hero']['logoName']      = post_str('hero_logo');
                $content['hero']['logoSub']       = post_str('hero_sub');
                $content['hero']['badge']         = post_str('hero_badge');
                $content['hero']['headlineTitle'] = post_str('hero_title');
                $content['hero']['headlineSub']   = post_str('hero_headline_sub');

                $content['story']['kicker'] = post_str('story_kicker');
                $content['story']['text']   = post_str('story_text');
                $content['story']['stat']   = post_str('story_stat');

                $content['access']['kicker'] = post_str('acces_kicker');
                $content['access']['title']  = post_str('acces_title');
                $content['access']['text']   = post_str('acces_text');

                $content['booking']['title'] = post_str('booking_title');
                $content['booking']['intro'] = post_str('booking_intro');

                $content['footer']['ctaTitle']  = post_str('footer_title');
                $content['footer']['ctaButton'] = post_str('footer_button');

                $store->write($content);
                Flash::ok('Textes enregistrés.');
                break;

            // ── Coordonnées et infos pratiques ───────────────────────────
            case 'contact':
                $email = post_str('email');
                if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new RuntimeException(
                        "L'adresse e-mail n'est pas valide. Exemple attendu : "
                        . "contact@homesakalava.mg"
                    );
                }

                $facebook = post_str('facebook');
                if ($facebook !== '' && !filter_var($facebook, FILTER_VALIDATE_URL)) {
                    throw new RuntimeException(
                        "Le lien Facebook doit être une adresse complète, "
                        . "commençant par https://"
                    );
                }

                $content['contact']['phone']        = post_str('phone');
                $content['contact']['email']        = $email;
                $content['contact']['gps']          = post_str('gps');
                $content['contact']['facebook']     = $facebook;
                $content['contact']['addressLines'] = post_list('address');
                $content['contact']['checkinFrom']  = post_str('checkin_from');
                $content['contact']['checkinTo']    = post_str('checkin_to');
                $content['contact']['checkoutFrom'] = post_str('checkout_from');
                $content['contact']['checkoutTo']   = post_str('checkout_to');
                $content['contact']['languages']    = post_str('languages');
                $content['contact']['payment']      = post_str('payment');

                $store->write($content);
                Flash::ok(
                    $email !== ''
                        ? 'Coordonnées enregistrées. Le formulaire de contact du site est désormais actif.'
                        : 'Coordonnées enregistrées.'
                );
                break;

            // ── Questions fréquentes ─────────────────────────────────────
            case 'faq':
                $qs = $_POST['faq_q'] ?? [];
                $as = $_POST['faq_a'] ?? [];
                $items = [];
                if (is_array($qs)) {
                    foreach ($qs as $k => $q) {
                        $q = is_string($q) ? clean_input($q) : '';
                        $a = is_string($as[$k] ?? '') ? clean_input((string) $as[$k]) : '';
                        if ($q !== '' && $a !== '') {
                            $items[] = ['q' => $q, 'a' => $a];
                        }
                    }
                }
                $content['faq']['kicker'] = post_str('faq_kicker');
                $content['faq']['title']  = post_str('faq_title');
                $content['faq']['intro']  = post_str('faq_intro');
                $content['faq']['items']  = $items;

                $store->write($content);
                Flash::ok(count($items) . ' question(s) enregistrée(s).');
                break;
        }
    } catch (Throwable $ex) {
        Flash::error($ex->getMessage());
    }

    redirect('textes.php');
}

$k = $content['contact'] ?? [];

layout_head('Textes');
?>

<!-- ═══ COORDONNÉES ═══ -->
<form method="post" data-guard id="contact">
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="contact">

  <div class="card">
    <div class="card__head"><h2 class="card__title">Coordonnées</h2></div>
    <p class="card__hint">
      Tant qu'un champ reste vide, le site affiche « à renseigner » à sa place
      plutôt qu'une information inventée.
    </p>

    <div class="grid-2">
      <div class="field">
        <label for="phone">Téléphone</label>
        <input id="phone" name="phone" type="tel" maxlength="40"
               placeholder="+261 32 12 345 67" value="<?= e($k['phone'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="email">Adresse e-mail</label>
        <input id="email" name="email" type="email" maxlength="120"
               placeholder="contact@…" value="<?= e($k['email'] ?? '') ?>">
        <p class="field__hint">
          <?php if (($k['email'] ?? '') === ''): ?>
            <strong>Important :</strong> le formulaire de contact du site reste
            désactivé tant que cette adresse n'est pas renseignée.
          <?php else: ?>
            Le formulaire du site prépare un message vers cette adresse.
          <?php endif; ?>
        </p>
      </div>
    </div>

    <div class="grid-2">
      <div class="field">
        <label for="gps">Coordonnées GPS</label>
        <input id="gps" name="gps" type="text" maxlength="60"
               placeholder="-13.3987, 48.2345" value="<?= e($k['gps'] ?? '') ?>">
        <p class="field__hint">
          Se relève dans Google Maps : appui long sur la maison, les chiffres
          s'affichent en haut.
        </p>
      </div>
      <div class="field">
        <label for="facebook">Page Facebook</label>
        <input id="facebook" name="facebook" type="url" maxlength="200"
               value="<?= e($k['facebook'] ?? '') ?>">
      </div>
    </div>

    <div class="field">
      <label>Adresse postale</label>
      <p class="field__hint" style="margin:0 0 8px">Une ligne par champ.</p>
      <div class="stack">
        <?php foreach (array_merge($k['addressLines'] ?? [], ['']) as $line): ?>
          <input type="text" name="address[]" maxlength="120" value="<?= e($line) ?>">
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card__head"><h2 class="card__title">Informations pratiques</h2></div>

    <div class="grid-2">
      <div class="field">
        <label for="cif">Arrivée à partir de</label>
        <input id="cif" name="checkin_from" type="text" maxlength="12"
               placeholder="12h00" value="<?= e($k['checkinFrom'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="cit">Arrivée jusqu'à</label>
        <input id="cit" name="checkin_to" type="text" maxlength="12"
               placeholder="23h00" value="<?= e($k['checkinTo'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="cof">Départ à partir de</label>
        <input id="cof" name="checkout_from" type="text" maxlength="12"
               placeholder="11h00" value="<?= e($k['checkoutFrom'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="cot">Départ avant</label>
        <input id="cot" name="checkout_to" type="text" maxlength="12"
               placeholder="12h00" value="<?= e($k['checkoutTo'] ?? '') ?>">
      </div>
    </div>

    <div class="grid-2">
      <div class="field">
        <label for="lang">Langues parlées</label>
        <input id="lang" name="languages" type="text" maxlength="120"
               value="<?= e($k['languages'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="pay">Paiement</label>
        <input id="pay" name="payment" type="text" maxlength="120"
               value="<?= e($k['payment'] ?? '') ?>">
      </div>
    </div>
  </div>

  <div class="savebar">
    <button type="submit" class="btn">Enregistrer les coordonnées</button>
  </div>
</form>

<!-- ═══ TEXTES ═══ -->
<form method="post" data-guard style="margin-top:30px">
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="texts">

  <div class="card">
    <div class="card__head"><h2 class="card__title">Nom de la maison</h2></div>
    <div class="grid-2">
      <div class="field">
        <label for="bn">Nom complet</label>
        <input id="bn" name="brand_name" type="text" maxlength="60"
               value="<?= e($content['brand']['name'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="bl">Nom court (menu)</label>
        <input id="bl" name="brand_logo" type="text" maxlength="30"
               value="<?= e($content['brand']['logoText'] ?? '') ?>">
      </div>
    </div>
    <div class="field">
      <label for="bw">Grand mot en bas de page</label>
      <input id="bw" name="brand_wordmark" type="text" maxlength="30"
             value="<?= e($content['brand']['wordmark'] ?? '') ?>">
    </div>
  </div>

  <div class="card">
    <div class="card__head"><h2 class="card__title">Première image du site</h2></div>

    <div class="grid-2">
      <div class="field">
        <label for="hl">Nom affiché en haut</label>
        <input id="hl" name="hero_logo" type="text" maxlength="60"
               value="<?= e($content['hero']['logoName'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="hs">Lieu, sous le nom</label>
        <input id="hs" name="hero_sub" type="text" maxlength="60"
               value="<?= e($content['hero']['logoSub'] ?? '') ?>">
      </div>
    </div>

    <div class="field">
      <label for="hb">Petite étiquette</label>
      <input id="hb" name="hero_badge" type="text" maxlength="40"
             value="<?= e($content['hero']['badge'] ?? '') ?>">
    </div>

    <div class="field">
      <label for="ht">Grande phrase d'accroche</label>
      <textarea id="ht" name="hero_title" rows="2" maxlength="90"><?= e($content['hero']['headlineTitle'] ?? '') ?></textarea>
      <p class="field__hint">Un retour à la ligne coupe la phrase en deux.</p>
    </div>

    <div class="field">
      <label for="hhs">Ligne au-dessus de l'accroche</label>
      <input id="hhs" name="hero_headline_sub" type="text" maxlength="60"
             value="<?= e($content['hero']['headlineSub'] ?? '') ?>">
    </div>
  </div>

  <div class="card">
    <div class="card__head"><h2 class="card__title">Notre histoire</h2></div>
    <div class="field">
      <label for="sk">Petit titre</label>
      <input id="sk" name="story_kicker" type="text" maxlength="60"
             value="<?= e($content['story']['kicker'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="st">Texte principal</label>
      <textarea id="st" name="story_text" rows="5" maxlength="800"><?= e($content['story']['text'] ?? '') ?></textarea>
    </div>
    <div class="field">
      <label for="ss">Second paragraphe</label>
      <textarea id="ss" name="story_stat" rows="5" maxlength="800"><?= e($content['story']['stat'] ?? '') ?></textarea>
    </div>
  </div>

  <div class="card">
    <div class="card__head"><h2 class="card__title">Accès et contact</h2></div>
    <div class="grid-2">
      <div class="field">
        <label for="ak">Petit titre</label>
        <input id="ak" name="acces_kicker" type="text" maxlength="60"
               value="<?= e($content['access']['kicker'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="at">Titre</label>
        <input id="at" name="acces_title" type="text" maxlength="120"
               value="<?= e($content['access']['title'] ?? '') ?>">
      </div>
    </div>
    <div class="field">
      <label for="ax">Texte</label>
      <textarea id="ax" name="acces_text" rows="4" maxlength="600"><?= e($content['access']['text'] ?? '') ?></textarea>
    </div>
  </div>

  <div class="card">
    <div class="card__head"><h2 class="card__title">Formulaire de contact</h2></div>
    <div class="field">
      <label for="bt">Titre</label>
      <input id="bt" name="booking_title" type="text" maxlength="80"
             value="<?= e($content['booking']['title'] ?? '') ?>">
    </div>
    <div class="field">
      <label for="bi">Texte d'introduction</label>
      <textarea id="bi" name="booking_intro" rows="4" maxlength="600"><?= e($content['booking']['intro'] ?? '') ?></textarea>
    </div>
  </div>

  <div class="card">
    <div class="card__head"><h2 class="card__title">Bas de page</h2></div>
    <div class="field">
      <label for="ft">Grande phrase</label>
      <textarea id="ft" name="footer_title" rows="2" maxlength="120"><?= e($content['footer']['ctaTitle'] ?? '') ?></textarea>
    </div>
    <div class="field">
      <label for="fb">Texte du bouton</label>
      <input id="fb" name="footer_button" type="text" maxlength="40"
             value="<?= e($content['footer']['ctaButton'] ?? '') ?>">
    </div>
  </div>

  <div class="savebar">
    <button type="submit" class="btn">Enregistrer les textes</button>
  </div>
</form>

<!-- ═══ FAQ ═══ -->
<form method="post" data-guard style="margin-top:30px">
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="faq">

  <div class="card">
    <div class="card__head"><h2 class="card__title">Questions fréquentes</h2></div>
    <p class="card__hint">
      Videz une question pour la supprimer. Une question sans réponse n'est
      pas enregistrée.
    </p>

    <div class="grid-2">
      <div class="field">
        <label for="fk">Petit titre</label>
        <input id="fk" name="faq_kicker" type="text" maxlength="60"
               value="<?= e($content['faq']['kicker'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="fqt">Titre</label>
        <textarea id="fqt" name="faq_title" rows="2" maxlength="120"><?= e($content['faq']['title'] ?? '') ?></textarea>
      </div>
    </div>

    <div class="field">
      <label for="fi">Introduction</label>
      <input id="fi" name="faq_intro" type="text" maxlength="200"
             value="<?= e($content['faq']['intro'] ?? '') ?>">
    </div>

    <div class="stack" style="margin-top:12px">
      <?php foreach (array_merge($content['faq']['items'] ?? [], [['q' => '', 'a' => '']]) as $n => $item): ?>
        <div class="card" style="margin:0;background:var(--c-bg)">
          <div class="field">
            <label for="q<?= $n ?>">Question <?= $n + 1 ?></label>
            <input id="q<?= $n ?>" name="faq_q[]" type="text" maxlength="200"
                   value="<?= e($item['q'] ?? '') ?>">
          </div>
          <div class="field" style="margin:0">
            <label for="a<?= $n ?>">Réponse</label>
            <textarea id="a<?= $n ?>" name="faq_a[]" rows="4" maxlength="900"><?= e($item['a'] ?? '') ?></textarea>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="savebar">
    <button type="submit" class="btn">Enregistrer les questions</button>
  </div>
</form>

<?php layout_foot(); ?>
