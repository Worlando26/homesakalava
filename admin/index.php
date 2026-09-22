<?php
/**
 * admin/index.php — Accueil de l'administration.
 *
 * Volontairement sobre : pas de statistiques, pas de graphiques. Juste des
 * raccourcis, et la liste de ce qui reste à renseigner.
 */

declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';
Auth::require();

$content = $store->read();

/**
 * Ce qu'il reste à compléter vient de config/site.php : la même source que
 * celle lue par le site, pour que cette liste ne mente jamais.
 * Chaque champ renvoie vers l'onglet où il se saisit.
 */
$todo = [];
foreach (Config::manquants() as $m) {
    $page = (str_starts_with($m['cle'], 'smtp.') || $m['cle'] === 'site.url')
        ? 'reglages.php'
        : 'textes.php#contact';
    $todo[] = [
        $m['libelle'] . ($m['bloquant'] ? '' : ' — facultatif'),
        $page,
    ];
}

if (Config::modeTest()) {
    $todo[] = [
        'Le mode test est actif : aucun e-mail ne part réellement',
        'reglages.php',
    ];
}

$noPrice = array_filter(
    $content['rooms']['items'] ?? [],
    fn($r) => trim((string) ($r['price'] ?? '')) === ''
);
if ($noPrice) {
    $todo[] = [count($noPrice) . ' chambre(s) sans tarif renseigné', 'chambres.php'];
}

$noPhoto = array_filter(
    $content['rooms']['items'] ?? [],
    fn($r) => ($r['coverId'] ?? '') === ''
);
if ($noPhoto) {
    $todo[] = [count($noPhoto) . ' chambre(s) sans photo', 'photos.php'];
}

$updated = $content['updated_at'] ?? '';

layout_head('Accueil');
?>

<p class="section-intro">
  Bonjour. Tout ce que vous modifiez ici apparaît sur le site public
  immédiatement après l'enregistrement.
</p>

<?php if ($todo): ?>
  <div class="card">
    <div class="card__head">
      <h2 class="card__title">À compléter</h2>
    </div>
    <p class="card__hint">
      Ces informations manquent encore. Le site fonctionne sans elles, mais
      il affiche « à renseigner » aux endroits concernés.
    </p>
    <ul class="stack">
      <?php foreach ($todo as [$label, $link]): ?>
        <li class="row">
          <span class="badge badge--off">!</span>
          <span class="row__main"><?= e($label) ?></span>
          <span class="row__actions">
            <a class="btn btn--outline btn--sm" href="<?= e($link) ?>">Compléter</a>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php else: ?>
  <p class="alert alert--ok">Toutes les informations principales sont renseignées.</p>
<?php endif; ?>

<h2 class="card__title" style="margin:26px 0 12px">Que voulez-vous modifier ?</h2>

<div class="shortcuts">
  <a class="shortcut" href="chambres.php">
    <div class="shortcut__title">Les chambres</div>
    <div class="shortcut__desc">
      Tarifs, descriptions, équipements, photos, afficher ou masquer une chambre.
    </div>
  </a>
  <a class="shortcut" href="photos.php">
    <div class="shortcut__title">Les photos</div>
    <div class="shortcut__desc">
      Ajouter des photos, choisir la photo d'accueil et celles des sections.
    </div>
  </a>
  <a class="shortcut" href="services.php">
    <div class="shortcut__title">Les services</div>
    <div class="shortcut__desc">
      Wifi, parking, navette, massages… avec la mention « supplément ».
    </div>
  </a>
  <a class="shortcut" href="textes.php">
    <div class="shortcut__title">Les textes et contacts</div>
    <div class="shortcut__desc">
      Textes des sections, horaires, téléphone, e-mail, adresse, Facebook.
    </div>
  </a>
  <a class="shortcut" href="reglages.php">
    <div class="shortcut__title">Les réglages</div>
    <div class="shortcut__desc">
      Afficher ou masquer les tarifs, référencement, mot de passe.
    </div>
  </a>
  <a class="shortcut" href="../index.html" target="_blank" rel="noopener">
    <div class="shortcut__title">Voir le site ↗</div>
    <div class="shortcut__desc">
      Ouvre le site public dans un nouvel onglet, tel que le voient vos hôtes.
    </div>
  </a>
</div>

<?php if ($updated): ?>
  <p class="muted" style="margin-top:22px">
    Dernière modification du contenu :
    <?= e(date('d/m/Y \à H:i', strtotime($updated))) ?>.
  </p>
<?php endif; ?>

<?php layout_foot(); ?>
