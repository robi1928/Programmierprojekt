<!-- Logik von Stackoverflow. Selber angepasst -->
<?php
require_once __DIR__ . '/bb_auth.php';
login_erforderlich();
$benutzer = aktueller_benutzer(); // Weiterleitung zu index.php, falls nicht eingeloggt

// Je nach Rolle des Benutzers auf das passende Hauptmenü umleiten
switch ($benutzer['rolle']) {
    case ROLLE_MITARBEITER:
        header('Location: dd_hauptmenu_mitarbeiter.php');
        break;
    case ROLLE_TEAMLEITUNG:
        header('Location: dd_hauptmenu_teamleitung.php');
        break;
    case ROLLE_PROJEKTLEITUNG:
        header('Location: dd_hauptmenu_projektleitung.php');
        break;
    // Fallback: wenn Rolle unbekannt oder fehlerhaft → zurück zur Login-Seite
    default:
        header('Location: bb_einloggen.php');
}
exit;