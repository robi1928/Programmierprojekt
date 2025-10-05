<!-- Standardseite, noch ohne Inhalt. Selber geschrieben -->
<?php
require_once 'auth.php';
rolle_erforderlich(ROLLE_TEAMLEITUNG);
modus_aus_url_setzen();
$benutzer = aktueller_benutzer();
?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Hauptmenü Teamleitung</title>
  <link rel="stylesheet" href="aussehen.css">
</head>
<body>
  <h1>Hauptmenü Teamleitung</h1>
  <p>Angemeldet: <?= htmlspecialchars($benutzer['name']) ?> (<?= htmlspecialchars($benutzer['rolle']) ?>)</p>
  <nav>
    <a href="ausloggen.php">Abmelden</a>
    <?= modus_navigation() ?>
  </nav>
<div class="menu-links">
    <a class="btn primary" href="erfassung_admin.php">Arbeitszeit erfassen</a>
    <a class="btn primary" href="freigaben_admin.php">Arbeitszeiten freigeben</a>
    <a class="btn primary" href="team_auswertung.php">Team-Auswertung</a>
    <a class="btn primary" href="monatsuebersicht.php">Monatsübersicht</a>
    <a class="btn primary" href="nutzer_anlegen_teamleitung.php">Nutzer anlegen</a>
        <!--Dashboard fehlt-->
  </div>
</body>
</html>
