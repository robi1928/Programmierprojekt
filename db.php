<?php
// Datenbank-Verbindungszeichenkette
$dsn  = 'mysql:host=localhost;dbname=db;charset=utf8mb4';
// Benutzername für die DB-Verbindung. Standard in XAMPP: "root" ohne Passwort.
$user = 'root';
$pass = ''; // XAMPP-Standard: root ohne Passwort. Abklären, ob das ohne Passwort bleiben kann weil ja eigentlich nur eine Studienleistung und keine wirkliche Seite.

// Stellt eine Verbindung zur Datenbank über PDO (PHP Data Objects) her.
// Verwendete Optionen:
// - ERRMODE_EXCEPTION → Fehler lösen eine Exception aus (statt still protokolliert zu werden)
// - FETCH_ASSOC → Ergebnisse werden als Array mit Spaltennamen als Schlüssel zurückgegeben
$pdo = new PDO($dsn, $user, $pass, [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
