<?php
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax'); // Prod ggf. 'Strict'
if (!empty($_SERVER['HTTPS'])) { ini_set('session.cookie_secure', '1'); }

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/darkmode.php';

// Modus aus URL übernehmen (hell/dunkel/auto)
modus_aus_url_setzen();
$attr = html_modus_attribut();

// Wenn schon eingeloggt → direkt weiter ins Routing
if (isset($_SESSION['benutzer'])) {
    header('Location: route.php');
    exit;
}

// Aktive Benutzer laden
$stmt = $pdo->query("
  SELECT b.benutzer_id, b.vorname, b.nachname, r.rollen_schluessel
  FROM benutzer b
  JOIN rollen r ON r.rollen_id = b.rollen_id
  WHERE b.aktiv = 1
  ORDER BY b.nachname, b.vorname
");
$benutzerListe = $stmt->fetchAll(); // Ergebnis der SQL-Abfrage: alle aktiven Nutzer als Array
?>
<!doctype html>
<html lang="de"<?= $attr ?>>
<head>
  <meta charset="utf-8">
  <title>Login · Arbeitszeitplanung</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- globales Stylesheet für Layout und Hell/Dunkel-Modus -->
  <link rel="stylesheet" href="aussehen.css?v=1">
</head>
<body>
  <main class="container">
    <header class="header">
            <!-- Seitentitel -->
      <h1>Arbeitszeitplanung – Anmeldung</h1>
      <?= modus_navigation() ?>
    </header>
      <!-- Login-Formular: Auswahl eines Profils -->
      <form class="card form" method="post" action="einloggen.php" novalidate>
        <div class="field">
          <label for="benutzer_id">Profil auswählen</label>
          <select id="benutzer_id" name="benutzer_id" required>
            <option value="">Bitte wählen</option>
            <?php foreach ($benutzerListe as $b): ?>
              <option value="<?= (int)$b['benutzer_id'] ?>">
                <?= htmlspecialchars($b['nachname'] . ', ' . $b['vorname'] . ' · ' . $b['rollen_schluessel']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
            <!-- Absende-Button: schickt Auswahl per POST an login.php -->
        <button class="btn primary" type="submit">Anmelden</button>
      </form>
    </main>
  </body>
  </html>