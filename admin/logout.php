<?php
/**
 * admin/logout.php — Déconnexion.
 *
 * En POST uniquement et avec jeton CSRF : un simple lien GET permettrait à
 * un site tiers de déconnecter le gérant à distance.
 */

declare(strict_types=1);
require __DIR__ . '/inc/bootstrap.php';

if (is_post()) {
    Csrf::require();
    Auth::logout();
}

redirect('login.php');
