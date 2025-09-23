<?php
require_once 'auth.php';
rolle_erforderlich(ROLLE_MITARBEITER);
modus_aus_url_setzen();
$benutzer = aktueller_benutzer();
?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Hauptmenü Mitarbeiter</title>
  <link rel="stylesheet" href="aussehen.css">
</head>
<body>
  <h1>Hauptmenü Mitarbeiter</h1>
  <p>Angemeldet: <?= htmlspecialchars($benutzer['name']) ?> (<?= htmlspecialchars($benutzer['rolle']) ?>)</p>
  <nav>
    <a href="ausloggen.php">Abmelden</a>
    <?= modus_navigation() ?>
  </nav>
  <ul>
    <li><a href="erfassung.php">Erfassung</a></li>
    <li><a href="auswertung.php">Meine Auswertung</a></li>
  </ul>
</body>
</html>
