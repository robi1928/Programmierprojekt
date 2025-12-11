<?php
declare(strict_types=1);

class CUrlaubsantrag
{
    private ?int $antrag_id = null;
    private int $benutzer_id;
    private string $start_datum;      // Y-m-d
    private string $ende_datum;
    private float $tage;
    private string $status = 'entwurf'; // 'entwurf','genehmigt','abgelehnt'
    private ?string $eingereicht_am = null;
    private ?int $eingereicht_von   = null;
    private ?int $entschieden_von   = null;
    private ?string $entschieden_am = null;
    private ?string $bemerkung      = null;
    private ?string $erstellt_am    = null;
    private ?string $aktualisiert_am = null;
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
        $obj->eingereicht_von = $row['eingereicht_von'] !== null ? (int)$row['eingereicht_von'] : null;
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
            eingereicht_am, eingereicht_von,  -- NEU
            entschieden_von, entschieden_am, bemerkung)
            VALUES
            (:bid, :start, :ende, :tage, :status,
            :eingereicht, :eingereicht_von,     -- NEU
            :entschieden_von, :entschieden_am, :bemerkung)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':bid'             => $this->benutzer_id,
            ':start'           => $this->start_datum,
            ':ende'            => $this->ende_datum,
            ':tage'            => $this->tage,
            ':status'          => $this->status,
            ':eingereicht'     => $this->eingereicht_am,
            ':eingereicht_von' => $this->eingereicht_von,
            ':entschieden_von' => $this->entschieden_von,
            ':entschieden_am'  => $this->entschieden_am,
            ':bemerkung'       => $this->bemerkung,
        ]);
            $this->antrag_id = (int)$pdo->lastInsertId();
        } else {
            // UPDATE
            $sql = "
            UPDATE urlaubsantraege
            SET benutzer_id     = :bid,
                start_datum     = :start,
                ende_datum      = :ende,
                tage            = :tage,
                status          = :status,
                eingereicht_am  = :eingereicht,
                eingereicht_von = :eingereicht_von,   -- NEU
                entschieden_von = :entschieden_von,
                entschieden_am  = :entschieden_am,
                bemerkung       = :bemerkung
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
            ':eingereicht_von' => $this->eingereicht_von,
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
    public function setEingereichtVon(int $benutzerId): void { $this->eingereicht_von = $benutzerId; }
    public function getEingereichtVon(): ?int     { return $this->eingereicht_von; }
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
        int $einreicherId,
        \DateTimeInterface $datum,
        ?\DateTimeInterface $datumEnde,
        ?string $bemerkung
    ): CUrlaubsantrag {

        // Wenn kein Enddatum gesetzt wurde → Einzelurlaubstag
        if ($datumEnde === null) {
            $datumEnde = $datum;
        }

        if ($datumEnde < $datum) {
            throw new InvalidArgumentException('Enddatum darf nicht vor dem Startdatum liegen.');
        }

        // Anzahl Tage berechnen (inkl. Start + Ende)
        $diff = $datum->diff($datumEnde);
        $tage = (float)($diff->days + 1);

        if ($tage <= 0) {
            throw new InvalidArgumentException('Tage müssen größer 0 sein.');
        }

        // Antrag erstellen
        $antrag = new CUrlaubsantrag(
            $benutzerId,
            $datum->format('Y-m-d'),
            $datumEnde->format('Y-m-d'),
            $tage
        );

        if ($bemerkung !== null && $bemerkung !== '') {
            $antrag->setBemerkung($bemerkung);
        }

        // Zeitpunkt des Einreichens
        $now = new \DateTimeImmutable();
        $antrag->setEingereichtZeitpunkt($now);
        $antrag->setEingereichtVon($einreicherId);

        // In DB speichern
        $antrag->save($pdo);

        return $antrag;
    }

    private static function bucheUrlaubNachGenehmigung(PDO $pdo, CUrlaubsantrag $antrag): void
    {
        $benutzerId = $antrag->getBenutzerId();
        $start      = new \DateTimeImmutable($antrag->getStartDatum());
        $ende       = new \DateTimeImmutable($antrag->getEndeDatum());

        $jahrStart = (int)$start->format('Y');

        // Versuch: Urlaubskonto für dieses Jahr exklusiv locken
        try {
            $konto = CUrlaubskonto::ladeFür($pdo, $benutzerId, $jahrStart);
        } catch (\RuntimeException $e) {
            $konto = null;
        }

        // Wenn es KEIN Urlaubskonto gibt → neu anlegen
        if ($konto === null) {
            // Versuche, den Anspruch aus dem letzten vorhandenen Urlaubskonto zu übernehmen
            $stmt = $pdo->prepare("
                SELECT anspruch_tage
                FROM urlaubskonten
                WHERE benutzer_id = :bid
                ORDER BY jahr DESC
                LIMIT 1
            ");
            $stmt->execute([':bid' => $benutzerId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($row) {
                $anspruch = (float)$row['anspruch_tage'];
            } else {
                // Fallback, falls es noch nie ein Konto gab – ggf. Wert anpassen
                $anspruch = 30.0;
            }

            $konto = CUrlaubskonto::create(
                $pdo,
                $benutzerId,
                $jahrStart,
                $anspruch
            );
        }

        // Urlaub aus dem Antrag auf dem Urlaubskonto buchen
        $konto->bucheUrlaub($pdo, $antrag->getTage());

        // Urlaub auf Monate verteilen
        $tageProMonat = [];
        for ($d = $start; $d <= $ende; $d = $d->add(new \DateInterval('P1D'))) {
            $jahr  = (int)$d->format('Y');
            $monat = (int)$d->format('n');
            $key   = sprintf('%04d-%02d', $jahr, $monat);

            if (!isset($tageProMonat[$key])) {
                $tageProMonat[$key] = 0.0;
            }
            $tageProMonat[$key] += 1.0;
        }

        foreach ($tageProMonat as $ym => $tageInMonat) {
            [$jahr, $monat] = array_map('intval', explode('-', $ym));

            $szId = CStundenzettelRepository::findeOderErstelle(
                $pdo,
                $benutzerId,
                $monat,
                $jahr
            );

            CStundenzettelRepository::bucheUrlaub($pdo, $szId, (float)$tageInMonat);
        }
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

        $pdo->beginTransaction();
        try {
            $antrag->entscheiden(
                'genehmigt',
                $aktuellerBenutzerId,
                $now,
                $bemerkung,
                $istGegenpart
            );

            $antrag->save($pdo);

            // JETZT erst Urlaub buchen (Konto + Stundenzettel)
            self::bucheUrlaubNachGenehmigung($pdo, $antrag);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

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

    /**
     * Zählt genehmigte Urlaubsanträge, die diesen Tag abdecken.
     * Praktisch 0 oder 1 – wir liefern aber int.
     */
    public static function anzahlGenehmigteUrlaubsantraegeAmTag(
        PDO $pdo,
        int $benutzerId,
        DateTimeInterface $tag
    ): int {
        $datumSql = $tag->format('Y-m-d');

        $sql = "
            SELECT COUNT(*) AS cnt
            FROM urlaubsantraege
            WHERE status     = 'genehmigt'
              AND benutzer_id = :bid
              AND start_datum <= :tag
              AND ende_datum  >= :tag
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':bid' => $benutzerId,
            ':tag' => $datumSql,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && $row['cnt'] !== null ? (int)$row['cnt'] : 0;
    }

    public static function UrlaubfuerMonatsuebersicht(
    \PDO $pdo,
    int $benutzerId,
    \DateTimeInterface $von,
    \DateTimeInterface $bis
): array {
    if ($bis < $von) {
        throw new \InvalidArgumentException('Ende darf nicht vor Beginn liegen.');
    }

    $vonDatum = new \DateTimeImmutable($von->format('Y-m-d'));
    $bisDatum = new \DateTimeImmutable($bis->format('Y-m-d'));

    $sql = "
        SELECT start_datum, ende_datum
        FROM urlaubsantraege
        WHERE status      = 'genehmigt'
          AND benutzer_id = :bid
          -- nur Anträge, die den Zeitraum schneiden
          AND NOT (ende_datum < :von OR start_datum > :bis)
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':bid' => $benutzerId,
        ':von' => $vonDatum->format('Y-m-d'),
        ':bis' => $bisDatum->format('Y-m-d'),
    ]);

    $urlaubProTag = []; // 'Y-m-d' => int

    while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
        $start = new \DateTimeImmutable($row['start_datum']);
        $ende  = new \DateTimeImmutable($row['ende_datum']);

        if ($start < $vonDatum) {
            $start = $vonDatum;
        }
        if ($ende > $bisDatum) {
            $ende = $bisDatum;
        }

        for ($d = $start; $d <= $ende; $d = $d->add(new \DateInterval('P1D'))) {
            $key = $d->format('Y-m-d');
            if (!isset($urlaubProTag[$key])) {
                $urlaubProTag[$key] = 0;
            }
            $urlaubProTag[$key] += 1;
        }
    }

    return $urlaubProTag;
    }   

        public static function freigabeDurchMitarbeiter(
        \PDO $pdo,
        int $antragId,
        int $mitarbeiterId,
        string $entscheidung,
        ?string $bemerkung = null
    ): CUrlaubsantrag {
        $entscheidung = strtolower($entscheidung);
        if (!in_array($entscheidung, ['genehmigt', 'abgelehnt'], true)) {
            throw new \InvalidArgumentException('Ungültige Entscheidung.');
        }

        // Prüfen, ob dieser Benutzer diesen Antrag freigeben darf
        $checkSql = "
            SELECT a.antrag_id
            FROM urlaubsantraege a
            JOIN benutzer b_mitarbeiter
              ON b_mitarbeiter.benutzer_id = a.benutzer_id
            JOIN benutzer b_einreicher
              ON b_einreicher.benutzer_id = a.eingereicht_von
            JOIN rollen r_m
              ON r_m.rollen_id = b_mitarbeiter.rollen_id
            JOIN rollen r_e
              ON r_e.rollen_id = b_einreicher.rollen_id
            WHERE a.antrag_id   = :id
              AND a.benutzer_id = :uid
              AND a.status      = 'entwurf'
              AND a.eingereicht_am IS NOT NULL
              AND r_m.rollen_schluessel = 'Mitarbeiter'
              AND r_e.rollen_schluessel IN ('Teamleitung','Projektleitung')
        ";

        $stmt = $pdo->prepare($checkSql);
        $stmt->execute([
            ':id'  => $antragId,
            ':uid' => $mitarbeiterId,
        ]);

        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            throw new \RuntimeException('Du darfst diesen Urlaubsantrag nicht freigeben.');
        }

        $istGegenpart = true;

        if ($entscheidung === 'genehmigt') {
            return self::genehmigeAntrag(
                $pdo,
                $antragId,
                $mitarbeiterId,
                $istGegenpart,
                $bemerkung
            ); 
        }

        return self::lehneAntragAb(
            $pdo,
            $antragId,
            $mitarbeiterId,
            $istGegenpart,
            $bemerkung
        );

    }

    public static function freigabeDurchProjektleitung(
        \PDO $pdo,
        int $antragId,
        int $projektleiterId,
        string $entscheidung,
        ?string $bemerkung = null
    ): CUrlaubsantrag {
        $entscheidung = strtolower($entscheidung);
        if (!in_array($entscheidung, ['genehmigt', 'abgelehnt'], true)) {
            throw new \InvalidArgumentException('Ungültige Entscheidung.');
        }

        $checkSql = "
            SELECT a.antrag_id
            FROM urlaubsantraege a
            JOIN benutzer e
            ON e.benutzer_id = a.eingereicht_von
            JOIN rollen r_e
            ON r_e.rollen_id = e.rollen_id
            JOIN benutzer b_pl
            ON b_pl.benutzer_id = :uid
            JOIN rollen r_pl
            ON r_pl.rollen_id = b_pl.rollen_id
            WHERE a.antrag_id   = :id
            AND a.status      = 'entwurf'
            AND a.eingereicht_am IS NOT NULL
            -- Einreicher ist TL oder Mitarbeiter
            AND r_e.rollen_schluessel IN ('Teamleitung','Mitarbeiter')
            -- Freigeber ist Projektleitung
            AND r_pl.rollen_schluessel = 'Projektleitung'
        ";

        $stmt = $pdo->prepare($checkSql);
        $stmt->execute([
            ':id'  => $antragId,
            ':uid' => $projektleiterId,
        ]);

        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            throw new \RuntimeException('Du darfst diesen Urlaubsantrag nicht freigeben.');
        }

        // jetzt regulären Workflow nutzen
        if ($entscheidung === 'genehmigt') {
            return self::genehmigeAntrag(
                $pdo,
                $antragId,
                $projektleiterId,
                true,
                $bemerkung
            );
        }

        return self::lehneAntragAb(
            $pdo,
            $antragId,
            $projektleiterId,
            true,
            $bemerkung
        );
    }

    public static function freigabeDurchTeamleitung(
        \PDO $pdo,
        int $antragId,
        int $teamleiterId,
        string $entscheidung,
        ?string $bemerkung = null
    ): CUrlaubsantrag {
        $entscheidung = strtolower($entscheidung);
        if (!in_array($entscheidung, ['genehmigt', 'abgelehnt'], true)) {
            throw new \InvalidArgumentException('Ungültige Entscheidung.');
        }

        $checkSql = "
            SELECT a.antrag_id
            FROM urlaubsantraege a
            JOIN benutzer e
            ON e.benutzer_id = a.eingereicht_von
            JOIN rollen r_e
            ON r_e.rollen_id = e.rollen_id
            JOIN benutzer b_tl
            ON b_tl.benutzer_id = :uid
            JOIN rollen r_tl
            ON r_tl.rollen_id = b_tl.rollen_id
            WHERE a.antrag_id   = :id
            AND a.status      = 'entwurf'
            AND a.eingereicht_am IS NOT NULL
            -- Einreicher ist Projektleitung oder Mitarbeiter
            AND r_e.rollen_schluessel IN ('Projektleitung','Mitarbeiter')
            -- Freigeber ist Teamleitung
            AND r_tl.rollen_schluessel = 'Teamleitung'
        ";

        $stmt = $pdo->prepare($checkSql);
        $stmt->execute([
            ':id'  => $antragId,
            ':uid' => $teamleiterId,
        ]);

        if (!$stmt->fetch(\PDO::FETCH_ASSOC)) {
            throw new \RuntimeException('Du darfst diesen Urlaubsantrag nicht freigeben.');
        }

        // regulären Workflow nutzen
        if ($entscheidung === 'genehmigt') {
            return self::genehmigeAntrag(
                $pdo,
                $antragId,
                $teamleiterId,
                true,
                $bemerkung
            );
        }

        return self::lehneAntragAb(
            $pdo,
            $antragId,
            $teamleiterId,
            true,
            $bemerkung
        );
    }


}
