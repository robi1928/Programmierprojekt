<?php
require_once 'bb_auth.php';
rolle_erforderlich(ROLLE_TEAMLEITUNG);
modus_aus_url_setzen();

 // Variablen vorbereiten
$fehler = [];
$erfolg = "";

// Nur prüfen, wenn Formular abgesendet wurde.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    // Eingaben abholen
$vorname           = trim($_POST['vorname'] ?? '');
$nachname          = trim($_POST['name'] ?? '');
$email             = trim($_POST['email'] ?? '');
$rollen_id         = trim($_POST['rolle'] ?? '');
$wochenstunden_raw = trim($_POST['wochenstunden'] ?? '');
$urlaubstage_raw   = trim($_POST['urlaubstage'] ?? '');
$einstellungsdatum = trim($_POST['einstellungsdatum'] ?? '');

// Name prüfen: nur Buchstaben
if (!preg_match("/^[A-Za-zÄÖÜäöüß' -]+$/u", $nachname)) {
    $fehler[] = "Name darf nur Buchstaben und Bindestrich enthalten.";
}

// Vorname prüfen: nur Buchstaben
if (!preg_match("/^[A-Za-zÄÖÜäöüß' -]+$/u", $vorname)) {
    $fehler[] = "Vorname darf nur Buchstaben und Bindestrich enthalten.";
}

//E-Mail-Adresse prüfen: gemäß Filter
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $fehler[] = "Ungültige E-Mail-Adresse.";
}

// Rollen-ID 1–3 (entspricht Tabelle rollen)
if (!in_array($rollen_id, ['1','2','3'], true)) {
    $fehler[] = "Ungültige Rolle.";
}

// Wochenstunden prüfen: 0 < x < 42, max. 1 Nachkommastelle, akzeptiert Komma oder Punkt
    $ws_norm = str_replace(',', '.', $wochenstunden_raw);
if (!preg_match("/^\d+(\.\d)?$/", $ws_norm)) {
    $fehler[] = "Wochenstunden dürfen maximal eine Nachkommastelle haben.";
}
$wochenstunden = (float)$ws_norm;
if ($wochenstunden <= 0 || $wochenstunden >= 42) {
    $fehler[] = "Wochenstunden müssen zwischen 0 und 42 liegen.";
}

// Urlaubstage prüfen: 1–30
if ($urlaubstage_raw === '' || !is_numeric($urlaubstage_raw)) {
    $fehler[] = "Urlaubstage müssen angegeben werden.";
} else {
    $urlaubstage = (float)$urlaubstage_raw;
    if ($urlaubstage < 1 || $urlaubstage > 30) {
        $fehler[] = "Urlaubstage müssen zwischen 1 und 30 liegen.";
    }
}

// Einstellungsdatum prüfen: 01.01.2000 < Datum < heute + 30 Tage
$minDate = strtotime("2000-01-01");
$maxDate = strtotime("+30 days");
$ts = strtotime($einstellungsdatum);


if ($ts === false) {
    $fehler[] = "Ungültiges Einstellungsdatum.";
} elseif ($ts < $minDate || $ts > $maxDate) {
    $fehler[] = "Einstellungsdatum muss zwischen 01.01.2000 und in 30 Tagen liegen.";
}

// In DB speichern (angepasst)
if (empty($fehler)) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO benutzer
                (vorname, nachname, email, rollen_id, Wochenstunden_raw, Urlaubstage, Einstellungsdatum)
            VALUES
                (:vorname, :nachname, :email, :rollen_id, :wochenstunden_raw, :urlaubstage, :einstellungsdatum)
        ");
        $stmt->execute([
            ':vorname'          => $vorname,
            ':nachname'         => $nachname,
            ':email'            => $email,
            ':rollen_id'        => (int)$rollen_id,
            ':wochenstunden_raw'=> $wochenstunden,
            ':urlaubstage'      => $urlaubstage,
            ':einstellungsdatum'=> $einstellungsdatum,
        ]);
        $erfolg = "Benutzer erfolgreich angelegt.";
        // Felder leeren nach Erfolg
        $_POST = [];
    } catch (PDOException $e) {
        $fehler[] = "Fehler beim Speichern: ".htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    }
}
}

?>

<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Benutzerverwaltung</title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>
  <h1>Benutzerverwaltung</h1>
  <nav>
    <a href="bb_route.php">Zurück zum Hauptmenü</a>
    <?= modus_navigation() ?>
  </nav>
    <?php if (!empty($fehler)): ?>
    <?php foreach ($fehler as $msg): ?>
        <p style="color:red;"><?= htmlspecialchars($msg, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endforeach; ?>
    <?php elseif (!empty($erfolg)): ?>
    <p style="color:green;"><?= htmlspecialchars($erfolg, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

  <p>Geben Sie hier alle Nutzerdaten ein. Alle Felder sind Pflichtfelder.</p>

  <form action="" method="post">
    <label for="name">Name</label>
    <input type="text" id="name" name="name" required
           value="<?= htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><br>

    <label for="vorname">Vorname</label>
    <input type="text" id="vorname" name="vorname" required
           value="<?= htmlspecialchars($_POST['vorname'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><br>

    <label for="email">E-Mail:</label>
    <input type="email" id="email" name="email" required
           value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><br>

    <label for="rolle">Rolle</label>
    <select id="rolle" name="rolle" required>
        <option value="">-- Bitte auswählen --</option>
        <option value="1" <?= (($_POST['rolle'] ?? '')==='1')?'selected':''; ?>>Mitarbeiter</option>
        <option value="2" <?= (($_POST['rolle'] ?? '')==='2')?'selected':''; ?>>Teamleitung</option>
        <option value="3" <?= (($_POST['rolle'] ?? '')==='3')?'selected':''; ?>>Projektleitung</option>
    </select><br>

    <label for="wochenstunden">Regelmäßige Wochenstunden</label>
    <input type="number" step="0.1" id="wochenstunden" name="wochenstunden" inputmode="decimal" required
           value="<?= htmlspecialchars($_POST['wochenstunden'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><br>

    <label for="urlaubstage">Urlaubstage</label>
    <input type="number" id="urlaubstage" name="urlaubstage" min="1" max="30" required
           value="<?= htmlspecialchars($_POST['urlaubstage'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><br>

    <label for="einstellungsdatum">Einstellungsdatum</label>
    <input type="date" id="einstellungsdatum" name="einstellungsdatum" required
           value="<?= htmlspecialchars($_POST['einstellungsdatum'] ?? '', ENT_QUOTES, 'UTF-8') ?>"><br>

    <button type="submit" name="save" value="1">Speichern</button>
    <button type="button" onclick="window.history.back()">Zurück</button>
  </form> 
</body>
</html>