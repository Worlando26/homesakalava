<?php
/**
 * admin/inc/flash.php — Messages de confirmation et d'erreur.
 *
 * Après un enregistrement, on redirige toujours plutôt que d'afficher
 * directement : cela évite qu'un rafraîchissement de page réenregistre les
 * données. Le message à afficher transite par la session.
 */

declare(strict_types=1);

final class Flash
{
    public static function ok(string $message): void
    {
        $_SESSION['flash'][] = ['type' => 'ok', 'text' => $message];
    }

    public static function error(string $message): void
    {
        $_SESSION['flash'][] = ['type' => 'error', 'text' => $message];
    }

    /** Récupère et vide la pile de messages. */
    public static function take(): array
    {
        $all = $_SESSION['flash'] ?? [];
        unset($_SESSION['flash']);
        return is_array($all) ? $all : [];
    }
}
