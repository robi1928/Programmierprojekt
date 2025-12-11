<?php
require_once 'bb_auth.php';
rolle_erforderlich(ROLLE_TEAMLEITUNG);
modus_aus_url_setzen();

require_once 'bb_db.php';

// Benutzerliste für Dropdown laden
$benutzer_liste = [];
try {
    $stmt = $pdo->prepare("
        SELECT benutzer_id, vorname, nachname, email
        FROM benutzer
        ORDER BY nachname, vorname
    ");
    $stmt->execute();
    $benutzer_liste = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    die("Fehler beim Laden der Benutzerdaten.");
}

// ID aus GET oder POST
$ausgewaehlte_id = null;
if (isset($_POST['id']) && ctype_digit((string)$_POST['id'])) {
    $ausgewaehlte_id = (int)$_POST['id'];
} elseif (isset($_GET['id']) && ctype_digit((string)$_GET['id'])) {
    $ausgewaehlte_id = (int)$_GET['id'];
}

$fehler = [];
$erfolg = "";

// Felder vorbelegen
$vorname = "";
$nachname = "";
$email = "";
$rollen_id = "";
$wochenstunden = "";
$urlaubstage = "";
$einstellungsdatum = "";

// Falls ein Benutzer ausgewählt ist und es KEIN POST-Save ist, aus DB laden
if ($ausgewaehlte_id && !($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save']))) {
    $stmt = $pdo->prepare("SELECT * FROM benutzer WHERE benutzer_id = :id");
    $stmt->execute([':id' => $ausgewaehlte_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $vorname = $row['vorname'];
        $nachname = $row['nachname'];
        $email = $row['email'];
        $rollen_id = (string)$row['rollen_id'];
        $wochenstunden = number_format((float)$row['wochenstunden'], 1, '.', '');
        // Datum im Format YYYY-MM-DD für <input type="date">
        $einstellungsdatum = date('Y-m-d', strtotime($row['einstellungsdatum']));

        // Urlaubstage aus Urlaubskonto (Jahr des Einstellungsdatums)
        $jahr = (int)date('Y');
        $stmtU = $pdo->prepare("
            SELECT anspruch_tage
            FROM urlaubskonten
            WHERE benutzer_id = :bid AND jahr = :jahr
        ");
        $stmtU->execute([':bid' => $ausgewaehlte_id, ':jahr' => $jahr]);
        $ur = $stmtU->fetch(PDO::FETCH_ASSOC);
        if ($ur) {
            $urlaubstage = (string)$ur['anspruch_tage'];
        } else {
            $urlaubstage = "";
        }
    } else {
        $fehler[] = "Benutzer nicht gefunden.";
        $ausgewaehlte_id = null;
    }
}

// POST: Speichern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {

    // ID prüfen
    $id_raw = $_POST['id'] ?? '';
    if ($id_raw === '' || !ctype_digit((string)$id_raw)) {
        $fehler[] = "Ungültige Benutzer-ID.";
    } else {
        $ausgewaehlte_id = (int)$id_raw;
    }

    // Eingaben holen (für Wiederbefüllung bei Fehlern)
    $vorname           = trim($_POST['vorname'] ?? '');
    $nachname          = trim($_POST['nachname'] ?? '');
    $email             = trim($_POST['email'] ?? '');
    $rollen_id         = trim($_POST['rolle'] ?? '');
    $wochenstunden_raw = trim($_POST['wochenstunden'] ?? '');
    $urlaubstage_raw   = trim($_POST['urlaubstage'] ?? '');
    $einstellungsdatum = trim($_POST['einstellungsdatum'] ?? '');

    // Name prüfen
    if ($nachname === '' || !preg_match("/^[A-Za-zÄÖÜäöüß' -]+$/u", $nachname)) {
        $fehler[] = "Name darf nur Buchstaben und Bindestrich enthalten.";
    }

    // Vorname prüfen
    if ($vorname === '' || !preg_match("/^[A-Za-zÄÖÜäöüß' -]+$/u", $vorname)) {
        $fehler[] = "Vorname darf nur Buchstaben und Bindestrich enthalten.";
    }

    // E-Mail-Adresse prüfen
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fehler[] = "Ungültige E-Mail-Adresse.";
    }

    // Rollen-ID 1–3
    if (!in_array($rollen_id, ['1','2','3'], true)) {
        $fehler[] = "Ungültige Rolle.";
    }

    // Wochenstunden: 0 < x < 42, max. 1 Nachkommastelle, Komma/Punkt
    $ws_norm = str_replace(',', '.', $wochenstunden_raw);

    if ($ws_norm === '' || !is_numeric($ws_norm)) {
        $fehler[] = "Wochenstunden müssen eine Zahl sein.";
    } else {
        // Auf eine Nachkommastelle runden, egal was eingegeben wurde
        $wochenstunden = round((float)$ws_norm, 1);

        if ($wochenstunden <= 0 || $wochenstunden >= 42) {
            $fehler[] = "Wochenstunden müssen zwischen 0 und 42 liegen.";
        }
    }

    // Urlaubstage: 1–30
    $urlaub_norm = str_replace(',', '.', $urlaubstage_raw);

    if ($urlaub_norm === '' || !is_numeric($urlaub_norm)) {
        $fehler[] = "Urlaubstage müssen angegeben werden.";
    } else {
        $urlaubstage = (float)$urlaub_norm;
        if ($urlaubstage < 1 || $urlaubstage > 30) {
            $fehler[] = "Urlaubstage müssen zwischen 1 und 30 liegen.";
        }
    }

    // Einstellungsdatum prüfen
    $minDate = strtotime("2000-01-01");
    $maxDate = strtotime("+30 days");
    $ts = strtotime($einstellungsdatum);

    if ($ts === false) {
        $fehler[] = "Ungültiges Einstellungsdatum.";
    } elseif ($ts < $minDate || $ts > $maxDate) {
        $fehler[] = "Einstellungsdatum muss zwischen 01.01.2000 und in 30 Tagen liegen.";
    }

    // Wenn alles ok, DB-Update
    if (empty($fehler)) {
        try {
            if (method_exists($pdo, 'beginTransaction')) {
                $pdo->beginTransaction();
            }

            $einstellungsdatum_sql = date('Y-m-d H:i:s', $ts);

            // 1. Benutzer aktualisieren
            $stmt = $pdo->prepare("
                UPDATE benutzer
                SET vorname           = :vorname,
                    nachname          = :nachname,
                    email             = :email,
                    rollen_id         = :rollen_id,
                    wochenstunden     = :wochenstunden,
                    einstellungsdatum = :einstellungsdatum
                WHERE benutzer_id     = :id
            ");
            $stmt->execute([
                ':vorname'           => $vorname,
                ':nachname'          => $nachname,
                ':email'             => $email,
                ':rollen_id'         => (int)$rollen_id,
                ':wochenstunden'     => $wochenstunden,
                ':einstellungsdatum' => $einstellungsdatum_sql,
                ':id'                => $ausgewaehlte_id,
            ]);

            // Unabhängig von rowCount(): Urlaubskonto anlegen/aktualisieren
            $jahr = (int)date('Y');

            $stmtU = $pdo->prepare("
                INSERT INTO urlaubskonten (benutzer_id, jahr, anspruch_tage, genutzt_tage)
                VALUES (:bid, :jahr, :anspruch, 0.0)
                ON DUPLICATE KEY UPDATE anspruch_tage = VALUES(anspruch_tage)
            ");
            $stmtU->execute([
                ':bid'      => $ausgewaehlte_id,
                ':jahr'     => $jahr,
                ':anspruch' => $urlaubstage,
            ]);

            if ($pdo->inTransaction()) {
                $pdo->commit();
            }

            $erfolg = "Benutzerdaten wurden erfolgreich aktualisiert.";

        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $fehler[] = "Fehler beim Speichern: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }
}
?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Nutzer aktualisieren</title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>
  <h1>Nutzer aktualisieren</h1>
  <nav class="menu">
    <a class="btn" href="bb_route.php">Zurück zum Hauptmenü</a>
    <?= modus_navigation() ?>
  </nav>
 </header>

  <?php if (!empty($fehler)): ?>
      <?php foreach ($fehler as $msg): ?>
          <p style="color:red;"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endforeach; ?>
  <?php elseif (!empty($erfolg)): ?>
      <p style="color:green;"><?= htmlspecialchars($erfolg, ENT_QUOTES, 'UTF-8') ?></p>
  <?php endif; ?>

  <!-- Dropdown zur Auswahl des Benutzers -->
    <form class="form" method="get" action="dd_nutzer_aktualisieren_teamleitung.php">
        <div class="field">
            <label for="benutzer_select">Wähle einen Benutzer:</label>
            <select id="benutzer_select" name="id" onchange="this.form.submit()">
                <option value="">-- Bitte auswählen --</option>
                <?php foreach ($benutzer_liste as $row): ?>
                <option value="<?= htmlspecialchars($row['benutzer_id']) ?>"
                    <?= ($ausgewaehlte_id == $row['benutzer_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($row['nachname'] . ", " . $row['vorname'] . " (" . $row['email'] . ")") ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </form>

  <?php if ($ausgewaehlte_id): ?>
  <div class="card card-narrow">
    <form action="dd_nutzer_aktualisieren_teamleitung.php" method="post" class="form">
      <input type="hidden" name="id" value="<?= htmlspecialchars($ausgewaehlte_id) ?>">

      <div class="field">
        <label for="vorname">Vorname</label>
        <input type="text" id="vorname" name="vorname" required
               value="<?= htmlspecialchars($vorname, ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="field">
        <label for="nachname">Nachname</label>
        <input type="text" id="nachname" name="nachname" required
               value="<?= htmlspecialchars($nachname, ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="field">
        <label for="email">E-Mail</label>
        <input type="email" id="email" name="email" required
               value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="field">
        <label for="rolle">Rolle</label>
        <select id="rolle" name="rolle" required>
          <option value="">-- Bitte auswählen --</option>
          <option value="1" <?= ($rollen_id === '1') ? 'selected' : '' ?>>Mitarbeiter</option>
          <option value="2" <?= ($rollen_id === '2') ? 'selected' : '' ?>>Teamleitung</option>
          <option value="3" <?= ($rollen_id === '3') ? 'selected' : '' ?>>Projektleitung</option>
        </select>
      </div>

      <div class="field">
        <label for="wochenstunden">Regelmäßige Wochenstunden</label>
        <input type="number" step="0.1" id="wochenstunden" name="wochenstunden"
               inputmode="decimal" required
               value="<?= htmlspecialchars($wochenstunden, ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="field">
        <label for="urlaubstage">Urlaubstage</label>
        <input type="number" id="urlaubstage" name="urlaubstage" min="1" max="30" required
               value="<?= htmlspecialchars($urlaubstage, ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="field">
        <label for="einstellungsdatum">Einstellungsdatum</label>
        <input type="date" id="einstellungsdatum" name="einstellungsdatum" required
               value="<?= htmlspecialchars($einstellungsdatum, ENT_QUOTES, 'UTF-8') ?>">
      </div>

      <div class="actions-inline">
        <button class="btn primary" type="submit" name="save" value="1">Speichern</button>
        <button class="btn" type="button" onclick="window.history.back()">Zurück</button>
      </div>
    </form>
  </div>
  <?php endif; ?>

</body>
</html>
