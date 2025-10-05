<?php
require_once 'auth.php';
rolle_erforderlich(ROLLE_ADMIN);
modus_aus_url_setzen();
?>

<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Benutzerverwaltung</title>
  <link rel="stylesheet" href="aussehen.css">
</head>
<body>


  <h1>Benutzerverwaltung</h1>
  <nav>
    <a href="route.php">Zurück zum Hauptmenü</a>
    <?= modus_navigation() ?>
  </nav>

 <p>Geben Sie hier alle Nutzerdaten ein. Alle Felder sind Pflichtfelder.</p>
  <form action="nutzer_angelegt_admin.php" method="get">
    <table>
    <tr><td>Vorname:</td><td><input type="text" name="vorname" required></td></tr>
	<tr><td>Nachname:</td><td><input type="text" name="nachname" required></td></tr>
	<tr><td>E-Mail:</td><td><input type="email" name="email" required></td></tr>
    <tr><td>Rolle:</td><td>    <select id="rolle" name="rolle" required>
        <option value="">-- Bitte auswählen --</option>
        <option value="1" <?= (($_POST['rolle'] ?? '')==='1')?'selected':''; ?>>Mitarbeiter</option>
        <option value="2" <?= (($_POST['rolle'] ?? '')==='2')?'selected':''; ?>>Teamleitung</option>
        <option value="3" <?= (($_POST['rolle'] ?? '')==='3')?'selected':''; ?>>Admin</option>
    </select></td></tr>
    <tr><td>Wochenstunden:</td><td><input type="number" step="0.1" name="wochenstunden" inputmode="decimal" required></td></tr>
    <tr><td>Urlaubstage:</td><td><input type="urlaubstage"name="urlaubstage" min="1" max="30" required></td></tr>
    <tr><td>Einstellungsdatum:</td><td><input type="date" name="einstellungsdatum" required></td></tr>

    <tr><td><button type="submit">Anlegen</button></td><td></td></tr>
    <tr><td><button type="button" onclick="window.history.back()">Zurück</button></td><td></td></tr>
    </table>
  </form>

</body>
</html>