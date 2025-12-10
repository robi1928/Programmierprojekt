<!-- Selber geschrieben, bzw kopiert aus anderem Projekt. Aria label erstmal übernommen. Am Ende schauen, ob Barrierefreiheit übernommen wird -->
<?php

/** Prüft, ob in der URL (?modus=…) ein Modus-Parameter übergeben wurde.
 * Zulässige Werte: hell, dunkel, auto. Bei Gültigkeit wird in die Session geschrieben. */
function modus_aus_url_setzen(): void {
    if (isset($_GET['modus']) && in_array($_GET['modus'], ['hell','dunkel','auto'], true)) {
        $_SESSION['modus'] = $_GET['modus'];
    }
}

/** Liefert das Attribut-Fragment für <html>, das den aktuellen Modus erzwingt.
 * 'auto' → kein Attribut. 'hell'/'dunkel' → Mapping auf CSS-Tokens 'light'/'dark'. */
function html_modus_attribut(): string {
    $modus = $_SESSION['modus'] ?? 'auto';
    if ($modus === 'auto') {
        return '';
    }
    $map = ['hell' => 'light', 'dunkel' => 'dark'];
    $token = $map[$modus] ?? 'light';
    return ' data-theme="' . $token . '"';
}

/** Baut die Umschalt-Buttons (oben rechts). Aktiver Modus erhält Klasse "active".
 * 'auto' wird nicht angezeigt, ist aber Standard. */
function modus_navigation(): string {
    $aktuellerModus = $_SESSION['modus'] ?? 'auto';
    return '
  <div class="modus-wechsel" role="toolbar" aria-label="Darstellungsmodus umschalten">
    <a class="modus-icon' . ($aktuellerModus === 'hell' ? ' active' : '') . '" href="?modus=hell"    title="hell"    aria-label="hell">☀️</a>
    <a class="modus-icon' . ($aktuellerModus === 'dunkel' ? ' active' : '') . '" href="?modus=dunkel" title="dunkel" aria-label="dunkel">🌙</a>
  </div>';
}

