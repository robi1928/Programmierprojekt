<?php
require_once 'bb_auth.php';
rolle_erforderlich(ROLLE_TEAMLEITUNG);
modus_aus_url_setzen();
include_once 'cc_benutzer.php';
include_once 'bb_db.php';

// Alle Benutzer für das Auswahlfeld laden
$benutzer_liste = [];
try {
    $statement = $pdo->prepare("SELECT benutzer_id, vorname, nachname, email FROM benutzer ORDER BY nachname, vorname");
    $statement->execute();
    $benutzer_liste = $statement->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo "Fehler beim Laden der Benutzerdaten.";
    exit;
}

// Welche ID ist selektiert?
$ausgewaehlte_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$Benutzer = null;
if ($ausgewaehlte_id) {
    $Benutzer = new CBenutzer($ausgewaehlte_id);
    if (!$Benutzer->Load()) {
        echo "Benutzer nicht gefunden.";
        exit;
    }
}
?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Nutzer aktualisieren</title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>
  <h1>Nutzer aktualisieren</h1>
  <nav>
    <a href="bb_route.php">Zurück zum Hauptmenü</a>
    <?= modus_navigation() ?>
  </nav>

  <!-- Dropdown-Menü zur Auswahl des Nutzers -->
  <form method="get" action="dd_nutzer_aktualisieren_teamleitung.php">
    <label for="benutzer_select">Wähle einen Benutzer:</label>
    <select id="benutzer_select" name="id" onchange="this.form.submit()">
      <option value="">-- Bitte auswählen --</option>
      <?php foreach ($benutzer_liste as $row): ?>
        <option value="<?= htmlspecialchars($row['benutzer_id']) ?>" <?= ($ausgewaehlte_id == $row['benutzer_id']) ? 'selected' : '' ?>>
          <?= htmlspecialchars($row['nachname'] . ", " . $row['vorname'] . " (" . $row['email'] . ")") ?>
        </option>
      <?php endforeach; ?>
    </select>
  </form>

  <?php if ($Benutzer): ?>
  <form action="dd_nutzer_aktualisiert_teamleitung.php" method="post">
    <input type="hidden" name="id" value="<?= htmlspecialchars($Benutzer->GetID()) ?>">
    <table>
      <tr><td>Vorname:</td><td><input type="text" name="vorname" value="<?= htmlspecialchars($Benutzer->GetVorname()) ?>" required></td></tr>
      <tr><td>Nachname:</td><td><input type="text" name="nachname" value="<?= htmlspecialchars($Benutzer->GetNachname()) ?>" required></td></tr>
      <tr><td>E-Mail:</td><td><input type="email" name="email" value="<?= htmlspecialchars($Benutzer->GetEMail()) ?>" required></td></tr>
      <tr><td>Rolle:</td>
        <td>
          <select name="rolle" required>
            <option value="1" <?= $Benutzer->GetRolle()==1?'selected':''; ?>>Mitarbeiter</option>
            <option value="2" <?= $Benutzer->GetRolle()==2?'selected':''; ?>>Teamleitung</option>
            <option value="3" <?= $Benutzer->GetRolle()==3?'selected':''; ?>>Projektleitung</option>
          </select>
        </td>
      </tr>
      <tr><td>Wochenstunden:</td><td><input type="number" name="wochenstunden" step="0.1" value="<?= htmlspecialchars($Benutzer->GetWochenstunden()) ?>" required></td></tr>
      <tr><td>Urlaubstage:</td><td><input type="number" name="urlaubstage" min="1" max="30"  required></td></tr>
      <tr><td>Einstellungsdatum:</td><td><input type="date" name="einstellungsdatum" value="<?= htmlspecialchars($Benutzer->GetEinstellungsdatum()) ?>" required></td></tr>
      <tr><td colspan="2"><button type="submit">Speichern</button></td></tr>
      <tr><td colspan="2"><button type="button" onclick="window.history.back()">Zurück</button></td></tr>
    </table>
  </form>
  <?php endif; ?>
</body>
</html>