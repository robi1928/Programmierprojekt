<?php
require_once 'auth.php';
require_role(ROLE_EMPLOYEE);
set_theme_from_get();
$u = current_user();
?>
<!doctype html>
<html lang="de"<?= theme_attr() ?>>
<head>
  <meta charset="utf-8">
  <title>Dashboard Mitarbeiter</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h1>Dashboard Mitarbeiter</h1>
  <p>Angemeldet: <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['role']) ?>)</p>
  <nav>
    <a href="logout.php">Abmelden</a>
    <?= theme_nav() ?>
  </nav>
  <ul>
    <li><a href="erfassung.php">Erfassung</a></li>
    <li><a href="auswertung.php">Meine Auswertung</a></li>
  </ul>
</body>
</html>
