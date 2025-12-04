<!-- Standardseite, noch ohne Inhalt. Selber geschrieben -->
<?php
require_once 'bb_auth.php';
include_once 'cc_benutzer.php';
rolle_erforderlich(ROLLE_MITARBEITER);
modus_aus_url_setzen();
$benutzer = aktueller_benutzer();

$BenutzerObjekt = new CBenutzer($benutzer['id']);
$mitarbeiter = new CBenutzer($benutzer['id']);
?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Hauptmenü Mitarbeiter</title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>
  <h1>Hauptmenü Mitarbeiter</h1>
  <p>Angemeldet: <?= htmlspecialchars($benutzer['name']) ?> (<?= htmlspecialchars($benutzer['rolle']) ?>)</p>
  <nav>
    <a href="bb_ausloggen.php">Abmelden</a>
    <?= modus_navigation() ?>
  </nav>
 
  <div class="menu-links">
    <a class="btn primary" href="dd_erfassung_mitarbeiter.php">Arbeitszeit einpflegen / bestätigen</a>
    <a class="btn primary" href="dd_monatsuebersicht_mitarbeiter.php">Eigene Monatsübersicht</a>
  </div>
    <?php
    
    //Lade Daten muss noch implementiert werden - $mitarbeiter->LadeDaten();
    echo '<p>Verbleibende Urlaubstage: '.$mitarbeiter->GetUrlaubstage().'</p>';
    //Der Teil funktioniert noch nicht richtig
    echo '<p> Deine Soll-Stunden dieses Monat: '.$BenutzerObjekt->GetSollStundenAktuellerMonat($BenutzerObjekt->GetWochenstunden()).'</p>';
    ?>
</body>
</html>
