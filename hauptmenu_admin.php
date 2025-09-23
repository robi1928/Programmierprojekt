<?php
require_once 'auth.php';
rolle_erforderlich(ROLLE_ADMIN);
modus_aus_url_setzen();
$benutzer = aktueller_benutzer();
?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Hauptmenü Admin</title>
  <link rel="stylesheet" href="aussehen.css">
</head>
<body>
  <h1>Hauptmenü Admin</h1>
  <p>Angemeldet: <?= htmlspecialchars($benutzer['name']) ?> (<?= htmlspecialchars($benutzer['rolle']) ?>)</p>
  <nav>
    <a href="ausloggen.php">Abmelden</a>
    <?= modus_navigation() ?>
  </nav>
  <ul>
    <li><a href="system_report.php">Systemreport</a></li>
  </ul>
</body>
</html>
