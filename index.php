<?php
ini_set('session.cookie_httponly','1');
ini_set('session.cookie_samesite','Lax'); // Prod ggf. 'Strict'
if (!empty($_SERVER['HTTPS'])) ini_set('session.cookie_secure','1');

session_start();
require_once __DIR__.'/db.php';

// Theme aus URL übernehmen (light/dark), Standard = auto
if (isset($_GET['theme']) && in_array($_GET['theme'], ['light','dark'], true)) {
  $_SESSION['theme'] = $_GET['theme'];
}
$theme = $_SESSION['theme'] ?? 'auto';
$attr  = ($theme === 'auto') ? '' : ' data-theme="'.$theme.'"';

// Wenn schon eingeloggt → direkt weiter ins Routing
if (isset($_SESSION['user'])) { header('Location: route.php'); exit; }

// Liste aller aktiven Nutzer laden
$stmt = $pdo->query("
  SELECT u.user_id, u.first_name, u.last_name, r.role_key
  FROM users u JOIN roles r ON r.role_id=u.role_id
  WHERE u.is_active=1
  ORDER BY u.last_name, u.first_name
");
$users = $stmt->fetchAll(); // Ergebnis der SQL-Abfrage: alle aktiven Nutzer als Array
?>
<!doctype html>
<html lang="de"<?= $attr ?>>
  <head>
    <meta charset="utf-8">
    <title>Login · Arbeitszeitplanung</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
        <!-- globales Stylesheet für Layout und Dark/Light-Mode -->
    <link rel="stylesheet" href="style.css?v=1">
  </head>
  <body>
    <main class="container">
      <header class="header">
            <!-- Seitentitel -->
        <h1>Arbeitszeitplanung – Anmeldung</h1>
                <!-- Umschalter für Light/Dark Theme (oben rechts) -->
        <nav class="theme-switch">
          <a class="theme-icon<?= ($theme==='light'?' active':'') ?>" href="?theme=light" title="Light">☀️</a>
          <a class="theme-icon<?= ($theme==='dark'?' active':'') ?>"  href="?theme=dark"  title="Dark">🌙</a>
        </nav>
      </header>
      <!-- Login-Formular: Auswahl eines Profils -->
      <form class="card form" method="post" action="login.php" novalidate>
        <div class="field">
          <label for="user_id">Profil auswählen</label>
          <select id="user_id" name="user_id" required>
            <option value="">Bitte wählen</option>
            <?php foreach ($users as $u): ?>
              <option value="<?= (int)$u['user_id'] ?>">
                <?= htmlspecialchars($u['last_name'].', '.$u['first_name'].' · '.$u['role_key']) ?>
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
