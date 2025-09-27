<?php
require_once 'auth.php';
rolle_erforderlich(ROLLE_TEAMLEITUNG);
modus_aus_url_setzen();
?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Meine Auswertung</title>
  <link rel="stylesheet" href="aussehen.css">
</head>
<body>
  <h1>Meine Auswertung</h1>
  <nav>
    <a href="route.php">Zurück zum Hauptmenü</a>
    <?= modus_navigation() ?>
  </nav>
  <p>Platzhalterseite.</p>
</body>
</html>
