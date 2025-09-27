<?php
require_once 'auth.php';
rolle_erforderlich(ROLLE_MITARBEITER);
modus_aus_url_setzen();
require_once 'db.php';
$benutzer = aktueller_benutzer();

// selber geschrieben mit stackoverflowvorlage
// nur Einträge für vergangene Tage möglich.
$heute = new DateTimeImmutable('today');
$maxDate = $heute->format('Y-m-d');
$msg = null; $err = null;

// Arbeitsorte laden (FK erforderlich)
$orte = $pdo->query("SELECT ort_id, bezeichnung FROM arbeitsorte ORDER BY ort_id")->fetchAll(PDO::FETCH_ASSOC);

// Helper
function ensure_stundenzettel(PDO $pdo, int $benutzerId, int $monat, int $jahr): int {
  $sel = $pdo->prepare("SELECT stundenzettel_id FROM stundenzettel WHERE benutzer_id=:b AND monat=:m AND jahr=:j LIMIT 1");
  $sel->execute([':b'=>$benutzerId, ':m'=>$monat, ':j'=>$jahr]);
  $id = $sel->fetchColumn();
  if ($id) return (int)$id;
  $ins = $pdo->prepare("INSERT INTO stundenzettel (benutzer_id, monat, jahr) VALUES (:b,:m,:j)");
  $ins->execute([':b'=>$benutzerId, ':m'=>$monat, ':j'=>$jahr]);
  return (int)$pdo->lastInsertId();
}

function recalc_ist(PDO $pdo, int $stundenzettelId): void {
  $upd = $pdo->prepare("
    UPDATE stundenzettel s
    JOIN (SELECT stundenzettel_id, COALESCE(SUM(stunden),0) AS ist
          FROM zeiteintraege WHERE stundenzettel_id=:id) x
      ON s.stundenzettel_id = x.stundenzettel_id
    SET s.ist_stunden = x.ist
    WHERE s.stundenzettel_id = :id
  ");
  $upd->execute([':id'=>$stundenzettelId]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $datum = $_POST['datum'] ?? '';
  $start = trim($_POST['start'] ?? '');
  $ende  = trim($_POST['ende'] ?? '');
  $ortId = isset($_POST['ort_id']) ? (int)$_POST['ort_id'] : 0;
  $status = $_POST['status'] ?? 'none'; // none|krank|urlaub

  try {
    // Validierung Datum
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datum)) throw new RuntimeException('Ungültiges Datum.');
    if ($datum > $maxDate) throw new RuntimeException('Zukunftsdatum nicht erlaubt.');

    // Dauer berechnen, falls kein Status
    $stunden = 0.0; $bemerkung = null;
    if ($status === 'krank' || $status === 'urlaub') {
      $stunden = 0.0;
      $bemerkung = $status;
      // bei Status wird Ort ignoriert, sichere Fallback 0
      $ortId = 0;
    } else {
      // Zeiten prüfen
      if (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $ende)) {
        throw new RuntimeException('Bitte Start- und Endzeit im Format HH:MM angeben.');
      }
      [$sh,$sm] = array_map('intval', explode(':',$start));
      [$eh,$em] = array_map('intval', explode(':',$ende));
      $startMin = $sh*60 + $sm; $endeMin = $eh*60 + $em;
      if ($endeMin <= $startMin) throw new RuntimeException('Endzeit muss nach Startzeit liegen.');
      $stunden = round(($endeMin - $startMin) / 60, 2);
      if ($stunden > 24) throw new RuntimeException('Zu viele Stunden für einen Tag.');
      $bemerkung = trim($_POST['bemerkung'] ?? '') ?: null;
      // Ort prüfen
      $validOrt = array_column($orte, 'ort_id');
      if (!in_array($ortId, $validOrt, true)) throw new RuntimeException('Ungültiger Arbeitsort.');
    }

    // Stundenzettel sicherstellen
    $d = new DateTimeImmutable($datum);
    $szId = ensure_stundenzettel($pdo, (int)$benutzer['id'], (int)$d->format('n'), (int)$d->format('Y'));
    $tag = (int)$d->format('j');

    // Upsert Zeiteintrag
    $stmt = $pdo->prepare("
      INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
      VALUES (:sid,:tag,:ort,:std,:bem)
      ON DUPLICATE KEY UPDATE ort_id=VALUES(ort_id), stunden=VALUES(stunden), bemerkung=VALUES(bemerkung)
    ");
    $stmt->execute([
      ':sid'=>$szId, ':tag'=>$tag, ':ort'=>$ortId, ':std'=>$stunden, ':bem'=>$bemerkung
    ]);

    // Ist-Stunden neu berechnen
    recalc_ist($pdo, $szId);

    $msg = 'Eintrag gespeichert.';
  } catch (Throwable $e) {
    $err = $e->getMessage();
  }
}
?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Erfassung</title>
  <link rel="stylesheet" href="aussehen.css">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    .menu { margin: 8px 0 16px }
    .form { display:grid; gap:14px; max-width:420px }
    .row { display:grid; grid-template-columns: 1fr 1fr; gap:12px }
    .status { display:flex; gap:16px; align-items:center }
    .note  { color: var(--text-gedämpft) }
    .alert-ok { padding:10px 12px; border:1px solid var(--rahmen); border-radius:var(--radius); background:rgba(16,185,129,.12) }
    .alert-err{ padding:10px 12px; border:1px solid var(--rahmen); border-radius:var(--radius); background:rgba(239,68,68,.12) }
    @media (max-width:480px){ .row{grid-template-columns:1fr} }
  </style>
</head>
<body>
  <h1>Erfassung</h1>
  <nav class="menu">
    <a class="btn" href="route.php">Zurück zum Hauptmenü</a>
    <?= modus_navigation() ?>
  </nav>

  <?php if ($msg): ?><p class="alert-ok"><?= htmlspecialchars($msg) ?></p><?php endif; ?>
  <?php if ($err): ?><p class="alert-err"><?= htmlspecialchars($err) ?></p><?php endif; ?>

  <form class="form" method="post" autocomplete="off">
    <div class="field">
      <label for="datum">Tag</label>
      <input id="datum" name="datum" type="date" max="<?= $maxDate ?>" value="<?= htmlspecialchars($_POST['datum'] ?? $maxDate) ?>" required>
      <small class="note">Nur vergangene Tage erlaubt.</small>
    </div>

    <div class="status">
      <label><input type="radio" name="status" value="none" <?= (!isset($_POST['status']) || $_POST['status']==='none')?'checked':''; ?>> Normal</label>
      <label><input type="radio" name="status" value="krank" <?= (($_POST['status'] ?? '')==='krank')?'checked':''; ?>> Krank</label>
      <label><input type="radio" name="status" value="urlaub" <?= (($_POST['status'] ?? '')==='urlaub')?'checked':''; ?>> Urlaub</label>
    </div>

    <div id="zeitBlock">
      <div class="row">
        <div class="field">
          <label for="start">Arbeitsbeginn</label>
          <input id="start" name="start" type="time" value="<?= htmlspecialchars($_POST['start'] ?? '') ?>">
        </div>
        <div class="field">
          <label for="ende">Arbeitsende</label>
          <input id="ende" name="ende" type="time" value="<?= htmlspecialchars($_POST['ende'] ?? '') ?>">
        </div>
      </div>

      <div class="field">
        <label for="ort_id">Arbeitsort</label>
        <select id="ort_id" name="ort_id">
          <?php foreach ($orte as $o): ?>
            <option value="<?= (int)$o['ort_id'] ?>" <?= (isset($_POST['ort_id']) && (int)$_POST['ort_id']===(int)$o['ort_id'])?'selected':''; ?>>
              <?= htmlspecialchars($o['bezeichnung']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label for="bemerkung">Bemerkung (optional)</label>
        <input id="bemerkung" name="bemerkung" type="text" maxlength="500" placeholder="z. B. Projekt X">
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
      const radios = document.querySelectorAll('input[name="status"]');
      const zeitBlock = document.getElementById('zeitBlock');
      const inputs = zeitBlock.querySelectorAll('input, select');
      function sync(){
        const v = document.querySelector('input[name="status"]:checked')?.value || 'none';
        const disable = (v === 'krank' || v === 'urlaub');
        inputs.forEach(el => {
          if (el.id === 'bemerkung') return; // Bemerkung bleibt frei
          el.disabled = disable;
        });
        if(disable){
          document.getElementById('start').value='';
          document.getElementById('ende').value='';
        }
      }
      radios.forEach(r => r.addEventListener('change', sync));
      sync();
    })();
  </script>
</body>
</html>