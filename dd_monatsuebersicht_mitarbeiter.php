<?php
require_once __DIR__ . '/bb_auth.php';

login_erforderlich();
$benutzer = aktueller_benutzer();
$benutzer_id = $benutzer['id'] ?? $benutzer['benutzer_id'] ?? null;
if (!$benutzer_id) {
    die("Fehler: Benutzer-ID nicht in der Session gefunden.");
}
// -------------------
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Verbindung fehlgeschlagen: " . $conn->connect_error);
}

// -------------------
// Aktuelles Quartal berechnen
// -------------------
$heute = new DateTime();
$jahr = $heute->format("Y");
$monat = (int)$heute->format("n");
$quartal = ceil($monat / 3);

$quartal_start_monat = (($quartal - 1) * 3) + 1;
$quartal_start = new DateTime("$jahr-$quartal_start_monat-01");
$quartal_ende = clone $quartal_start;
$quartal_ende->modify("+3 months")->modify("-1 day");

// -------------------
// Feiertage
// -------------------
function getFeiertage($jahr) {
    $feiertage = [
        "$jahr-01-01", "$jahr-05-01", "$jahr-10-03", "$jahr-12-25", "$jahr-12-26",
    ];
    $ostersonntag  = date("Y-m-d", easter_date($jahr));
    $feiertage[] = date("Y-m-d", strtotime("$ostersonntag -2 days")); // Karfreitag
    $feiertage[] = date("Y-m-d", strtotime("$ostersonntag +1 day")); // Ostermontag
    $feiertage[] = date("Y-m-d", strtotime("$ostersonntag +39 days")); // Himmelfahrt
    $feiertage[] = date("Y-m-d", strtotime("$ostersonntag +50 days")); // Pfingstmontag
    return $feiertage;
}
$feiertage = getFeiertage($jahr);

// -------------------
// Hilfsfunktion: Summen aus DB holen (nur eigener Benutzer)
// -------------------
function getSummen($conn, $datumSql, $benutzer_id) {
    $result = ["stunden" => 0, "urlaub" => 0, "krank" => 0];

    // Gearbeitete Stunden NUR eigener Benutzer
    $sql = "
        SELECT SUM(z.stunden) AS summe
        FROM zeiteintraege z
        JOIN stundenzettel s ON z.stundenzettel_id = s.stundenzettel_id
        WHERE s.jahr = YEAR(?) AND s.monat = MONTH(?) AND z.tag = DAY(?) AND s.benutzer_id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $datumSql, $datumSql, $datumSql, $benutzer_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $result["stunden"] = $res["summe"] ?? 0;
    $stmt->close();

    // Urlaubstage NUR eigener Benutzer
    $sql = "
        SELECT COUNT(*) AS cnt FROM urlaubsantraege
        WHERE status = 'genehmigt'
          AND benutzer_id = ?
          AND start_datum <= ?
          AND ende_datum >= ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iss", $benutzer_id, $datumSql, $datumSql);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $result["urlaub"] = $res["cnt"] ?? 0;
    $stmt->close();

    // Krank (Platzhalter, nur eigener Benutzer)
    $result["krank"] = 0;

    return $result;
}

?>
<!doctype html>
<html lang="de"<?= html_modus_attribut() ?>>
<head>
  <meta charset="utf-8">
  <title>Monatsübersicht </title>
  <link rel="stylesheet" href="aa_aussehen.css">
</head>
<body>

  <nav>
    <a href="bb_route.php">Zurück zum Hauptmenü</a>
    <?= modus_navigation() ?>
  </nav>
  </body>
  </html>
<?php

// -------------------
// Tabelle ausgeben
// -------------------
echo "<h1>Eigene Monatsübersicht – Quartal 0$quartal/$jahr</h1>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr style='background-color:#f0f0f0; font-weight:bold;'>
        <th>Datum</th>
        <th>Stunden</th>
        <th>Urlaubstage</th>
        <th>Krank</th>
      </tr>";

$monat_summen = ["stunden" => 0, "urlaub" => 0, "krank" => 0];
$quartal_summen = ["stunden" => 0, "urlaub" => 0, "krank" => 0];

$aktueller_monat = (int)$quartal_start->format("n");

for ($d = clone $quartal_start; $d <= $quartal_ende; $d->modify("+1 day")) {
    $datumSql     = $d->format("Y-m-d");
    $datumAnzeige = $d->format("d.m.Y");
    $wochentag    = $d->format("N"); // 6=Sa, 7=So

    $werte = getSummen($conn, $datumSql, $benutzer_id);

    $monat_summen["stunden"] += $werte["stunden"];
    $monat_summen["urlaub"]  += $werte["urlaub"];
    $monat_summen["krank"]   += $werte["krank"];

    $quartal_summen["stunden"] += $werte["stunden"];
    $quartal_summen["urlaub"]  += $werte["urlaub"];
    $quartal_summen["krank"]   += $werte["krank"];

    $klasse = ($wochentag >= 6 || in_array($datumSql, $feiertage)) ? "style='background-color:#ddd;'" : '';

    echo "<tr $klasse>
            <td>{$datumAnzeige}</td>
            <td>{$werte["stunden"]}</td>
            <td>{$werte["urlaub"]}</td>
            <td>{$werte["krank"]}</td>
          </tr>";

    $nextTag = clone $d;
    $nextTag->modify("+1 day");

    $monatWechsel = ((int)$nextTag->format("n") !== $aktueller_monat) || ($d == $quartal_ende);

    if ($monatWechsel) {
        echo "<tr style='font-weight:bold; background-color:#eef;'>
                <td>Summe Monat {$aktueller_monat}.{$jahr}</td>
                <td>{$monat_summen["stunden"]}</td>
                <td>{$monat_summen["urlaub"]}</td>
                <td>{$monat_summen["krank"]}</td>
              </tr>";
        echo "<tr><td colspan='4'>&nbsp;</td></tr>";
        $monat_summen = ["stunden" => 0, "urlaub" => 0, "krank" => 0];
        $aktueller_monat = (int)$nextTag->format("n");
    }
}

echo "<tr style='font-weight:bold; background-color:#cfc;'>
        <td>Summe Quartal {$quartal}/{$jahr}</td>
        <td>{$quartal_summen["stunden"]}</td>
        <td>{$quartal_summen["urlaub"]}</td>
        <td>{$quartal_summen["krank"]}</td>
      </tr>";

echo "</table>";

$conn->close();
?>