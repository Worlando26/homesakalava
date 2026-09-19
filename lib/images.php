<?php
/**
 * lib/images.php — Traitement d'images partagé.
 *
 * Utilisé à la fois par l'outil de build (import des photos d'origine) et par
 * l'uploader du back-office. Une seule implémentation : mêmes règles de
 * validation, de redimensionnement et de conversion partout.
 *
 * Dépend uniquement de l'extension GD, présente dans XAMPP par défaut.
 */

declare(strict_types=1);

final class ImageService
{
    /** Types MIME réellement acceptés (vérifiés par finfo, pas par extension). */
    public const ALLOWED_MIME = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    /** Taille maximale d'un fichier uploadé (8 Mo). */
    public const MAX_BYTES = 8 * 1024 * 1024;

    /** Dimensions maximales en pixels, pour refuser les "image bombs". */
    public const MAX_DIMENSION = 8000;

    /**
     * Largeurs générées pour chaque image. La plus grande sert de rendu par
     * défaut, les autres alimentent l'attribut srcset du site public.
     */
    public const WIDTHS = [
        'thumb' => 320,   // vignettes admin
        'small' => 640,   // mobile
        'med'   => 1280,  // tablette / cartes desktop
        'large' => 1920,  // hero plein écran
    ];

    /**
     * Valide un fichier image et renvoie [mime, largeur, hauteur].
     *
     * @throws RuntimeException si le fichier n'est pas une image acceptable.
     */
    public static function validate(string $path, ?int $declaredSize = null): array
    {
        if (!is_file($path)) {
            throw new RuntimeException("Fichier introuvable.");
        }

        $size = $declaredSize ?? filesize($path);
        if ($size === false || $size <= 0) {
            throw new RuntimeException("Fichier vide.");
        }
        if ($size > self::MAX_BYTES) {
            throw new RuntimeException(
                'Image trop lourde (' . self::formatBytes($size) . '). Maximum : '
                . self::formatBytes(self::MAX_BYTES) . '.'
            );
        }

        // Type MIME réel, déduit du contenu et non de l'extension fournie.
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($path);
        if (!isset(self::ALLOWED_MIME[$mime])) {
            throw new RuntimeException(
                "Format non accepté. Formats autorisés : JPEG, PNG, WebP."
            );
        }

        // getimagesize confirme que GD saura décoder le fichier.
        $info = @getimagesize($path);
        if ($info === false) {
            throw new RuntimeException("Fichier image illisible ou corrompu.");
        }
        [$w, $h] = $info;
        if ($w > self::MAX_DIMENSION || $h > self::MAX_DIMENSION) {
            throw new RuntimeException(
                "Image trop grande ({$w}×{$h} px). Maximum : "
                . self::MAX_DIMENSION . " px de côté."
            );
        }

        return [$mime, $w, $h];
    }

    /**
     * Importe une image dans la médiathèque.
     *
     * Écrit l'original dans storage/originals/ (hors des dossiers servis
     * directement) et les dérivés optimisés dans uploads/.
     *
     * @param string $srcPath  Fichier source (jamais modifié ni supprimé).
     * @param string $baseName Nom de base souhaité ; il est systématiquement
     *                         nettoyé et suffixé, le nom d'origine n'est
     *                         jamais réutilisé tel quel.
     * @return array Enregistrement média prêt à être stocké en JSON.
     */
    public static function import(string $srcPath, string $baseName, string $alt = ''): array
    {
        [$mime, $w, $h] = self::validate($srcPath);

        $root      = Paths::root();
        $slug      = self::slugify($baseName);
        $id        = $slug . '-' . bin2hex(random_bytes(4));
        $origExt   = self::ALLOWED_MIME[$mime];
        $originals = $root . '/storage/originals';
        $uploads   = $root . '/uploads';

        self::ensureDir($originals);
        self::ensureDir($uploads);

        // 1. Conserver l'original tel quel (copie, jamais de déplacement).
        if (!copy($srcPath, $originals . '/' . $id . '.' . $origExt)) {
            throw new RuntimeException("Impossible d'archiver l'original.");
        }

        // 2. Générer les dérivés.
        $src = self::load($srcPath, $mime);

        $variants = [];
        $reachedNative = false;
        foreach (self::WIDTHS as $key => $targetW) {
            // On n'agrandit jamais une image. Dès qu'une largeur cible dépasse
            // la largeur native, on produit une dernière variante à la taille
            // native puis on s'arrête : inutile d'en générer plusieurs
            // identiques, mais il en faut toujours une en pleine résolution.
            if ($reachedNative) {
                break;
            }
            if ($targetW >= $w) {
                $reachedNative = true;
            }

            $scale = min(1.0, $targetW / $w);
            $dw    = max(1, (int) round($w * $scale));
            $dh    = max(1, (int) round($h * $scale));

            $resized = self::resample($src, $w, $h, $dw, $dh);

            $webpName = "{$id}-{$key}.webp";
            imagewebp($resized, $uploads . '/' . $webpName, 82);

            // Repli JPEG pour les navigateurs sans WebP.
            $fallbackName = "{$id}-{$key}.jpg";
            imagejpeg($resized, $uploads . '/' . $fallbackName, 82);

            imagedestroy($resized);

            $variants[$key] = [
                'webp'     => 'uploads/' . $webpName,
                'fallback' => 'uploads/' . $fallbackName,
                'w'        => $dw,
                'h'        => $dh,
            ];
        }
        imagedestroy($src);

        $largest = array_key_last($variants);

        return [
            'id'       => $id,
            'alt'      => $alt,
            'src'      => $variants[$largest]['fallback'],
            'webp'     => $variants[$largest]['webp'],
            'width'    => $variants[$largest]['w'],
            'height'   => $variants[$largest]['h'],
            'variants' => $variants,
            'original' => 'storage/originals/' . $id . '.' . $origExt,
            'added_at' => date('c'),
        ];
    }

    /**
     * Supprime tous les fichiers d'un média (dérivés + original).
     * Ne touche jamais à autre chose que les dossiers uploads/ et storage/.
     */
    public static function deleteFiles(array $media): void
    {
        $root  = Paths::root();
        $paths = [];

        foreach (($media['variants'] ?? []) as $v) {
            $paths[] = $v['webp'] ?? null;
            $paths[] = $v['fallback'] ?? null;
        }
        $paths[] = $media['original'] ?? null;

        foreach (array_filter($paths) as $rel) {
            // Garde-fou : on refuse tout chemin hors des dossiers médias.
            if (!preg_match('#^(uploads|storage/originals)/[A-Za-z0-9._-]+$#', $rel)) {
                continue;
            }
            $abs = $root . '/' . $rel;
            if (is_file($abs)) {
                @unlink($abs);
            }
        }
    }

    // ─── Helpers internes ────────────────────────────────────────────────

    private static function load(string $path, string $mime)
    {
        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png'  => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default      => false,
        };
        if ($img === false) {
            throw new RuntimeException("Décodage de l'image impossible.");
        }

        // Respecte l'orientation EXIF des photos prises au téléphone.
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);
            $rot  = match ($exif['Orientation'] ?? 1) {
                3       => 180,
                6       => -90,
                8       => 90,
                default => 0,
            };
            if ($rot !== 0) {
                $rotated = imagerotate($img, $rot, 0);
                if ($rotated !== false) {
                    imagedestroy($img);
                    $img = $rotated;
                }
            }
        }

        return $img;
    }

    private static function resample($src, int $sw, int $sh, int $dw, int $dh)
    {
        $dst = imagecreatetruecolor($dw, $dh);
        // Fond blanc : les PNG transparents deviennent des JPEG propres.
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255));
        imagealphablending($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dw, $dh, $sw, $sh);
        return $dst;
    }

    public static function slugify(string $value): string
    {
        $value = preg_replace('/\.[A-Za-z0-9]{1,5}$/', '', $value); // extension
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');
        $value = substr($value, 0, 40);
        return $value !== '' ? $value : 'photo';
    }

    private static function ensureDir(string $dir): void
    {
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException("Impossible de créer le dossier : $dir");
        }
    }

    private static function formatBytes(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576, 1) . ' Mo'
            : round($bytes / 1024) . ' Ko';
    }
}

/** Résolution de la racine du projet, quel que soit le point d'entrée. */
final class Paths
{
    public static function root(): string
    {
        return dirname(__DIR__);
    }
}
