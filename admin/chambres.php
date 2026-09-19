<?php
/**
 * admin/chambres.php — Gestion des chambres.
 *
 * Deux écrans dans un seul fichier :
 *   - sans paramètre : la liste (ordre, affichage, accès à l'édition)
 *   - ?id=xxx        : le formulaire d'une chambre
 *
 * Ce découpage évite au gérant un formulaire géant à faire défiler sur
 * téléphone.
 */

declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/media.php';
require __DIR__ . '/inc/layout.php';
Auth::require();

$content = $store->read();
$rooms   = $content['rooms']['items'] ?? [];

/** Retrouve l'indice d'une chambre par son identifiant. */
function room_index(array $rooms, string $id): ?int
{
    foreach ($rooms as $i => $r) {
        if (($r['id'] ?? '') === $id) {
            return $i;
        }
    }
    return null;
}

// ══ Traitement des formulaires ═══════════════════════════════════════════
if (is_post()) {
    Csrf::require();
    $action = post_str('action');

    try {
        switch ($action) {

            // ── Ordre et visibilité depuis la liste ──────────────────────
            case 'list':
                $order = [];
                foreach ($rooms as $i => $room) {
                    $rooms[$i]['active'] = post_bool('active_' . $room['id']);
                    $order[$i] = (int) ($_POST['order_' . $room['id']] ?? $i);
                }
                // Tri stable : à position égale, on garde l'ordre actuel.
                array_multisort($order, SORT_ASC, SORT_NUMERIC, $rooms);

                $content['rooms']['items'] = array_values($rooms);
                $store->write($content);
                Flash::ok('Ordre et affichage des chambres enregistrés.');
                redirect('chambres.php');

            // ── Enregistrement d'une chambre ─────────────────────────────
            case 'save':
                $id = post_str('id');
                $i  = room_index($rooms, $id);
                if ($i === null) {
                    throw new RuntimeException("Cette chambre n'existe plus.");
                }

                $name = post_str('name');
                if ($name === '') {
                    throw new RuntimeException('Le nom de la chambre est obligatoire.');
                }

                /**
                 * Photos de la chambre : les champs arrivent dans l'ordre
                 * d'affichage. On ne garde que les identifiants qui désignent
                 * une photo réellement présente, et on retire les doublons.
                 * La première photo de la liste est la photo principale :
                 * un seul réglage plutôt que deux à tenir cohérents.
                 */
                $mediaIds = [];
                foreach (post_list('mediaIds') as $id) {
                    if (isset($content['media'][$id]) && !in_array($id, $mediaIds, true)) {
                        $mediaIds[] = $id;
                    }
                }

                $rooms[$i]['name']      = $name;
                $rooms[$i]['area']      = post_str('area');
                $rooms[$i]['loc']       = post_str('loc');
                $rooms[$i]['tag']       = post_str('tag');
                $rooms[$i]['desc']      = post_str('desc');
                $rooms[$i]['price']     = post_str('price');
                $rooms[$i]['amenities'] = post_list('amenities');
                $rooms[$i]['mediaIds']  = $mediaIds;
                $rooms[$i]['coverId']   = $mediaIds[0] ?? '';
                $rooms[$i]['active']    = post_bool('active');

                $content['rooms']['items'] = array_values($rooms);
                $store->write($content);
                Flash::ok('Chambre « ' . $name . ' » enregistrée.');
                redirect('chambres.php');

            // ── Ajout d'une chambre ──────────────────────────────────────
            case 'add':
                $name = post_str('new_name');
                if ($name === '') {
                    throw new RuntimeException('Donnez un nom à la nouvelle chambre.');
                }

                // Identifiant technique dérivé du nom, rendu unique.
                $base = ImageService::slugify($name);
                $id   = $base;
                $n    = 2;
                while (room_index($rooms, $id) !== null) {
                    $id = $base . '-' . $n++;
                }

                $rooms[] = [
                    'id'        => $id,
                    'name'      => $name,
                    'loc'       => '',
                    'tag'       => '',
                    'area'      => '',
                    'price'     => '',
                    'desc'      => '',
                    'amenities' => [],
                    'mediaIds'  => [],
                    'coverId'   => '',
                    // Dégradé de repli repris de la première chambre, pour
                    // rester dans la palette du site.
                    'gradient'  => $rooms[0]['gradient']
                                 ?? 'linear-gradient(145deg, #1a2a3a 0%, #3a6a90 100%)',
                    // Une nouvelle chambre n'est pas publiée tant qu'elle n'est
                    // pas décrite : on évite une carte vide en ligne.
                    'active'    => false,
                ];

                $content['rooms']['items'] = array_values($rooms);
                $store->write($content);
                Flash::ok(
                    'Chambre « ' . $name . ' » créée. Complétez-la, puis cochez '
                    . '« Afficher cette chambre » pour la publier.'
                );
                redirect('chambres.php?id=' . urlencode($id));

            // ── Suppression d'une chambre ────────────────────────────────
            case 'delete':
                $id = post_str('id');
                $i  = room_index($rooms, $id);
                if ($i === null) {
                    throw new RuntimeException("Cette chambre n'existe plus.");
                }
                $name = $rooms[$i]['name'] ?? '';
                array_splice($rooms, $i, 1);

                $content['rooms']['items'] = array_values($rooms);
                $store->write($content);
                Flash::ok('Chambre « ' . $name . ' » supprimée.');
                redirect('chambres.php');

            // ── Textes communs à toutes les chambres ─────────────────────
            case 'shared':
                $content['rooms']['kicker'] = post_str('kicker');
                $content['rooms']['intro']  = post_str('intro');
                $content['rooms']['shared'] = post_list('shared');
                $store->write($content);
                Flash::ok('Présentation des chambres enregistrée.');
                redirect('chambres.php');
        }
    } catch (Throwable $ex) {
        Flash::error($ex->getMessage());
        redirect('chambres.php');
    }
}

// ══ Écran d'édition d'une chambre ════════════════════════════════════════
$editId = isset($_GET['id']) ? (string) $_GET['id'] : '';

if ($editId !== '') {
    $i = room_index($rooms, $editId);
    if ($i === null) {
        Flash::error("Cette chambre n'existe pas.");
        redirect('chambres.php');
    }
    $room = $rooms[$i];

    layout_head('Chambre : ' . ($room['name'] ?? ''));
    ?>

    <p><a class="btn btn--outline btn--sm" href="chambres.php">← Retour à la liste</a></p>

    <form method="post" data-guard style="margin-top:16px">
      <?= Csrf::field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="id" value="<?= e($room['id']) ?>">

      <div class="card">
        <div class="card__head"><h2 class="card__title">Description</h2></div>

        <div class="field">
          <label for="name">Nom de la chambre</label>
          <input id="name" name="name" type="text" required maxlength="80"
                 value="<?= e($room['name'] ?? '') ?>">
        </div>

        <div class="grid-2">
          <div class="field">
            <label for="area">Superficie</label>
            <input id="area" name="area" type="text" maxlength="20"
                   placeholder="30 m²" value="<?= e($room['area'] ?? '') ?>">
          </div>
          <div class="field">
            <label for="tag">Étiquette</label>
            <input id="tag" name="tag" type="text" maxlength="30"
                   placeholder="La plus grande" value="<?= e($room['tag'] ?? '') ?>">
            <p class="field__hint">Petit badge en haut de la carte.</p>
          </div>
        </div>

        <div class="field">
          <label for="loc">Sous-titre</label>
          <input id="loc" name="loc" type="text" maxlength="60"
                 placeholder="Vue mer · 30 m²" value="<?= e($room['loc'] ?? '') ?>">
          <p class="field__hint">Ligne grise sous le nom, avec l'icône de repère.</p>
        </div>

        <div class="field">
          <label for="desc">Description</label>
          <textarea id="desc" name="desc" rows="4" maxlength="400"><?= e($room['desc'] ?? '') ?></textarea>
          <p class="field__hint">
            Deux à trois phrases. Écrivez comme vous parleriez à quelqu'un qui
            visite la maison.
          </p>
        </div>
      </div>

      <div class="card">
        <div class="card__head"><h2 class="card__title">Tarif</h2></div>
        <div class="field">
          <label for="price">Prix affiché</label>
          <input id="price" name="price" type="text" maxlength="60"
                 placeholder="Ex. : 120 000 Ar la nuit"
                 value="<?= e($room['price'] ?? '') ?>">
          <p class="field__hint">
            <?php if (empty($content['settings']['showPrices'])): ?>
              L'affichage des tarifs est actuellement <strong>désactivé</strong> pour
              tout le site : le site montre « <?= e($content['settings']['priceFallback'] ?? '') ?> ».
              Vous pouvez saisir les prix dès maintenant et les rendre visibles
              plus tard depuis <a href="reglages.php">Réglages</a>.
            <?php else: ?>
              Les tarifs sont visibles sur le site. Laissez vide pour afficher
              « <?= e($content['settings']['priceFallback'] ?? '') ?> » sur cette chambre.
            <?php endif; ?>
          </p>
        </div>
      </div>

      <div class="card">
        <div class="card__head"><h2 class="card__title">Équipements</h2></div>
        <p class="card__hint">
          Un équipement par ligne. Laissez une ligne vide pour la supprimer.
          Les lignes s'affichent dans l'ordre indiqué.
        </p>

        <div class="stack">
          <?php
          $amenities = $room['amenities'] ?? [];
          // Trois champs vides en plus, pour ajouter sans manipulation.
          $slots = array_merge($amenities, ['', '', '']);
          foreach ($slots as $k => $value): ?>
            <input type="text" name="amenities[]" maxlength="60"
                   value="<?= e($value) ?>"
                   placeholder="<?= $k < count($amenities) ? '' : 'Ajouter un équipement…' ?>">
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card">
        <div class="card__head"><h2 class="card__title">Photos</h2></div>
        <p class="card__hint">
          <strong>La première photo est la photo principale</strong> : c'est elle
          qui s'affiche sur la carte de la chambre. Les suivantes apparaissent
          quand le visiteur ouvre la chambre. Pour changer l'ordre, changez les
          photos choisies dans les listes. Pour en retirer une, remettez-la sur
          « aucune photo ».
        </p>

        <?php
        // Les photos déjà choisies, plus deux emplacements libres pour en
        // ajouter sans manipulation préalable.
        $chosen = array_values(array_filter(
            $room['mediaIds'] ?? [],
            fn($id) => isset($content['media'][$id])
        ));
        $slots = array_merge($chosen, ['', '']);
        foreach ($slots as $slot => $mediaId):
            $label = $slot === 0
                ? 'Photo principale'
                : 'Photo ' . ($slot + 1);
        ?>
          <?= MediaAdmin::picker($content, 'mediaIds[]', $mediaId, $label) ?>
        <?php endforeach; ?>

        <p class="field__hint">
          Pour envoyer une nouvelle photo, passez par <a href="photos.php">Photos</a> :
          elle apparaîtra ensuite dans ces listes.
        </p>
      </div>

      <div class="card">
        <label class="switch">
          <input type="checkbox" name="active" value="1"
                 <?= !empty($room['active']) ? 'checked' : '' ?>>
          Afficher cette chambre sur le site
        </label>
        <p class="field__hint">
          Décochée, la chambre disparaît du site mais reste enregistrée ici.
        </p>
      </div>

      <div class="savebar">
        <button type="submit" class="btn">Enregistrer la chambre</button>
        <a class="btn btn--outline" href="chambres.php">Annuler</a>
      </div>
    </form>

    <div class="card" style="margin-top:26px;border-color:#e9b0ab">
      <div class="card__head"><h2 class="card__title">Supprimer cette chambre</h2></div>
      <p class="card__hint">
        Pour la retirer temporairement du site, décochez plutôt
        « Afficher cette chambre » ci-dessus : elle restera enregistrée.
        La suppression, elle, est définitive.
      </p>
      <form method="post">
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="id" value="<?= e($room['id']) ?>">
        <?= delete_button(
              'Supprimer définitivement',
              'Supprimer définitivement la chambre « ' . ($room['name'] ?? '')
              . ' » ? Cette action est irréversible.'
            ) ?>
      </form>
    </div>

    <?php
    layout_foot();
    exit;
}

// ══ Écran liste ══════════════════════════════════════════════════════════
layout_head('Chambres');
?>

<p class="section-intro">
  <?= count($rooms) ?> chambre<?= count($rooms) > 1 ? 's' : '' ?>. Vous pouvez
  changer leur ordre d'apparition, les masquer temporairement, et modifier
  chacune en détail.
</p>

<form method="post" data-guard>
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="list">

  <div class="card">
    <div class="card__head"><h2 class="card__title">Ordre et affichage</h2></div>
    <p class="card__hint">
      Le numéro fixe la position : 1 apparaît en premier dans le carrousel.
    </p>

    <div class="rows">
      <?php foreach ($rooms as $i => $room): ?>
        <div class="row">
          <input class="row__order" type="number" min="1" max="99"
                 name="order_<?= e($room['id']) ?>" value="<?= $i + 1 ?>"
                 aria-label="Position de <?= e($room['name'] ?? '') ?>">

          <div class="row__main">
            <strong><?= e($room['name'] ?? '') ?></strong>
            <div class="muted">
              <?= e($room['area'] ?? '') ?>
              <?php if (($room['coverId'] ?? '') === ''): ?>
                · <span style="color:var(--c-err-ink)">sans photo</span>
              <?php endif; ?>
              <?php if (trim((string) ($room['price'] ?? '')) === ''): ?>
                · <span style="color:var(--c-err-ink)">sans tarif</span>
              <?php endif; ?>
            </div>
            <label class="switch" style="min-height:34px;font-size:13.5px">
              <input type="checkbox" name="active_<?= e($room['id']) ?>" value="1"
                     <?= !empty($room['active']) ? 'checked' : '' ?>>
              Visible sur le site
            </label>
          </div>

          <div class="row__actions">
            <a class="btn btn--outline btn--sm"
               href="chambres.php?id=<?= urlencode($room['id']) ?>">Modifier</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="savebar">
    <button type="submit" class="btn">Enregistrer l'ordre</button>
  </div>
</form>

<form method="post" style="margin-top:26px">
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="add">

  <div class="card">
    <div class="card__head"><h2 class="card__title">Ajouter une chambre</h2></div>
    <p class="card__hint">
      La nouvelle chambre est créée masquée. Vous la complétez tranquillement,
      puis vous la publiez quand elle est prête.
    </p>
    <div class="field">
      <label for="new_name">Nom de la chambre</label>
      <input id="new_name" name="new_name" type="text" maxlength="80"
             placeholder="Ex. : Double vue mer" required>
    </div>
    <button type="submit" class="btn btn--outline">Créer la chambre</button>
  </div>
</form>

<form method="post" data-guard style="margin-top:26px">
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="shared">

  <div class="card">
    <div class="card__head"><h2 class="card__title">Présentation de la section</h2></div>

    <div class="field">
      <label for="kicker">Titre de la section</label>
      <input id="kicker" name="kicker" type="text" maxlength="80"
             value="<?= e($content['rooms']['kicker'] ?? '') ?>">
    </div>

    <div class="field">
      <label for="intro">Phrase d'introduction</label>
      <textarea id="intro" name="intro" rows="3" maxlength="300"><?= e($content['rooms']['intro'] ?? '') ?></textarea>
    </div>

    <div class="field">
      <label>Ce que l'on trouve dans toutes les chambres</label>
      <p class="field__hint" style="margin:0 0 8px">
        Un élément par ligne. Videz une ligne pour la retirer.
      </p>
      <div class="stack">
        <?php foreach (array_merge($content['rooms']['shared'] ?? [], ['', '']) as $s): ?>
          <input type="text" name="shared[]" maxlength="60" value="<?= e($s) ?>">
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <div class="savebar">
    <button type="submit" class="btn">Enregistrer la présentation</button>
  </div>
</form>

<?php layout_foot(); ?>
