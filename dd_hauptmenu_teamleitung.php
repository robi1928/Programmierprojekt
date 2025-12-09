<!-- Standardseite, noch ohne Inhalt. Selber geschrieben -->
<?php
require_once 'bb_auth.php';
rolle_erforderlich(ROLLE_TEAMLEITUNG);
modus_aus_url_setzen();
$benutzer = aktueller_benutzer();
?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Hauptmenü Teamleitung</title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>
  <h1>Hauptmenü Teamleitung</h1>
  <p>Angemeldet: <?= htmlspecialchars($benutzer['name']) ?> (<?= htmlspecialchars($benutzer['rolle']) ?>)</p>
  <nav>
    <a href="bb_ausloggen.php">Abmelden</a>
    <?= modus_navigation() ?>
  </nav>
<div class="menu-links">

    <a class="btn primary" href="dd_nutzer_anlegen_teamleitung.php">Nutzer anlegen</a>
    <a class="btn primary" href="dd_nutzer_aktualisieren_teamleitung.php">Nutzer aktualisieren</a>
    <a class="btn primary" href="dd_erfassung_teamleitung.php">Arbeitszeit erfassen</a>
    <a class="btn primary" href="dd_freigaben_teamleitung.php">Arbeitszeiten freigeben</a>    
    <a class="btn primary" href="dd_monatsuebersicht.php">Monatsübersicht</a>
  </div>
</body>
</html>
