<?php
/**
 * config/site.php — LE SEUL FICHIER À REMPLIR
 *
 * Ce fichier a été mis à jour depuis le back-office.
 * Vous pouvez aussi le modifier à la main : le site le relit à
 * chaud, sans rien à régénérer.
 *
 * Une valeur laissée à « A_REMPLIR » n'est jamais inventée : le
 * site affiche « à renseigner » et masque le lien correspondant.
 *
 * CE FICHIER CONTIENT UN MOT DE PASSE une fois rempli. Il est
 * refusé au navigateur par config/.htaccess et exclu du dossier
 * de livraison. Ne le publiez pas sur un dépôt git ouvert.
 */

declare(strict_types=1);

return [
    'hotel' => [
        'nom' => 'Home Sakalava',
        'telephone' => '+261 32 21 709 93',
        'whatsapp' => '261322170993',
        'email' => 'home.sakalava27@gmail.com',
        'adresse' => [
            'Ampasikely',
            '207 Dzamandzar',
            'Nosy Be, région Diana',
            'Madagascar',
        ],
        'gps' => '-13.3987, 48.2345',
        'maps' => 'A_REMPLIR',
    ],
    'reseaux' => [
        'facebook' => 'https://www.facebook.com/home.sakalava.nosybe',
        'instagram' => 'A_REMPLIR',
        'tripadvisor' => 'A_REMPLIR',
        'booking' => 'A_REMPLIR',
    ],
    'site' => [
        'url' => 'A_REMPLIR',
    ],
    'smtp' => [
        'hote' => 'smtp.gmail.com',
        'port' => 587,
        'securite' => 'tls',
        'utilisateur' => 'home.sakalava27@gmail.com',
        'motdepasse' => 'A_REMPLIR',
        'expediteur_email' => 'home.sakalava27@gmail.com',
        'expediteur_nom' => 'Home Sakalava',
    ],
    'formulaire' => [
        'mode_test' => true,
        'max_par_heure' => 5,
        'copie_cachee' => 'A_REMPLIR',
    ],
];
