<!-- Weiterverarbeitung des Formulars aus dd_vorgaben_anlegen.php -->
<?php
require_once 'bb_auth.php';
rolle_erforderlich(ROLLE_PROJEKTLEITUNG);
modus_aus_url_setzen();
?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Vorgaben Auftraggeber angelegt</title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>
  <h1>Vorgaben Auftraggeber angelegt</h1>
  <nav class="menu">
    <a class="btn" href="bb_route.php">Zurück zum Hauptmenü</a>
    <?= modus_navigation() ?>
  </nav>
 </header>

<?php

include_once 'cc_vorgabenAuftraggeber.php';
$Vorgaben = new CVorgabenAuftraggeber( 
    //-1, 
    $_GET['jahr'], 
    $_GET['quartal'], 
    $_GET['erwarteteKrankenquote'], 
    $_GET['sollStunden'], 
    $_GET['toleranz'], 

);
$result = $Vorgaben->InsertIntoDB($pdo);
if( $result ) {
    echo '<p>Speichern erfolgreich.</p>';
} else {
    echo '<p>Fehler beim Speichern.</p>';
}
?>
</body>
</html>