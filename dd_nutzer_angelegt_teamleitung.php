<!-- Weiterverarbeitung des Formulars aus nutzer_anlegen_andmin.php -->
<?php
require_once 'bb_auth.php';

rolle_erforderlich(ROLLE_TEAMLEITUNG);
modus_aus_url_setzen();
?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Nutzer anlegen</title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>
  <h1>Nutzer anlegen</h1>
  <nav>
    <a href="bb_route.php">Zurück zum Hauptmenü</a>
    <?= modus_navigation() ?>
  </nav>

<?php

include_once 'cc_benutzer.php';
$Benutzer = new CBenutzer( 
    //-1, 
    $_GET['vorname'], 
    $_GET['nachname'], 
    $_GET['email'], 
    $_GET['rolle'], 
    $_GET['wochenstunden'], 
    $_GET['urlaubstage'], 
    $_GET['einstellungsdatum']
);
$result = $Benutzer->Create();
if( $result ) {
    echo '<p>Speichern erfolgreich.</p>';
} else {
    echo '<p>Fehler beim Speichern.</p>';
}
?>

<!--<p>Platzhalterseite.</p>-->
</body>
</html>