<?php
require_once 'auth.php';
require_role(ROLE_ADMIN);
set_theme_from_get();


// Auskommentiert, weil die Verbindung zur Datenbank noch nicht steht
/* //ChatGPT
// Verbindung zur Datenbank (anpassen!)
$pdo = new PDO("mysql:host=localhost;dbname=deine_db;charset=utf8", "dein_user", "dein_pass");

// Eingaben aus dem Formular holen
// Eingaben abholen
$name      = trim($_POST['name']);
$vorname   = trim($_POST['vorname']);
$role      = $_POST['role'];
$hours     = (float)$_POST['hours'];
$vacation  = (int)$_POST['vacation'];
$hire_date = $_POST['hire_date'];

$errors = [];

// Name prüfen: nur Buchstaben
if (!preg_match("/^[A-Za-zÄÖÜäöüß]+$/u", $name)) {
    $errors[] = "Name darf nur Buchstaben enthalten.";
}

// Vorname prüfen: nur Buchstaben
if (!preg_match("/^[A-Za-zÄÖÜäöüß]+$/u", $vorname)) {
    $errors[] = "Vorname darf nur Buchstaben enthalten.";
}

// Rolle prüfen
//Keine Prüfung da Listenauswahl

// Wochenstunden prüfen: 0 < x < 42, max. 1 Nachkommastelle
if ($hours <= 0 || $hours >= 42) {
    $errors[] = "Wochenstunden müssen zwischen 0 und 42 liegen.";
}
if (!preg_match("/^\d+(\.\d)?$/", $_POST['hours'])) {
    $errors[] = "Wochenstunden dürfen maximal eine Nachkommastelle haben.";
}

// Urlaubstage prüfen: 0 < x < 30
if ($vacation <= 0 || $vacation >= 30) {
    $errors[] = "Urlaubstage müssen zwischen 1 und 29 liegen.";
}

// Einstellungsdatum prüfen: gültiges Datum, 01.01.2000 < Datum < heute + 30 Tage
$minDate = strtotime("2000-01-01");
$maxDate = strtotime("+30 days");
$hireDateTimestamp = strtotime($hire_date);

if ($hireDateTimestamp === false) {
    $errors[] = "Ungültiges Einstellungsdatum.";
} elseif ($hireDateTimestamp < $minDate || $hireDateTimestamp > $maxDate) {
    $errors[] = "Einstellungsdatum muss zwischen 01.01.2000 und in 30 Tagen liegen.";
}

// Fehler ausgeben und abbrechen
if (!empty($errors)) {
    foreach ($errors as $error) {
        echo "<p style='color:red;'>$error</p>";
    }
    exit;
}

// In DB speichern (anpassen!)
$stmt = $pdo->prepare("
    INSERT INTO users (name, vorname, rolle, wochenstunden, urlaubstage, einstellungsdatum)
    VALUES (:name, :vorname, :role, :hours, :vacation, :hire_date)
");
$stmt->execute([
    ':name'      => $name,
    ':vorname'   => $vorname,
    ':role'      => $role,
    ':hours'     => $hours,
    ':vacation'  => $vacation,
    ':hire_date' => $hire_date
]);

echo "<p style='color:green;'>Benutzer erfolgreich angelegt!</p>"; */
?>
<!doctype html>
<html lang="de"<?= theme_attr() ?>>
<head>
  <meta charset="utf-8">
  <title>Benutzerverwaltung</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h1>Benutzerverwaltung</h1>
  <nav>
    <a href="route.php">Zurück zum Dashboard</a>
    <?= theme_nav() ?>
  </nav>
  // ChatGPT
<form action="insert_user.php" method="post">
    <!-- Name -->
    <label for="name">Name:</label>
    <input type="text" id="name" name="name" required><br><br>

    <!-- Vorname -->
    <label for="vorname">Vorname:</label>
    <input type="text" id="vorname" name="vorname" required><br><br>

    <!-- Rolle -->
    <label for="role">Rolle:</label>
    <select id="role" name="role" required>
        <option value="">-- Bitte auswählen --</option>
        <option value="Projektleiter">Projektleiter</option>
        <option value="Teamleiter">Teamleiter</option>
        <option value="Agent">Agent</option>
    </select><br><br>

    <!-- Wochenstunden -->
    <label for="hours">Regelmäßige Wochenstunden:</label>
    <input type="number" step="0.1" id="hours" name="hours" required><br><br>

    <!-- Urlaubstage -->
    <label for="vacation">Urlaubstage:</label>
    <input type="number" id="vacation" name="vacation" required><br><br>

    <!-- Einstellungsdatum -->
    <label for="hire_date">Einstellungsdatum:</label>
    <input type="date" id="hire_date" name="hire_date" required><br><br>

    <!-- Buttons -->
    <button type="submit">Speichern</button>
    <button type="button" onclick="window.history.back()">Zurück</button>
</form>
  
  <p>
  Platzhalter.    
  </p>
</body>
</html>
