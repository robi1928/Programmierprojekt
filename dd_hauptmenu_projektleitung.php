<!-- Standardseite, noch ohne Inhalt. Selber geschrieben -->
<?php
require_once 'bb_auth.php';
include_once 'bb_db.php';
include_once 'cc_vorgabenAuftraggeber.php';

rolle_erforderlich(ROLLE_PROJEKTLEITUNG);
modus_aus_url_setzen();
$benutzer = aktueller_benutzer();

// Aktuelles Jahr + Quartal bestimmen
$heute   = new DateTime();
$jahr    = (int)$heute->format("Y");
$monat   = (int)$heute->format("n");
$quartal = ceil($monat / 3);

// Vorgaben für aktuelles Quartal laden
$vorgabe =  CVorgabenAuftraggeber::LoadByJahrQuartal($pdo, $jahr, $quartal);


?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Hauptmenü Projektleitung</title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>
  <h1>Hauptmenü Projektleitung</h1>
  <p>Angemeldet: <?= htmlspecialchars($benutzer['name']) ?> (<?= htmlspecialchars($benutzer['rolle']) ?>)</p>
  <nav>
    <a href="bb_ausloggen.php">Abmelden</a>
    <?= modus_navigation() ?>
  </nav>
  <ul>
    <!--Liste aller Geschäftsvorfälle, die ein Projektleiter ausführen darf-->

<div class="menu-links">
    <a class="btn primary" href="dd_vorgaben_anlegen.php">Auftraggeber Vorgaben anlegen</a>  
    <a class="btn primary" href="dd_nutzer_anlegen_projektleitung.php">Nutzer anlegen</a>
    <a class="btn primary" href="dd_nutzer_aktualisieren_projektleitung.php">Nutzer aktualisieren</a>
    <a class="btn primary" href="dd_erfassung_projektleitung.php">Arbeitszeit erfassen</a>
    <a class="btn primary" href="dd_freigaben_projektleitung.php">Arbeitszeiten freigeben</a>
    <a class="btn primary" href="dd_monatsuebersicht_projektleitung.php">Monatsübersicht</a>
  </div>
    <br>
  </ul>

    <!--  Das sind die Anzeigen, die man zur Steuerung der Zeitplanung braucht. Die können „irgendwie sinnvoll“ angeordnet werden.-->
    <!--Bei den Prozentzahlen dachte ich an zwei „Ladebalken“. Vielleicht ändert sich die Farbe vom IST, abhängig ob sie größer oder kleiner ist als der Zeitfortschritt.-->
  <?php
if ($vorgabe === null) {
    echo "<p>Für dieses Quartal ($quartal/$jahr) liegen keine Vorgaben vor.</p>";
} else {
    echo "<p> Sollstunden (Quartal): " . $vorgabe->GetSollStunden() . "</p>";
    echo "<p> Untergrenze Toleranzbereich: " . $vorgabe->Toleranzbereich()['min'] . "</p>";
    echo "<p> Obergrenze Toleranzbereich: " . $vorgabe->Toleranzbereich()['max'] . "</p>";
    echo "<p> IstStunden (Quartal): " . $vorgabe->GetIstStunden() . "</p>";
    echo "<p> Zielerreichung für Sollstunden: " . $vorgabe->GetAnteilIstStunden() . " %</p>"; //IstStunden / Sollstunden * 100 
    echo "<p> Arbeitstage insgesamt: " . $vorgabe->berechneArbeitstageMitFeiertagen($jahr, $quartal) . "</p>";
    echo "<p> Davon bereits vergangene Arbeitstage: " . $vorgabe->berechneVergangeneArbeitstageImQuartal($jahr, $quartal) . "</p>";
    echo "<p> Anteil der bereits vergangenen Arbeitstage im Quartal: " . number_format($vorgabe->prozentualeVergangeneArbeitstageImQuartal($jahr, $quartal), 1, ',', '') . " %</p>";
    echo "<p> Erforderliche Planstunden bis Quartalsende (Minimum): " . number_format($vorgabe->BedarfPlanstundenBisEndeQuartal()['bis_min'], 2, ',', '') . "</p>";
    echo "<p> Erforderliche Planstunden bis Quartalsende (Maximum): " . number_format($vorgabe->BedarfPlanstundenBisEndeQuartal()['bis_max'], 2, ',', '') . "</p>";

}
?>

  <br>
</body>
</html>
