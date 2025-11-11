<!-- Standardseite, noch ohne Inhalt. Selber geschrieben -->
<?php
require_once 'bb_auth.php';
rolle_erforderlich(ROLLE_ADMIN);
modus_aus_url_setzen();
?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Erfassung</title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>
  <h1>Erfassung</h1>
  <nav>
    <a href="bb_route.php">Zurück zum Hauptmenü</a>
    <?= modus_navigation() ?>
  </nav>
  <p>Platzhalterseite.</p>
</body>
</html>
