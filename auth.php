<?php
session_start();

// Definiert Konstanten für die drei Rollen im System.
// Diese Strings müssen zu den Werten in der Datenbank passen
const ROLLE_MITARBEITER='mitarbeiter';
const ROLLE_TEAMLEITUNG='teamleitung';
const ROLLE_ADMIN='admin';

/**
 * Gibt die Daten des aktuell eingeloggten Users zurück (aus $_SESSION['user']), oder null, falls niemand eingeloggt ist.
 * Rückgabeformat ist ein Array mit Benutzerdaten (id, email, role, …), das beim Login in die Session geschrieben wird.*/
function aktueller_benutzer(): ?array {
    return $_SESSION['benutzer'] ?? null;
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
        header('Location: route.php');
        exit;
    }
}

require_once __DIR__ . '/darkmode.php';
require_once __DIR__ . '/db.php';
