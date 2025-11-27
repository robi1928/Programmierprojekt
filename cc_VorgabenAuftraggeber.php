<?php

class CVorgabenAuftraggeber
{
    private int $jahr;
    private int $quartal;
    private float $erwarteteKrankenquote;
    private int $sollStunden;
    private int $ist_Stunden;
    private float $toleranz;

    public function __construct(
        int $jahr,
        int $quartal,
        float $erwarteteKrankenquote,
        int $sollStunden,
        int $ist_Stunden = 0,
        float $toleranz
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
        $this->ist_Stunden = $ist_Stunden; // Initialwert = 0
        $this->toleranz = $toleranz;
    }

    private function RechneIstStunden(PDO $pdo): void
    {
        $stmt = $pdo->prepare(
            "SELECT SUM(stundenzettel.ist_stunden) AS summeStunden
             FROM stundenzettel
             WHERE stundenzettel.Jahr = :endjahr
               AND  stundenzettel.Monat >= :startmonat 
               AND stundenzettel.Monat <= :endmonat"
        );

        $startmonat = ($this->quartal - 1) * 3 + 1;
        //$startdatum = sprintf("%04d-%02d-01", $this->jahr, $startmonat);
        $endmonat = $startmonat + 3;
        $endjahr = $this->jahr;
        if ($endmonat > 12) {
            $endmonat -= 12;
            $endjahr += 1;
        }
        $enddatum = sprintf("%04d-%02d-01", $endjahr, $endmonat);

    $stmt->execute([
        ':endjahr' => $endjahr,
        ':startmonat' => $startmonat,
        ':endmonat' => $endmonat
    ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->ist_Stunden = (int)($row['summeStunden'] ?? 0);
    }


    // GETTER
    public function GetJahr(): int { return $this->jahr; }
    public function GetQuartal(): int { return $this->quartal; }
    public function GetErwarteteKrankenquote(): float { return $this->erwarteteKrankenquote; }
    public function GetSollStunden(): int { return $this->sollStunden; }
    public function GetIstStunden(): int { 
        $ist_Stunden = CVorgabenAuftraggeber::RechneIstStunden($GLOBALS['pdo']);
        return $this->ist_Stunden; }

    public function GetToleranz(): float { return $this->toleranz; }

    // INSERT
    public function InsertIntoDB(PDO $pdo): bool
    {
        $sql = "INSERT INTO vorgabenAuftraggeber 
            (jahr, quartal, erwarteteKrankenquote, sollStunden, ist_Stunden, toleranz)
            VALUES (:jahr, :quartal, :ekq, :soll, :ist, :tol)";

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':jahr' => $this->jahr,
            ':quartal' => $this->quartal,
            ':ekq' => $this->erwarteteKrankenquote,
            ':soll' => $this->sollStunden,
            ':ist' => $this->ist_Stunden,
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
            $row['jahr'],
            $row['quartal'],
            $row['erwarteteKrankenquote'],
            $row['sollStunden'],
            ($row['ist_Stunden']?? 0),
            $row['toleranz']
        );
    }
    
public function GetAnteilIstStunden(): float
    {
        $Anteil = 0;
        if ($this->sollStunden === 0) {
            return 0.0;
        }
        else {
            $this->Anteil = ($this->ist_Stunden / $this->sollStunden) * 100.0;
        }

        return $this->Anteil;
    }

    
}

