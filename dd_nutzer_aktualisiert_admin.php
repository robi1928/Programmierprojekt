<?php
require_once 'bb_auth.php';
rolle_erforderlich(ROLLE_ADMIN);
modus_aus_url_setzen();

include_once 'cc_benutzer.php';

if (!isset($_POST['id'])) {
    echo "Ungültige Anfrage.";
    exit;
}
$Benutzer = new CBenutzer((int)$_POST['id']);

// Wir nehmen an, dass die CBenutzer-Klasse die Funktion Update() wie folgt hat:
// Setzt die neuen Werte und speichert sie
$Benutzer->Init(
    $_POST['vorname'],
    $_POST['nachname'],
    $_POST['email'],
    (int)$_POST['rolle'],
    (float)$_POST['wochenstunden'],
    (float)$_POST['urlaubstage'],
    $_POST['einstellungsdatum']
);

if ($Benutzer->Update()) {
    $msg = "Benutzerdaten wurden erfolgreich aktualisiert.";
} else {
    $msg = "Fehler beim Aktualisieren.";
}
?>

<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Benutzer aktualisiert</title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>
  <h1><?= $msg ?></h1>
  <nav>
    <a href="bb_route.php">Zurück zum Hauptmenü</a>
    <?= modus_navigation() ?>
  </nav>
</body>
</html>