<?php
require_once 'auth.php';
set_theme_from_get();
?>
<!doctype html>
<html lang="de"<?= theme_attr() ?>>
<head>
  <meta charset="utf-8">
  <title>Team-Auswertung</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h1>Team-Auswertung</h1>
  <nav>
    <a href="route.php">Zurück zum Dashboard</a>
    <?= theme_nav() ?>
  </nav>
  <p>Platzhalterseite.</p>
</body>
</html>
