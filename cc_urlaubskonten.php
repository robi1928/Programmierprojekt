<?php
class CUrlaubskonto
{
    private $konto_id;
    private $benutzer_id;
    private $jahr;
    private $anspruch_tage;
    private $genutzt_tage;

    public function __construct(?int $kontoId = null)
    {
        $this->konto_id      = $kontoId;
        $this->benutzer_id   = 0;
        $this->jahr          = (int)date('Y');
        $this->anspruch_tage = 0.0;
        $this->genutzt_tage  = 0.0;
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

    public static function ladeFür(PDO $pdo, int $benutzerId, int $jahr): CUrlaubskonto
    {
        $sql = "SELECT * FROM urlaubskonten
                WHERE benutzer_id = :bid AND jahr = :jahr
                FOR UPDATE";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':bid'  => $benutzerId,
            ':jahr' => $jahr
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new RuntimeException('Kein Urlaubskonto für dieses Jahr vorhanden.');
        }

        return self::fromRow($row);
    }

    // Erzeugt ein neues Urlaubskonto in der Datenbank.
    public static function create(
        PDO $pdo,
        int $benutzerId,
        int $jahr,
        float $anspruchTage
    ): CUrlaubskonto {
        $sql = "INSERT INTO urlaubskonten
                  (benutzer_id, jahr, anspruch_tage, genutzt_tage)
                VALUES
                  (:bid, :jahr, :anspruch, :genutzt)";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            ':bid'      => $benutzerId,
            ':jahr'     => $jahr,
            ':anspruch' => $anspruchTage,
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
        $konto->genutzt_tage   = 0.0;

        return $konto;
    }

    /**
     * Hilfsmethode: Erzeugt ein Objekt aus einem DB-Row-Array. */
    private static function fromRow(array $row): CUrlaubskonto
    {
        $konto = new CUrlaubskonto((int)$row['konto_id']);
        $konto->benutzer_id    = (int)$row['benutzer_id'];
        $konto->jahr           = (int)$row['jahr'];
        $konto->anspruch_tage  = (float)$row['anspruch_tage'];
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
                       genutzt_tage   = :genutzt
                 WHERE konto_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':bid'      => $this->benutzer_id,
            ':jahr'     => $this->jahr,
            ':anspruch' => $this->anspruch_tage,
            ':genutzt'  => $this->genutzt_tage,
            ':id'       => $this->konto_id
        ]);
    }

    /**
     * Urlaub buchen mit Plausibilitätsprüfung.
     * Wirft Exception, wenn das Kontingent überschritten würde.
     */
    public function bucheUrlaub(PDO $pdo, float $tage): void
    {
        if ($tage <= 0) {
            return;
        }

        if ($tage > $this->getVerfuegbar()) {
            throw new RuntimeException('Urlaubskontingent überschritten.');
        }

        $this->genutzt_tage += $tage;
        $this->save($pdo);
    }


    /**
     * Berechnet die verfügbaren (verbleibenden) Urlaubstage*/
    public function getVerfuegbar(): float
    {
        return $this->anspruch_tage - $this->genutzt_tage;
    }

    // *  Getter

    public function getKontoId(): ?int      { return $this->konto_id; }
    public function getBenutzerId(): int    { return $this->benutzer_id; }
    public function getJahr(): int          { return $this->jahr; }
    public function getAnspruchTage(): float{ return $this->anspruch_tage; }
    public function getGenutztTage(): float { return $this->genutzt_tage; }

    // Setter (optional)

    public function setAnspruchTage(float $tage): void   { $this->anspruch_tage  = $tage; }
    public function setGenutztTage(float $tage): void    { $this->genutzt_tage   = $tage; }
}