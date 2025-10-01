<?php
require_once 'auth.php';
rolle_erforderlich(ROLLE_ADMIN);
modus_aus_url_setzen();

// Auskommentiert, weil die Verbindung zur Datenbank noch nicht steht
//ChatGPT
// Verbindung zur Datenbank (anpassen!)
 $pdo = new PDO("mysql:host=localhost;dbname=db;charset=utf8mb4", "root", "");

 // Variablen vorbereiten
$fehler = [];
$erfolg = "";

// Nur prüfen, wenn Formular abgesendet wurde 
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
// Eingaben abholen  
$vorname            = trim($_POST['vorname'] ?? '');
$nachname           = trim($_POST['name'] ?? '');
$email              = trim($_POST['email']);
$rollen_id          = $_POST['rolle'] ?? '';
$wochenstunden_raw  = $_POST['wochenstunden'] ?? '';
$urlaubstage        = (int)($_POST['urlaubstage'] ?? 0);
$einstellungsdatum  = $_POST['einstellungsdatum'] ?? '';



// Name prüfen: nur Buchstaben
if (!preg_match("/^[A-Za-zÄÖÜäöüß-]+$/u", $nachname)) {
    $fehler[] = "Name darf nur Buchstaben und Bindestrich enthalten.";
}

// Vorname prüfen: nur Buchstaben
if (!preg_match("/^[A-Za-zÄÖÜäöüß-]+$/u", $vorname)) {
    $fehler[] = "Vorname darf nur Buchstaben und Bindestrich enthalten.";
}

//E-Mail-Adresse prüfen: gemäß Filter
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Ungültige E-Mail-Adresse.";
}
// Rolle prüfen
//Keine Prüfung notwendig da Listenauswahl
$zulaessigeRollen = ['mitarbeiter','teamleitung','admin'];
if (!in_array($rollen_id, $zulaessigeRollen, true)) {
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

// Urlaubstage prüfen: 1–30
if ($urlaubstage <= 1 || $urlaubstage >= 30) {
    $fehler[] = "Urlaubstage müssen zwischen 1 und 30 liegen.";
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
}

// In DB speichern (anpassen!)
$stmt = $pdo->prepare("
    INSERT INTO benutzer (name, vorname, email, rolle, wochenstunden, urlaubstage, einstellungsdatum)
    VALUES (:name, :vorname, :email, :rolle, :wochenstunden, :urlaubstage, :einstellungsdatum)
");
try{
$stmt->execute([
    ':vorname'           => $vorname,
    ':name'              => $nachname,
    ':email'             => $email,
    ':rolle'             => $rollen_id,
    ':wochenstunden'     => $wochenstunden,
    ':urlaubstage'       => $urlaubstage,
    ':einstellungsdatum' => $einstellungsdatum
]); $erfolg = "Benutzer erfolgreich angelegt!";
}

catch (PDOException $e) {
            $fehler[] = "Fehler beim Speichern: " . $e->getMessage();
         }


echo "<p style='color:green;'>Benutzer erfolgreich angelegt!</p>"; 

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


    <p>
        Geben Sie hier alle Nutzerdaten ein.
        Alle Felder sind Pflichtfelder.
    </p>

  <form action="insert_user.php" method="post"><br>
    <!-- Name -->
    <label for="name">Name</label>
    <input type="text" id="name" name="name" required><br>

    <!-- Vorname -->
    <label for="vorname">Vorname</label>
    <input type="text" id="vorname" name="vorname" required><br>


    <!-- E-Mail -->
    <label for="email">E-Mail:</label>
    <input type="email" id="email" name="email" required><br>

    <!-- Rolle -->
    <label for="rolle">Rolle</label>
    <select id="rolle" name="rolle" required>
        <option value="">-- Bitte auswählen --</option>
        <option value="mitarbeiter">Mitarbeiter</option>
        <option value="teamleitung">Teamleitung</option>
        <option value="admin">Admin</option>
    </select><br>

    <!-- Wochenstunden -->
    <label for="wochenstunden">Regelmäßige Wochenstunden</label>
    <input type="number" step="0.1" id="wochenstunden" name="wochenstunden" inputmode="decimal" required><br>

    <!-- Urlaubstage -->
    <label for="urlaubstage">Urlaubstage</label>
    <input type="number" id="urlaubstage" name="urlaubstage" min="1" max="30" required><br>

    <!-- Einstellungsdatum -->
    <label for="einstellungsdatum">Einstellungsdatum</label>
    <input type="date" id="einstellungsdatum" name="einstellungsdatum" required><br>

    <!-- Buttons -->
    <button type="submit">Speichern</button>
    <button type="button" onclick="window.history.back()">Zurück</button>
  </form>

 
</body>
</html>
