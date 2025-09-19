<?php
require_once 'auth.php';
require_role(ROLE_TEAMLEAD);
set_theme_from_get();
$u = current_user();
?>
<!doctype html>
<html lang="de"<?= theme_attr() ?>>
<head>
  <meta charset="utf-8">
  <title>Dashboard Teamlead</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h1>Dashboard Teamlead</h1>
  <p>Angemeldet: <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['role']) ?>)</p>
  <nav>
    <a href="logout.php">Abmelden</a>
    <?= theme_nav() ?>
  </nav>
  <ul>
    <li><a href="team_auswertung.php">Team-Auswertung</a></li>
    <li><a href="freigaben.php">Freigaben</a></li>
  </ul>
</body>
</html>
