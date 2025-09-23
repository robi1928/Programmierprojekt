<!-- Standardseite, noch ohne Inhalt. Selber geschrieben -->
<?php
require_once 'auth.php';
modus_aus_url_setzen();
?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Erfassung</title>
  <link rel="stylesheet" href="aussehen.css">
</head>
<body>
  <h1>Erfassung</h1>
  <nav>
    <a href="route.php">Zurück zum Hauptmenü</a>
    <?= modus_navigation() ?>
  </nav>
  <p>Platzhalterseite.</p>
</body>
</html>
