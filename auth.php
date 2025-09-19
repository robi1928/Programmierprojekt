<?php
session_start();

// Definiert Konstanten für die drei Rollen im System.
// Diese Strings müssen zu den Werten in der Datenbank passen
const ROLE_EMPLOYEE='employee';
const ROLE_TEAMLEAD='teamlead';
const ROLE_ADMIN='admin';

/**
 * Gibt die Daten des aktuell eingeloggten Users zurück (aus $_SESSION['user']), oder null, falls niemand eingeloggt ist.
 * Rückgabeformat ist ein Array mit Benutzerdaten (id, email, role, …), das beim Login in die Session geschrieben wird.*/
function current_user(): ?array { return $_SESSION['user'] ?? null; }

// Erzwingt, dass ein Benutzer eingeloggt ist.
function require_login(): void {
  if (!isset($_SESSION['user'])) { header('Location: index.php'); exit; }
}
// Erzwingt, dass ein Benutzer eingeloggt ist UND eine bestimmte Rolle hat.
function require_role(string $role): void {
  require_login();
  if (($_SESSION['user']['role'] ?? '') !== $role) { header('Location: route.php'); exit; }
}

require_once __DIR__.'/darkmode.php';
require_once __DIR__.'/db.php';
