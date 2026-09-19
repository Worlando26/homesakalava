<?php
/**
 * admin/login.php — Page de connexion, hors de la navigation publique.
 */

declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';
require __DIR__ . '/inc/layout.php';

// Déjà connecté : inutile de réafficher le formulaire.
if (Auth::check()) {
    redirect('index.php');
}

$error = '';

if (is_post()) {
    Csrf::require();
    $error = Auth::attempt(post_str('username'), (string) ($_POST['password'] ?? ''));

    if ($error === '') {
        // On ne redirige que vers une page interne : un paramètre "suite"
        // pointant ailleurs serait une redirection ouverte.
        $next = (string) ($_GET['suite'] ?? '');
        $safe = preg_match('#^/?[a-z0-9_-]+\.php(\?[^\s]*)?$#i', basename($next))
            ? basename($next)
            : 'index.php';
        redirect($safe);
    }
}

$configured = Auth::isConfigured();

layout_head('Connexion', false);
?>

<form class="login" method="post" autocomplete="on">
  <?= Csrf::field() ?>

  <h1 class="login__title">Administration</h1>
  <p class="login__sub">Home Sakalava — gestion du contenu du site</p>

  <?php if ($error !== ''): ?>
    <p class="alert alert--error" role="alert"><?= e($error) ?></p>
  <?php endif; ?>

  <?php if (!$configured): ?>
    <p class="alert alert--warn">
      Aucun compte n'existe encore. Ouvrez un terminal à la racine du projet
      et lancez&nbsp;: <code>php tools/create-admin.php</code>
    </p>
  <?php endif; ?>

  <div class="field">
    <label for="u">Identifiant</label>
    <input id="u" name="username" type="text" autocomplete="username"
           required autofocus value="<?= e(post_str('username')) ?>">
  </div>

  <div class="field">
    <label for="p">Mot de passe</label>
    <input id="p" name="password" type="password"
           autocomplete="current-password" required>
  </div>

  <button type="submit" class="btn btn--full">Se connecter</button>

  <p class="field__hint" style="margin-top:16px">
    Après 5 tentatives échouées, la connexion est bloquée 15 minutes.
  </p>
</form>

<?php layout_foot(false); ?>
