<?php

class CVorgabenAuftraggeber
{
    private int $jahr;
    private int $quartal;
    private float $erwarteteKrankenquote;
    private int $sollStunden;
    private float $toleranz;

    public function __construct(
        int $jahr,
        int $quartal,
        float $erwarteteKrankenquote,
        int $sollStunden,
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
        $this->toleranz = $toleranz;
    }

    // GETTER
    public function GetJahr(): int { return $this->jahr; }
    public function GetQuartal(): int { return $this->quartal; }
    public function GetErwarteteKrankenquote(): float { return $this->erwarteteKrankenquote; }
    public function GetSollStunden(): int { return $this->sollStunden; }
    public function GetToleranz(): float { return $this->toleranz; }

    // INSERT
    public function InsertIntoDB(PDO $pdo): bool
    {
        $sql = "INSERT INTO vorgabenAuftraggeber 
            (jahr, quartal, erwarteteKrankenquote, sollStunden, toleranz)
            VALUES (:jahr, :quartal, :ekq, :soll, :tol)";

        $stmt = $pdo->prepare($sql);

        return $stmt->execute([
            ':jahr' => $this->jahr,
            ':quartal' => $this->quartal,
            ':ekq' => $this->erwarteteKrankenquote,
            ':soll' => $this->sollStunden,
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
            $row['toleranz']
        );
    }
}
