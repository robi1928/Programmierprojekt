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

$benutzer   = aktueller_benutzer();
$benutzerId = CBenutzerHelper::ermittleIdAusKontext($benutzer);
if ($benutzerId === null) {
    die('Fehler: Benutzer-ID nicht im Kontext gefunden. Bitte neu anmelden.');
}

$heute = new DateTimeImmutable('today');
$model = CStundenzettelRepository::monatsuebersichtQuartal($pdo, $benutzerId, $heute);

$jahr          = $model['jahr'];
$quartal       = $model['quartal'];
$tage          = $model['tage'];
$quartalSummen = $model['quartalSummen'];

$monatsDaten = [];
foreach ($tage as $t) {
    if (isset($t['trenner']) && $t['trenner'] === true) {
        continue;
    }
    if (isset($t['monat_summe'])) {
        $m = (int)$t['monat_summe']['monat'];
        $monatsDaten[$m]['summe'] = $t['monat_summe'];
        continue;
    }
    if (isset($t['monat'])) {
        $m = (int)$t['monat'];
        $monatsDaten[$m]['tage'][] = $t;
    }
}

$quartalKrankSumme = 0;

foreach ($monatsDaten as $monatNummer => &$daten) {
    // Krankheitstage für diesen Monat holen
    $krankProTag = CZeiteintragRepository::ermittleKrankheitstagefuerMonatsuebersicht(
        $pdo,
        $benutzerId,
        $monatNummer,
        $jahr
    );

    // Für später im Template speichern
    $daten['krankProTag'] = $krankProTag;

    // Monatssumme = Anzahl der Tage mit krank = 1
    $monatKrankSumme = array_sum($krankProTag);

    // Falls noch keine Summe existiert, Grundgerüst anlegen
    if (!isset($daten['summe'])) {
        $daten['summe'] = [
            'stunden' => 0,
            'urlaub'  => 0,
            'krank'   => 0,
            'monat'   => $monatNummer,
            'jahr'    => $jahr,
        ];
    }

    // Krank-Summe im Monat hinterlegen
    $daten['summe']['krank'] = $monatKrankSumme;

    // Quartalssumme aufaddieren
    $quartalKrankSumme += $monatKrankSumme;
}
unset($daten);
$quartalSummen['krank'] = $quartalKrankSumme;

$monatsnamen = [
    1 => 'Januar', 2 => 'Februar', 3 => 'März',
    4 => 'April', 5 => 'Mai', 6 => 'Juni',
    7 => 'Juli', 8 => 'August', 9 => 'September',
    10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
];

$aktuellerMonatJetzt = (int)$heute->format('n');
$heuteSql            = $heute->format('Y-m-d');

?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Monatsübersicht</title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>
<header class="page-header">
  <h1>Monatsübersicht – Quartal <?= h((string)$quartal) ?>/<?= h((string)$jahr) ?></h1>
  <nav class="menu">
    <a class="btn" href="bb_route.php">Zurück zum Hauptmenü</a>
    <?= modus_navigation() ?>
  </nav>
</header>

<main>
  <section class="quartal-block">
    <h2>Quartalsübersicht</h2>
    <table class="monatsuebersicht monatsuebersicht--compact">
      <thead>
        <tr>
          <th>Quartal/Jahr</th>
          <th>Stunden</th>
          <th>Urlaubstage</th>
          <th>Krank</th>
        </tr>
      </thead>
      <tbody>
        <tr class="row-summe-quartal">
          <td>Q<?= h((string)$quartal) ?>/<?= h((string)$jahr) ?></td>
          <td><?= h(number_format($quartalSummen['stunden'], 2, ',', '.')) ?></td>
          <td><?= h((string)$quartalSummen['urlaub']) ?></td>
          <td><?= h((string)$quartalSummen['krank']) ?></td>
        </tr>
      </tbody>
    </table>
  </section>

  <section class="months">
    <h2>Monate im Quartal</h2>

    <?php foreach ($monatsDaten as $monatNummer => $daten): ?>
      <?php
        $summe = $daten['summe'] ?? [
            'stunden' => 0,
            'urlaub'  => 0,
            'krank'   => 0,
            'monat'   => $monatNummer,
            'jahr'    => $jahr,
        ];
        $monatName = $monatsnamen[$monatNummer] ?? ('Monat ' . $monatNummer);
        $openAttr  = ($monatNummer === $aktuellerMonatJetzt) ? ' open' : '';
        $krankProTag = CZeiteintragRepository::ermittleKrankheitstagefuerMonatsuebersicht(
            $pdo,
            $benutzerId,
            $monatNummer,
            $jahr
        );
    ?>
    <details class="month"<?= $openAttr ?>>
        <summary class="month__header">
          <span class="month__title">
            <?= h($monatName) ?> <?= h((string)$jahr) ?>
          </span>
          <span class="month__summary-values">
            <span><?= h(number_format($summe['stunden'], 2, ',', '.')) ?> h</span>
            <span><?= h((string)$summe['urlaub']) ?> Urlaub</span>
            <span><?= h((string)$summe['krank']) ?> Krank</span>
          </span>
        </summary>

        <div class="month__body">
          <?php if (empty($daten['tage'])): ?>
            <p class="note">Keine Einträge in diesem Monat.</p>
          <?php else: ?>
            <table class="monatsuebersicht">
              <caption>Einträge im <?= h($monatName) ?> <?= h((string)$jahr) ?></caption>
              <thead>
                <tr>
                  <th>Datum</th>
                  <th>Stunden</th>
                  <th>Urlaub</th>
                  <th>Krank</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($daten['tage'] as $t): ?>
                <?php
                  $werte       = $t['werte'];

                  // Flag aus DB-Mapping ergänzen (0 oder 1)
                  $werte['krank'] = $krankProTag[$t['datum_sql']] ?? 0;

                  $istFeiertag = $t['ist_feiertag_od'];
                  $istHeute    = ($t['datum_sql'] === $heuteSql);
                  $rowClasses  = [];
                  if ($istFeiertag) { $rowClasses[] = 'tag-feiertag'; }
                  else              { $rowClasses[] = 'tag-werktag'; }
                  if ($istHeute)    { $rowClasses[] = 'tag-heute'; }
                  $rowClass = implode(' ', $rowClasses);
                ?>
                <tr class="<?= h($rowClass) ?>">
                  <td>
                    <time datetime="<?= h($t['datum_sql']) ?>">
                      <?= h($t['datum_anzeige']) ?>
                    </time>
                    <?php if ($istHeute): ?>
                      <span class="tag-heute-label">(heute)</span>
                    <?php endif; ?>
                  </td>
                  <td><?= h(number_format($werte['stunden'], 2, ',', '.')) ?></td>
                  <td><?= h((string)$werte['urlaub']) ?></td>
                  <td><?= h((string)$werte['krank']) ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr class="row-summe-monat">
                  <th>Summe Monat</th>
                  <td><?= h(number_format($summe['stunden'], 2, ',', '.')) ?></td>
                  <td><?= h((string)$summe['urlaub']) ?></td>
                  <td><?= h((string)$summe['krank']) ?></td>
                </tr>
              </tfoot>
            </table>
          <?php endif; ?>
        </div>
      </details>
    <?php endforeach; ?>
  </section>
</main>
</body>
</html>
