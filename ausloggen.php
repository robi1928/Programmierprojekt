<?php
session_start();

// Session leeren und beenden
session_unset();
session_destroy();

// Zurück zur Login-Seite
header('Location: index.php');
exit;