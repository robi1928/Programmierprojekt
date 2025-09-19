<?php
// Optionale Sicherheitseinstellungen für Session-Cookies
ini_set('session.cookie_httponly','1'); // Cookies nur für HTTP, nicht für JavaScript
ini_set('session.cookie_samesite','Lax'); // Schutz gegen CSRF. Habs drinnen gelassen. Mit Prof. klären. An sich kann es raus. Wie realistisch soll diese Seite sein?
if (!empty($_SERVER['HTTPS'])) ini_set('session.cookie_secure','1'); // Cookies nur per HTTPS senden, wenn SSL aktiv

session_start();
require_once __DIR__.'/db.php';

// Liest die user_id aus dem POST-Request und wandelt sie in eine Ganzzahl um.
$userId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
if ($userId <= 0) { header('Location: index.php'); exit; } // Wenn keine gültige ID vorhanden → zurück zur Login-Seite

// Holt Userdaten und die zugehörige Rolle aus der DB
$stmt = $pdo->prepare("
  SELECT u.user_id, u.first_name, u.last_name, r.role_key
  FROM users u
  JOIN roles r ON r.role_id = u.role_id
  WHERE u.user_id = ? AND u.is_active = 1
");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Wenn kein aktiver User gefunden wurde → zurück zur Login-Seite
if (!$user) { header('Location: index.php'); exit; }

// User in die Session eintragen (wird später von current_user() genutzt)
$_SESSION['user'] = [
  'id'   => (int)$user['user_id'],
  'name' => $user['first_name'].' '.$user['last_name'],
  'role' => $user['role_key'],
];

// Sicherheitsmaßnahme: Session-ID nach Login neu generieren
session_regenerate_id(true);

// Nach erfolgreichem Login weiterleiten auf die Routing-Seite (entscheidet nach Rolle)
header('Location: route.php');
exit;
