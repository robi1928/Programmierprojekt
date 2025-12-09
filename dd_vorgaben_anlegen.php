<?php
require_once 'bb_auth.php';
rolle_erforderlich(ROLLE_PROJEKTLEITUNG);
modus_aus_url_setzen();

 // Variablen vorbereiten
$fehler = [];
$erfolg = "";


include 'bb_db.php';               
include 'cc_VorgabenAuftraggeber.php';
?>

<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Vorgaben verwalten</title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>

<h2>Vorgaben Auftraggeber anlegen</h2>

  <form action="dd_vorgaben_angelegt.php" method="get">
    <table>
    <tr><td>Jahr:</td><td><input type="int" name="jahr" required></td></tr>
    <tr><td>Quartal</td><td>    <select type="int" name="quartal" required>
        <option value="">-- Bitte auswählen --</option>
        <option value="1" <?= (($_POST['quartal'] ?? '')==='1')?'selected':''; ?>>Q1</option>
        <option value="2" <?= (($_POST['quartal'] ?? '')==='2')?'selected':''; ?>>Q2</option>
        <option value="3" <?= (($_POST['quartal'] ?? '')==='3')?'selected':''; ?>>Q3</option>
        <option value="4" <?= (($_POST['quartal'] ?? '')==='4')?'selected':''; ?>>Q4</option>
    </select></td></tr>
    <tr><td>Krankenquote:</td><td><input type="number" name="erwarteteKrankenquote" min="0" step="0.1" inputmode="decimal" required></td></tr>
    <tr><td>Soll-Stunden:</td><td><input type="number" step="1.0" name="sollStunden" min="1" inputmode="decimal" required></td></tr>
    <tr><td>Toleranz:</td><td><input type="number" name="toleranz" min="0" max="100" step="0.1" inputmode="decimal" required></td></tr>

    <tr><td><button type="submit">Speichern</button></td><td></td></tr>
    <tr><td><button type="button" onclick="window.history.back()">Zurück</button></td><td></td></tr>
    </table>
  </form>

</body>
</html>
