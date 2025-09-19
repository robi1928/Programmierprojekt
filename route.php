<?php
require_once 'auth.php'; require_login(); $u=current_user(); // Weiterleitung zu index.php, falls nicht eingeloggt

// Je nach Rolle des Users auf das passende Dashboard umleiten
switch($u['role']){
  case ROLE_EMPLOYEE: header('Location: dashboard_employee.php'); break;
  case ROLE_TEAMLEAD: header('Location: dashboard_teamlead.php'); break;
  case ROLE_ADMIN: header('Location: dashboard_admin.php'); break;
  // Fallback: wenn Rolle unbekannt oder fehlerhaft → zurück zur Login-Seite
  default: header('Location: index.php');
}
// Script endet nach Weiterleitung
exit;
?>