<?php
require_once 'auth.php';
rolle_erforderlich(ROLLE_ADMIN);
modus_aus_url_setzen();

// Auskommentiert, weil die Verbindung zur Datenbank noch nicht steht
/* //ChatGPT
// Verbindung zur Datenbank (anpassen!)
$pdo = new PDO("mysql:host=localhost;dbname=deine_db;charset=utf8", "dein_user", "dein_pass");

// Eingaben aus dem Formular holen
// Eingaben abholen
$name               = trim($_POST['name'] ?? '');
$vorname            = trim($_POST['vorname'] ?? '');
$rolle              = $_POST['rolle'] ?? '';
$wochenstunden_raw  = $_POST['wochenstunden'] ?? '';
$urlaubstage        = (int)($_POST['urlaubstage'] ?? 0);
$einstellungsdatum  = $_POST['einstellungsdatum'] ?? '';

$fehler = [];

// Name prüfen: nur Buchstaben
if (!preg_match("/^[A-Za-zÄÖÜäöüß-]+$/u", $name)) {
    $fehler[] = "Name darf nur Buchstaben und Bindestrich enthalten.";
}

// Vorname prüfen: nur Buchstaben
if (!preg_match("/^[A-Za-zÄÖÜäöüß-]+$/u", $vorname)) {
    $fehler[] = "Vorname darf nur Buchstaben und Bindestrich enthalten.";
}

// Rolle prüfen
//Keine Prüfung da Listenauswahl
$zulaessigeRollen = ['mitarbeiter','teamleitung','admin'];
if (!in_array($rolle, $zulaessigeRollen, true)) {
    $fehler[] = "Ungültige Rolle.";
}

// Wochenstunden prüfen: 0 < x < 42, max. 1 Nachkommastelle, akzeptiert Komma oder Punkt
$ws_norm = str_replace(',', '.', trim($wochenstunden_raw));
if (!preg_match("/^\d+(\.\d)?$/", $ws_norm)) {
    $fehler[] = "Wochenstunden dürfen maximal eine Nachkommastelle haben.";
}
$wochenstunden = (float)$ws_norm;
if ($wochenstunden <= 0 || $wochenstunden >= 42) {
    $fehler[] = "Wochenstunden müssen zwischen 0 und 42 liegen.";
}

// Urlaubstage prüfen: 1–29
if ($urlaubstage <= 0 || $urlaubstage >= 30) {
    $fehler[] = "Urlaubstage müssen zwischen 1 und 29 liegen.";
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

// Fehler ausgeben und abbrechen
if (!empty($fehler)) {
    foreach ($fehler as $msg) {
        echo "<p style='color:red;'>".htmlspecialchars($msg, ENT_QUOTES, 'UTF-8')."</p>";
    }
    exit;
}

// In DB speichern (anpassen!)
$stmt = $pdo->prepare("
    INSERT INTO users (name, vorname, rolle, wochenstunden, urlaubstage, einstellungsdatum)
    VALUES (:name, :vorname, :rolle, :wochenstunden, :urlaubstage, :einstellungsdatum)
");
$stmt->execute([
    ':name'              => $name,
    ':vorname'           => $vorname,
    ':rolle'             => $rolle,
    ':wochenstunden'     => $wochenstunden,
    ':urlaubstage'       => $urlaubstage,
    ':einstellungsdatum' => $einstellungsdatum
]);

echo "<p style='color:green;'>Benutzer erfolgreich angelegt!</p>"; */

?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Benutzerverwaltung</title>
  <link rel="stylesheet" href="aussehen.css">
</head>
<body>
  <h1>Benutzerverwaltung</h1>
  <nav>
    <a href="route.php">Zurück zum Hauptmenü</a>
    <?= modus_navigation() ?>
  </nav>
    <!-- ChatGTP -->
  <form action="insert_user.php" method="post">
    <!-- Name -->
    <label for="name">Name</label>
    <input type="text" id="name" name="name" required>

    <!-- Vorname -->
    <label for="vorname">Vorname</label>
    <input type="text" id="vorname" name="vorname" required>

    <!-- Rolle -->
    <label for="rolle">Rolle</label>
    <select id="rolle" name="rolle" required>
        <option value="">-- Bitte auswählen --</option>
        <option value="mitarbeiter">Mitarbeiter</option>
        <option value="teamleitung">Teamleitung</option>
        <option value="admin">Admin</option>
    </select>

    <!-- Wochenstunden -->
    <label for="wochenstunden">Regelmäßige Wochenstunden</label>
    <input type="number" step="0.1" id="wochenstunden" name="wochenstunden" inputmode="decimal" required>

    <!-- Urlaubstage -->
    <label for="urlaubstage">Urlaubstage</label>
    <input type="number" id="urlaubstage" name="urlaubstage" min="1" max="29" required>

    <!-- Einstellungsdatum -->
    <label for="einstellungsdatum">Einstellungsdatum</label>
    <input type="date" id="einstellungsdatum" name="einstellungsdatum" required>

    <!-- Buttons -->
    <button type="submit">Speichern</button>
    <button type="button" onclick="window.history.back()">Zurück</button>
  </form>

  <p>Platzhalter.</p>
</body>
</html>
