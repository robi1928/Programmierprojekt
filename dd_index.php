<!-- Mischung aus selber geschrieben und ChatGPT. Einzeln gekennzeichnet -->
<?php
require_once __DIR__ . '/bb_auth.php';
// kopiert aus anderem projekt, damals selber geschrieben
gast_erforderlich('bb_route.php');   // schon eingeloggt?, dann weiterleiten
// Modus aus URL übernehmen (hell/dunkel/auto)
modus_aus_url_setzen();
$attr = html_modus_attribut();
?>

<!-- selber geschrieben -->
<!doctype html>
<html lang="de"<?= $attr ?>> <!-- attr = modus -->
<head>
  <meta charset="utf-8">
  <title>Login · Arbeitszeitplanung</title>
  <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- muss noch dringend angepasst werden, wenn klar ist, wie mobilversion gemacht werden soll (Pixel, skaliert etc) -->
        <!-- globales Stylesheet für Layout und Hell/Dunkel-Modus -->
  <link rel="stylesheet" href="aa_aussehen.css?v=1">
</head>
<body>
  <main class="container">
    <header class="header">
            <!-- Seitentitel -->
      <h1>Arbeitszeitplanung – Anmeldung</h1>
      <?= modus_navigation() ?>  <!-- hell/dunkel für navigation -->
    </header>
      <!-- Login-Formular: Auswahl eines Profils -->
      <form class="card form" method="post" action="bb_einloggen.php" novalidate> <!-- class aus css. Übertragung via http und nicht url. action = Zielskript. Novalidate = Fehlerprüfung im PHP, dadurch auch bei fehlendem/leeren Feld Formularsendung)  -->
        <div class="field">
          <label for="benutzer_id">Profil auswählen</label>
          <select id="benutzer_id" name="benutzer_id" required>
            <option value="" disabled selected>Bitte wählen…</option>
          </select>
        </div>
            <!-- Absende-Button: schickt Auswahl per POST an login.php -->
        <button class="btn primary" type="submit">Anmelden</button>
      </form>
    </main>
    <!-- JS notwendig, weil die Seite nicht mehr direkt auf die Datenbank zugreifen soll. Evtl gibt es besseren Weg -->
      <script>
        (async () => {
          try {
            const res = await fetch('bb_einloggen.php?action=benutzer_liste', {credentials: 'same-origin'});
            if (!res.ok) throw new Error('Fehler beim Laden');
            const list = await res.json();
            const select = document.getElementById('benutzer_id');
            if (!Array.isArray(list) || list.length === 0) {
              select.innerHTML = '<option value="" disabled>Keine aktiven Benutzer</option>';
              return;
            }
            const options = list.map(u =>
              `<option value="${u.id}">${u.label} – ${u.rolle}</option>`
            ).join('');
            select.insertAdjacentHTML('beforeend', options);
          } catch (err) {
            console.error(err);
            document.getElementById('benutzer_id').innerHTML =
              '<option value="" disabled>Fehler beim Laden</option>';
          }
        })();
    </script>
  </body>
  </html>
