<?php
/**
 * lib/i18n.php — Gestion des langues du site (français et anglais).
 *
 * Le français est la langue source : il vit dans data/content.json. Les
 * autres langues sont des surcouches dans data/i18n/<langue>.json, qui ne
 * reprennent que les textes traduits. Tout ce qui n'y figure pas retombe
 * sur le français, ce qui garantit qu'une page n'est jamais vide, même si
 * une traduction manque.
 *
 * Les photos, les couleurs et les coordonnées ne sont pas traduits : ils
 * restent communs aux deux langues.
 */

declare(strict_types=1);

final class I18n
{
    /**
     * Langues publiées, dans l'ordre d'affichage du sélecteur.
     *
     * L'allemand et l'italien ont été retirés à la demande du client. Leurs
     * traductions sont conservées dans data/i18n/desactive/ : les remettre en
     * ligne consiste à replacer le fichier dans data/i18n/ et à rajouter sa
     * ligne ici, rien de plus.
     */
    public const LANGUES = [
        'fr' => ['nom' => 'Français', 'locale' => 'fr_FR', 'court' => 'FR'],
        'en' => ['nom' => 'English',  'locale' => 'en_GB', 'court' => 'EN'],
    ];

    public const SOURCE = 'fr';

    /**
     * Textes d'interface en français — la référence.
     * Les fichiers de traduction reprennent ces mêmes clés.
     */
    public static function uiSource(): array
    {
        return [
            // Navigation et repères généraux
            'skip'         => 'Aller au contenu',
            'openMenu'     => 'Ouvrir le menu',
            'follow'       => 'Suivez-nous',
            'info'         => 'Informations',
            'supplement'   => 'supplément',
            'toFill'       => '[À renseigner]',
            'around'       => 'Aux alentours',
            'facebookPage' => 'Notre page Facebook',
            'langLabel'    => 'Choisir la langue',

            // Chambres
            'room'         => 'Chambre',
            'seeRoom'      => 'Voir la chambre',
            'allRooms'     => 'Voir les 5 chambres en détail',
            'prevRoom'     => 'Chambre précédente',
            'nextRoom'     => 'Chambre suivante',
            'inEveryRoom'  => 'Dans toutes les chambres',
            'askThisRoom'  => 'Demander cette chambre',
            'viewPhotosOf' => 'Voir les photos de la chambre',
            'photo'        => 'Photo',
            'from'         => 'Tarif',
            'accessTitle'  => 'Accès et contact',

            // Fiche pratique
            'address'      => 'Adresse',
            'phone'        => 'Téléphone',
            'email'        => 'E-mail',
            'gps'          => 'Coordonnées GPS',
            'checkin'      => 'Arrivée',
            'checkout'     => 'Départ',
            'languages'    => 'Langues parlées',
            'languagesShort' => 'Langues',
            'payment'      => 'Paiement',

            // Formulaire de contact
            'formFirstName' => 'Prénom',
            'formLastName'  => 'Nom',
            'formEmail'     => 'E-mail',
            'formPhone'     => 'Téléphone',
            'formCheckIn'   => "Date d'arrivée",
            'formCheckOut'  => 'Date de départ',
            'formRoomType'  => 'Chambre souhaitée',
            'formGuests'    => 'Voyageurs',
            'formMessage'   => 'Votre message',
            'formSubmit'    => 'Envoyer ma demande',
            'formSelect'    => '— Sélectionner —',
            'guest'         => 'voyageur',
            'guests'        => 'voyageurs',
            'formError'     => 'Merci de compléter les champs obligatoires.',
            'formNote'      => "Votre demande nous est envoyée directement. Vous recevez "
                             . "une confirmation par e-mail dans la foulée.",
            'formNoEmail'   => "L'adresse e-mail de la maison n'est pas encore en ligne. "
                             . "En attendant, écrivez-nous sur Facebook :",
            'formFbLink'    => 'Ouvrir la page Facebook',
            'formSuccessTitle' => 'Demande envoyée',
            'formSuccessText'  => "Merci. Nous avons bien reçu votre demande et vous répondons "
                                . "sous deux jours. Une confirmation vient de vous être envoyée.",
            'formReset'     => 'Envoyer une autre demande',
            'formSending'   => 'Envoi en cours…',
            'formOffline'   => "La connexion a été interrompue. Vérifiez votre réseau et réessayez.",
            'formRequired'  => 'Ce champ est obligatoire.',
            'formBadEmail'  => "Cette adresse e-mail ne semble pas valide.",
            'formBadDates'  => "Le départ doit être après l'arrivée.",
            'formPastDate'  => "Cette date est déjà passée.",
            'phFirstName'   => 'Prénom',
            'phLastName'    => 'Nom',
            'phEmail'       => 'vous@exemple.com',
            'phPhone'       => 'Indicatif compris',
            'phMessage'     => "Nombre de nuits, heure d'arrivée, navette aéroport, régime alimentaire…",

            // Message pré-rédigé
            'mailGreeting'  => 'Bonjour Boda et Bakoly,',
            'mailIntro'     => 'Je souhaite réserver une chambre à Home Sakalava.',
            'mailName'      => 'Nom',
            'mailEmail'     => 'E-mail',
            'mailPhone'     => 'Téléphone',
            'mailDates'     => 'Dates',
            'mailRoom'      => 'Chambre',
            'mailGuests'    => 'Voyageurs',
            'mailMessage'   => 'Message',
            'mailThanks'    => "Merci d'avance,",
            'mailAdvise'    => 'à conseiller',
            'mailSubject'   => 'Demande de réservation',
            'mailNoDates'   => 'dates à définir',

            // Titres de pages, quand aucun n'est saisi
            'pageRooms'     => 'Nos chambres',
            'pageActivities'=> 'Activités',
            'pageAccess'    => 'Accès et contact',
        ];
    }

    private array $ui;
    private array $content;

    public function __construct(private string $root, private string $lang)
    {
        $source = self::uiSource();

        if ($lang === self::SOURCE) {
            $this->ui = $source;
            $this->content = [];
            return;
        }

        $fichier = $root . '/data/i18n/' . $lang . '.json';
        $data = is_file($fichier)
            ? (json_decode((string) file_get_contents($fichier), true) ?: [])
            : [];

        // Les clés absentes de la traduction gardent leur valeur française.
        $this->ui      = array_merge($source, $data['ui'] ?? []);
        $this->content = $data['content'] ?? [];
    }

    public function lang(): string
    {
        return $this->lang;
    }

    public function locale(): string
    {
        return self::LANGUES[$this->lang]['locale'] ?? 'fr_FR';
    }

    /** Dictionnaire d'interface complet, injecté dans config.js. */
    public function ui(): array
    {
        return $this->ui;
    }

    public function t(string $cle, string $repli = ''): string
    {
        return (string) ($this->ui[$cle] ?? ($repli !== '' ? $repli : $cle));
    }

    /**
     * Applique la traduction sur le contenu français.
     *
     * Fusion récursive : une liste traduite remplace la liste française
     * élément par élément, ce qui permet de ne traduire que le libellé d'une
     * entrée sans avoir à recopier son icône ou son identifiant.
     */
    public function traduire(array $contenu): array
    {
        if ($this->lang === self::SOURCE || !$this->content) {
            return $contenu;
        }
        return self::fusionner($contenu, $this->content);
    }

    private static function fusionner(array $base, array $surcouche): array
    {
        foreach ($surcouche as $cle => $valeur) {
            if (is_array($valeur) && isset($base[$cle]) && is_array($base[$cle])) {
                $base[$cle] = self::fusionner($base[$cle], $valeur);
            } elseif ($valeur !== null && $valeur !== '') {
                $base[$cle] = $valeur;
            }
        }
        return $base;
    }

    /** Préfixe de chemin vers la racine depuis le dossier de cette langue. */
    public function prefixe(): string
    {
        return $this->lang === self::SOURCE ? '' : '../';
    }

    /** Dossier de sortie de cette langue, relatif à la racine. */
    public function dossier(): string
    {
        return $this->lang === self::SOURCE ? '' : $this->lang . '/';
    }

    /**
     * Sélecteur de langue, rendu dans la barre de navigation.
     * Des liens simples : il fonctionne sans JavaScript, et chaque langue a
     * sa propre adresse, ce qui est indispensable au référencement.
     */
    public function selecteur(string $page): string
    {
        $fichier = $page . '.html';
        $out = '<div class="langsel" role="group" aria-label="'
             . htmlspecialchars($this->t('langLabel'), ENT_QUOTES) . '">';

        foreach (self::LANGUES as $code => $info) {
            $href = ($code === self::SOURCE)
                ? $this->prefixe() . $fichier
                : $this->prefixe() . $code . '/' . $fichier;

            $actif = $code === $this->lang;
            $out .= '<a class="langsel__item' . ($actif ? ' is-active' : '') . '"'
                  . ' href="' . htmlspecialchars($href, ENT_QUOTES) . '"'
                  . ' lang="' . $code . '" hreflang="' . $code . '"'
                  . ' data-lang="' . $code . '"'
                  . ' title="' . htmlspecialchars($info['nom'], ENT_QUOTES) . '"'
                  . ($actif ? ' aria-current="true"' : '')
                  . '>' . $info['court'] . '</a>';
        }

        return $out . '</div>';
    }

    /**
     * Petit script placé dans l'en-tête, qui ouvre le site dans la langue
     * du visiteur et retient son choix.
     *
     * Trois règles, dans cet ordre :
     *   1. un choix explicite fait autorité et n'est jamais contredit ;
     *   2. à défaut, et à la première visite seulement, on suit la langue
     *      du navigateur ;
     *   3. si cette langue n'est pas publiée, on ne touche à rien.
     *
     * Il est écrit directement dans l'en-tête, avant l'affichage : une
     * redirection décidée plus tard ferait clignoter la page dans la
     * mauvaise langue. Il ne dépend d'aucune librairie et échoue en silence
     * si le navigateur refuse le stockage local (navigation privée).
     */
    public function scriptLangue(string $page): string
    {
        $fichier = $page . '.html';
        $langues = json_encode(array_keys(self::LANGUES));
        $courante = $this->lang;
        $prefixe  = $this->prefixe();
        $defaut   = self::SOURCE;

        return <<<JS
          <script>
          (function () {
            var PUBLIEES = {$langues};
            var COURANTE = "{$courante}";
            var PAGE     = "{$fichier}";
            var PREFIXE  = "{$prefixe}";
            var DEFAUT   = "{$defaut}";
            var CLE      = "sakalava_langue";

            function lire()  { try { return localStorage.getItem(CLE); } catch (e) { return null; } }
            function ecrire(v) { try { localStorage.setItem(CLE, v); } catch (e) {} }

            // Mémorise le choix au moment du clic, avant que la page ne change.
            document.addEventListener("click", function (e) {
              var lien = e.target && e.target.closest && e.target.closest("[data-lang]");
              if (lien) ecrire(lien.getAttribute("data-lang"));
            }, true);

            var voulue = lire();

            // Langue du navigateur : uniquement sur la page d'accueil, et
            // seulement à la première visite. Dérouter quelqu'un qui a reçu
            // un lien direct vers une page précise serait désagréable — et
            // il a peut-être choisi cette langue en connaissance de cause.
            if (!voulue && PAGE === "index.html") {
              var nav = (navigator.languages && navigator.languages[0]) || navigator.language || "";
              nav = String(nav).toLowerCase().slice(0, 2);
              if (PUBLIEES.indexOf(nav) !== -1) voulue = nav;
            }

            if (!voulue || voulue === COURANTE) return;
            if (PUBLIEES.indexOf(voulue) === -1) return;

            var cible = (voulue === DEFAUT ? PREFIXE : PREFIXE + voulue + "/") + PAGE;
            location.replace(cible + location.hash);
          })();
          </script>
        JS;
    }

    /** Liens alternatifs hreflang, pour que Google relie les versions. */
    public function hreflang(string $page, string $siteUrl): string
    {
        if ($siteUrl === '') {
            return '';
        }
        $base    = rtrim($siteUrl, '/');
        $fichier = $page === 'index' ? '' : $page . '.html';

        $lignes = [];
        foreach (array_keys(self::LANGUES) as $code) {
            $url = $code === self::SOURCE
                ? $base . '/' . $fichier
                : $base . '/' . $code . '/' . $fichier;
            $lignes[] = '  <link rel="alternate" hreflang="' . $code . '" href="'
                      . htmlspecialchars($url, ENT_QUOTES) . '">';
        }
        $lignes[] = '  <link rel="alternate" hreflang="x-default" href="'
                  . htmlspecialchars($base . '/' . $fichier, ENT_QUOTES) . '">';

        return implode("\n", $lignes);
    }
}
