<?php
require_once 'auth.php';
require_role(ROLE_ADMIN);
set_theme_from_get();
$u = current_user();
?>
<!doctype html>
<html lang="de"<?= theme_attr() ?>>
<head>
  <meta charset="utf-8">
  <title>Dashboard Admin</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h1>Dashboard Admin</h1>
  <p>Angemeldet: <?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['role']) ?>)</p>
  <nav>
    <a href="logout.php">Abmelden</a>
    <?= theme_nav() ?>
  </nav>
  <ul>
    <li><a href="users.php">Benutzerverwaltung</a></li>
    <li><a href="system_report.php">Systemreport</a></li>
  </ul>
</body>
</html>
