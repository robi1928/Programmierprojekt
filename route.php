<?php
require_once 'auth.php';
login_erforderlich();
$benutzer = aktueller_benutzer(); // Weiterleitung zu index.php, falls nicht eingeloggt

// Je nach Rolle des Benutzers auf das passende Hauptmenü umleiten
switch ($benutzer['rolle']) {
    case ROLLE_MITARBEITER:
        header('Location: hauptmenu_mitarbeiter.php');
        break;
    case ROLLE_TEAMLEITUNG:
        header('Location: hauptmenu_teamleitung.php');
        break;
    case ROLLE_ADMIN:
        header('Location: hauptmenu_admin.php');
        break;
    // Fallback: wenn Rolle unbekannt oder fehlerhaft → zurück zur Login-Seite
    default:
        header('Location: einloggen.php');
}
exit;
