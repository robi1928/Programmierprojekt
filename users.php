<?php
require_once 'auth.php';
require_role(ROLE_ADMIN);
set_theme_from_get();
?>
<!doctype html>
<html lang="de"<?= theme_attr() ?>>
<head>
  <meta charset="utf-8">
  <title>Benutzerverwaltung</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h1>Benutzerverwaltung</h1>
  <nav>
    <a href="route.php">Zurück zum Dashboard</a>
    <?= theme_nav() ?>
  </nav>
  <p>Platzhalterseite.</p>
</body>
</html>
