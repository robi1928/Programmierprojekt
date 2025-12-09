<?php
declare(strict_types=1);

/**
 * Domänenmodell für einen Urlaubsantrag.
 *
 * Statusfluss (Soll):
 *   - neu:       'entwurf'  (mit eingereicht_am = Anlegezeitpunkt)
 *   - Entscheidung: 'genehmigt' oder 'abgelehnt' (immer durch den Gegenpart)
 *
 * Gegenpart-Logik (muss außerhalb dieser Klasse geprüft werden):
 *   - Antragsteller ist Mitarbeiter   → Gegenpart ist Teamleiter/Projektleiter
 *   - Antragsteller ist TL/PL        → Gegenpart ist Mitarbeiter
 */
class CUrlaubsantrag
{
    private ?int $antrag_id = null;
    private int $benutzer_id;
    private string $start_datum;      // Y-m-d
    private string $ende_datum;       // Y-m-d
    private float $tage;
    private string $status = 'entwurf'; // 'entwurf','genehmigt','abgelehnt','storniert'
    private ?string $eingereicht_am = null;   // DATETIME (hier: Anlege-/Beantragungszeitpunkt)
    private ?int $entschieden_von   = null;
    private ?string $entschieden_am = null;   // DATETIME
    private ?string $bemerkung      = null;
    private ?string $erstellt_am    = null;   // TIMESTAMP (DB-seitig gepflegt)
    private ?string $aktualisiert_am = null;  // TIMESTAMP (DB-seitig gepflegt)

    public function __construct(
        int $benutzerId,
        string $startDatum,
        string $endeDatum,
        float $tage
    ) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDatum)) {
            throw new InvalidArgumentException('Ungültiges Startdatum.');
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $endeDatum)) {
            throw new InvalidArgumentException('Ungültiges Enddatum.');
        }
        if ($endeDatum < $startDatum) {
            throw new InvalidArgumentException('Enddatum darf nicht vor dem Startdatum liegen.');
        }
        if ($tage <= 0) {
            throw new InvalidArgumentException('Tage müssen größer 0 sein.');
        }

        $this->benutzer_id  = $benutzerId;
        $this->start_datum  = $startDatum;
        $this->ende_datum   = $endeDatum;
        $this->tage         = $tage;
        // $this->status bleibt standardmäßig 'entwurf'
    }

    /**
     * Erzeugt ein Objekt aus einem DB-Row-Array.
     *
     * @param array<string,mixed> $row
     */
    public static function fromRow(array $row): self
    {
        $obj = new self(
            (int)$row['benutzer_id'],
            (string)$row['start_datum'],
            (string)$row['ende_datum'],
            (float)$row['tage']
        );

        $obj->antrag_id       = (int)$row['antrag_id'];
        $obj->status          = (string)$row['status'];
        $obj->eingereicht_am  = $row['eingereicht_am'] ?? null;
        $obj->entschieden_von = $row['entschieden_von'] !== null ? (int)$row['entschieden_von'] : null;
        $obj->entschieden_am  = $row['entschieden_am'] ?? null;
        $obj->bemerkung       = $row['bemerkung'] ?? null;
        $obj->erstellt_am     = $row['erstellt_am'] ?? null;
        $obj->aktualisiert_am = $row['aktualisiert_am'] ?? null;

        return $obj;
    }

    // ---------------------------------------------------------------------
    // Status- / Workflow-Methoden
    // ---------------------------------------------------------------------

    /**
     * Setzt eine optionale Bemerkung (leerer String wird als NULL gespeichert).
     */
    public function setBemerkung(?string $bemerkung): void
    {
        $this->bemerkung = ($bemerkung !== null && $bemerkung !== '') ? $bemerkung : null;
    }

    /**
     * Beim Anlegen/Erfassen: Timestamp setzen, aber Status bleibt 'entwurf'.
     */
    public function setEingereichtZeitpunkt(\DateTimeInterface $now): void
    {
        $this->eingereicht_am = $now->format('Y-m-d H:i:s');
    }

    /**
     * Entscheidung über den Antrag durch den Gegenpart.
     *
     * $entscheidung: 'genehmigt' oder 'abgelehnt'
     */
    public function entscheiden(
        string $entscheidung,
        int $entscheidungsBenutzerId,
        \DateTimeInterface $now,
        ?string $bemerkung,
        bool $istGegenpart
    ): void {
    
        if ($this->status !== 'entwurf') {
            throw new LogicException('Nur Entwürfe können entschieden werden.');
        }

        if (!$istGegenpart) {
            throw new LogicException('Du darfst nicht über diesen Antrag entscheiden.');
        }

        if (!in_array($entscheidung, ['genehmigt', 'abgelehnt'], true)) {
            throw new InvalidArgumentException('Ungültige Entscheidung.');
        }

        $this->status          = $entscheidung;
        $this->entschieden_von = $entscheidungsBenutzerId;
        $this->entschieden_am  = $now->format('Y-m-d H:i:s');

        if ($bemerkung !== null && $bemerkung !== '') {
            $this->bemerkung = $bemerkung;
        }
    }

    /**
     * INSERT oder UPDATE (falls antrag_id bereits gesetzt).
     */
    public function save(\PDO $pdo): void
    {
        if ($this->antrag_id === null) {
            // INSERT
            $sql = "
                INSERT INTO urlaubsantraege
                  (benutzer_id, start_datum, ende_datum, tage, status,
                   eingereicht_am, entschieden_von, entschieden_am, bemerkung)
                VALUES
                  (:bid, :start, :ende, :tage, :status,
                   :eingereicht, :entschieden_von, :entschieden_am, :bemerkung)
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':bid'             => $this->benutzer_id,
                ':start'           => $this->start_datum,
                ':ende'            => $this->ende_datum,
                ':tage'            => $this->tage,
                ':status'          => $this->status,
                ':eingereicht'     => $this->eingereicht_am,
                ':entschieden_von' => $this->entschieden_von,
                ':entschieden_am'  => $this->entschieden_am,
                ':bemerkung'       => $this->bemerkung,
            ]);
            $this->antrag_id = (int)$pdo->lastInsertId();
        } else {
            // UPDATE
            $sql = "
                UPDATE urlaubsantraege
                   SET benutzer_id    = :bid,
                       start_datum    = :start,
                       ende_datum     = :ende,
                       tage           = :tage,
                       status         = :status,
                       eingereicht_am = :eingereicht,
                       entschieden_von= :entschieden_von,
                       entschieden_am = :entschieden_am,
                       bemerkung      = :bemerkung
                 WHERE antrag_id = :id
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':bid'             => $this->benutzer_id,
                ':start'           => $this->start_datum,
                ':ende'            => $this->ende_datum,
                ':tage'            => $this->tage,
                ':status'          => $this->status,
                ':eingereicht'     => $this->eingereicht_am,
                ':entschieden_von' => $this->entschieden_von,
                ':entschieden_am'  => $this->entschieden_am,
                ':bemerkung'       => $this->bemerkung,
                ':id'              => $this->antrag_id,
            ]);
        }
    }

    // Getter
    public function getId(): ?int                 { return $this->antrag_id; }
    public function getBenutzerId(): int          { return $this->benutzer_id; }
    public function getStartDatum(): string       { return $this->start_datum; }
    public function getEndeDatum(): string        { return $this->ende_datum; }
    public function getTage(): float              { return $this->tage; }
    public function getStatus(): string           { return $this->status; }
    public function getBemerkung(): ?string       { return $this->bemerkung; }
    public function getEingereichtAm(): ?string   { return $this->eingereicht_am; }
    public function getEntschiedenVon(): ?int     { return $this->entschieden_von; }
    public function getEntschiedenAm(): ?string   { return $this->entschieden_am; }
    public function getErstelltAm(): ?string      { return $this->erstellt_am; }
    public function getAktualisiertAm(): ?string  { return $this->aktualisiert_am; }
}

/**
 * Repository für urlaubsantraege (Laden / spezielle Erzeuger / Workflow-Helfer).
 */
final class CUrlaubsantragRepository
{
    public static function loadById(\PDO $pdo, int $antragId): ?CUrlaubsantrag
    {
        $sql = "SELECT * FROM urlaubsantraege WHERE antrag_id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $antragId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return CUrlaubsantrag::fromRow($row);
    }

    /**
     * Wird im Erfassungs-Workflow genutzt, wenn status='urlaub' gewählt wurde.
     *
     * Führt jetzt:
     *  - Erzeugung eines Antrags (Status 'entwurf')
     *  - Setzen von eingereicht_am auf den aktuellen Zeitpunkt
     */
    public static function erzeugeGenehmigtenTag(
        \PDO $pdo,
        int $benutzerId,
        \DateTimeInterface $datum,
        float $tage,
        ?string $bemerkung
    ): CUrlaubsantrag {
        if ($tage <= 0) {
            throw new InvalidArgumentException('Tage müssen größer 0 sein.');
        }

        $dStr = $datum->format('Y-m-d');

        $antrag = new CUrlaubsantrag(
            $benutzerId,
            $dStr,
            $dStr,
            $tage
        );
        $antrag->setBemerkung($bemerkung);

        // „Einreichen“ im Sinne von: Zeitpunkt setzen, Status bleibt 'entwurf'
        $now = new \DateTimeImmutable();
        $antrag->setEingereichtZeitpunkt($now);

        $antrag->save($pdo);

        return $antrag;
    }

    /**
     * Genehmigt einen Antrag durch den Gegenpart.
     */
    public static function genehmigeAntrag(
        \PDO $pdo,
        int $antragId,
        int $aktuellerBenutzerId,
        bool $istGegenpart,
        ?string $bemerkung = null
    ): CUrlaubsantrag {
        $antrag = self::loadById($pdo, $antragId);
        if ($antrag === null) {
            throw new RuntimeException('Urlaubsantrag nicht gefunden.');
        }

        $now = new \DateTimeImmutable();

        $antrag->entscheiden(
            'genehmigt',
            $aktuellerBenutzerId,
            $now,
            $bemerkung,
            $istGegenpart
        );

        $antrag->save($pdo);

        return $antrag;
    }

    /**
     * Lehnt einen Antrag durch den Gegenpart ab.
     */
    public static function lehneAntragAb(
        \PDO $pdo,
        int $antragId,
        int $aktuellerBenutzerId,
        bool $istGegenpart,
        ?string $bemerkung = null
    ): CUrlaubsantrag {
        $antrag = self::loadById($pdo, $antragId);
        if ($antrag === null) {
            throw new RuntimeException('Urlaubsantrag nicht gefunden.');
        }

        $now = new \DateTimeImmutable();

        $antrag->entscheiden(
            'abgelehnt',
            $aktuellerBenutzerId,
            $now,
            $bemerkung,
            $istGegenpart
        );

        $antrag->save($pdo);

        return $antrag;
    }
}
