<?php
/**
 * admin/inc/media.php — Médiathèque : upload, suppression, sélecteurs.
 *
 * Toutes les pages qui manipulent des photos passent par ici, pour que la
 * validation et le nommage des fichiers soient identiques partout.
 */

declare(strict_types=1);

final class MediaAdmin
{
    /**
     * Traite un fichier envoyé et l'ajoute à la médiathèque.
     *
     * @param array  $file  Entrée de $_FILES
     * @param string $alt   Texte alternatif saisi par le gérant
     * @return string       Identifiant du média créé
     * @throws RuntimeException message déjà rédigé en français
     */
    public static function upload(array $file, string $alt, array &$content): string
    {
        $code = $file['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($code !== UPLOAD_ERR_OK) {
            throw new RuntimeException(match ($code) {
                UPLOAD_ERR_NO_FILE   => "Aucun fichier n'a été choisi.",
                UPLOAD_ERR_INI_SIZE,
                UPLOAD_ERR_FORM_SIZE => "L'image est trop lourde pour être envoyée "
                                      . "(limite du serveur). Réduisez-la avant l'envoi.",
                UPLOAD_ERR_PARTIAL   => "L'envoi a été interrompu. Réessayez.",
                default              => "L'envoi a échoué (code $code).",
            });
        }

        // is_uploaded_file : garantit que le chemin vient bien d'un upload
        // HTTP et non d'un chemin local glissé dans la requête.
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new RuntimeException("Fichier d'envoi invalide.");
        }

        // ImageService revalide type MIME réel, taille et dimensions.
        $record = ImageService::import(
            $tmp,
            (string) ($file['name'] ?? 'photo'),
            $alt
        );

        $content['media'][$record['id']] = $record;
        return $record['id'];
    }

    /**
     * Supprime un média : fichiers ET références dans le contenu.
     * Une photo retirée ne doit jamais laisser une image cassée sur le site.
     */
    public static function delete(string $id, array &$content): void
    {
        $media = $content['media'][$id] ?? null;
        if (!$media) {
            throw new RuntimeException("Cette photo n'existe plus.");
        }

        ImageService::deleteFiles($media);
        unset($content['media'][$id]);
        self::purgeReferences($id, $content);
    }

    /** Retire toute référence à un média supprimé. */
    private static function purgeReferences(string $id, array &$content): void
    {
        $clear = static function (&$value) use ($id) {
            if (($value ?? '') === $id) {
                $value = '';
            }
        };

        $clear($content['hero']['mediaId']);
        $clear($content['restaurant']['mediaId']);
        $clear($content['faq']['mediaId']);
        $clear($content['footer']['ctaMediaId']);
        $clear($content['seo']['ogImageId']);

        foreach (($content['spaces']['items'] ?? []) as &$item) {
            $clear($item['mediaId']);
        }
        unset($item);

        foreach (($content['services']['floatImages'] ?? []) as &$item) {
            $clear($item['mediaId']);
        }
        unset($item);

        foreach (($content['rooms']['items'] ?? []) as &$room) {
            $clear($room['coverId']);
            $room['mediaIds'] = array_values(array_filter(
                $room['mediaIds'] ?? [],
                fn($m) => $m !== $id
            ));
        }
        unset($room);
    }

    /** Où une photo est-elle utilisée ? Sert à prévenir avant suppression. */
    public static function usages(string $id, array $content): array
    {
        $used = [];

        if (($content['hero']['mediaId'] ?? '') === $id)        { $used[] = "photo d'accueil"; }
        if (($content['restaurant']['mediaId'] ?? '') === $id)  { $used[] = 'section restaurant'; }
        if (($content['faq']['mediaId'] ?? '') === $id)         { $used[] = 'section questions fréquentes'; }
        if (($content['footer']['ctaMediaId'] ?? '') === $id)   { $used[] = 'bas de page'; }
        if (($content['seo']['ogImageId'] ?? '') === $id)       { $used[] = 'image de partage'; }

        foreach (($content['spaces']['items'] ?? []) as $item) {
            if (($item['mediaId'] ?? '') === $id) {
                $used[] = 'bloc « ' . ($item['name'] ?? '') . ' »';
            }
        }
        foreach (($content['services']['floatImages'] ?? []) as $item) {
            if (($item['mediaId'] ?? '') === $id) {
                $used[] = 'vignette « ' . ($item['label'] ?? '') . ' »';
            }
        }
        foreach (($content['rooms']['items'] ?? []) as $room) {
            if (($room['coverId'] ?? '') === $id
                || in_array($id, $room['mediaIds'] ?? [], true)) {
                $used[] = 'chambre « ' . ($room['name'] ?? '') . ' »';
            }
        }

        return $used;
    }

    /** URL de la vignette d'un média, relative au dossier admin/. */
    public static function thumb(array $content, string $id): string
    {
        $m = $content['media'][$id] ?? null;
        if (!$m) {
            return '';
        }
        $rel = $m['variants']['thumb']['fallback'] ?? $m['src'] ?? '';
        return $rel !== '' ? '../' . $rel : '';
    }

    /** Compteur garantissant un identifiant HTML unique par sélecteur. */
    private static int $pickerSeq = 0;

    /**
     * Menu déroulant de choix d'une photo, avec aperçu piloté par admin.js.
     *
     * Le nom du champ peut se répéter (« mediaIds[] » pour une galerie) : les
     * identifiants HTML, eux, sont toujours uniques, sinon les étiquettes
     * pointeraient toutes sur le même menu.
     *
     * @param string $name     nom du champ, éventuellement en tableau
     * @param string $selected identifiant actuellement retenu
     */
    public static function picker(array $content, string $name, string $selected, string $label): string
    {
        $seq      = ++self::$pickerSeq;
        $fieldId  = 'pick-' . $seq;
        $pickerId = 'prev-' . $seq;

        $out  = '<div class="field"><label class="label" for="' . e($fieldId) . '">'
              . e($label) . '</label><div class="picker">';
        $out .= '<img class="picker__preview" id="' . e($pickerId) . '" alt=""'
              . ($selected === '' ? ' hidden' : ' src="' . e(self::thumb($content, $selected)) . '"')
              . '>';
        $out .= '<select id="' . e($fieldId) . '" name="' . e($name) . '" data-picker="' . e($pickerId) . '">';
        $out .= '<option value="">— Aucune photo (dégradé de couleur) —</option>';

        foreach (($content['media'] ?? []) as $id => $m) {
            $out .= '<option value="' . e($id) . '"'
                  . ' data-thumb="' . e(self::thumb($content, $id)) . '"'
                  . ($id === $selected ? ' selected' : '') . '>'
                  . e(self::shortLabel($id, $m)) . '</option>';
        }

        $out .= '</select></div></div>';
        return $out;
    }

    /** Libellé lisible d'un média : son texte alternatif, tronqué. */
    public static function shortLabel(string $id, array $m): string
    {
        $alt = trim((string) ($m['alt'] ?? ''));
        if ($alt === '') {
            return $id;
        }
        return mb_strlen($alt) > 62 ? mb_substr($alt, 0, 60) . '…' : $alt;
    }
}
