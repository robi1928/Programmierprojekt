<!-- Mischung aus selber geschrieben und ChatGPT. Einzeln gekennzeichnet -->
<!-- Absatz von ChatGTP. Prüfen, ob wirklich sinnvoll, weil wir es ja nicht "wirklich" nutzen -->
<?php
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax'); // Cross-Site-Anfragen eingeschränkt
if (!empty($_SERVER['HTTPS'])) { ini_set('session.cookie_secure', '1'); } // Session nur per HTTPS, falls vorhanden

// selber geschrieben mit stack overflow.
session_start(); // Session starten oder wieder aufnehmen
require_once __DIR__ . '/db.php'; // autoloader der beiden wichtigen Dinge. Datenbank und Darkmode
require_once __DIR__ . '/darkmode.php';

// kopiert aus anderem projekt, damals selber geschrieben
// Modus aus URL übernehmen (hell/dunkel/auto)
modus_aus_url_setzen();
$attr = html_modus_attribut();

// vorgeschlagen von ChatGTP
// Wenn schon eingeloggt → direkt weiter ins Routing
if (isset($_SESSION['benutzer'])) {
    header('Location: route.php');
    exit;
}

// stackoverflow kommentar kopiert, dann selber angepasst
// Aktive Benutzer laden für dropdown
$stmt = $pdo->query("
  SELECT b.benutzer_id, b.vorname, b.nachname, r.rollen_schluessel
  FROM benutzer b
  JOIN rollen r ON r.rollen_id = b.rollen_id
  WHERE b.aktiv = 1
  ORDER BY b.nachname, b.vorname
");
$benutzerListe = $stmt->fetchAll(); // Ergebnis der SQL-Abfrage: alle aktiven Nutzer als Array
?>

<!-- selber geschrieben -->
<!doctype html>
<html lang="de"<?= $attr ?>> <!-- attr = modus -->
<head>
  <meta charset="utf-8">
  <title>Login · Arbeitszeitplanung</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- muss noch dringend angepasst werden, wenn klar ist, wie mobilversion gemacht werden soll (Pixel, skaliert etc) -->
        <!-- globales Stylesheet für Layout und Hell/Dunkel-Modus -->
  <link rel="stylesheet" href="aussehen.css?v=1">
</head>
<body>
  <main class="container">
    <header class="header">
            <!-- Seitentitel -->
      <h1>Arbeitszeitplanung – Anmeldung</h1>
      <?= modus_navigation() ?>  <!-- hell/dunkel für navigation -->
    </header>
      <!-- Login-Formular: Auswahl eines Profils -->
      <form class="card form" method="post" action="einloggen.php" novalidate> <!-- class aus css. Übertragung via http und nicht url. action = Zielskript. Novalidate = Fehlerprüfung im PHP, dadurch auch bei fehlendem/leeren Feld Formularsendung)  -->
        <div class="field">
          <label for="benutzer_id">Profil auswählen</label>
          <select id="benutzer_id" name="benutzer_id" required>
            <option value="">Bitte wählen</option>
                  <!-- lädt alles aus der db mit wert und was angezeigt wird. hmtlspecialchards damit Sonderzeichen nicht als html fehlintepretiert -->
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
