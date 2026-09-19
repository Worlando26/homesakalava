<?php
/**
 * admin/inc/layout.php — En-tête et pied de page communs à l'admin.
 *
 * Usage :
 *   layout_head('Les chambres');
 *   ... contenu ...
 *   layout_foot();
 */

declare(strict_types=1);

/** Les onglets de navigation, dans l'ordre d'usage du gérant. */
function admin_nav(): array
{
    return [
        'index.php'    => 'Accueil',
        'chambres.php' => 'Chambres',
        'photos.php'   => 'Photos',
        'services.php' => 'Services',
        'textes.php'   => 'Textes',
        'reglages.php' => 'Réglages',
        'diagnostic.php' => 'Diagnostic',
    ];
}

function layout_head(string $title, bool $chrome = true): void
{
    $current = basename($_SERVER['PHP_SELF'] ?? '');
    ?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> — Administration Home Sakalava</title>
<link rel="icon" href="../assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body<?= $chrome ? '' : ' class="is-bare"' ?>>
<?php if ($chrome): ?>

<header class="topbar">
  <div class="topbar__inner">
    <a class="topbar__brand" href="index.php">
      <span class="topbar__dot" aria-hidden="true"></span>
      Home Sakalava
    </a>
    <div class="topbar__right">
      <a class="topbar__link" href="../index.html" target="_blank" rel="noopener">
        Voir le site
      </a>
      <form method="post" action="logout.php" class="topbar__logout">
        <?= Csrf::field() ?>
        <button type="submit" class="btn btn--ghost btn--sm">Déconnexion</button>
      </form>
    </div>
  </div>
  <nav class="tabs" aria-label="Sections de l'administration">
    <?php foreach (admin_nav() as $href => $label): ?>
      <a class="tabs__item<?= $current === $href ? ' is-active' : '' ?>"
         href="<?= e($href) ?>"<?= $current === $href ? ' aria-current="page"' : '' ?>>
        <?= e($label) ?>
      </a>
    <?php endforeach; ?>
  </nav>
</header>

<main class="page">
  <h1 class="page__title"><?= e($title) ?></h1>
  <?php layout_flash(); ?>
<?php else: ?>
<main class="bare">
<?php endif;
}

function layout_flash(): void
{
    foreach (Flash::take() as $msg) {
        $cls = $msg['type'] === 'ok' ? 'alert alert--ok' : 'alert alert--error';
        echo '<p class="' . $cls . '" role="status">' . e($msg['text']) . '</p>';
    }
}

function layout_foot(bool $chrome = true): void
{
    ?>
</main>
<?php if ($chrome): ?>
<footer class="footnote">
  Connecté en tant que <strong><?= e(Auth::username()) ?></strong>.
  Chaque enregistrement met à jour le site public immédiatement.
</footer>
<?php endif; ?>
<script src="assets/admin.js" defer></script>
</body>
</html>
<?php
}

/**
 * Bandeau de confirmation avant suppression.
 * Le bouton porte data-confirm : admin.js demande confirmation au clic.
 */
function delete_button(string $label, string $question): string
{
    return '<button type="submit" class="btn btn--danger btn--sm" '
         . 'data-confirm="' . e($question) . '">' . e($label) . '</button>';
}
