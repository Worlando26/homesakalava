<?php
/**
 * admin/photos.php — Médiathèque et affectation des photos du site.
 *
 * Deux parties : la bibliothèque (ajout, texte alternatif, suppression),
 * puis le choix des photos utilisées par chaque section.
 */

declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/media.php';
require __DIR__ . '/inc/layout.php';
Auth::require();

$content = $store->read();

if (is_post()) {
    Csrf::require();
    $action = post_str('action');

    try {
        switch ($action) {

            // ── Ajout d'une photo ────────────────────────────────────────
            case 'upload':
                $alt = post_str('alt');
                if ($alt === '') {
                    throw new RuntimeException(
                        "Décrivez la photo en une phrase avant de l'envoyer : "
                        . "c'est ce que lisent les personnes malvoyantes."
                    );
                }
                MediaAdmin::upload($_FILES['photo'] ?? [], $alt, $content);
                $store->write($content);
                Flash::ok('Photo ajoutée. Vous pouvez maintenant l\'utiliser dans une section.');
                break;

            // ── Modification du texte alternatif ─────────────────────────
            case 'alt':
                $id  = post_str('id');
                $alt = post_str('alt');
                if (!isset($content['media'][$id])) {
                    throw new RuntimeException("Cette photo n'existe plus.");
                }
                if ($alt === '') {
                    throw new RuntimeException('La description ne peut pas être vide.');
                }
                $content['media'][$id]['alt'] = $alt;
                $store->write($content);
                Flash::ok('Description mise à jour.');
                break;

            // ── Suppression ──────────────────────────────────────────────
            case 'delete':
                MediaAdmin::delete(post_str('id'), $content);
                $store->write($content);
                Flash::ok('Photo supprimée, ainsi que ses fichiers.');
                break;

            // ── Affectation aux sections ─────────────────────────────────
            case 'assign':
                /**
                 * Une valeur envoyée doit désigner une photo qui existe
                 * réellement. Sans ce contrôle, un identifiant fantaisiste
                 * serait enregistré tel quel et le site afficherait une image
                 * cassée. On retombe alors sur « aucune photo », c'est-à-dire
                 * sur le dégradé de repli.
                 */
                $pick = static function (string $field) use ($content): string {
                    $id = post_str($field);
                    return isset($content['media'][$id]) ? $id : '';
                };

                $content['hero']['mediaId']       = $pick('hero');
                $content['restaurant']['mediaId'] = $pick('restaurant');
                $content['faq']['mediaId']        = $pick('faq');
                $content['footer']['ctaMediaId']  = $pick('footer');
                $content['seo']['ogImageId']      = $pick('og');

                foreach (array_keys($content['spaces']['items'] ?? []) as $i) {
                    $content['spaces']['items'][$i]['mediaId'] = $pick("space_$i");
                }
                foreach (array_keys($content['services']['floatImages'] ?? []) as $i) {
                    $content['services']['floatImages'][$i]['mediaId'] = $pick("float_$i");
                }

                $store->write($content);
                Flash::ok('Photos des sections mises à jour. Le site est à jour.');
                break;
        }
    } catch (Throwable $ex) {
        Flash::error($ex->getMessage());
    }

    redirect('photos.php');
}

$media = $content['media'] ?? [];

layout_head('Photos');
?>

<p class="section-intro">
  Les photos envoyées ici sont automatiquement redimensionnées et compressées
  pour que le site reste rapide, y compris sur une connexion lente. Les
  originaux sont conservés à part.
</p>

<!-- ═══ Ajouter une photo ═══ -->
<div class="card">
  <div class="card__head"><h2 class="card__title">Ajouter une photo</h2></div>

  <form method="post" enctype="multipart/form-data" data-guard>
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="upload">

    <div class="field">
      <label for="photo">Choisir une image</label>
      <input id="photo" name="photo" type="file"
             accept="image/jpeg,image/png,image/webp"
             data-preview="prev-upload" required>
      <p class="field__hint">JPEG, PNG ou WebP, 8 Mo maximum.</p>
      <img id="prev-upload" hidden alt=""
           style="margin-top:12px;max-height:190px;border-radius:10px">
    </div>

    <div class="field">
      <label for="alt">Que voit-on sur cette photo ?</label>
      <input id="alt" name="alt" type="text" required maxlength="220"
             placeholder="Ex. : la terrasse au petit matin, vue sur le jardin">
      <p class="field__hint">
        Une phrase simple. Elle est lue à voix haute par les lecteurs d'écran
        et s'affiche si l'image ne se charge pas.
      </p>
    </div>

    <button type="submit" class="btn">Envoyer la photo</button>
  </form>
</div>

<!-- ═══ Bibliothèque ═══ -->
<div class="card">
  <div class="card__head">
    <h2 class="card__title">Bibliothèque (<?= count($media) ?>)</h2>
  </div>

  <?php if (!$media): ?>
    <p class="muted">Aucune photo pour le moment.</p>
  <?php else: ?>
    <div class="media-grid">
      <?php foreach ($media as $id => $m): ?>
        <?php $usages = MediaAdmin::usages($id, $content); ?>
        <div class="media-item">
          <img class="media-item__thumb" loading="lazy"
               src="<?= e(MediaAdmin::thumb($content, $id)) ?>"
               alt="<?= e($m['alt'] ?? '') ?>">

          <div class="media-item__body">
            <form method="post">
              <?= Csrf::field() ?>
              <input type="hidden" name="action" value="alt">
              <input type="hidden" name="id" value="<?= e($id) ?>">
              <label class="label" for="alt-<?= e($id) ?>" style="font-size:12px">Description</label>
              <textarea id="alt-<?= e($id) ?>" name="alt" rows="3"
                        style="font-size:13px;min-height:70px"><?= e($m['alt'] ?? '') ?></textarea>
              <button type="submit" class="btn btn--outline btn--sm" style="margin-top:7px">
                Enregistrer
              </button>
            </form>

            <p class="media-item__name" style="margin-top:9px">
              <?php if ($usages): ?>
                Utilisée : <?= e(implode(', ', $usages)) ?>
              <?php else: ?>
                Pas encore utilisée
              <?php endif; ?>
            </p>
          </div>

          <div class="media-item__actions">
            <form method="post" style="width:100%">
              <?= Csrf::field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= e($id) ?>">
              <?= delete_button(
                    'Supprimer',
                    $usages
                      ? "Cette photo est utilisée (" . implode(', ', $usages)
                        . "). La supprimer la retirera de ces emplacements. Confirmer ?"
                      : 'Supprimer définitivement cette photo ?'
                  ) ?>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- ═══ Affectation ═══ -->
<form method="post" data-guard>
  <?= Csrf::field() ?>
  <input type="hidden" name="action" value="assign">

  <div class="card">
    <div class="card__head"><h2 class="card__title">Photos des sections</h2></div>
    <p class="card__hint">
      Une section sans photo s'affiche avec un dégradé de couleur : le site
      reste présentable, il n'y a jamais de trou blanc.
    </p>

    <?= MediaAdmin::picker($content, 'hero', $content['hero']['mediaId'] ?? '',
        "Grande photo d'accueil (la première que l'on voit)") ?>

    <?php foreach (($content['spaces']['items'] ?? []) as $i => $item): ?>
      <?= MediaAdmin::picker($content, "space_$i", $item['mediaId'] ?? '',
          'Bloc « ' . ($item['name'] ?? '') . ' »') ?>
    <?php endforeach; ?>

    <?= MediaAdmin::picker($content, 'restaurant', $content['restaurant']['mediaId'] ?? '',
        'Section restaurant') ?>

    <?php foreach (($content['services']['floatImages'] ?? []) as $i => $item): ?>
      <?= MediaAdmin::picker($content, "float_$i", $item['mediaId'] ?? '',
          'Petite vignette « ' . ($item['label'] ?? '') . ' »') ?>
    <?php endforeach; ?>

    <?= MediaAdmin::picker($content, 'faq', $content['faq']['mediaId'] ?? '',
        'Section questions fréquentes') ?>

    <?= MediaAdmin::picker($content, 'footer', $content['footer']['ctaMediaId'] ?? '',
        'Bandeau du bas de page') ?>

    <?= MediaAdmin::picker($content, 'og', $content['seo']['ogImageId'] ?? '',
        "Image affichée quand on partage le site (Facebook, WhatsApp)") ?>
  </div>

  <div class="savebar">
    <button type="submit" class="btn">Enregistrer les photos des sections</button>
    <span class="muted">Le site public est mis à jour immédiatement.</span>
  </div>
</form>

<?php layout_foot(); ?>
