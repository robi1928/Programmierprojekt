<?php

class CVorgabenAuftraggeber
{
    private int $jahr;
    private int $quartal;
    private float $erwarteteKrankenquote;
    private int $sollStunden;
    private int $istStunden;
    private float $toleranz;

    public function __construct(
        int $jahr,
        int $quartal,
        float $erwarteteKrankenquote,
        int $sollStunden,
        float $toleranz,
        int $istStunden = 0
    ) {
        if ($jahr < 2000 || $jahr > 2040) {
            throw new InvalidArgumentException("Jahr muss 2000–2040 sein.");
        }
        if ($quartal < 1 || $quartal > 4) {
            throw new InvalidArgumentException("Quartal muss 1–4 sein.");
        }
        if ($erwarteteKrankenquote < 0 || $erwarteteKrankenquote > 100) {
            throw new InvalidArgumentException("Krankenquote muss 0–100% sein.");
        }
        if ($sollStunden <= 0) {
            throw new InvalidArgumentException("Sollstunden müssen > 0 sein.");
        }
        if ($toleranz < 0 || $toleranz > 100) {
            throw new InvalidArgumentException("Toleranz muss 0–100% sein.");
        }

        $this->jahr = $jahr;
        $this->quartal = $quartal;
        $this->erwarteteKrankenquote = $erwarteteKrankenquote;
        $this->sollStunden = $sollStunden;
        $this->istStunden = $istStunden; // Initialwert = 0
        $this->toleranz = $toleranz;
    }

    // Nebenrechnung
    private function RechneIstStunden(PDO $pdo): int
    {
        $startmonat = ($this->quartal - 1) * 3 + 1;
        $endmonat   = $startmonat + 2; // genau 3 Monate

        $stmt = $pdo->prepare(
            "SELECT SUM(ist_stunden) AS summeStunden
             FROM stundenzettel
             WHERE jahr  = :jahr
               AND monat BETWEEN :startmonat AND :endmonat"
        );

        $stmt->execute([
            ':jahr'       => $this->jahr,
            ':startmonat' => $startmonat,
            ':endmonat'   => $endmonat
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->istStunden = (int)($row['summeStunden'] ?? 0);

        return $this->istStunden;
    }

    // GETTER
    public function GetJahr(): int { return $this->jahr; }
    public function GetQuartal(): int { return $this->quartal; }
    public function GetErwarteteKrankenquote(): float { return $this->erwarteteKrankenquote; }
    public function GetSollStunden(): int { return $this->sollStunden; }
    public function GetIstStunden(): int 
    {
        return $this->RechneIstStunden($GLOBALS['pdo']);
    }

    public function GetToleranz(): float { return $this->toleranz; }

    // INSERT
    public function InsertIntoDB(PDO $pdo): bool
    {
        $sql = "INSERT INTO vorgabenAuftraggeber 
            (jahr, quartal, erwarteteKrankenquote, sollStunden, istStunden, toleranz)
            VALUES (:jahr, :quartal, :ekq, :soll, :ist, :tol)";

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':jahr' => $this->jahr,
            ':quartal' => $this->quartal,
            ':ekq' => $this->erwarteteKrankenquote,
            ':soll' => $this->sollStunden,
            ':ist' => $this->istStunden,
            ':tol' => $this->toleranz
        ]);
    }

    // Optional: Datensatz laden
    public static function LoadByJahrQuartal(PDO $pdo, int $jahr, int $quartal): ?self
    {
        $stmt = $pdo->prepare(
            "SELECT * FROM vorgabenAuftraggeber WHERE jahr = :jahr AND quartal = :quartal"
        );
        $stmt->execute([
            ':jahr' => $jahr,
            ':quartal' => $quartal
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) return null;

        return new self(
            (int)$row['jahr'],
            (int)$row['quartal'],
            (float)$row['erwarteteKrankenquote'],
            (int)$row['sollStunden'],
            (float)$row['toleranz'],
            (int)($row['istStunden'] ?? 0)
        );
    }
    
    // Danke Copilot
public function GetAnteilIstStunden(): float
{
    if ($this->sollStunden === 0) {
        return 0.0;
    }

    return ($this->istStunden / $this->sollStunden) * 100.0;
}

    // Danke Copilot
    // Berechnet den Toleranzbereich basierend auf den Sollstunden und der Toleranz in Prozent
public function Toleranzbereich(): array
    {
        $min = $this->sollStunden * (1 - $this->toleranz / 100);
        $max = $this->sollStunden * (1 + $this->toleranz / 100);
        return ['min' => $min, 'max' => $max];
    }



 // Danke Copilot 
 // Nebenrechnung
 // Generiere Liste der Feiertage in SH für ein gegebenes Jahr, um sie später von den Arbeitstagen abzuziehen 
public static function feiertageSH(int $jahr): array
{
    $feiertage = [];

    // feste Feiertage
    $feiertage[] = "$jahr-01-01"; // Neujahr
    $feiertage[] = "$jahr-05-01"; // Tag der Arbeit
    $feiertage[] = "$jahr-10-03"; // Tag der dt. Einheit
    $feiertage[] = "$jahr-12-25";
    $feiertage[] = "$jahr-12-26";

    // bewegliche Feiertage
    $ostersonntag = date("Y-m-d", easter_date($jahr));
    $feiertage[] = date("Y-m-d", strtotime("$ostersonntag -2 days"));  // Karfreitag
    $feiertage[] = date("Y-m-d", strtotime("$ostersonntag +1 day"));   // Ostermontag
    $feiertage[] = date("Y-m-d", strtotime("$ostersonntag +39 days")); // Christi Himmelfahrt
    $feiertage[] = date("Y-m-d", strtotime("$ostersonntag +50 days")); // Pfingstmontag

    return $feiertage;
}

// ChatGPT generierte Funktion
// berechnet die die verfügbare Anzahl der Arbeitstage im Quartal
public static function berechneArbeitstageMitFeiertagen(int $jahr, int $quartal): int
{
    $feiertage = self::feiertageSH($jahr);

    $startMonat = ($quartal - 1) * 3 + 1;
    $start = new DateTime("$jahr-$startMonat-01");
    $ende = (clone $start)->modify("+3 months")->modify("-1 day");

    $arbeitstageInsgesamt = 0;

    for ($d = clone $start; $d <= $ende; $d->modify("+1 day")) {
        $datum = $d->format("Y-m-d");
        $wochentag = (int)$d->format("N");

        if ($wochentag >= 6) continue;                 // Wochenende
        if (in_array($datum, $feiertage)) continue;    // Feiertag

        $arbeitstageInsgesamt++;
    }

    return $arbeitstageInsgesamt;
}


 public static function berechneVergangeneArbeitstageImQuartal(int $jahr, int $quartal): int
    {
        $feiertage = self::feiertageSH($jahr);

        $startMonat = ($quartal - 1) * 3 + 1;
        $start = new DateTime(sprintf("%04d-%02d-01", $jahr, $startMonat));

        // Quartalsende
        $quartalsEnde = (clone $start)->modify("+3 months")->modify("-1 day");

        // Heute, aber nicht über das Quartalsende hinaus
        $heute = new DateTime();
        if ($heute > $quartalsEnde) {
            $ende = $quartalsEnde;
        } else {
            $ende = $heute;
        }

        if ($ende < $start) {
            return 0;
        }

        $arbeitstageBisher = 0;

        for ($d = clone $start; $d <= $ende; $d->modify("+1 day")) {
            $datum = $d->format("Y-m-d");
            $wochentag = (int)$d->format("N");

            if ($wochentag >= 6) continue;
            if (in_array($datum, $feiertage, true)) continue;

            $arbeitstageBisher++;
        }

        return $arbeitstageBisher;
    }




// Danke Copilot
// Ich möchte den prozentualen Anteil der vergangenen Arbeitstage im Quartal berechnen, im Vergleich zu den insgesamt verfügbaren Arbeitstagen.
public static function prozentualeVergangeneArbeitstageImQuartal(int $jahr, int $quartal): float
    {
        $vergangeneArbeitstage = self::berechneVergangeneArbeitstageImQuartal($jahr, $quartal);
        $insgesamtArbeitstage  = self::berechneArbeitstageMitFeiertagen($jahr, $quartal);

        if ($insgesamtArbeitstage === 0) {
            return 0.0;
        }

        return ($vergangeneArbeitstage / $insgesamtArbeitstage) * 100.0;
    }


// Danke Copilot
// Ich möchte den Bedarf an Planstunden bis zum Ende des Quartals berechnen, unter Berücksichtigung der erwarteten Krankenquote.    
public function BedarfPlanstundenBisEndeQuartal(): array
{
    $toleranz = $this->Toleranzbereich();
    $minimum = $toleranz['min'];
    $maximum = $toleranz['max'];
    $istStunden = $this->GetIstStunden();  
    $erwarteteKrankenquote = $this->GetErwarteteKrankenquote();

    $bedarfMin = ($minimum - $istStunden) * (1 + $erwarteteKrankenquote / 100);
    $bedarfMax = ($maximum - $istStunden) * (1 + $erwarteteKrankenquote / 100);

    return [
        'bis_min' => $bedarfMin,
        'bis_max' => $bedarfMax
    ];
}
    
}

