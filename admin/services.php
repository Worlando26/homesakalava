<?php
/**
 * admin/services.php — Services de la maison, restaurant et activités.
 *
 * Trois listes construites sur le même principe : libellé, icône, et pour
 * les services une case « supplément ». L'ordre de saisie est l'ordre
 * d'affichage ; une ligne vidée est supprimée.
 */

declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';
Auth::require();

$content = $store->read();

/**
 * Icônes proposées. La liste est volontairement courte et nommée en
 * français : le gérant choisit une image, pas un identifiant technique.
 */
const ICONS = [
    'wifi'            => 'Wifi',
    'square-parking'  => 'Parking',
    'shield-check'    => 'Sécurité',
    'sparkles'        => 'Ménage',
    'waves'           => 'Mer / plage',
    'map'             => 'Excursions',
    'banknote'        => 'Argent',
    'dices'           => 'Jeux',
    'plane'           => 'Avion / navette',
    'car'             => 'Voiture',
    'bike'            => 'Vélo',
    'hand-heart'      => 'Massage',
    'shirt'           => 'Blanchisserie',
    'luggage'         => 'Bagages',
    'baby'            => 'Enfants',
    'utensils'        => 'Restaurant',
    'fish'            => 'Poisson',
    'coffee'          => 'Café',
    'salad'           => 'Végétarien',
    'wine'            => 'Boissons',
    'bed'             => 'Chambre',
    'shopping-bag'    => 'Panier repas',
    'concierge-bell'  => 'Service en chambre',
    'anchor'          => 'Plongée',
    'mountain'        => 'Randonnée',
    'rabbit'          => 'Équitation',
    'flag'            => 'Golf',
    'drum'            => 'Culture locale',
    'check'           => 'Autre',
];

/** Menu déroulant d'icône. */
function icon_select(string $name, string $selected): string
{
    $out = '<select name="' . e($name) . '" aria-label="Icône">';
    foreach (ICONS as $value => $label) {
        $out .= '<option value="' . e($value) . '"'
              . ($value === $selected ? ' selected' : '') . '>'
              . e($label) . '</option>';
    }
    return $out . '</select>';
}

/**
 * Reconstruit une liste depuis les champs postés.
 * Une ligne dont le libellé est vide est simplement abandonnée.
 */
function rebuild_list(string $prefix, bool $withSupplement): array
{
    $labels = $_POST[$prefix . '_label'] ?? [];
    $icons  = $_POST[$prefix . '_icon']  ?? [];
    $supp   = $_POST[$prefix . '_supp']  ?? [];

    if (!is_array($labels)) {
        return [];
    }

    $out = [];
    foreach ($labels as $k => $label) {
        $label = is_string($label) ? clean_input($label) : '';
        if ($label === '') {
            continue;
        }
        $icon = (string) ($icons[$k] ?? 'check');
        if (!array_key_exists($icon, ICONS)) {
            $icon = 'check';
        }
        $entry = ['label' => mb_substr($label, 0, 120), 'icon' => $icon];
        if ($withSupplement) {
            $entry['supplement'] = !empty($supp[$k]);
        }
        $out[] = $entry;
    }
    return $out;
}

if (is_post()) {
    Csrf::require();

    try {
        switch (post_str('action')) {

            case 'services':
                $content['services']['title'] = post_str('title');
                $content['services']['pills'] = post_list('pills');
                $content['services']['note']  = post_str('note');
                $content['services']['card']['title'] = post_str('card_title');
                $content['services']['items'] = rebuild_list('svc', true);
                $store->write($content);
                Flash::ok('Services enregistrés.');
                break;

            case 'restaurant':
                $content['restaurant']['kicker'] = post_str('kicker');
                $content['restaurant']['title']  = post_str('title');
                $content['restaurant']['text']   = post_str('text');
                $content['restaurant']['note']   = post_str('note');
                $content['restaurant']['items']  = rebuild_list('rst', false);
                $store->write($content);
                Flash::ok('Restaurant enregistré.');
                break;

            case 'activities':
                $content['activities']['kicker'] = post_str('kicker');
                $content['activities']['title']  = post_str('title');
                $content['activities']['text']   = post_str('text');
                $content['activities']['note']   = post_str('note');
                $content['activities']['items']  = rebuild_list('act', false);

                // Distances : deux colonnes appariées
                $labels = $_POST['dist_label'] ?? [];
                $values = $_POST['dist_value'] ?? [];
                $dist   = [];
                if (is_array($labels)) {
                    foreach ($labels as $k => $label) {
                        $label = is_string($label) ? clean_input($label) : '';
                        $value = is_string($values[$k] ?? '') ? clean_input((string) $values[$k]) : '';
                        if ($label !== '' && $value !== '') {
                            $dist[] = ['label' => $label, 'value' => $value];
                        }
                    }
                }
                $content['activities']['distances'] = $dist;

                $store->write($content);
                Flash::ok('Activités enregistrées.');
                break;
        }
    } catch (Throwable $ex) {
        Flash::error($ex->getMessage());
    }

    redirect('services.php');
}

layout_head('Services');
?>

<p class="section-intro">
  Pour retirer une ligne, videz son libellé puis enregistrez. Des lignes
  vides sont ajoutées en bas de chaque liste pour vous permettre d'en créer
  de nouvelles.
</p>

<!-- ═══ SERVICES ═══ -->
<form method="post" data-guard>
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="services">

  <div class="card">
    <div class="card__head"><h2 class="card__title">Services de la maison</h2></div>

    <div class="field">
      <label for="title">Titre de la section</label>
      <textarea id="title" name="title" rows="2" maxlength="160"><?= e($content['services']['title'] ?? '') ?></textarea>
      <p class="field__hint">Un retour à la ligne coupe le titre en deux lignes sur le site.</p>
    </div>

    <div class="field">
      <label for="card_title">Message de l'encart sombre</label>
      <textarea id="card_title" name="card_title" rows="3" maxlength="240"><?= e($content['services']['card']['title'] ?? '') ?></textarea>
    </div>

    <div class="field">
      <label>Pastilles mises en avant</label>
      <p class="field__hint" style="margin:0 0 8px">
        Les six mots-clés affichés en haut de la section.
      </p>
      <div class="stack">
        <?php foreach (array_merge($content['services']['pills'] ?? [], ['', '']) as $p): ?>
          <input type="text" name="pills[]" maxlength="50" value="<?= e($p) ?>">
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card__head"><h2 class="card__title">Liste complète des services</h2></div>
    <div class="rows">
      <?php
      $items = array_merge($content['services']['items'] ?? [], [
          ['label' => '', 'icon' => 'check', 'supplement' => false],
          ['label' => '', 'icon' => 'check', 'supplement' => false],
      ]);
      foreach ($items as $k => $item): ?>
        <div class="row" style="grid-template-columns:1fr">
          <div class="stack">
            <input type="text" name="svc_label[]" maxlength="120"
                   placeholder="Libellé du service"
                   value="<?= e($item['label'] ?? '') ?>"
                   aria-label="Libellé du service">
            <div class="tools">
              <?= icon_select('svc_icon[]', $item['icon'] ?? 'check') ?>
              <label class="switch">
                <input type="checkbox" name="svc_supp[<?= $k ?>]" value="1"
                       <?= !empty($item['supplement']) ? 'checked' : '' ?>>
                Supplément
              </label>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="field" style="margin-top:16px">
      <label for="note">Mention sous la liste</label>
      <input id="note" name="note" type="text" maxlength="200"
             value="<?= e($content['services']['note'] ?? '') ?>">
    </div>
  </div>

  <div class="savebar">
    <button type="submit" class="btn">Enregistrer les services</button>
  </div>
</form>

<!-- ═══ RESTAURANT ═══ -->
<form method="post" data-guard style="margin-top:30px">
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="restaurant">

  <div class="card">
    <div class="card__head"><h2 class="card__title">Le restaurant</h2></div>

    <div class="grid-2">
      <div class="field">
        <label for="r_kicker">Petit titre</label>
        <input id="r_kicker" name="kicker" type="text" maxlength="60"
               value="<?= e($content['restaurant']['kicker'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="r_title">Titre</label>
        <input id="r_title" name="title" type="text" maxlength="120"
               value="<?= e($content['restaurant']['title'] ?? '') ?>">
      </div>
    </div>

    <div class="field">
      <label for="r_text">Texte de présentation</label>
      <textarea id="r_text" name="text" rows="4" maxlength="600"><?= e($content['restaurant']['text'] ?? '') ?></textarea>
    </div>

    <label class="label">Ce que vous proposez</label>
    <div class="rows">
      <?php foreach (array_merge($content['restaurant']['items'] ?? [], [['label' => '', 'icon' => 'check']]) as $item): ?>
        <div class="row" style="grid-template-columns:1fr">
          <div class="stack">
            <input type="text" name="rst_label[]" maxlength="120"
                   value="<?= e($item['label'] ?? '') ?>" aria-label="Élément du restaurant">
            <?= icon_select('rst_icon[]', $item['icon'] ?? 'check') ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="field" style="margin-top:16px">
      <label for="r_note">Mention sous la liste</label>
      <input id="r_note" name="note" type="text" maxlength="200"
             value="<?= e($content['restaurant']['note'] ?? '') ?>">
    </div>
  </div>

  <div class="savebar">
    <button type="submit" class="btn">Enregistrer le restaurant</button>
  </div>
</form>

<!-- ═══ ACTIVITÉS ═══ -->
<form method="post" data-guard style="margin-top:30px">
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="activities">

  <div class="card">
    <div class="card__head"><h2 class="card__title">Les activités</h2></div>

    <div class="grid-2">
      <div class="field">
        <label for="a_kicker">Petit titre</label>
        <input id="a_kicker" name="kicker" type="text" maxlength="60"
               value="<?= e($content['activities']['kicker'] ?? '') ?>">
      </div>
      <div class="field">
        <label for="a_title">Titre</label>
        <input id="a_title" name="title" type="text" maxlength="120"
               value="<?= e($content['activities']['title'] ?? '') ?>">
      </div>
    </div>

    <div class="field">
      <label for="a_text">Texte de présentation</label>
      <textarea id="a_text" name="text" rows="4" maxlength="600"><?= e($content['activities']['text'] ?? '') ?></textarea>
    </div>

    <label class="label">Activités proposées</label>
    <div class="rows">
      <?php foreach (array_merge($content['activities']['items'] ?? [], [['label' => '', 'icon' => 'check']]) as $item): ?>
        <div class="row" style="grid-template-columns:1fr">
          <div class="stack">
            <input type="text" name="act_label[]" maxlength="120"
                   value="<?= e($item['label'] ?? '') ?>" aria-label="Activité">
            <?= icon_select('act_icon[]', $item['icon'] ?? 'check') ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="field" style="margin-top:16px">
      <label for="a_note">Mention sous la liste</label>
      <input id="a_note" name="note" type="text" maxlength="200"
             value="<?= e($content['activities']['note'] ?? '') ?>">
    </div>
  </div>

  <div class="card">
    <div class="card__head"><h2 class="card__title">Distances alentour</h2></div>
    <p class="card__hint">Lieu à gauche, distance ou durée à droite.</p>

    <div class="rows">
      <?php foreach (array_merge($content['activities']['distances'] ?? [], [['label' => '', 'value' => '']]) as $d): ?>
        <div class="row" style="grid-template-columns:1fr 1fr">
          <input type="text" name="dist_label[]" maxlength="120"
                 placeholder="Lieu" value="<?= e($d['label'] ?? '') ?>"
                 aria-label="Lieu">
          <input type="text" name="dist_value[]" maxlength="60"
                 placeholder="25 km" value="<?= e($d['value'] ?? '') ?>"
                 aria-label="Distance">
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="savebar">
    <button type="submit" class="btn">Enregistrer les activités</button>
  </div>
</form>

<?php layout_foot(); ?>
