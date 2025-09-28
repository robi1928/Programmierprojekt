<?php
// --- Datenbankverbindung herstellen ---
$servername = "localhost";
$username   = "root";   // Standard bei XAMPP
$password   = "";       // Standard: leer
$dbname     = "db";     // Name der Datenbank aus db.sql

$conn = new mysqli($servername, $username, $password, $dbname);

// Verbindung prüfen
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// --- Formulardaten auslesen (nur wenn POST) ---
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Eingaben absichern (SQL-Injection vermeiden)
    $vorname   = trim($_POST["vorname"] ?? "");
    $nachname  = trim($_POST["nachname"] ?? "");
    $email     = trim($_POST["email"] ?? "");
    $rollen_id = intval($_POST["rollen_id"] ?? 0);

    // Prüfen ob Pflichtfelder ausgefüllt sind
    if ($vorname === "" || $nachname === "" || $email === "" || $rollen_id === 0) {
        die("Bitte alle Felder ausfüllen!");
    }

    // SQL vorbereiten
    $sql = "INSERT INTO benutzer (vorname, nachname, email, rollen_id) 
            VALUES (?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Fehler beim Vorbereiten des Statements: " . $conn->error);
    }

    $stmt->bind_param("sssi", $vorname, $nachname, $email, $rollen_id);

    // --- Ausführen & Feedback ---
    if ($stmt->execute()) {
        echo "<p>✅ Benutzer erfolgreich angelegt!</p>";
        echo "<p><a href='nutzer.php'>Zurück zum Formular</a></p>";
    } else {
        echo "<p>❌ Fehler beim Anlegen: " . $stmt->error . "</p>";
    }

    $stmt->close();
}

$conn->close();
?>
