<?php
/**
 * ═══════════════════════════════════════════════════════════════════
 *  config/site.php — LE SEUL FICHIER À REMPLIR
 * ═══════════════════════════════════════════════════════════════════
 *
 *  Toutes les coordonnées et les réglages d'envoi d'e-mails sont ici.
 *  Remplacez chaque « A_REMPLIR » par la vraie valeur, enregistrez, et
 *  le site se met à jour : liens tel:, WhatsApp, e-mail, plan d'accès,
 *  formulaire de réservation, données structurées, pied de page.
 *
 *  Une valeur laissée à « A_REMPLIR » n'est jamais inventée : le site
 *  affiche « à renseigner » à sa place et masque le lien correspondant.
 *
 *  ─────────────────────────────────────────────────────────────────
 *  CE FICHIER CONTIENT UN MOT DE PASSE une fois rempli.
 *  Il est refusé au navigateur par config/.htaccess, et exclu du
 *  dossier de livraison publique. Ne le publiez jamais sur un dépôt
 *  git ouvert une fois les vraies valeurs saisies.
 *  ─────────────────────────────────────────────────────────────────
 *
 *  Après modification : rien à faire, le site lit ce fichier à chaud.
 *  Pour rafraîchir les pages déjà générées : php tools/build.php
 */

declare(strict_types=1);

return [

    // ═══════════════════════════════════════════════════════════════
    //  1. LA MAISON
    // ═══════════════════════════════════════════════════════════════
    'hotel' => [

        'nom' => 'Home Sakalava',

        /**
         * Téléphone au format international, indicatif compris.
         * Exemple : +261 32 12 345 67
         * Sert à afficher le numéro ET à créer le lien d'appel.
         */
        'telephone' => 'A_REMPLIR',

        /**
         * WhatsApp, au format international SANS espaces, SANS « + »,
         * SANS zéro initial.  Exemple : 261321234567
         * Laissez « A_REMPLIR » si la maison n'a pas de WhatsApp :
         * le bouton flottant ne s'affichera pas.
         */
        'whatsapp' => 'A_REMPLIR',

        /**
         * Adresse qui reçoit les demandes de réservation du formulaire.
         * Tant qu'elle vaut « A_REMPLIR », le formulaire reste désactivé
         * et le site propose Facebook à la place.
         */
        'email' => 'A_REMPLIR',

        /** Adresse postale, une ligne par entrée. */
        'adresse' => [
            'Ampasikely',
            '207 Dzamandzar',
            'Nosy Be, région Diana',
            'Madagascar',
        ],

        /**
         * Coordonnées GPS, « latitude, longitude ».
         * Se relèvent dans Google Maps : appui long sur la maison, les
         * chiffres s'affichent en haut.  Exemple : -13.3987, 48.2345
         */
        'gps' => 'A_REMPLIR',

        /**
         * Lien Google Maps vers la maison.
         * Dans Google Maps : Partager → Copier le lien.
         * Laissé vide, le site fabrique un lien à partir des coordonnées
         * GPS ci-dessus si elles sont renseignées.
         */
        'maps' => 'A_REMPLIR',
    ],

    // ═══════════════════════════════════════════════════════════════
    //  2. RÉSEAUX SOCIAUX
    //  Laissez « A_REMPLIR » pour ne pas afficher le lien.
    // ═══════════════════════════════════════════════════════════════
    'reseaux' => [
        'facebook'    => 'https://www.facebook.com/home.sakalava.nosybe',
        'instagram'   => 'A_REMPLIR',
        'tripadvisor' => 'A_REMPLIR',
        'booking'     => 'A_REMPLIR',
    ],

    // ═══════════════════════════════════════════════════════════════
    //  3. ADRESSE DU SITE
    //  Nécessaire au plan du site, à l'aperçu de partage et aux liens
    //  dans les e-mails.  Sans barre oblique finale.
    //  Exemple : https://homesakalava.mg
    // ═══════════════════════════════════════════════════════════════
    'site' => [
        'url' => 'A_REMPLIR',
    ],

    // ═══════════════════════════════════════════════════════════════
    //  4. ENVOI DES E-MAILS (SMTP)
    //
    //  Ces réglages vous sont donnés par votre hébergeur, ou par le
    //  fournisseur de la boîte mail (OVH, Gmail, Infomaniak…).
    //
    //  ATTENTION avec Gmail : il faut un « mot de passe d'application »,
    //  pas le mot de passe du compte.
    // ═══════════════════════════════════════════════════════════════
    'smtp' => [

        /** Serveur d'envoi. Exemple : ssl0.ovh.net, smtp.gmail.com */
        'hote' => 'A_REMPLIR',

        /**
         * Port :  587 avec 'tls'  (le plus courant)
         *         465 avec 'ssl'
         *          25 avec ''     (sans chiffrement, à éviter)
         */
        'port' => 587,

        /** 'tls', 'ssl', ou '' pour aucun chiffrement. */
        'securite' => 'tls',

        /** Identifiant de connexion, souvent l'adresse e-mail complète. */
        'utilisateur' => 'A_REMPLIR',

        /** Mot de passe de la boîte mail. */
        'motdepasse' => 'A_REMPLIR',

        /**
         * Expéditeur affiché dans les messages envoyés par le site.
         * Doit appartenir au même domaine que le compte SMTP, sans quoi
         * les messages partiront en indésirables.
         */
        'expediteur_email' => 'A_REMPLIR',
        'expediteur_nom'   => 'Home Sakalava',
    ],

    // ═══════════════════════════════════════════════════════════════
    //  5. COMPORTEMENT DU FORMULAIRE
    // ═══════════════════════════════════════════════════════════════
    'formulaire' => [

        /**
         * MODE TEST.
         *
         *   true  → aucun e-mail n'est envoyé. Les messages sont écrits
         *           dans data/emails-test.log, lisibles tels quels.
         *           Permet de vérifier le formulaire sans SMTP.
         *
         *   false → les e-mails partent réellement.
         *
         * À BASCULER SUR false LE JOUR DE LA MISE EN LIGNE,
         * une fois les réglages SMTP ci-dessus vérifiés.
         */
        'mode_test' => true,

        /**
         * Nombre maximal de demandes acceptées depuis une même adresse
         * IP par heure. Protège d'un envoi en boucle.
         */
        'max_par_heure' => 5,

        /**
         * Copie cachée de chaque demande, si vous voulez en garder une
         * trace sur une autre adresse. « A_REMPLIR » pour ne rien
         * envoyer en copie.
         */
        'copie_cachee' => 'A_REMPLIR',
    ],
];
