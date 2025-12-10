<?php
require_once __DIR__ . '/bb_auth.php';
require_once 'bb_db.php';
require_once 'cc_stundenzettel.php';
require_once 'cc_urlaubsantraege.php';
require_once 'cc_urlaubskonten.php';
rolle_erforderlich(ROLLE_MITARBEITER);
modus_aus_url_setzen();

$aktuellerBenutzerId = (int)$_SESSION['benutzer']['id'];
$benutzer            = aktueller_benutzer();

$meldungOk    = null;
$meldungFehler = null;

// POST-Handling: Freigabe/Ablehnung
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $typ         = $_POST['typ']         ?? '';
    $entscheidung = $_POST['entscheidung'] ?? '';
    $id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $bemerkung   = $_POST['bemerkung']   ?? null;

    try {
        if ($id <= 0) {
            throw new RuntimeException('Ungültige ID.');
        }

        if ($typ === 'stundenzettel') {
            CStundenzettelRepository::freigabeDurchMitarbeiter(
                $pdo,
                $id,
                $aktuellerBenutzerId,
                $entscheidung
            );
            $meldungOk = 'Stundenzettel wurde erfolgreich verarbeitet.';
        } elseif ($typ === 'urlaub') {
          CUrlaubsantragRepository::freigabeDurchMitarbeiter(
              $pdo,
              $id,
              $aktuellerBenutzerId,
              $entscheidung,
              $bemerkung
          );
            $meldungOk = 'Urlaubsantrag wurde erfolgreich verarbeitet.';
        } else {
            throw new RuntimeException('Unbekannter Datentyp.');
        }
    } catch (Throwable $e) {
        $meldungFehler = $e->getMessage();
    }
}

// Offene Freigaben (Stundenzettel) für diesen Mitarbeiter laden
$sqlStz = "
    SELECT
        sz.stundenzettel_id,
        sz.monat,
        sz.jahr,
        sz.soll_stunden,
        sz.ist_stunden,
        sz.urlaub_gesamt,
        sz.eingereicht_am,
        e.vorname AS einreicher_vorname,
        e.nachname AS einreicher_nachname
    FROM stundenzettel sz
    JOIN benutzer b_mitarbeiter
      ON b_mitarbeiter.benutzer_id = sz.benutzer_id
    JOIN benutzer e
      ON e.benutzer_id = sz.eingereicht_von
    JOIN rollen r_m
      ON r_m.rollen_id = b_mitarbeiter.rollen_id
    JOIN rollen r_e
      ON r_e.rollen_id = e.rollen_id
    WHERE sz.benutzer_id = :uid
      AND sz.status      = 'entwurf'
      AND sz.eingereicht_am IS NOT NULL
      AND r_m.rollen_schluessel = 'Mitarbeiter'
      AND r_e.rollen_schluessel IN ('Teamleitung','Projektleitung')
    ORDER BY sz.jahr DESC, sz.monat DESC
";

$stmtStz = $pdo->prepare($sqlStz);
$stmtStz->execute([':uid' => $aktuellerBenutzerId]);
$offeneStundenzettel = $stmtStz->fetchAll(PDO::FETCH_ASSOC);

// Offene Freigaben (Urlaub) für diesen Mitarbeiter laden
$sqlUrlaub = "
    SELECT
        a.antrag_id,
        a.start_datum,
        a.ende_datum,
        a.tage,
        a.eingereicht_am,
        a.bemerkung,
        e.vorname AS einreicher_vorname,
        e.nachname AS einreicher_nachname
    FROM urlaubsantraege a
    JOIN benutzer b_mitarbeiter
      ON b_mitarbeiter.benutzer_id = a.benutzer_id
    JOIN benutzer e
      ON e.benutzer_id = a.eingereicht_von
    JOIN rollen r_m
      ON r_m.rollen_id = b_mitarbeiter.rollen_id
    JOIN rollen r_e
      ON r_e.rollen_id = e.rollen_id
    WHERE a.benutzer_id = :uid
      AND a.status      = 'entwurf'
      AND a.eingereicht_am IS NOT NULL
      AND r_m.rollen_schluessel = 'Mitarbeiter'
      AND r_e.rollen_schluessel IN ('Teamleitung','Projektleitung')
    ORDER BY a.start_datum
";

$stmtUrlaub = $pdo->prepare($sqlUrlaub);
$stmtUrlaub->execute([':uid' => $aktuellerBenutzerId]);
$offeneUrlaube = $stmtUrlaub->fetchAll(PDO::FETCH_ASSOC);

// Abgelehnte Urlaubsanträge für diesen Mitarbeiter laden
$sqlUrlaubAbgelehnt = "
    SELECT
        a.antrag_id,
        a.start_datum,
        a.ende_datum,
        a.tage,
        a.entschieden_am,
        a.bemerkung,
        e.vorname AS entscheider_vorname,
        e.nachname AS entscheider_nachname
    FROM urlaubsantraege a
    JOIN benutzer b_antragsteller
      ON b_antragsteller.benutzer_id = a.benutzer_id
    LEFT JOIN benutzer e
      ON e.benutzer_id = a.entschieden_von
    WHERE a.benutzer_id = :uid
      AND a.status      = 'abgelehnt'
    ORDER BY a.entschieden_am DESC, a.start_datum DESC
";

$stmtUrlaubAbgelehnt = $pdo->prepare($sqlUrlaubAbgelehnt);
$stmtUrlaubAbgelehnt->execute([':uid' => $aktuellerBenutzerId]);
$abgelehnteUrlaube = $stmtUrlaubAbgelehnt->fetchAll(PDO::FETCH_ASSOC);


?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Arbeitszeit & Urlaub freigeben</title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>
  <h1>Arbeitszeit & Urlaub freigeben</h1>
  <nav class="menu">
    <a class="btn" href="bb_route.php">Zurück zum Hauptmenü</a>
    <?= modus_navigation() ?>
  </nav>
 </header>

<div class="container">
    <header class="header">
        <h1>Offene Freigaben für Sie</h1>
    </header>

    <?php if ($meldungOk): ?>
        <div class="alert-ok">
            <?= htmlspecialchars($meldungOk, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if ($meldungFehler): ?>
        <div class="alert-err">
            <?= htmlspecialchars($meldungFehler, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h2>Stundenzettel</h2>

        <?php if (empty($offeneStundenzettel)): ?>
            <p class="note">Es liegen aktuell keine Stundenzettel zur Freigabe vor.</p>
        <?php else: ?>
            <table class="monatsuebersicht monatsuebersicht--compact">
                <thead>
                <tr>
                    <th>Monat/Jahr</th>
                    <th>Sollstunden</th>
                    <th>Iststunden</th>
                    <th>Urlaub</th>
                    <th>Eingereicht von</th>
                    <th>Eingereicht am</th>
                    <th>Aktion</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($offeneStundenzettel as $sz): ?>
                    <tr>
                        <td>
                            <?= (int)$sz['monat'] ?>/<?= (int)$sz['jahr'] ?>
                        </td>
                        <td><?= htmlspecialchars($sz['soll_stunden'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($sz['ist_stunden'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($sz['urlaub_gesamt'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?= htmlspecialchars($sz['einreicher_vorname'] . ' ' . $sz['einreicher_nachname'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($sz['eingereicht_am'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="typ" value="stundenzettel">
                                <input type="hidden" name="id" value="<?= (int)$sz['stundenzettel_id'] ?>">

                                <div class="actions-vertical">
                                    <button class="btn primary" type="submit" name="entscheidung" value="genehmigt">
                                        Bestätigen
                                    </button>
                                    <button class="btn" type="submit" name="entscheidung" value="abgelehnt">
                                        Ablehnen
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top:24px">
        <h2>Urlaubsanträge</h2>

        <?php if (empty($offeneUrlaube)): ?>
            <p class="note">Es liegen aktuell keine Urlaubsanträge zur Freigabe vor.</p>
        <?php else: ?>
            <table class="monatsuebersicht monatsuebersicht--compact">
                <thead>
                <tr>
                    <th>Zeitraum</th>
                    <th>Tage</th>
                    <th>Eingereicht von</th>
                    <th>Eingereicht am</th>
                    <th>Bemerkung</th>
                    <th>Aktion</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($offeneUrlaube as $ua): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($ua['start_datum'], ENT_QUOTES, 'UTF-8') ?>
                            &ndash;
                            <?= htmlspecialchars($ua['ende_datum'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td><?= htmlspecialchars($ua['tage'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?= htmlspecialchars($ua['einreicher_vorname'] . ' ' . $ua['einreicher_nachname'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td><?= htmlspecialchars($ua['eingereicht_am'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if (!empty($ua['bemerkung'])): ?>
                                <?= htmlspecialchars($ua['bemerkung'], ENT_QUOTES, 'UTF-8') ?>
                            <?php else: ?>
                                <span class="note">Keine Bemerkung hinterlegt.</span>
                            <?php endif; ?>
                        </td>

                        <td>
                            <form method="post">
                                <input type="hidden" name="typ" value="urlaub">
                                <input type="hidden" name="id" value="<?= (int)$ua['antrag_id'] ?>">

                                <div class="actions-vertical">
                                    <button class="btn primary" type="submit" name="entscheidung" value="genehmigt">
                                        Bestätigen
                                    </button>
                                    <button class="btn" type="submit" name="entscheidung" value="abgelehnt">
                                        Ablehnen
                                    </button>
                                </div>
                            </form>
                        </td>

                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <div class="card" style="margin-top:24px">
        <h2>Abgelehnte Urlaubsanträge</h2>
        <p class="note">
            Wenn Sie Fragen zu einer Ablehnung haben, sprechen Sie bitte die Person an,
            die Ihren Antrag abgelehnt hat.
        </p>

        <?php if (empty($abgelehnteUrlaube)): ?>
            <p class="note">Es liegen aktuell keine abgelehnten Urlaubsanträge vor.</p>
        <?php else: ?>
            <table class="monatsuebersicht monatsuebersicht--compact">
                <thead>
                <tr>
                    <th>Zeitraum</th>
                    <th>Tage</th>
                    <th>Abgelehnt von</th>
                    <th>Abgelehnt am</th>
                    <th>Bemerkung</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($abgelehnteUrlaube as $ua): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($ua['start_datum'], ENT_QUOTES, 'UTF-8') ?>
                            &ndash;
                            <?= htmlspecialchars($ua['ende_datum'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td><?= htmlspecialchars($ua['tage'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if (!empty($ua['entscheider_vorname']) || !empty($ua['entscheider_nachname'])): ?>
                                <?= htmlspecialchars(
                                    trim($ua['entscheider_vorname'] . ' ' . $ua['entscheider_nachname']),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            <?php else: ?>
                                <span class="note">Keine Person hinterlegt.</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($ua['entschieden_am'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?php if (!empty($ua['bemerkung'])): ?>
                                <?= htmlspecialchars($ua['bemerkung'], ENT_QUOTES, 'UTF-8') ?>
                            <?php else: ?>
                                <span class="note">Keine Bemerkung hinterlegt.</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

</div>
</body>
</html>