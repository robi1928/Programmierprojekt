<?php
require_once 'bb_auth.php';
rolle_erforderlich(ROLLE_PROJEKTLEITUNG);
modus_aus_url_setzen();

 // Variablen vorbereiten
$fehler = [];
$erfolg = "";


include 'bb_db.php';               
include 'cc_vorgabenAuftraggeber.php';
?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Auftraggeber Vorgaben anlegen</title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>
  <h1>Auftraggeber Vorgaben anlegen</h1>
  <nav class="menu">
    <a class="btn" href="bb_route.php">Zurück zum Hauptmenü</a>
    <?= modus_navigation() ?>
  </nav>
 </header>

<body>

<div class="form-container">

  <div class="card">

    <form class="form" action="dd_vorgaben_angelegt.php" method="get">

      <div class="field">
        <label for="jahr">Jahr</label>
        <input id="jahr" type="number" name="jahr" required>
      </div>

      <div class="field">
        <label for="quartal">Quartal</label>
        <select id="quartal" name="quartal" required>
          <option value="">-- Bitte auswählen --</option>
          <option value="1">Q1</option>
          <option value="2">Q2</option>
          <option value="3">Q3</option>
          <option value="4">Q4</option>
        </select>
      </div>

      <div class="field">
        <label for="krankenquote">Erwartete Krankenquote (%)</label>
        <input id="krankenquote" type="number" step="0.1" min="0" max="100" name="erwarteteKrankenquote" required>
      </div>

      <div class="field">
        <label for="sollstunden">Soll-Stunden</label>
        <input id="sollstunden" type="number" step="1" min="1" name="sollStunden" required>
      </div>

      <div class="field">
        <label for="toleranz">Toleranz (%)</label>
        <input id="toleranz" type="number" step="0.1" min="0" max="100" name="toleranz" required>
      </div>

      <div class="actions-vertical left">
        <button type="submit" class="btn primary">Speichern</button>
        <button type="button" class="btn" onclick="window.history.back()">Zurück</button>
      </div>
    </form>

  </div>
</div>

</body>

