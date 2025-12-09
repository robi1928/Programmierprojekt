<?php
class CUrlaubskonto
{
    private $konto_id;
    private $benutzer_id;
    private $jahr;
    private $anspruch_tage;
    private $uebertrag_tage;
    private $genutzt_tage;

    public function __construct(?int $kontoId = null)
    {
        $this->konto_id      = $kontoId;
        $this->benutzer_id   = 0;
        $this->jahr          = (int)date('Y');
        $this->anspruch_tage = 0.0;
        $this->uebertrag_tage = 0.0;
        $this->genutzt_tage   = 0.0;
    }

    // Statische Loader / Factory-Methoden
    public static function loadById(PDO $pdo, int $kontoId): ?CUrlaubskonto
    {
        $sql = "SELECT * FROM urlaubskonten WHERE konto_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $kontoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return self::fromRow($row);
    }

    // Lädt Urlaubskonto für Benutzer + Jahr. Falls keines existiert, wird NULL zurückgegeben.

    public static function loadForUserYear(PDO $pdo, int $benutzerId, int $jahr): ?CUrlaubskonto
    {
        $sql = "SELECT * FROM urlaubskonten
                WHERE benutzer_id = :bid AND jahr = :jahr";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':bid'  => $benutzerId,
            ':jahr' => $jahr
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        return self::fromRow($row);
    }

    // Erzeugt ein neues Urlaubskonto in der Datenbank.
    public static function create(
        PDO $pdo,
        int $benutzerId,
        int $jahr,
        float $anspruchTage,
        float $uebertragTage = 0.0
    ): CUrlaubskonto {
        $sql = "INSERT INTO urlaubskonten
                  (benutzer_id, jahr, anspruch_tage, uebertrag_tage, genutzt_tage)
                VALUES
                  (:bid, :jahr, :anspruch, :uebertrag, :genutzt)";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            ':bid'      => $benutzerId,
            ':jahr'     => $jahr,
            ':anspruch' => $anspruchTage,
            ':uebertrag'=> $uebertragTage,
            ':genutzt'  => 0.0
        ]);

        if (!$ok) {
            $errorInfo = $stmt->errorInfo();
            throw new RuntimeException('Fehler beim Anlegen des Urlaubskontos: ' . ($errorInfo[2] ?? 'unbekannt'));
        }

        $kontoId = (int)$pdo->lastInsertId();
        $konto = new CUrlaubskonto($kontoId);
        $konto->benutzer_id    = $benutzerId;
        $konto->jahr           = $jahr;
        $konto->anspruch_tage  = $anspruchTage;
        $konto->uebertrag_tage = $uebertragTage;
        $konto->genutzt_tage   = 0.0;

        return $konto;
    }

    /**
     * Hilfsmethode: Erzeugt ein Objekt aus einem DB-Row-Array.
     *
     * @param array<string,mixed> $row
     * @return CUrlaubskonto
     */
    private static function fromRow(array $row): CUrlaubskonto
    {
        $konto = new CUrlaubskonto((int)$row['konto_id']);
        $konto->benutzer_id    = (int)$row['benutzer_id'];
        $konto->jahr           = (int)$row['jahr'];
        $konto->anspruch_tage  = (float)$row['anspruch_tage'];
        $konto->uebertrag_tage = (float)$row['uebertrag_tage'];
        $konto->genutzt_tage   = (float)$row['genutzt_tage'];

        return $konto;
    }

     //*  Persistenz / Änderungen

    public function save(PDO $pdo): void
    {
        if ($this->konto_id === null) {
            throw new RuntimeException('Speichern nicht möglich: konto_id ist NULL (Objekt wurde nicht erzeugt / geladen).');
        }

        $sql = "UPDATE urlaubskonten
                   SET benutzer_id    = :bid,
                       jahr           = :jahr,
                       anspruch_tage  = :anspruch,
                       uebertrag_tage = :uebertrag,
                       genutzt_tage   = :genutzt
                 WHERE konto_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':bid'      => $this->benutzer_id,
            ':jahr'     => $this->jahr,
            ':anspruch' => $this->anspruch_tage,
            ':uebertrag'=> $this->uebertrag_tage,
            ':genutzt'  => $this->genutzt_tage,
            ':id'       => $this->konto_id
        ]);
    }

    public function addGenutzt(PDO $pdo, float $tage): void
    {
        if ($tage <= 0) {
            return;
        }

        $this->genutzt_tage += $tage;
        $this->save($pdo);
    }


    /**
     * Berechnet die verfügbaren (verbleibenden) Urlaubstage:
     *   anspruch_tage + uebertrag_tage - genutzt_tage
     *
     * Entspricht "Verfuegbar" aus dem Fachkonzept.
     *
     * @return float
     */
    public function getVerfuegbar(): float
    {
        return $this->anspruch_tage + $this->uebertrag_tage - $this->genutzt_tage;
    }

    // *  Getter

    public function getKontoId(): ?int
    {
        return $this->konto_id;
    }

    public function getBenutzerId(): int
    {
        return $this->benutzer_id;
    }

    public function getJahr(): int
    {
        return $this->jahr;
    }

    public function getAnspruchTage(): float
    {
        return $this->anspruch_tage;
    }

    public function getUebertragTage(): float
    {
        return $this->uebertrag_tage;
    }

    public function getGenutztTage(): float
    {
        return $this->genutzt_tage;
    }

    // *  Setter (optional, falls du Werte ändern willst)

    public function setAnspruchTage(float $tage): void
    {
        $this->anspruch_tage = $tage;
    }

    public function setUebertragTage(float $tage): void
    {
        $this->uebertrag_tage = $tage;
    }

    public function setGenutztTage(float $tage): void
    {
        $this->genutzt_tage = $tage;
    }
}
