<!-- Standardseite, noch ohne Inhalt. Selber geschrieben -->
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
    <!--Liste aller Geschäftsvorfälle, die ein Admin/Projektleiter ausführen darf-->

<div class="menu-links">
    <a class="btn primary" href="system_report.php">Systemreport</a>
    <a class="btn primary" href="erfassung_admin.php">Arbeitszeit erfassen</a>
    <a class="btn primary" href="freigaben_admin.php">Arbeitszeiten freigeben</a>
    <a class="btn primary" href="team_auswertung.php">Team-Auswertung</a>
    <a class="btn primary" href="monatsuebersicht.php">Monatsübersicht</a>
    <!--Daten exportieren fehlt-->
    <a class="btn primary" href="nutzer_anlegen_admin.php">Nutzer anlegen</a>
  </div>
    <br>
  </ul>
  <br>
</body>
</html>
