<?php
require_once __DIR__ . '/bb_auth.php';

rolle_erforderlich(ROLLE_MITARBEITER);
modus_aus_url_setzen();

require_once __DIR__ . '/bb_db.php';
require_once __DIR__ . '/cc_benutzer.php';
require_once __DIR__ . '/cc_stundenzettel.php';
require_once __DIR__ . '/cc_zeiteintraege.php';
require_once __DIR__ . '/cc_urlaubskonten.php';
require_once __DIR__ . '/cc_urlaubsantraege.php';

$benutzer = aktueller_benutzer();

// Benutzernummer robust aus Fachklasse/Array/Objekt holen
$benutzerId = null;
if ($benutzer instanceof CBenutzer) {
    $benutzerId = (int)$benutzer->GetID();
} elseif (is_array($benutzer)) {
    $benutzerId = isset($benutzer['benutzer_id'])
        ? (int)$benutzer['benutzer_id']
        : (isset($benutzer['id']) ? (int)$benutzer['id'] : null);
} elseif (is_object($benutzer)) {
    $benutzerId = isset($benutzer->benutzer_id)
        ? (int)$benutzer->benutzer_id
        : (isset($benutzer->id) ? (int)$benutzer->id : null);
}

if ($benutzerId === null) {
    die('Fehler: Benutzer-ID nicht im Kontext gefunden. Bitte neu anmelden.');
}

// Aktuelles Quartal bestimmen
$heute  = new DateTimeImmutable('today');
$jahr   = (int)$heute->format('Y');
$monat  = (int)$heute->format('n');
$quartal = (int)ceil($monat / 3);

// Start/Ende des Quartals
$quartalStartMonat = (($quartal - 1) * 3) + 1;
$quartalStart = new DateTimeImmutable(sprintf('%04d-%02d-01', $jahr, $quartalStartMonat));
$quartalEnde  = $quartalStart->modify('+3 months')->modify('-1 day');

// Feiertage-Funktion (rein fachlich, keine DB)
function getFeiertage(int $jahr): array
{
    $feiertage = [
        sprintf('%04d-01-01', $jahr), // Neujahr
        sprintf('%04d-05-01', $jahr), // 1. Mai
        sprintf('%04d-10-03', $jahr), // Tag der dt. Einheit
        sprintf('%04d-12-25', $jahr), // 1. Weihnachtstag
        sprintf('%04d-12-26', $jahr), // 2. Weihnachtstag
    ];

    $ostersonntag = date('Y-m-d', easter_date($jahr));
    $feiertage[] = date('Y-m-d', strtotime($ostersonntag . ' -2 days')); // Karfreitag
    $feiertage[] = date('Y-m-d', strtotime($ostersonntag . ' +1 day')); // Ostermontag
    $feiertage[] = date('Y-m-d', strtotime($ostersonntag . ' +39 days')); // Himmelfahrt
    $feiertage[] = date('Y-m-d', strtotime($ostersonntag . ' +50 days')); // Pfingstmontag

    return $feiertage;
}

$feiertage = getFeiertage($jahr);

// Summen über Repositories holen (nur eigener Benutzer)
function ermittleTageswerte(PDO $pdo, int $benutzerId, DateTimeInterface $tag): array
{
    $stunden = CZeiteintragRepository::summeStundenProTag($pdo, $benutzerId, $tag);
    $urlaubCnt = CUrlaubsantragRepository::anzahlGenehmigteUrlaubsantraegeAmTag($pdo, $benutzerId, $tag);

    // Krankheit aktuell: noch keine fachliche DB-Logik, daher 0
    $krank = 0;

    return [
        'stunden' => $stunden,
        'urlaub'  => $urlaubCnt,
        'krank'   => $krank,
    ];
}

// Daten für Anzeige vorbereiten
$quartalSummen = ['stunden' => 0.0, 'urlaub' => 0, 'krank' => 0];
$monatSummen   = ['stunden' => 0.0, 'urlaub' => 0, 'krank' => 0];

$aktuellerMonat = (int)$quartalStart->format('n');

// Wir laufen über alle Tage im Quartal und erzeugen eine Struktur,
// damit das HTML unten sauber arbeiten kann.
$tage = [];

for ($tag = $quartalStart; $tag <= $quartalEnde; $tag = $tag->modify('+1 day')) {
    $datumSql     = $tag->format('Y-m-d');
    $datumAnzeige = $tag->format('d.m.Y');
    $wochentag    = (int)$tag->format('N'); // 6=Sa, 7=So

    $werte = ermittleTageswerte($pdo, $benutzerId, $tag);

    $monatSummen['stunden'] += $werte['stunden'];
    $monatSummen['urlaub']  += $werte['urlaub'];
    $monatSummen['krank']   += $werte['krank'];

    $quartalSummen['stunden'] += $werte['stunden'];
    $quartalSummen['urlaub']  += $werte['urlaub'];
    $quartalSummen['krank']   += $werte['krank'];

    $istWochenendeOderFeiertag = ($wochentag >= 6) || in_array($datumSql, $feiertage, true);

    // Ermitteln, ob nach diesem Tag ein Monatswechsel stattfindet
    $naechsterTag = $tag->modify('+1 day');
    $monatWechsel = ((int)$naechsterTag->format('n') !== $aktuellerMonat) || ($tag == $quartalEnde);

    $tage[] = [
        'datum_sql'        => $datumSql,
        'datum_anzeige'    => $datumAnzeige,
        'monat'            => $aktuellerMonat,
        'werte'            => $werte,
        'ist_feiertag_od'  => $istWochenendeOderFeiertag,
        'monat_wechsel'    => $monatWechsel,
        // Referenzen für Summenzeile (werden beim Monatswechsel genutzt)
        'monat_summen_ref' => $monatSummen,
    ];

    if ($monatWechsel) {
        // Summen merken und für nächsten Monat zurücksetzen
        $tage[] = [
            'monat_summe' => [
                'monat'   => $aktuellerMonat,
                'jahr'    => $jahr,
                'stunden' => $monatSummen['stunden'],
                'urlaub'  => $monatSummen['urlaub'],
                'krank'   => $monatSummen['krank'],
            ],
        ];

        // Trenner-Zeile
        $tage[] = ['trenner' => true];

        $monatSummen = ['stunden' => 0.0, 'urlaub' => 0, 'krank' => 0];
        $aktuellerMonat = (int)$naechsterTag->format('n');
    }
}
?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Monatsübersicht</title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>
  <h1>Eigene Monatsübersicht – Quartal <?= h((string)$quartal) ?>/<?= h((string)$jahr) ?></h1>

  <nav class="menu">
    <a class="btn" href="bb_route.php">Zurück zum Hauptmenü</a>
    <?= modus_navigation() ?>
  </nav>

  <table class="monatsuebersicht">
    <thead>
      <tr>
        <th>Datum</th>
        <th>Stunden</th>
        <th>Urlaubstage</th>
        <th>Krank</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($tage as $t): ?>
        <?php if (isset($t['trenner']) && $t['trenner'] === true): ?>
          <tr class="row-separator">
            <td colspan="4">&nbsp;</td>
          </tr>
        <?php elseif (isset($t['monat_summe'])): ?>
          <tr class="row-summe-monat">
            <td>Summe Monat <?= h(sprintf('%02d.%04d', $t['monat_summe']['monat'], $t['monat_summe']['jahr'])) ?></td>
            <td><?= h(number_format($t['monat_summe']['stunden'], 2, ',', '.')) ?></td>
            <td><?= h((string)$t['monat_summe']['urlaub']) ?></td>
            <td><?= h((string)$t['monat_summe']['krank']) ?></td>
          </tr>
        <?php else: ?>
          <?php
            $werte  = $t['werte'];
            $klasse = $t['ist_feiertag_od'] ? 'tag-feiertag' : 'tag-werktag';
          ?>
          <tr class="<?= $klasse ?>">
            <td><?= h($t['datum_anzeige']) ?></td>
            <td><?= h(number_format($werte['stunden'], 2, ',', '.')) ?></td>
            <td><?= h((string)$werte['urlaub']) ?></td>
            <td><?= h((string)$werte['krank']) ?></td>
          </tr>
        <?php endif; ?>
    <?php endforeach; ?>
      <tr class="row-summe-quartal">
        <td>Summe Quartal <?= h((string)$quartal) ?>/<?= h((string)$jahr) ?></td>
        <td><?= h(number_format($quartalSummen['stunden'], 2, ',', '.')) ?></td>
        <td><?= h((string)$quartalSummen['urlaub']) ?></td>
        <td><?= h((string)$quartalSummen['krank']) ?></td>
      </tr>
    </tbody>
  </table>
</body>
</html>
