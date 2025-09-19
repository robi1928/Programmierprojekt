<?php
// muss nach session_start() eingebunden sein

/** Prüft, ob in der URL (?theme=…) ein Theme-Parameter übergeben wurde.
 * Zulässige Werte: 'light', 'dark', 'auto'.
 * Wenn gültig, wird der Wert in die Session geschrieben, damit die Auswahl auch auf anderen Seiten erhalten bleibt.*/
function set_theme_from_get(): void {
  if (isset($_GET['theme']) && in_array($_GET['theme'], ['light','dark','auto'], true)) {
    $_SESSION['theme'] = $_GET['theme'];
  }
}

// Liefert ein Attribut-Fragment für das <html>-Tag zurück, das das aktuell gewählte Theme erzwingt. Auto = Browser entscheidt nach Systemeinstellungen
function theme_attr(): string {
  $theme = $_SESSION['theme'] ?? 'auto';
  return ($theme === 'auto') ? '' : ' data-theme="'.$theme.'"';
}

/**
 * Baut die kleinen Buttons zum Umschalten des Themes (oben rechts). Dabei wird das aktuell aktive Theme mit der Klasse "active" markiert.
 * (Das automatische Theme "auto" wird nicht direkt angezeigt, ist aber der Default, wenn nichts gewählt wurde.) */
function theme_nav(): string {
  $t = $_SESSION['theme'] ?? 'auto';
  return '
  <div class="theme-switch" role="toolbar" aria-label="Theme umschalten">
    <a class="theme-icon'.($t==='light'?' active':'').'" href="?theme=light" title="Light" aria-label="Light">☀️</a>
    <a class="theme-icon'.($t==='dark'?' active':'').'"  href="?theme=dark"  title="Dark"  aria-label="Dark">🌙</a>
  </div>';
}
