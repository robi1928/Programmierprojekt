<?php
require_once __DIR__ . '/bb_auth.php';
rolle_erforderlich(ROLLE_TEAMLEITUNG);
modus_aus_url_setzen();

require_once __DIR__ . '/bb_db.php';
require_once __DIR__ . '/cc_benutzer.php';
require_once __DIR__ . '/cc_stundenzettel.php';
require_once __DIR__ . '/cc_zeiteintraege.php';
require_once __DIR__ . '/cc_urlaubskonten.php';
require_once __DIR__ . '/cc_urlaubsantraege.php';

// Aktueller Benutzer (für Fallback, falls keine Auswahl getroffen wurde)
$benutzer   = aktueller_benutzer();
$benutzerId = CBenutzerHelper::ermittleIdAusKontext($benutzer);
if ($benutzerId === null) {
    die('Fehler: Benutzer-ID nicht im Kontext gefunden. Bitte neu anmelden.');
}

// Datum für die Übersicht (heute)
$heute = new DateTimeImmutable('today');

// Alle Benutzer für das Auswahlfeld laden
$benutzer_liste = [];
try {
    $stmt = $pdo->prepare("SELECT benutzer_id, vorname, nachname, email FROM benutzer ORDER BY nachname, vorname");
    $stmt->execute();
    $benutzer_liste = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Fehler beim Laden der Benutzerdaten - trotzdem weiter versuchen, ggf. mit aktuellem User
    $benutzer_liste = [];
}

// Globales Quartalsaggregat (alle aktiven Benutzer)
$globalQuartalSummen = [
    'stunden' => 0.0,
    'urlaub'  => 0.0,
    'krank'   => 0.0,
];

// Wir nehmen das Quartal von "heute" als Bezug
foreach ($benutzer_liste as $row) {
    $id = (int)$row['benutzer_id'];

    // Optional: nur aktive Benutzer berücksichtigen → dafür brauchst du "aktiv" im SELECT oben
    // if (!(int)$row['aktiv']) { continue; }

$m = CStundenzettelRepository::monatsuebersichtQuartal($pdo, $id, $heute);

$quartalKrankMapUser = CZeiteintragRepository::ermittleKrankheitstagefuerQuartal(
    $pdo,
    $id,
    $m['jahr'],
    $m['quartal']
);
$quartalKrankSummeUser = array_sum($quartalKrankMapUser);

    $globalQuartalSummen['stunden'] += (float)$m['quartalSummen']['stunden'];
    $globalQuartalSummen['urlaub']  += (float)$m['quartalSummen']['urlaub'];
    $globalQuartalSummen['krank']   += (float)$quartalKrankSummeUser;
}


// Welche ID ist selektiert? (GET param "id")
$ausgewaehlte_id = isset($_GET['id']) && $_GET['id'] !== '' ? (int)$_GET['id'] : null;

// Falls keine Auswahl getroffen wurde, den aktuellen Benutzer als Default verwenden
if ($ausgewaehlte_id === null && $benutzerId !== null) {
    $ausgewaehlte_id = $benutzerId;
}

// Prüfen, ob der ausgewählte Benutzer existiert (falls eine ID vorhanden)
$gewaehlterBenutzerObj = null;
if ($ausgewaehlte_id) {
    $gewaehlterBenutzerObj = new CBenutzer($ausgewaehlte_id);
    if (!$gewaehlterBenutzerObj->Load()) {
        $gewaehlterBenutzerObj = null;
        if ($benutzerId !== null) {
            $ausgewaehlte_id = $benutzerId;
            $gewaehlterBenutzerObj = new CBenutzer($ausgewaehlte_id);
            $gewaehlterBenutzerObj->Load();
        } else {
            die('Benutzer nicht gefunden.');
        }
    }
}

$model = CStundenzettelRepository::monatsuebersichtQuartal($pdo, $ausgewaehlte_id, $heute);

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
        $ausgewaehlte_id,
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


// Benutzeranzeige für Überschrift
$gewaehlterName = $gewaehlterBenutzerObj
    ? ($gewaehlterBenutzerObj->GetVorname() . ' ' . $gewaehlterBenutzerObj->GetNachname() . ' ')
    : '–';
?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Arbeitszeit & Urlaub erfassen</title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>
  <h1>Monatsübersicht – Quartal <?= h((string)$quartal) ?>/<?= h((string)$jahr) ?></h1>
  <nav class="menu">
    <a class="btn" href="bb_route.php">Zurück zum Hauptmenü</a>
    <?= modus_navigation() ?>
  </nav>

<main>
  <section class="quartal-block">
    <h2>Quartalsübersicht – gesamt (alle Benutzer)</h2>
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
          <td>Q<?= h((string)$quartal) ?>/<?= h((string)$jahr) ?> – gesamt</td>
          <td><?= h(number_format($globalQuartalSummen['stunden'], 2, ',', '.')) ?></td>
          <td><?= h(number_format($globalQuartalSummen['urlaub'], 2, ',', '.')) ?></td>
          <td><?= h(number_format($globalQuartalSummen['krank'], 2, ',', '.')) ?></td>
        </tr>
      </tbody>
    </table>
  </section>

  <section class="user-select">
    <form method="get" action="dd_monatsuebersicht_teamleitung.php">
      <label for="benutzer_select">Benutzer anzeigen:</label>
      <select id="benutzer_select" name="id" onchange="this.form.submit()">
        <option value="">-- Bitte auswählen --</option>
        <?php foreach ($benutzer_liste as $row): ?>
          <?php $id = (int)$row['benutzer_id']; ?>
          <option value="<?= h((string)$id) ?>" <?= ($ausgewaehlte_id == $id) ? 'selected' : '' ?>>
            <?= h($row['nachname'] . ", " . $row['vorname'] . " (" . $row['email'] . ")") ?>
          </option>
        <?php endforeach; ?>
      </select>
      <noscript><button type="submit">Anzeigen</button></noscript>
    </form>
  </section>

  <section class="quartal-block">
    <h2>Quartalsübersicht – ausgewählter Benutzer</h2>
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
          <td><?= h(number_format($quartalSummen['urlaub'], 2, ',', '.')) ?></td>
          <td><?= h(number_format($quartalSummen['krank'], 2, ',', '.')) ?></td>
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
            $ausgewaehlte_id,
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
          <span><?= h(number_format($summe['urlaub'], 2, ',', '.')) ?> Urlaub</span>
          <span><?= h(number_format($summe['krank'], 2, ',', '.')) ?> Krank</span>
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
                  <td><?= h(number_format((float)$werte['urlaub'], 2, ',', '.')) ?></td>
                  <td><?= h(number_format((float)$werte['krank'], 2, ',', '.')) ?></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr class="row-summe-monat">
                  <th>Summe Monat</th>
                  <td><?= h(number_format($summe['stunden'], 2, ',', '.')) ?></td>
                  <td><?= h(number_format($summe['urlaub'], 2, ',', '.')) ?></td>
                  <td><?= h(number_format($summe['krank'], 2, ',', '.')) ?></td>
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
