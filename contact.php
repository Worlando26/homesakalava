<?php
/**
 * contact.php — Réception du formulaire de demande de réservation.
 *
 * Seul point d'entrée PHP du site public. Il valide la demande, se protège
 * des envois automatisés, puis envoie deux messages : l'un à la maison,
 * l'autre au client.
 *
 * Répond en JSON : le formulaire l'appelle en arrière-plan, sans recharger
 * la page. Si le visiteur n'a pas JavaScript, le formulaire est soumis
 * normalement et la réponse est une page complète, lisible.
 *
 * Rien n'est stocké ici : ce n'est pas une gestion de réservations, juste
 * un message qui part. Seul un compteur anonyme sert à limiter les envois.
 */

declare(strict_types=1);

// Les erreurs ne s'affichent jamais : elles révèlent des chemins de fichiers.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

define('APP_ROOT', __DIR__);

if (is_dir(APP_ROOT . '/data') && is_writable(APP_ROOT . '/data')) {
    ini_set('error_log', APP_ROOT . '/data/erreurs.log');
}

require_once APP_ROOT . '/lib/config.php';
require_once APP_ROOT . '/lib/mailer.php';
require_once APP_ROOT . '/lib/emails.php';

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

// ══════════════════════════════════════════════════════════════════════
//  Réponse
// ══════════════════════════════════════════════════════════════════════

/** Le formulaire a-t-il été envoyé en arrière-plan ? */
function enArrierePlan(): bool
{
    $entete = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
    return $entete === 'fetch' || str_contains($accept, 'application/json');
}

/**
 * Termine la requête.
 *
 * @param array<string,string> $champs Erreurs par champ, pour surligner.
 */
function repondre(bool $ok, string $message, array $champs = [], int $code = 200): never
{
    if (enArrierePlan()) {
        http_response_code($code);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(
            ['ok' => $ok, 'message' => $message, 'champs' => $champs],
            JSON_UNESCAPED_UNICODE
        );
        exit;
    }

    // Repli sans JavaScript : une vraie page, pas un fragment JSON.
    http_response_code($code);
    header('Content-Type: text/html; charset=UTF-8');

    $e = fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $retour = str_starts_with((string) ($_POST['lang'] ?? 'fr'), 'en')
        ? 'en/acces.html' : 'acces.html';
    $titre  = $ok ? 'Demande envoyée' : 'Demande non envoyée';
    $couleur = $ok ? '#125c47' : '#8f2018';
    $fond    = $ok ? '#e6f4ef' : '#fdeceb';

    echo <<<HTML
    <!doctype html>
    <html lang="fr"><head><meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{$e($titre)}</title>
    <link rel="stylesheet" href="css/reset.css">
    <link rel="stylesheet" href="css/variables.css">
    <link rel="stylesheet" href="css/base.css">
    <link rel="stylesheet" href="css/components.css">
    </head>
    <body style="display:grid;place-items:center;min-height:100vh;padding:24px">
      <main style="max-width:520px;text-align:center">
        <div style="background:{$fond};color:{$couleur};border-radius:14px;
                    padding:26px 24px;font-size:15px;line-height:1.6">
          <h1 style="font-size:21px;margin-bottom:10px">{$e($titre)}</h1>
          <p>{$e($message)}</p>
        </div>
        <p style="margin-top:22px">
          <a class="btn btn--pill btn--outline" href="{$e($retour)}">Retour au site</a>
        </p>
      </main>
    </body></html>
    HTML;
    exit;
}

// ══════════════════════════════════════════════════════════════════════
//  Garde-fous
// ══════════════════════════════════════════════════════════════════════

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    repondre(false, "Cette adresse ne répond qu'aux envois du formulaire.", [], 405);
}

$lang = ($_POST['lang'] ?? 'fr') === 'en' ? 'en' : 'fr';

/** Messages rendus au visiteur, dans sa langue. */
$DIT = [
    'fr' => [
        'inactif'    => "Le formulaire n'est pas encore activé. Écrivez-nous sur Facebook en attendant.",
        'trop'       => "Vous avez déjà envoyé plusieurs demandes. Réessayez dans une heure, ou appelez-nous.",
        'requis'     => 'Ce champ est obligatoire.',
        'email'      => "Cette adresse e-mail ne semble pas valide.",
        'tel'        => "Ce numéro ne semble pas valide. Indiquez l'indicatif du pays.",
        'datePassee' => "Cette date est déjà passée.",
        'dateOrdre'  => "Le départ doit être après l'arrivée.",
        'dateLoin'   => "Cette date est trop éloignée.",
        'corrige'    => 'Merci de corriger les champs signalés.',
        'echec'      => "Votre demande n'a pas pu être envoyée. Réessayez, ou appelez-nous directement.",
        'succes'     => "Votre demande est envoyée. Vous recevez une confirmation par e-mail, et nous vous répondons sous deux jours.",
        'succesTest' => "Mode test : la demande a été enregistrée dans data/emails-test.log, aucun e-mail n'est parti.",
    ],
    'en' => [
        'inactif'    => "The form is not active yet. Please write to us on Facebook in the meantime.",
        'trop'       => "You have already sent several requests. Please try again in an hour, or call us.",
        'requis'     => 'This field is required.',
        'email'      => "This email address does not look valid.",
        'tel'        => "This number does not look valid. Please include the country code.",
        'datePassee' => "This date is in the past.",
        'dateOrdre'  => "Departure must be after arrival.",
        'dateLoin'   => "This date is too far ahead.",
        'corrige'    => 'Please correct the highlighted fields.',
        'echec'      => "Your request could not be sent. Please try again, or call us directly.",
        'succes'     => "Your request has been sent. You will receive a confirmation by email, and we will reply within two days.",
        'succesTest' => "Test mode: the request was written to data/emails-test.log, no email was sent.",
    ],
][$lang];

// Le formulaire ne fonctionne que si l'adresse de réception est renseignée.
if (!Config::formulaireActif()) {
    repondre(false, $DIT['inactif'], [], 503);
}

/**
 * Piège à robots. Le champ est invisible et vide pour un humain ; un
 * automate le remplit. On répond alors un succès sans rien envoyer : le
 * robot ne réessaie pas, et un humain qui aurait rempli ce champ par
 * accident n'est pas bloqué avec un message incompréhensible.
 */
if (trim((string) ($_POST['site_web'] ?? '')) !== '') {
    repondre(true, $DIT['succes']);
}

/**
 * Deuxième filtre : un formulaire envoyé en moins de trois secondes n'a pas
 * pu être rempli par un être humain.
 */
$ouvertA = (int) ($_POST['_t'] ?? 0);
if ($ouvertA > 0 && (time() - $ouvertA) < 3) {
    repondre(true, $DIT['succes']);
}

// ── Limitation du nombre d'envois ─────────────────────────────────────

/**
 * Compteur par adresse IP, gardé une heure.
 * L'IP est stockée hachée : le fichier ne conserve aucune donnée
 * personnelle lisible.
 */
function tropDEnvois(): bool
{
    $fichier = APP_ROOT . '/data/envois.json';
    $max     = Config::maxParHeure();
    $ip      = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $cle     = substr(hash('sha256', $ip . '|sakalava'), 0, 16);
    $maintenant = time();

    $fp = @fopen($fichier, 'c+');
    if ($fp === false) {
        // Impossible de compter : on laisse passer plutôt que de bloquer
        // un client légitime.
        return false;
    }

    try {
        if (!flock($fp, LOCK_EX)) {
            return false;
        }

        $taille  = filesize($fichier) ?: 0;
        $contenu = $taille > 0 ? (string) fread($fp, $taille) : '';
        $data    = json_decode($contenu, true);
        $data    = is_array($data) ? $data : [];

        // On ne garde que la dernière heure, pour tout le monde.
        foreach ($data as $k => $horodatages) {
            $data[$k] = array_values(array_filter(
                (array) $horodatages,
                fn($t) => is_int($t) && $t > $maintenant - 3600
            ));
            if (!$data[$k]) {
                unset($data[$k]);
            }
        }

        $deja = count($data[$cle] ?? []);
        if ($deja >= $max) {
            return true;
        }

        $data[$cle][] = $maintenant;

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, (string) json_encode($data));
        fflush($fp);

        return false;
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

if (tropDEnvois()) {
    repondre(false, $DIT['trop'], [], 429);
}

// ══════════════════════════════════════════════════════════════════════
//  Lecture et validation
// ══════════════════════════════════════════════════════════════════════

/** Nettoie une saisie : UTF-8 garanti, caractères de contrôle retirés. */
function champ(string $nom, int $max = 200): string
{
    $v = $_POST[$nom] ?? '';
    if (!is_string($v)) {
        return '';
    }
    if (!mb_check_encoding($v, 'UTF-8')) {
        $v = mb_convert_encoding($v, 'UTF-8', 'Windows-1252');
    }
    // On conserve les sauts de ligne du message, on retire le reste.
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v) ?? '';
    return mb_substr(trim($v), 0, $max);
}

$d = [
    'lang'      => $lang,
    'prenom'    => champ('prenom', 60),
    'nom'       => champ('nom', 60),
    'email'     => champ('email', 120),
    'telephone' => champ('telephone', 40),
    'arrivee'   => champ('arrivee', 10),
    'depart'    => champ('depart', 10),
    'chambre'   => champ('chambre', 80),
    'voyageurs' => champ('voyageurs', 10),
    'message'   => champ('message', 2000),
];

$erreurs = [];

foreach (['prenom', 'nom', 'email'] as $obligatoire) {
    if ($d[$obligatoire] === '') {
        $erreurs[$obligatoire] = $DIT['requis'];
    }
}

if ($d['email'] !== '' && !filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
    $erreurs['email'] = $DIT['email'];
}

// Le téléphone est facultatif, mais s'il est donné il doit être plausible :
// au moins 8 chiffres, aucun caractère exotique.
if ($d['telephone'] !== '') {
    $chiffres = preg_replace('/\D/', '', $d['telephone']) ?? '';
    if (strlen($chiffres) < 8 || strlen($chiffres) > 15
        || !preg_match('/^[\d\s+().\-\/]+$/', $d['telephone'])) {
        $erreurs['telephone'] = $DIT['tel'];
    }
}

/** Date au format du formulaire, ou chaîne vide si absente ou absurde. */
function dateValide(string $v): ?DateTimeImmutable
{
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
        return null;
    }
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $v);
    return $dt === false ? null : $dt;
}

$aujourdhui = new DateTimeImmutable('today');
$arrivee    = dateValide($d['arrivee']);
$depart     = dateValide($d['depart']);

if ($d['arrivee'] !== '' && $arrivee === null) {
    $erreurs['arrivee'] = $DIT['requis'];
} elseif ($arrivee !== null) {
    if ($arrivee < $aujourdhui) {
        $erreurs['arrivee'] = $DIT['datePassee'];
    } elseif ($arrivee > $aujourdhui->modify('+3 years')) {
        $erreurs['arrivee'] = $DIT['dateLoin'];
    }
}

if ($d['depart'] !== '' && $depart === null) {
    $erreurs['depart'] = $DIT['requis'];
} elseif ($depart !== null) {
    if ($depart < $aujourdhui) {
        $erreurs['depart'] = $DIT['datePassee'];
    } elseif ($arrivee !== null && $depart <= $arrivee) {
        $erreurs['depart'] = $DIT['dateOrdre'];
    }
}

if ($erreurs) {
    repondre(false, $DIT['corrige'], $erreurs, 422);
}

// ══════════════════════════════════════════════════════════════════════
//  Envoi
// ══════════════════════════════════════════════════════════════════════

$versMaison = Emails::versMaison($d);
$versClient = Emails::versClient($d);

$copie = Config::val('formulaire.copie_cachee');

// 1. La demande, à la maison. Reply-To sur le client : répondre depuis la
//    boîte de la maison écrit directement au voyageur.
$r1 = Mailer::envoyer([
    'a'       => ['email' => Config::val('hotel.email'), 'nom' => Config::val('hotel.nom')],
    'sujet'   => $versMaison['sujet'],
    'html'    => $versMaison['html'],
    'texte'   => $versMaison['texte'],
    'replyTo' => ['email' => $d['email'], 'nom' => trim($d['prenom'] . ' ' . $d['nom'])],
    'bcc'     => $copie,
]);

if (!$r1['ok']) {
    repondre(false, $DIT['echec'], [], 502);
}

// 2. L'accusé de réception, au client. Son échec ne doit pas faire croire
//    au visiteur que sa demande est perdue : elle est déjà partie.
$r2 = Mailer::envoyer([
    'a'       => ['email' => $d['email'], 'nom' => trim($d['prenom'] . ' ' . $d['nom'])],
    'sujet'   => $versClient['sujet'],
    'html'    => $versClient['html'],
    'texte'   => $versClient['texte'],
    'replyTo' => ['email' => Config::val('hotel.email'), 'nom' => Config::val('hotel.nom')],
]);

if (!$r2['ok']) {
    error_log('[contact] accusé de réception non remis à ' . $d['email']);
}

repondre(true, $r1['mode'] === 'test' ? $DIT['succesTest'] : $DIT['succes']);
