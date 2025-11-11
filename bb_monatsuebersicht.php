<?php
// -------------------
// Datenbank-Verbindung
// -------------------
$servername = "localhost";
$username   = "root";   // Standard bei XAMPP
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

$quartal_start = new DateTime("$jahr-" . (($quartal - 1) * 3 + 1) . "-01");
$quartal_ende = clone $quartal_start;
$quartal_ende->modify("+3 months")->modify("-1 day");

// -------------------
// Feiertage Schleswig-Holstein
// -------------------
function getFeiertage($jahr) {
    $feiertage = [];

    // Feste Feiertage
    $feiertage[] = "$jahr-01-01"; // Neujahr
    $feiertage[] = "$jahr-05-01"; // Tag der Arbeit
    $feiertage[] = "$jahr-10-03"; // Tag der Deutschen Einheit
    $feiertage[] = "$jahr-12-25"; // 1. Weihnachtstag
    $feiertage[] = "$jahr-12-26"; // 2. Weihnachtstag

    // Bewegliche Feiertage (basierend auf Ostersonntag)
    $ostersonntag  = date("Y-m-d", easter_date($jahr));
    $ostermontag   = date("Y-m-d", strtotime("$ostersonntag +1 day"));
    $karfreitag    = date("Y-m-d", strtotime("$ostersonntag -2 days"));
    $himmelfahrt   = date("Y-m-d", strtotime("$ostersonntag +39 days"));
    $pfingstmontag = date("Y-m-d", strtotime("$ostersonntag +50 days"));

    $feiertage[] = $karfreitag;
    $feiertage[] = $ostermontag;
    $feiertage[] = $himmelfahrt;
    $feiertage[] = $pfingstmontag;

    return $feiertage;
}

$feiertage = getFeiertage($jahr);

// -------------------
// Hilfsfunktion: Summen aus DB holen
// -------------------
function getSummen($conn, $datumSql) {
    $result = [
        "stunden" => 0,
        "urlaub"  => 0,
        "krank"   => 0 // Platzhalter
    ];

    // Gearbeitete Stunden (nur Mitarbeiter + Teamleiter)
    $sql = "
        SELECT SUM(z.stunden) AS summe
        FROM zeiteintraege z
        JOIN stundenzettel s ON z.stundenzettel_id = s.stundenzettel_id
        JOIN benutzer b ON s.benutzer_id = b.benutzer_id
        JOIN rollen r ON b.rollen_id = r.rollen_id
        WHERE s.jahr = YEAR(?) AND s.monat = MONTH(?) AND z.tag = DAY(?)
          AND r.rollen_schluessel IN ('mitarbeiter','teamleitung')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $datumSql, $datumSql, $datumSql);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $result["stunden"] = $res["summe"] ?? 0;
    $stmt->close();

    // Urlaubstage (genehmigt, aktueller Tag im Zeitraum)
    $sql = "
        SELECT COUNT(*) AS cnt
        FROM urlaubsantraege
        WHERE status = 'genehmigt'
          AND start_datum <= ?
          AND ende_datum >= ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $datumSql, $datumSql);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $result["urlaub"] = $res["cnt"] ?? 0;
    $stmt->close();

    // Krank (noch nicht implementiert)
    $result["krank"] = 0;

    return $result;
}

// -------------------
// Tabelle ausgeben
// -------------------
echo "<h1>Monatsübersicht – Quartal $quartal/$jahr</h1>";
echo "<table border='1' cellpadding='5' cellspacing='0'>";
echo "<tr style='background-color:#f0f0f0; font-weight:bold;'>
        <th>Datum</th>
        <th>Stunden (MA+TL)</th>
        <th>Urlaubstage</th>
        <th>Krank</th>
      </tr>";

$monat_summen = ["stunden" => 0, "urlaub" => 0, "krank" => 0];
$quartal_summen = ["stunden" => 0, "urlaub" => 0, "krank" => 0];

$aktueller_monat = $quartal_start->format("n");

for ($d = clone $quartal_start; $d <= $quartal_ende; $d->modify("+1 day")) {
    $datumSql     = $d->format("Y-m-d");   // für SQL
    $datumAnzeige = $d->format("d.m.Y");   // für Ausgabe
    $wochentag    = $d->format("N");       // 6 = Samstag, 7 = Sonntag

    $werte = getSummen($conn, $datumSql);

    // Summen aufaddieren
    $monat_summen["stunden"] += $werte["stunden"];
    $monat_summen["urlaub"]  += $werte["urlaub"];
    $monat_summen["krank"]   += $werte["krank"];

    $quartal_summen["stunden"] += $werte["stunden"];
    $quartal_summen["urlaub"]  += $werte["urlaub"];
    $quartal_summen["krank"]   += $werte["krank"];

    // Wochenenden & Feiertage ausgrauen
    $klasse = "";
    if ($wochentag >= 6 || in_array($datumSql, $feiertage)) {
        $klasse = "style='background-color:#ddd;'";
    }

    echo "<tr $klasse>
            <td>{$datumAnzeige}</td>
            <td>{$werte["stunden"]}</td>
            <td>{$werte["urlaub"]}</td>
            <td>{$werte["krank"]}</td>
          </tr>";

    // Monatswechsel: Summenzeile + Absatz
    if ((int)$d->format("n") !== $aktueller_monat || $d == $quartal_ende) {
        echo "<tr style='font-weight:bold; background-color:#eef;'>
                <td>Summe Monat {$aktueller_monat}.{$jahr}</td>
                <td>{$monat_summen["stunden"]}</td>
                <td>{$monat_summen["urlaub"]}</td>
                <td>{$monat_summen["krank"]}</td>
              </tr>";
        echo "<tr><td colspan='4'>&nbsp;</td></tr>"; // Leerzeile/Absatz
        $monat_summen = ["stunden" => 0, "urlaub" => 0, "krank" => 0];
        $aktueller_monat = (int)$d->format("n");
    }
}

// Quartalssumme
echo "<tr style='font-weight:bold; background-color:#cfc;'>
        <td>Summe Quartal $quartal/$jahr</td>
        <td>{$quartal_summen["stunden"]}</td>
        <td>{$quartal_summen["urlaub"]}</td>
        <td>{$quartal_summen["krank"]}</td>
      </tr>";

echo "</table>";

$conn->close();
?>
