<?php
require_once __DIR__ . '/bb_auth.php';

rolle_erforderlich(ROLLE_MITARBEITER);
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

// selber geschrieben mit stackoverflowvorlage
// nur Einträge für vergangene Tage möglich.
$heute = new DateTimeImmutable('today');
$maxDate = $heute->format('Y-m-d');
$msg = null; $err = null;

// Arbeitsorte laden
$orte = CArbeitsortRepository::alle($pdo);

  // prüft, ob das http request verfahren durch ist, also Formular versendet wurde
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if ($benutzerId === null) {
            throw new RuntimeException('Benutzerkontext fehlt. Bitte neu anmelden.');
        }
        CErfassungVerarbeitung::erfasse($pdo, $_POST, $orte, $benutzerId, $maxDate);
        $msg = 'Eintrag gespeichert.';
    } catch (Throwable $e) {
        $err = $e->getMessage();
    }
}

// selber geschrieben, mit GTP verbessert
?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Erfassung</title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>
  <h1>Erfassung</h1>
  <nav class="menu">
    <a class="btn" href="bb_route.php">Zurück zum Hauptmenü</a>
    <?= modus_navigation() ?>
  </nav>
<!-- darstellung von Fehler& Erfolgsmeldungen.-->
  <?php if ($msg): ?><p class="alert-ok"><?= h($msg) ?></p><?php endif; ?>
  <?php if ($err): ?><p class="alert-err"><?= h($err) ?></p><?php endif; ?>

  <form class="form" method="post" autocomplete="off">
    <div class="field">
      <label for="datum">Tag</label>
      <input id="datum" name="datum" type="date"
            max="<?= $maxDate ?>"
            value="<?= h($_POST['datum'] ?? $maxDate) ?>" required>
      <small class="note" id="datumHint">
        Arbeitszeit &amp; Krankheit nur rückwirkend (inkl. heute). Urlaub auch im Voraus möglich.
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
          <?php
          $selOrt = isset($_POST['ort_id']) ? (int)$_POST['ort_id'] : null;
          foreach ($orte as $o):
              $id  = (int)$o['ort_id'];
              $txt = $o['bezeichnung'] ?? '';
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

  <!-- selber versucht, mit GTP verbessert. Hab hier ausnahmsweise JavaScript genutzt.-->
  <!-- braucht es nicht zwingend, aber ist schöner. Deaktiviert einige Felder wenn sinnvoll. Für die Server Logik egal-->
<script>
(function(){
  const radios = document.querySelectorAll('input[name="status"]');
  const zeitBlock = document.getElementById('zeitBlock');
  const inputs = zeitBlock.querySelectorAll('input, select');
  const dateInput = document.getElementById('datum');
  const maxDate = dateInput.max;              // <- hier: nicht dataset.max
  const hint = document.getElementById('datumHint');

  function sync(){
    const v = document.querySelector('input[name="status"]:checked')?.value || 'none';
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

    // Datum: nur für Urlaub Zukunft erlauben
    if (v === 'urlaub') {
      dateInput.removeAttribute('max');
      if (hint) {
        hint.textContent = 'Arbeitszeit & Krankheit nur rückwirkend (inkl. heute). Urlaub beliebig (auch in Zukunft).';
      }
    } else {
      dateInput.max = maxDate;
      if (hint) {
        hint.textContent = 'Arbeitszeit & Krankheit nur rückwirkend (inkl. heute). Urlaub auch im Voraus möglich.';
      }
      if (dateInput.value && dateInput.value > maxDate) {
        dateInput.value = maxDate;
      }
    }
  }

  radios.forEach(r => r.addEventListener('change', sync));
  sync();
})();
</script>
</body>
</html>