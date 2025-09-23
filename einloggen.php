<?php
// Optionale Sicherheitseinstellungen für Session-Cookies
ini_set('session.cookie_httponly','1'); // Cookies nur für HTTP, nicht für JavaScript
ini_set('session.cookie_samesite','Lax'); // Schutz gegen CSRF. Habs drinnen gelassen. Mit Prof. klären. An sich kann es raus. Wie realistisch soll diese Seite sein?
if (!empty($_SERVER['HTTPS'])) ini_set('session.cookie_secure','1'); // Cookies nur per HTTPS senden, wenn SSL aktiv

session_start();
require_once __DIR__.'/db.php';

// Liest die user_id aus dem POST-Request und wandelt sie in eine Ganzzahl um.
$benutzerId = isset($_POST['benutzer_id']) ? (int)$_POST['benutzer_id'] : 0;
if ($benutzerId <= 0) {
    header('Location: index.php');
    exit;
} // Wenn keine gültige ID vorhanden → zurück zur Login-Seite

// Holt Userdaten und die zugehörige Rolle aus der DB
$stmt = $pdo->prepare("
  SELECT b.benutzer_id, b.vorname, b.nachname, r.rollen_schluessel
  FROM benutzer b
  JOIN rollen r ON r.rollen_id = b.rollen_id
  WHERE b.benutzer_id = ? AND b.aktiv = 1
");
$stmt->execute([$benutzerId]);
$benutzer = $stmt->fetch(PDO::FETCH_ASSOC);

// Wenn kein aktiver User gefunden wurde → zurück zur Login-Seite
if (!$benutzer) {
    header('Location: index.php');
    exit;
}

// User in die Session eintragen (wird später von current_user() genutzt)
$_SESSION['benutzer'] = [
    'id'    => (int)$benutzer['benutzer_id'],
    'name'  => $benutzer['vorname'] . ' ' . $benutzer['nachname'],
    'rolle' => $benutzer['rollen_schluessel'],
];

// Sicherheitsmaßnahme: Session-ID nach Login neu generieren
session_regenerate_id(true);

// Nach erfolgreichem Login weiterleiten auf die Routing-Seite (entscheidet nach Rolle)
header('Location: route.php');
exit;
