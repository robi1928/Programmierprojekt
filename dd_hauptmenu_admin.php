<!-- Standardseite, noch ohne Inhalt. Selber geschrieben -->
<?php
require_once 'bb_auth.php';
include_once 'aa_db.php';
include_once 'cc_VorgabenAuftraggeber.php';

rolle_erforderlich(ROLLE_ADMIN);
modus_aus_url_setzen();
$benutzer = aktueller_benutzer();

// Aktuelles Jahr + Quartal bestimmen
$heute   = new DateTime();
$jahr    = (int)$heute->format("Y");
$monat   = (int)$heute->format("n");
$quartal = ceil($monat / 3);

// Vorgaben für aktuelles Quartal laden
$vorgabe = CVorgabenAuftraggeber::LoadByJahrQuartal($pdo, $jahr, $quartal);

?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Hauptmenü Admin</title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>
  <h1>Hauptmenü Admin</h1>
  <p>Angemeldet: <?= htmlspecialchars($benutzer['name']) ?> (<?= htmlspecialchars($benutzer['rolle']) ?>)</p>
  <nav>
    <a href="bb_ausloggen.php">Abmelden</a>
    <?= modus_navigation() ?>
  </nav>
  <ul>
    <!--Liste aller Geschäftsvorfälle, die ein Admin/Projektleiter ausführen darf-->

<div class="menu-links">
    <a class="btn primary" href="dd_system_report.php">Systemreport</a>
    <a class="btn primary" href="dd_erfassung_admin.php">Arbeitszeit erfassen</a>
    <a class="btn primary" href="dd_freigaben_admin.php">Arbeitszeiten freigeben</a>
    <a class="btn primary" href="dd_team_auswertung.php">Team-Auswertung</a>
    <a class="btn primary" href="dd_monatsuebersicht.php">Monatsübersicht</a>
    <a class="btn primary" href="dd_vorgaben_anlegen.php">Auftraggeber Vorgaben anlegen</a>
    <!--Daten exportieren fehlt-->
    <a class="btn primary" href="dd_nutzer_anlegen_admin.php">Nutzer anlegen</a>
  </div>
    <br>
  </ul>
  <?php
if ($vorgabe === null) {
    echo "<p>Für dieses Quartal ($quartal/$jahr) liegen keine Vorgaben vor.</p>";
} else {
    echo "<p>Sollstunden (Quartal): " . $vorgabe->GetSollStunden() . "</p>";
}
?>
  <br>
</body>
</html>
