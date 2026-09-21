<?php
/**
 * lib/emails.php — Les deux messages envoyés par le formulaire.
 *
 *   versMaison() — ce que reçoivent Boda et Bakoly : toute la demande,
 *                  avec Reply-To sur l'adresse du client pour pouvoir
 *                  répondre d'un clic.
 *   versClient()  — l'accusé de réception : récapitulatif de la demande et
 *                  coordonnées de la maison.
 *
 * Les deux existent en HTML et en texte. Le HTML est construit en tableaux
 * avec styles en ligne : c'est la seule mise en page que toutes les
 * messageries affichent correctement, Outlook compris.
 *
 * Toute valeur venant du visiteur passe par e() avant d'entrer dans le HTML.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

final class Emails
{
    /** Libellés des deux langues du site. */
    private const MOTS = [
        'fr' => [
            'sujetMaison'   => 'Nouvelle demande de réservation',
            'sujetClient'   => 'Votre demande — ',
            'titreMaison'   => 'Nouvelle demande de réservation',
            'titreClient'   => 'Nous avons bien reçu votre demande',
            'introMaison'   => 'Une demande vient d\'arriver depuis le site.',
            'introClient'   => 'Bonjour %s,',
            'corpsClient'   => 'Merci pour votre message. Nous avons bien reçu votre '
                             . 'demande et nous vous répondons dans les plus brefs délais, '
                             . 'généralement sous deux jours.',
            'recap'         => 'Récapitulatif de votre demande',
            'recapMaison'   => 'Détail de la demande',
            'nom'           => 'Nom',
            'email'         => 'E-mail',
            'telephone'     => 'Téléphone',
            'arrivee'       => 'Arrivée',
            'depart'        => 'Départ',
            'chambre'       => 'Chambre',
            'voyageurs'     => 'Voyageurs',
            'message'       => 'Message',
            'nousJoindre'   => 'Nous joindre',
            'adresse'       => 'Adresse',
            'repondre'      => 'Répondez directement à ce message pour joindre le client.',
            'pasDeReponse'  => 'Ce message est un accusé de réception automatique. '
                             . 'Vous pouvez y répondre : nous le lirons.',
            'aucun'         => '—',
            'recu'          => 'Reçu le %s',
        ],
        'en' => [
            'sujetMaison'   => 'New booking request',
            'sujetClient'   => 'Your request — ',
            'titreMaison'   => 'New booking request',
            'titreClient'   => 'We have received your request',
            'introMaison'   => 'A request has just come in from the website.',
            'introClient'   => 'Hello %s,',
            'corpsClient'   => 'Thank you for your message. We have received your '
                             . 'request and will get back to you shortly, usually within '
                             . 'two days.',
            'recap'         => 'Summary of your request',
            'recapMaison'   => 'Request details',
            'nom'           => 'Name',
            'email'         => 'Email',
            'telephone'     => 'Phone',
            'arrivee'       => 'Arrival',
            'depart'        => 'Departure',
            'chambre'       => 'Room',
            'voyageurs'     => 'Guests',
            'message'       => 'Message',
            'nousJoindre'   => 'Contact us',
            'adresse'       => 'Address',
            'repondre'      => 'Reply directly to this message to reach the guest.',
            'pasDeReponse'  => 'This is an automatic acknowledgement. '
                             . 'You may reply to it — we will read it.',
            'aucun'         => '—',
            'recu'          => 'Received on %s',
        ],
    ];

    private static function mots(string $lang): array
    {
        return self::MOTS[$lang] ?? self::MOTS['fr'];
    }

    private static function e(string $v): string
    {
        return htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    // ══ Message destiné à la maison ═══════════════════════════════════

    /**
     * @param array $d Demande déjà validée et nettoyée.
     * @return array{sujet:string, html:string, texte:string}
     */
    public static function versMaison(array $d): array
    {
        $m = self::mots($d['lang']);

        $lignes = self::lignes($d, $m);
        $sujet  = $m['sujetMaison'] . ' — ' . $d['prenom'] . ' ' . $d['nom']
                . ($d['arrivee'] !== '' ? ' (' . $d['arrivee'] . ')' : '');

        $html = self::gabarit(
            $m['titreMaison'],
            '<p style="margin:0 0 18px;font-size:15px;line-height:1.6;color:#0d1e1c">'
                . self::e($m['introMaison']) . '</p>'
            . self::tableau($m['recapMaison'], $lignes)
            . '<p style="margin:22px 0 0;font-size:13px;line-height:1.6;color:#5f6d69">'
                . self::e($m['repondre']) . '</p>',
            $d['lang']
        );

        $texte = $m['titreMaison'] . "\n"
               . str_repeat('=', mb_strlen($m['titreMaison'])) . "\n\n"
               . $m['introMaison'] . "\n\n"
               . self::texteLignes($lignes) . "\n"
               . $m['repondre'] . "\n";

        return ['sujet' => $sujet, 'html' => $html, 'texte' => $texte];
    }

    // ══ Accusé de réception pour le client ════════════════════════════

    /** @return array{sujet:string, html:string, texte:string} */
    public static function versClient(array $d): array
    {
        $m = self::mots($d['lang']);

        $lignes  = self::lignes($d, $m, false);
        $contact = self::coordonnees($m);
        $maison  = Config::val('hotel.nom') ?: 'Home Sakalava';
        $sujet   = $m['sujetClient'] . $maison;

        $html = self::gabarit(
            $m['titreClient'],
            '<p style="margin:0 0 6px;font-size:15px;line-height:1.6;color:#0d1e1c">'
                . self::e(sprintf($m['introClient'], $d['prenom'])) . '</p>'
            . '<p style="margin:0 0 22px;font-size:15px;line-height:1.6;color:#0d1e1c">'
                . self::e($m['corpsClient']) . '</p>'
            . self::tableau($m['recap'], $lignes)
            . self::tableau($m['nousJoindre'], $contact)
            . '<p style="margin:22px 0 0;font-size:13px;line-height:1.6;color:#5f6d69">'
                . self::e($m['pasDeReponse']) . '</p>',
            $d['lang']
        );

        $texte = $m['titreClient'] . "\n"
               . str_repeat('=', mb_strlen($m['titreClient'])) . "\n\n"
               . sprintf($m['introClient'], $d['prenom']) . "\n\n"
               . $m['corpsClient'] . "\n\n"
               . $m['recap'] . "\n" . str_repeat('-', mb_strlen($m['recap'])) . "\n"
               . self::texteLignes($lignes) . "\n"
               . $m['nousJoindre'] . "\n" . str_repeat('-', mb_strlen($m['nousJoindre'])) . "\n"
               . self::texteLignes($contact) . "\n"
               . $m['pasDeReponse'] . "\n";

        return ['sujet' => $sujet, 'html' => $html, 'texte' => $texte];
    }

    // ══ Fabrication ═══════════════════════════════════════════════════

    /** Les lignes du récapitulatif, dans l'ordre de lecture. */
    private static function lignes(array $d, array $m, bool $avecContact = true): array
    {
        $l = [];

        if ($avecContact) {
            $l[] = [$m['nom'],       trim($d['prenom'] . ' ' . $d['nom'])];
            $l[] = [$m['email'],     $d['email']];
            $l[] = [$m['telephone'], $d['telephone'] ?: $m['aucun']];
        }

        $l[] = [$m['arrivee'],   self::dateLisible($d['arrivee'], $d['lang']) ?: $m['aucun']];
        $l[] = [$m['depart'],    self::dateLisible($d['depart'], $d['lang']) ?: $m['aucun']];
        $l[] = [$m['chambre'],   $d['chambre'] ?: $m['aucun']];
        $l[] = [$m['voyageurs'], $d['voyageurs'] ?: $m['aucun']];

        if ($d['message'] !== '') {
            $l[] = [$m['message'], $d['message']];
        }

        $l[] = [$m['recu'] === '' ? '' : '', sprintf($m['recu'], date('d/m/Y à H:i'))];
        array_pop($l); // la date part dans le pied de page, pas dans le tableau

        return $l;
    }

    /** Coordonnées de la maison, telles qu'elles figurent dans l'accusé. */
    private static function coordonnees(array $m): array
    {
        $l = [];

        if ($tel = Config::val('hotel.telephone')) {
            $l[] = [$m['telephone'], $tel];
        }
        if ($mail = Config::val('hotel.email')) {
            $l[] = [$m['email'], $mail];
        }
        if ($adresse = Config::liste('hotel.adresse')) {
            $l[] = [$m['adresse'], implode(', ', $adresse)];
        }
        if ($url = Config::siteUrl()) {
            $l[] = ['Site', $url];
        }

        return $l;
    }

    /** Tableau à deux colonnes, lisible partout. */
    private static function tableau(string $titre, array $lignes): string
    {
        if (!$lignes) {
            return '';
        }

        $out = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" '
             . 'width="100%" style="width:100%;border-collapse:collapse;margin:0 0 20px">'
             . '<tr><td colspan="2" style="padding:0 0 10px;font-size:12px;'
             . 'letter-spacing:.08em;text-transform:uppercase;color:#187773;'
             . 'font-weight:700">' . self::e($titre) . '</td></tr>';

        foreach ($lignes as [$cle, $valeur]) {
            $out .= '<tr>'
                  . '<td style="padding:9px 14px 9px 0;font-size:14px;color:#5f6d69;'
                  . 'border-top:1px solid #dedad2;vertical-align:top;white-space:nowrap">'
                  . self::e((string) $cle) . '</td>'
                  . '<td style="padding:9px 0;font-size:14px;color:#0d1e1c;'
                  . 'border-top:1px solid #dedad2;vertical-align:top;font-weight:600">'
                  . nl2br(self::e((string) $valeur)) . '</td>'
                  . '</tr>';
        }

        return $out . '</table>';
    }

    private static function texteLignes(array $lignes): string
    {
        $out = '';
        foreach ($lignes as [$cle, $valeur]) {
            $out .= $cle . ' : ' . str_replace("\n", "\n    ", (string) $valeur) . "\n";
        }
        return $out;
    }

    /**
     * Gabarit commun. Largeur fixée à 600 px, la seule qui passe partout,
     * et qui se réduit d'elle-même sur téléphone grâce à width:100%.
     */
    private static function gabarit(string $titre, string $corps, string $lang): string
    {
        $maison = self::e(Config::val('hotel.nom') ?: 'Home Sakalava');
        $pied   = self::e(implode(' · ', Config::liste('hotel.adresse')));
        $site   = Config::siteUrl();

        $lienSite = $site !== ''
            ? '<a href="' . self::e($site) . '" style="color:#187773;text-decoration:none">'
              . self::e(preg_replace('#^https?://#', '', $site) ?: $site) . '</a>'
            : '';

        return <<<HTML
        <!doctype html>
        <html lang="{$lang}">
        <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{$titre}</title>
        </head>
        <body style="margin:0;padding:0;background:#f0ece5;
                     -webkit-text-size-adjust:100%">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0"
               width="100%" style="width:100%;background:#f0ece5">
          <tr>
            <td align="center" style="padding:24px 12px">

              <table role="presentation" cellpadding="0" cellspacing="0" border="0"
                     width="600" style="width:100%;max-width:600px;background:#ffffff;
                     border:1px solid #dedad2;border-radius:14px;overflow:hidden">

                <tr>
                  <td style="padding:26px 28px;background:#0a1a1a;text-align:center">
                    <div style="font-family:Georgia,'Times New Roman',serif;
                                font-size:19px;letter-spacing:.26em;
                                text-transform:uppercase;color:#ffffff">{$maison}</div>
                  </td>
                </tr>

                <tr>
                  <td style="padding:30px 28px;font-family:-apple-system,
                             BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,
                             sans-serif">
                    <h1 style="margin:0 0 18px;font-size:20px;line-height:1.3;
                               color:#0d1e1c;font-weight:700">{$titre}</h1>
                    {$corps}
                  </td>
                </tr>

                <tr>
                  <td style="padding:18px 28px;background:#faf8f4;
                             border-top:1px solid #dedad2;text-align:center;
                             font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',
                             Roboto,Helvetica,Arial,sans-serif;
                             font-size:12px;line-height:1.6;color:#5f6d69">
                    {$pied}<br>{$lienSite}
                  </td>
                </tr>

              </table>

            </td>
          </tr>
        </table>
        </body>
        </html>
        HTML;
    }

    /** « 2026-12-24 » → « 24/12/2026 » ou « 24 December 2026 ». */
    private static function dateLisible(string $iso, string $lang): string
    {
        if ($iso === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $iso)) {
            return $iso;
        }
        $t = strtotime($iso);
        if ($t === false) {
            return $iso;
        }
        return $lang === 'en' ? date('j F Y', $t) : date('d/m/Y', $t);
    }
}
