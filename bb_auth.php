<!-- Entwurf selber gemacht, hat aber nicht so funktioniert. Verbessert mit ChatGTP -->
<?php

ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax'); // Cross-Site-Anfragen eingeschränkt
if (!empty($_SERVER['HTTPS'])) { ini_set('session.cookie_secure', '1'); } // Session nur per HTTPS, falls vorhanden

session_start();

// Definiert Konstanten für die drei Rollen im System.
// Diese Strings müssen zu den Werten in der Datenbank passen
const ROLLE_MITARBEITER='mitarbeiter';
const ROLLE_TEAMLEITUNG='teamleitung';
const ROLLE_PROJEKTLEITUNG='projektleitung';

/* Gibt die Daten des aktuell eingeloggten Benutzers zurück (aus $_SESSION['benutzer']), oder null, falls niemand eingeloggt ist.
 * Rückgabeformat ist ein Array mit Benutzerdaten (id, email, role, …), das beim Login in die Session geschrieben wird.*/
function aktueller_benutzer(): ?array {
    return $_SESSION['benutzer'] ?? null;
}

// Status-Helfer
function ist_eingeloggt(): bool {
    return isset($_SESSION['benutzer']);
}

// Erzwingt, dass ein Benutzer eingeloggt ist.
function login_erforderlich(): void {
    if (!isset($_SESSION['benutzer'])) {
        header('Location: index.php');
        exit;
    }
}
// Erzwingt, dass ein Benutzer eingeloggt ist UND eine bestimmte Rolle hat.
function rolle_erforderlich(string $rolle): void {
    login_erforderlich();
    if (($_SESSION['benutzer']['rolle'] ?? '') !== $rolle) {
        header('Location: bb_route.php');
        exit;
    }
}

// Erzwingen Gast (Login-Seite)
function gast_erforderlich(string $redirect = 'bb_route.php'): void {
    if (ist_eingeloggt()) {
        header("Location: {$redirect}");
        exit;
    }
}

require_once __DIR__ . '/bb_darkmode.php';
require_once __DIR__ . '/bb_db.php';
