<!-- Selber geschrieben -->
<?php
require_once 'bb_auth.php';
include_once 'cc_benutzer.php';
include_once 'cc_urlaubskonten.php';
include_once 'cc_stundenzettel.php';
rolle_erforderlich(ROLLE_MITARBEITER);
modus_aus_url_setzen();

$benutzer   = aktueller_benutzer();
$benutzerId = (int)$benutzer['id'];
$jahr = (int)date('Y');
$monat = (int)date('n');

$BenutzerObjekt = new CBenutzer($benutzerId);
$BenutzerObjekt->Load();
$sollstunden = $BenutzerObjekt->GetSollStundenAktuellerMonat();

$konto      = CUrlaubskonto::loadForUserYear($pdo, $benutzerId, $jahr);
$resturlaub = $konto ? $konto->getVerfuegbar() : 0;

$iststunden = holeIststundenAktuellerMonat($pdo, $benutzerId, $monat, $jahr);

?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Hauptmenü Mitarbeiter</title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>
  <h1>Hauptmenü Mitarbeiter</h1>
  <p>Angemeldet: <?= htmlspecialchars($benutzer['name']) ?> (<?= htmlspecialchars($benutzer['rolle']) ?>)</p>
  <nav>
    <a href="bb_ausloggen.php">Abmelden</a>
    <?= modus_navigation() ?>
  </nav>
 </header>

  <main>
  <div class="menu-links">
      <a class="btn primary" href="dd_erfassung_mitarbeiter.php">Arbeitszeit erfassen</a>
      <a class="btn primary" href="dd_freigaben_mitarbeiter.php">Arbeitszeit freigeben</a>
      <a class="btn primary" href="dd_monatsuebersicht_mitarbeiter.php">Eigene Monatsübersicht</a>
  </div>

 <section class="monatsueberblick" aria-labelledby="kompakt-title">
    <h2 id="kompakt-title">Dein kompakter Monatsüberblick</h2>

    <!-- Urlaub -->
    <article class="tile" aria-label="Verbleibende Urlaubstage">
        <figure>
            <div class="tile-icon">🌴</div>
            <figcaption>Urlaub verfügbar</figcaption>
        </figure>

        <p class="tile-value">
            <data value="<?= $resturlaub ?>">
                <?= $resturlaub ?>
            </data>
            <span class="unit">Tage</span>
        </p>

        <details>
          <summary>Wie wird der verfügbare Urlaub berechnet?</summary>
          <p>
            Der verfügbare Urlaub ergibt sich aus:
          </p>
          <ul>
            <li>dem <strong>Jahresurlaubsanspruch</strong>,</li>
            <li>einem möglichen <strong>Übertrag aus dem Vorjahr</strong>,</li>
            <li>abzüglich aller bereits <strong>genehmigten oder genommenen Urlaubstage</strong>.</li>
          </ul>
          <p>
            Urlaubsanträge stellst du im Bereich „Arbeitszeit erfassen“. Nicht genommener Urlaub
            wird, soweit zulässig, automatisch in das nächste Jahr übertragen.
          </p>
        </details>
    </article>

    <!-- Sollstunden -->
    <article class="tile" aria-label="Sollstunden aktueller Monat">
        <figure>
            <div class="tile-icon">⏱️</div>
            <figcaption>Sollstunden in diesem Monat</figcaption>
        </figure>

        <p class="tile-value">
            <output><?= $sollstunden ?></output>
            <span class="unit">Std</span>
        </p>

        <meter min="0" max="<?= $sollstunden ?>" value="<?= $iststunden ?>"></meter>

        <details>
          <summary>Wie entstehen die Sollstunden?</summary>
          <p>
            Deine Sollstunden werden automatisch berechnet auf Basis von:
          </p>
          <ul>
            <li>den hinterlegten <strong>Wochenstunden</strong>,</li>
            <li>den <strong>Arbeitstagen des Monats</strong> (Montag–Freitag),</li>
            <li>den <strong>gesetzlichen Feiertagen</strong> von SH.</li>
          </ul>
          <p>
            Änderungen deiner Wochenstunden wirken sich automatisch auf künftige Monate aus.
          </p>
        </details>
    </article>

  </section>