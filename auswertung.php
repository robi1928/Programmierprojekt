<?php
session_start();
require_once 'darkmode.php';
require_once 'auth.php';
require_role(ROLE_EMPLOYEE);
set_theme_from_get();
?>
<!doctype html>
<html lang="de"<?= theme_attr() ?>>
<head>
  <meta charset="utf-8">
  <title>Meine Auswertung</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h1>Meine Auswertung</h1>
  <nav>
    <a href="route.php">Zurück zum Dashboard</a>
    <?= theme_nav() ?>
  </nav>
  <p>Platzhalterseite.</p>
</body>
</html>
