<!-- Standardseite, noch ohne Inhalt. Selber geschrieben -->
<?php
require_once 'bb_auth.php';
include_once 'bb_db.php';
include_once 'cc_vorgabenAuftraggeber.php';

rolle_erforderlich(ROLLE_PROJEKTLEITUNG);
modus_aus_url_setzen();
$benutzer = aktueller_benutzer();

// Aktuelles Jahr + Quartal bestimmen
$heute   = new DateTime();
$jahr    = (int)$heute->format("Y");
$monat   = (int)$heute->format("n");
$quartal = ceil($monat / 3);

// Vorgaben für aktuelles Quartal laden
$vorgabe =  CVorgabenAuftraggeber::LoadByJahrQuartal($pdo, $jahr, $quartal);


?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Hauptmenü Projektleitung</title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>
  <h1>Hauptmenü Projektleitung</h1>
  <p>Angemeldet: <?= htmlspecialchars($benutzer['name']) ?> (<?= htmlspecialchars($benutzer['rolle']) ?>)</p>
  <nav>
    <a href="bb_ausloggen.php">Abmelden</a>
    <?= modus_navigation() ?>
  </nav>
  <main>
    <div class="menu-links">
      <a class="btn primary" href="dd_vorgaben_anlegen.php">Auftraggeber Vorgaben anlegen</a>  
      <a class="btn primary" href="dd_nutzer_anlegen_projektleitung.php">Nutzer anlegen</a>
      <a class="btn primary" href="dd_nutzer_aktualisieren_projektleitung.php">Nutzer aktualisieren</a>
      <a class="btn primary" href="dd_erfassung_projektleitung.php">Arbeitszeit & Urlaub erfassen</a>
      <a class="btn primary" href="dd_freigaben_projektleitung.php">Arbeitszeit & Urlaub freigeben</a>
      <a class="btn primary" href="dd_monatsuebersicht_projektleitung.php">Monatsübersicht</a>
    </div>

    <?php if ($vorgabe === null): ?>
      <section class="monatsueberblick" aria-labelledby="quartal-ueberblick-title">
        <h2 id="quartal-ueberblick-title">Quartalsüberblick</h2>
        <p>Für dieses Quartal (<?= $quartal ?>/<?= $jahr ?>) liegen keine Vorgaben vor.</p>
      </section>
    <?php else: 
        $toleranz = $vorgabe->Toleranzbereich();
        $arbeitstageGesamt    = $vorgabe->berechneArbeitstageMitFeiertagen($jahr, $quartal);
        $arbeitstageVergangen = $vorgabe->berechneVergangeneArbeitstageImQuartal($jahr, $quartal);
        $anteilTage           = $vorgabe->prozentualeVergangeneArbeitstageImQuartal($jahr, $quartal);
        $bedarf               = $vorgabe->BedarfPlanstundenBisEndeQuartal();
    ?>
      <section class="monatsueberblick" aria-labelledby="quartal-ueberblick-title">
        <h2 id="quartal-ueberblick-title">Quartalsüberblick</h2>

        <!-- Sollstunden & Toleranz -->
        <article class="tile" aria-label="Sollstunden im Quartal">
          <figure>
            <div class="tile-icon">📊</div>
            <figcaption>Sollstunden im Quartal</figcaption>
          </figure>

          <p class="tile-value">
            <output><?= $vorgabe->GetSollStunden() ?></output>
            <span class="unit">Std</span>
          </p>
          <p class="tile-note">
            Toleranzbereich:
            <?= $toleranz['min'] ?>–<?= $toleranz['max'] ?> Std
          </p>
        </article>

        <!-- Iststunden & Erfüllung der Sollstunden -->
        <article class="tile" aria-label="Iststunden und Zielerreichung">
          <figure>
            <div class="tile-icon">⏱️</div>
            <figcaption>Iststunden im Quartal</figcaption>
          </figure>

          <p class="tile-value">
            <output><?= $vorgabe->GetIstStunden() ?></output>
            <span class="unit">Std</span>
          </p>

          <p class="tile-note">
            Zielerreichung:
            <?= $vorgabe->GetAnteilIstStunden() ?> %
          </p>

          <meter
            min="0"
            max="100"
            value="<?= $vorgabe->GetAnteilIstStunden() ?>"
            aria-label="Prozentuale Zielerreichung der Sollstunden im Quartal">
          </meter>
        </article>

        <!-- Zeitfortschritt im Quartal -->
        <article class="tile" aria-label="Zeitfortschritt im Quartal">
          <figure>
            <div class="tile-icon">📅</div>
            <figcaption>Zeitfortschritt im Quartal</figcaption>
          </figure>

          <p class="tile-value">
            <output><?= $arbeitstageVergangen ?></output>
            <span class="unit">von <?= $arbeitstageGesamt ?> Arbeitstagen</span>
          </p>

          <p class="tile-note">
            <?= number_format($anteilTage, 1, ',', '') ?> % der Arbeitstage sind bereits vergangen.
          </p>

          <meter
            min="0"
            max="100"
            value="<?= number_format($anteilTage, 1, '.', '') ?>"
            aria-label="Anteil der bereits vergangenen Arbeitstage im Quartal">
          </meter>
        </article>

        <!-- Erforderliche Planstunden bis Quartalsende -->
        <article class="tile" aria-label="Erforderliche Planstunden bis Quartalsende">
          <figure>
            <div class="tile-icon">🧮</div>
            <figcaption>Erforderliche Planstunden bis Quartalsende</figcaption>
          </figure>

          <p class="tile-value">
            <span class="label">Minimum:</span>
            <output><?= number_format($bedarf['bis_min'], 2, ',', '') ?></output>
            <span class="unit">Std</span>
          </p>

          <p class="tile-value">
            <span class="label">Maximum:</span>
            <output><?= number_format($bedarf['bis_max'], 2, ',', '') ?></output>
            <span class="unit">Std</span>
          </p>
        </article>

      </section>
    <?php endif; ?>
  </main>
