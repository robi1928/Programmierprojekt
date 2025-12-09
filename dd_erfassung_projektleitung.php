<?php
require_once __DIR__ . '/bb_auth.php';

rolle_erforderlich(ROLLE_PROJEKTLEITUNG);
modus_aus_url_setzen();
require_once __DIR__ . '/bb_db.php';
require_once __DIR__ . '/cc_benutzer.php';
require_once __DIR__ . '/cc_arbeitsorte.php';
require_once __DIR__ . '/cc_stundenzettel.php';
require_once __DIR__ . '/cc_zeiteintraege.php';
require_once __DIR__ . '/cc_urlaubskonten.php';
require_once __DIR__ . '/cc_urlaubsantraege.php';
$benutzer = aktueller_benutzer();

// benutzer aus fachklasse holen. Ergibt das sinn, dass in cc benutzer die ID private ist?
$benutzerId = null;
if ($benutzer instanceof CBenutzer) {
    $benutzerId = (int)$benutzer->GetID();       // <- Getter nutzen
} elseif (is_array($benutzer)) {
    $benutzerId = isset($benutzer['benutzer_id']) ? (int)$benutzer['benutzer_id']
               : (isset($benutzer['id']) ? (int)$benutzer['id'] : null);
} elseif (is_object($benutzer)) {
    // Nur falls anderes Objekt mit öffentlichen Properties
    $benutzerId = isset($benutzer->benutzer_id) ? (int)$benutzer->benutzer_id
               : (isset($benutzer->id) ? (int)$benutzer->id : null);
}

// nur Einträge für vergangene Tage möglich (außer Urlaub)
$heute = new DateTimeImmutable('today');
$maxDate = $heute->format('Y-m-d');
$msg = null; $err = null;

// Arbeitsorte laden
$orte = CArbeitsortRepository::alle($pdo);

// prüft, ob das http request verfahren durch ist, also Formular versendet wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // setze PDO auf Exception-Modus, falls noch nicht gesetzt (hilft bei Debug)
        if ($pdo instanceof PDO) {
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }

        // Ziel-Benutzer: aus POST (wird durch Hidden-Input übergeben). Fallback auf GET.
        $zielBenutzerId = isset($_POST['id']) ? (int)$_POST['id'] : (isset($_GET['id']) ? (int)$_GET['id'] : null);
        if ($zielBenutzerId === null) {
            throw new RuntimeException('Bitte wählen Sie zuerst einen Benutzer aus.');
        }

        // Datum aus Formular (Pflichtfeld)
        if (empty($_POST['datum'])) {
            throw new RuntimeException('Bitte geben Sie ein Datum an.');
        }
        $datum = new DateTimeImmutable($_POST['datum']);

        // Monat/Jahr für Stundenzettel bestimmen
        $monat = (int)$datum->format('n');
        $jahr  = (int)$datum->format('Y');

        // Prüfen, ob für diesen Benutzer und Monat/Jahr bereits ein Stundenzettel existiert
        $stmt = $pdo->prepare('SELECT stundenzettel_id FROM stundenzettel WHERE benutzer_id = :bid AND monat = :monat AND jahr = :jahr LIMIT 1');
        $stmt->execute([':bid' => $zielBenutzerId, ':monat' => $monat, ':jahr' => $jahr]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            // Stundenzettel anlegen
            $stz = new CStundenzettel($zielBenutzerId, $monat, $jahr);

            // Wenn create false/Exception => catch weiter unten
            $erfolg = $stz->create($pdo);
            if (!$erfolg) {
                throw new RuntimeException('CErstellung des Stundenzettels gab false zurück.');
            }

            // Zusätzlicher DB-Check: sicherstellen, dass jetzt ein Datensatz existiert
            $stmt2 = $pdo->prepare('SELECT stundenzettel_id FROM stundenzettel WHERE benutzer_id = :bid AND monat = :monat AND jahr = :jahr LIMIT 1');
            $stmt2->execute([':bid' => $zielBenutzerId, ':monat' => $monat, ':jahr' => $jahr]);
            $row2 = $stmt2->fetch(PDO::FETCH_ASSOC);
            if (!$row2) {
                throw new RuntimeException('Stundenzettel wurde nicht in der Datenbank gefunden nach create().');
            }
            // Optional: $stundenzettelId zur weiteren Verwendung
            $stundenzettelId = (int)$row2['stundenzettel_id'];
        }

        // Jetzt die eigentliche Erfassung durchführen. CErfassungVerarbeitung::erfasse erwartet die Ziel-Benutzer-ID.
        CErfassungVerarbeitung::erfasse($pdo, $_POST, $orte, $zielBenutzerId, $maxDate);
        $msg = 'Eintrag gespeichert.';
    } catch (Throwable $e) {
        // Für Debug kurz die konkrete Meldung anzeigen (kann in Produktion abgeschwächt werden)
        $err = 'Fehler: ' . $e->getMessage();
    }
}

// Alle Benutzer für das Auswahlfeld laden
$benutzer_liste = [];
try {
    $statement = $pdo->prepare("SELECT benutzer_id, vorname, nachname, email FROM benutzer ORDER BY nachname, vorname");
    $statement->execute();
    $benutzer_liste = $statement->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Falls Laden fehlschlägt, Nutzerliste leer lassen und Fehler melden (nicht fatal)
    $benutzer_liste = [];
    $err = $err ?? 'Fehler beim Laden der Benutzerdaten.';
}

// Welche ID ist selektiert? (kommt per GET vom Auswahlformular)
$ausgewaehlte_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$Benutzer = null;
if ($ausgewaehlte_id) {
    // Nur zur Anzeige/Aufbau, nicht zwingend notwendig hier
    $Benutzer = new CBenutzer($ausgewaehlte_id);
    if (method_exists($Benutzer, 'Load')) {
        $Benutzer->Load();
    }
}
?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Arbeitszeit erfassen (Projektleitung)</title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>
  <h1>Arbeitszeit erfassen</h1>
  <nav class="menu">
    <a class="btn" href="bb_route.php">Zurück zum Hauptmenü</a>
    <?= modus_navigation() ?>
  </nav>

  <?php if ($msg): ?><p class="alert-ok"><?= h($msg) ?></p><?php endif; ?>
  <?php if ($err): ?><p class="alert-err"><?= h($err) ?></p><?php endif; ?>

  <!-- Auswahlformular für Benutzer (GET) -->
  <form id="userSelectForm" method="get" class="form-inline" style="margin-bottom:1rem;">
    <label for="benutzer_select">Wähle einen Benutzer:</label>
    <select id="benutzer_select" name="id" onchange="document.getElementById('userSelectForm').submit()">
      <option value="">-- Bitte auswählen --</option>
      <?php foreach ($benutzer_liste as $row): ?>
        <option value="<?= htmlspecialchars($row['benutzer_id']) ?>" <?= ($ausgewaehlte_id == $row['benutzer_id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($row['nachname'] . ", " . $row['vorname'] . " (" . $row['email'] . ")") ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <form class="form" method="post" autocomplete="off">
    <!-- Hidden: ausgewählte Benutzer-ID wird beim Absenden des Erfassungsformulars mitgeschickt -->
    <input type="hidden" name="id" value="<?= h($ausgewaehlte_id) ?>">

    <div class="field">
      <label for="datum">Tag</label>
      <input id="datum" name="datum" type="date"
            max="<?= $maxDate ?>"
            value="<?= h($_POST['datum'] ?? $maxDate) ?>" required>
      <small class="note" id="datumHint">
        Arbeitszeit &amp; Krankheit nur rückwirkend (inkl. heute). Urlaub auch im Voraus möglich.
      </small>
    </div>

    <!-- Enddatum für Urlaub (wird per JS ein-/ausgeblendet) -->
    <div class="field fade-block" id="datumEndeField">
      <label for="datum_ende">bis (inkl.)</label>
      <input id="datum_ende" name="datum_ende" type="date"
            max="<?= $maxDate ?>"
            value="<?= h($_POST['datum_ende'] ?? '') ?>">
      <small class="note" id="datumEndeHint">
        Nur bei Urlaub: letzter Tag des Urlaubs (inklusive).
      </small>
    </div>

    <div class="status">
      <label class="status-option arbeitstag">
        <input type="radio" name="status" value="none" <?= (($_POST['status'] ?? 'none') === 'none') ? 'checked' : '' ?>>
        Arbeitstag
      </label>
      <label class="status-option krank">
        <input type="radio" name="status" value="krank" <?= (($_POST['status'] ?? '') === 'krank') ? 'checked' : '' ?>>
        Krank
      </label>
      <label class="status-option urlaub">
        <input type="radio" name="status" value="urlaub" <?= (($_POST['status'] ?? '') === 'urlaub') ? 'checked' : '' ?>>
        Urlaub
      </label>
    </div>

    <div id="zeitBlock">
      <div class="row">
        <div class="field">
          <label for="start">Arbeitsbeginn</label>
          <input id="start" name="start" type="time" value="<?= h($_POST['start'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="ende">Arbeitsende</label>
          <input id="ende" name="ende" type="time" value="<?= h($_POST['ende'] ?? '') ?>">
        </div>
      </div>

      <div class="field">
        <label for="ort_id">Arbeitsort</label>
        <select id="ort_id" name="ort_id">
          <option value="">-- bitte wählen --</option>
          <?php
          $selOrt = isset($_POST['ort_id']) ? (int)$_POST['ort_id'] : null;
          foreach ($orte as $o):
              $id  = isset($o['ort_id']) ? (int)$o['ort_id'] : (isset($o['id']) ? (int)$o['id'] : 0);
              $txt = $o['bezeichnung'] ?? ($o['name'] ?? '');
          ?>
            <option value="<?= $id ?>" <?= ($selOrt !== null && $selOrt === $id) ? 'selected' : '' ?>>
              <?= h($txt) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="bemerkung">Bemerkung (optional)</label>
        <input id="bemerkung" name="bemerkung" type="text" maxlength="500"
               value="<?= h($_POST['bemerkung'] ?? '') ?>" placeholder="z. B. Details">
      </div>
    </div>

    <div>
      <button class="btn primary" type="submit">Speichern</button>
      <a class="btn" href="?">Zurücksetzen</a>
    </div>

    <small class="note">Bei „Krank“ oder „Urlaub“ werden Zeiten ignoriert und 0:00 h verbucht.</small>
  </form>

<script>
(function(){
  const radios      = document.querySelectorAll('input[name="status"]');
  const zeitBlock   = document.getElementById('zeitBlock');
  const inputs      = zeitBlock.querySelectorAll('input, select');
  const dateInput   = document.getElementById('datum');
  const maxDate     = dateInput ? dateInput.max : null;
  const hint        = document.getElementById('datumHint');

  const endDateField = document.getElementById('datumEndeField');
  const endDateInput = document.getElementById('datum_ende');

  function aktuellerStatus() {
    const checked = document.querySelector('input[name="status"]:checked');
    return checked ? checked.value : 'none';
  }

  function sync(){
    const v = aktuellerStatus();
    const disableZeit = (v === 'krank' || v === 'urlaub');

    // Zeitfelder (Start/Ende, Ort) sperren bei Krank/Urlaub
    inputs.forEach(el => {
      if (el.id === 'bemerkung') return;
      el.disabled = disableZeit;
    });
    if (disableZeit) {
      const s = document.getElementById('start');
      const e = document.getElementById('ende');
      if (s) s.value = '';
      if (e) e.value = '';
    }

    if (v === 'urlaub') {
      // Startdatum darf in der Zukunft liegen
      if (dateInput) {
        dateInput.removeAttribute('max');
      }

      if (hint) {
        hint.textContent =
          'Arbeitszeit & Krankheit nur rückwirkend (inkl. heute). Urlaub auch im Voraus möglich';
      }

      // Enddatum-Feld einblenden + Werte setzen
      if (endDateField && endDateInput) {
        endDateField.classList.add('is-visible');

        // Standard: wenn leer, auf Startdatum bzw. maxDate setzen
        if (!endDateInput.value) {
          endDateInput.value = (dateInput && dateInput.value) || maxDate || '';
        }

        // Ende darf nicht vor Start liegen
        endDateInput.removeAttribute('max');   // Urlaub beliebig
        endDateInput.min = (dateInput && dateInput.value) || '';
        if (endDateInput.value && endDateInput.min && endDateInput.value < endDateInput.min) {
          endDateInput.value = endDateInput.min;
        }
      }
    } else {
      // Startdatum: wieder auf maxDate (heute) begrenzen
      if (dateInput && maxDate) {
        dateInput.max = maxDate;
        if (dateInput.value && dateInput.value > maxDate) {
          dateInput.value = maxDate;
        }
      }

      if (hint) {
        hint.textContent =
          'Arbeitszeit & Krankheit nur rückwirkend (inkl. heute). Urlaub auch im Voraus möglich.';
      }

      // Enddatum-Feld ausblenden und zurücksetzen
      if (endDateField && endDateInput) {
        endDateField.classList.remove('is-visible');
        endDateInput.value = '';
        endDateInput.removeAttribute('min');
        if (maxDate) {
          endDateInput.max = maxDate;
        }
      }
    }
  }

  // Wenn Startdatum geändert wird und Urlaub aktiv ist, Enddatum nachziehen
  if (dateInput && endDateInput) {
    dateInput.addEventListener('change', function(){
      if (aktuellerStatus() === 'urlaub') {
        endDateInput.min = dateInput.value || '';
        if (endDateInput.value && endDateInput.min && endDateInput.value < endDateInput.min) {
          endDateInput.value = endDateInput.min;
        }
      }
    });
  }

  radios.forEach(r => r.addEventListener('change', sync));
  sync(); // Initialzustand anwenden
})();
</script>
</body>
</html>