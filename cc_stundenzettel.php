<?php
// orientiert an cc_benutzer. Dringend klären, ob das so bleiben soll.

// beschreibt im Prinzip das was (logik und Zustand)
class CStundenzettel {
    private ?int $stundenzettel_id = null;
    private int $benutzer_id;
    private int $monat;
    private int $jahr;
    private string $status = 'entwurf';
    private ?string $eingereicht_am = null;
    private ?int $eingereicht_von = null;
    private ?int $genehmigt_von = null;
    private ?string $genehmigt_am = null;
    private float $soll_stunden = 0.0;
    private float $ist_stunden = 0.0;
    private float $saldo_stunden = 0.0;
    private float $urlaub_gesamt = 0.0;
    private string $erstellt_am;
    private string $aktualisiert_am;

    public function __construct(int $benutzer_id, int $monat, int $jahr) {
        if ($monat < 1 || $monat > 12) {
            throw new InvalidArgumentException('Monat muss 1..12 sein.');
        }
        if ($jahr < 2000 || $jahr > 2040) {
            throw new InvalidArgumentException('Jahr muss 2000..2040 sein.');
        }
        $this->benutzer_id = $benutzer_id;
        $this->monat = $monat;
        $this->jahr = $jahr;
        $this->erstellt_am = date('Y-m-d H:i:s');
        $this->aktualisiert_am = date('Y-m-d H:i:s');
    }

    public static function fromRow(array $row): self {
        $obj = new self(
            (int)$row['benutzer_id'],
            (int)$row['monat'],
            (int)$row['jahr']
        );

        $obj->stundenzettel_id = (int)$row['stundenzettel_id'];
        $obj->status           = $row['status'] ?? 'entwurf';

        $obj->eingereicht_am   = $row['eingereicht_am'] ?? null;
        $obj->eingereicht_von  = isset($row['eingereicht_von'])
                                ? (int)$row['eingereicht_von']
                                : null;
        $obj->genehmigt_von    = isset($row['genehmigt_von'])
                                ? (int)$row['genehmigt_von']
                                : null;
        $obj->genehmigt_am     = $row['genehmigt_am'] ?? null;

        $obj->soll_stunden     = (float)($row['soll_stunden'] ?? 0);
        $obj->ist_stunden      = (float)($row['ist_stunden'] ?? 0);
        $obj->saldo_stunden    = isset($row['saldo_stunden']) ? (float)$row['saldo_stunden'] : 0.0;
        $obj->urlaub_gesamt    = isset($row['urlaub_gesamt']) ? (float)$row['urlaub_gesamt'] : 0.0;
        $obj->erstellt_am      = $row['erstellt_am'] ?? date('Y-m-d H:i:s');
        $obj->aktualisiert_am  = $row['aktualisiert_am'] ?? date('Y-m-d H:i:s');

        return $obj;
    }

    // das Folgende war eigentlich alles in Erfassung Mitarbeiter und ist jetzt für die Fachobjekte hierrein gezogen. Ursprungscode geschrieben von mir, jetzt angepasst mit GTP für die Fachobjekte
    public function create(PDO $pdo): bool {
        $allowed = ['entwurf','genehmigt','abgelehnt'];
        if (!in_array($this->status, $allowed, true)) {
            $this->status = 'entwurf';
        }

        // DB verwaltet erstellt_am/aktualisiert_am via DEFAULT/NOW()
        $sql = "
            INSERT INTO stundenzettel
            (benutzer_id, monat, jahr, status, soll_stunden, ist_stunden, eingereicht_am, eingereicht_von)
            VALUES (:b, :m, :j, :s, :soll, :ist, :ea, :ev)
        ";
        $st = $pdo->prepare($sql);
        $ok = $st->execute([
            ':b'  => $this->benutzer_id,
            ':m'  => $this->monat,
            ':j'  => $this->jahr,
            ':s'  => $this->status,
            ':soll' => $this->soll_stunden,
            ':ist'  => $this->ist_stunden,
            ':ea'   => $this->eingereicht_am,
            ':ev'   => $this->eingereicht_von,
        ]);
        if ($ok) {
            $this->stundenzettel_id = (int)$pdo->lastInsertId();
        }
        return $ok;
    }

        public function getId(): ?int
    {
        return $this->stundenzettel_id;
    }

    public function einreichen(int $benutzerId): void
    {
        if ($this->eingereicht_am !== null) {
            return;
        }

        $this->eingereicht_von = $benutzerId;
        $this->eingereicht_am  = date('Y-m-d H:i:s');
    }
}

final class CStundenzettelRepository {

    public static function findeOderErstelle(
        PDO $pdo,
        int $benutzerId,
        int $monat,
        int $jahr
    ): int {
        if ($monat < 1 || $monat > 12) {
            throw new InvalidArgumentException('Monat muss 1..12 sein.');
        }
        if ($jahr < 2000 || $jahr > 2040) {
            throw new InvalidArgumentException('Jahr muss 2000..2040 sein.');
        }

        $sql = "
            INSERT INTO stundenzettel (benutzer_id, monat, jahr, status, soll_stunden, ist_stunden)
            VALUES (:b,:m,:j,'entwurf',0,0)
            ON DUPLICATE KEY UPDATE
                stundenzettel_id = LAST_INSERT_ID(stundenzettel_id)
        ";
        $st = $pdo->prepare($sql);
        $st->execute([':b' => $benutzerId, ':m' => $monat, ':j' => $jahr]);

        return (int)$pdo->lastInsertId();
    }

    public static function recalcIst(PDO $pdo, int $szId): void {
        $st = $pdo->prepare("
            UPDATE stundenzettel s
            SET ist_stunden = (
              SELECT COALESCE(SUM(z.stunden),0)
              FROM zeiteintraege z
              WHERE z.stundenzettel_id = s.stundenzettel_id
            ),
            aktualisiert_am = NOW()
            WHERE s.stundenzettel_id = :id
        ");
        $st->execute([':id' => $szId]);
    }

    public static function holeIststundenAktuellerMonat(
        PDO $pdo,
        int $benutzerId,
        int $monat,
        int $jahr
    ): float {
        $sql = "SELECT ist_stunden
                FROM stundenzettel
                WHERE benutzer_id = :bid
                  AND monat       = :monat
                  AND jahr        = :jahr
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':bid'   => $benutzerId,
            ':monat' => $monat,
            ':jahr'  => $jahr,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return 0.0;
        }

        return (float)$row['ist_stunden'];
    }

    public static function bucheUrlaub(PDO $pdo, int $szId, float $tage): void {
        if ($tage <= 0) {
            return; // nichts zu tun
        }

        $st = $pdo->prepare("
            UPDATE stundenzettel
            SET urlaub_gesamt = urlaub_gesamt + :tage,
                aktualisiert_am = NOW()
            WHERE stundenzettel_id = :id
        ");
        $st->execute([
            ':tage' => $tage,
            ':id'   => $szId,
        ]);
    }

    public static function monatsuebersichtQuartal(
        PDO $pdo,
        int $benutzerId,
        DateTimeImmutable $stichtag
    ): array {
        $jahr    = (int)$stichtag->format('Y');
        $monat   = (int)$stichtag->format('n');
        $quartal = (int)ceil($monat / 3);

        $quartalStartMonat = (($quartal - 1) * 3) + 1;
        $quartalStart = new DateTimeImmutable(sprintf('%04d-%02d-01', $jahr, $quartalStartMonat));
        $quartalEnde  = $quartalStart->modify('+3 months')->modify('-1 day');

        // NEU: Urlaubstage für das gesamte Quartal auf einmal berechnen
        $urlaubMap = CUrlaubsantragRepository::UrlaubfuerMonatsuebersicht(
            $pdo,
            $benutzerId,
            $quartalStart,
            $quartalEnde
        );


        $feiertage = self::feiertageFuerJahr($jahr);

        $quartalSummen = ['stunden' => 0.0, 'urlaub' => 0, 'krank' => 0];
        $monatSummen   = ['stunden' => 0.0, 'urlaub' => 0, 'krank' => 0];
        $aktuellerMonat = (int)$quartalStart->format('n');

        $tage = [];

        for ($tag = $quartalStart; $tag <= $quartalEnde; $tag = $tag->modify('+1 day')) {
            $datumSql     = $tag->format('Y-m-d');
            $datumAnzeige = $tag->format('d.m.Y');
            $wochentag    = (int)$tag->format('N');

            // Tageswerte über andere Repos holen
            $stunden   = CZeiteintragRepository::summeStundenProTag($pdo, $benutzerId, $tag);
            $urlaubCnt = $urlaubMap[$datumSql] ?? 0;

            $krank     = 0;

            $werte = [
                'stunden' => $stunden,
                'urlaub'  => $urlaubCnt,
                'krank'   => $krank,
            ];

            $monatSummen['stunden'] += $werte['stunden'];
            $monatSummen['urlaub']  += $werte['urlaub'];
            $monatSummen['krank']   += $werte['krank'];

            $quartalSummen['stunden'] += $werte['stunden'];
            $quartalSummen['urlaub']  += $werte['urlaub'];
            $quartalSummen['krank']   += $werte['krank'];

            $istWochenendeOderFeiertag =
                ($wochentag >= 6) || in_array($datumSql, $feiertage, true);

            $naechsterTag  = $tag->modify('+1 day');
            $monatWechsel  = ((int)$naechsterTag->format('n') !== $aktuellerMonat) || ($tag == $quartalEnde);

            $tage[] = [
                'datum_sql'        => $datumSql,
                'datum_anzeige'    => $datumAnzeige,
                'monat'            => $aktuellerMonat,
                'werte'            => $werte,
                'ist_feiertag_od'  => $istWochenendeOderFeiertag,
                'monat_wechsel'    => $monatWechsel,
            ];

            if ($monatWechsel) {
                $tage[] = [
                    'monat_summe' => [
                        'monat'   => $aktuellerMonat,
                        'jahr'    => $jahr,
                        'stunden' => $monatSummen['stunden'],
                        'urlaub'  => $monatSummen['urlaub'],
                        'krank'   => $monatSummen['krank'],
                    ],
                ];
                $tage[] = ['trenner' => true];

                $monatSummen = ['stunden' => 0.0, 'urlaub' => 0, 'krank' => 0];
                $aktuellerMonat = (int)$naechsterTag->format('n');
            }
        }

        return [
            'jahr'          => $jahr,
            'quartal'       => $quartal,
            'tage'          => $tage,
            'quartalSummen' => $quartalSummen,
        ];
    }

    protected static function feiertageFuerJahr(int $jahr): array
    {
        $feiertage = [
            sprintf('%04d-01-01', $jahr),
            sprintf('%04d-05-01', $jahr),
            sprintf('%04d-10-03', $jahr),
            sprintf('%04d-12-25', $jahr),
            sprintf('%04d-12-26', $jahr),
        ];

        $ostersonntag = date('Y-m-d', easter_date($jahr));
        $feiertage[] = date('Y-m-d', strtotime($ostersonntag . ' -2 days'));
        $feiertage[] = date('Y-m-d', strtotime($ostersonntag . ' +1 day'));
        $feiertage[] = date('Y-m-d', strtotime($ostersonntag . ' +39 days'));
        $feiertage[] = date('Y-m-d', strtotime($ostersonntag . ' +50 days'));

        return $feiertage;
    }

    public static function reicheEin(PDO $pdo, int $stundenzettelId, int $benutzerId): void
    {
        $st = $pdo->prepare("
            UPDATE stundenzettel
            SET eingereicht_am  = NOW(),
                eingereicht_von = :uid,
                aktualisiert_am = NOW()
            WHERE stundenzettel_id = :id
            AND eingereicht_am IS NULL
        ");
        $st->execute([
            ':uid' => $benutzerId,
            ':id'  => $stundenzettelId,
        ]);
    }

    public static function freigabeDurchMitarbeiter(
        PDO $pdo,
        int $stundenzettelId,
        int $mitarbeiterId,
        string $entscheidung
    ): void {
        $entscheidung = strtolower($entscheidung);
        if (!in_array($entscheidung, ['genehmigt', 'abgelehnt'], true)) {
            throw new InvalidArgumentException('Ungültige Entscheidung.');
        }

        // Prüfen, ob dieser Benutzer diesen Stundenzettel freigeben darf
        $checkSql = "
            SELECT sz.stundenzettel_id
            FROM stundenzettel sz
            JOIN benutzer b_mitarbeiter
              ON b_mitarbeiter.benutzer_id = sz.benutzer_id
            JOIN benutzer b_einreicher
              ON b_einreicher.benutzer_id = sz.eingereicht_von
            JOIN rollen r_m
              ON r_m.rollen_id = b_mitarbeiter.rollen_id
            JOIN rollen r_e
              ON r_e.rollen_id = b_einreicher.rollen_id
            WHERE sz.stundenzettel_id = :id
              AND sz.benutzer_id      = :uid
              AND sz.status           = 'entwurf'
              AND sz.eingereicht_am IS NOT NULL
              AND r_m.rollen_schluessel = 'Mitarbeiter'
              AND r_e.rollen_schluessel IN ('Teamleitung','Projektleitung')
        ";

        $stmt = $pdo->prepare($checkSql);
        $stmt->execute([
            ':id'  => $stundenzettelId,
            ':uid' => $mitarbeiterId,
        ]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new RuntimeException('Du darfst diesen Stundenzettel nicht freigeben.');
        }

        // Entscheidung setzen
        $updateSql = "
            UPDATE stundenzettel
            SET status        = :status,
                genehmigt_von = :uid,
                genehmigt_am  = NOW(),
                aktualisiert_am = NOW()
            WHERE stundenzettel_id = :id
              AND status = 'entwurf'
        ";

        $stUpdate = $pdo->prepare($updateSql);
        $stUpdate->execute([
            ':status' => $entscheidung,
            ':uid'    => $mitarbeiterId,
            ':id'     => $stundenzettelId,
        ]);
    }

    public static function freigabeDurchProjektleitung(
        PDO $pdo,
        int $stundenzettelId,
        int $projektleiterId,
        string $entscheidung
    ): void {
        $entscheidung = strtolower($entscheidung);
        if (!in_array($entscheidung, ['genehmigt', 'abgelehnt'], true)) {
            throw new InvalidArgumentException('Ungültige Entscheidung.');
        }

        $checkSql = "
            SELECT sz.stundenzettel_id
            FROM stundenzettel sz
            JOIN benutzer e
            ON e.benutzer_id = sz.eingereicht_von
            JOIN rollen r_e
            ON r_e.rollen_id = e.rollen_id
            JOIN benutzer b_pl
            ON b_pl.benutzer_id = :uid
            JOIN rollen r_pl
            ON r_pl.rollen_id = b_pl.rollen_id
            WHERE sz.stundenzettel_id = :id
            AND sz.status = 'entwurf'
            AND sz.eingereicht_am IS NOT NULL
            -- Einreicher ist TL oder Mitarbeiter
            AND r_e.rollen_schluessel IN ('Teamleitung','Mitarbeiter')
            -- Freigeber ist Projektleitung
            AND r_pl.rollen_schluessel = 'Projektleitung'
        ";

        $stmt = $pdo->prepare($checkSql);
        $stmt->execute([
            ':id'  => $stundenzettelId,
            ':uid' => $projektleiterId,
        ]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new RuntimeException('Du darfst diesen Stundenzettel nicht freigeben.');
        }

        $updateSql = "
            UPDATE stundenzettel
            SET status        = :status,
                genehmigt_von = :uid,
                genehmigt_am  = NOW(),
                aktualisiert_am = NOW()
            WHERE stundenzettel_id = :id
            AND status = 'entwurf'
        ";

        $stUpdate = $pdo->prepare($updateSql);
        $stUpdate->execute([
            ':status' => $entscheidung,
            ':uid'    => $projektleiterId,
            ':id'     => $stundenzettelId,
        ]);
    }

    public static function freigabeDurchTeamleitung(
        PDO $pdo,
        int $stundenzettelId,
        int $teamleiterId,
        string $entscheidung
    ): void {
        $entscheidung = strtolower($entscheidung);
        if (!in_array($entscheidung, ['genehmigt', 'abgelehnt'], true)) {
            throw new InvalidArgumentException('Ungültige Entscheidung.');
        }

        $checkSql = "
            SELECT sz.stundenzettel_id
            FROM stundenzettel sz
            JOIN benutzer e
            ON e.benutzer_id = sz.eingereicht_von
            JOIN rollen r_e
            ON r_e.rollen_id = e.rollen_id
            JOIN benutzer b_tl
            ON b_tl.benutzer_id = :uid
            JOIN rollen r_tl
            ON r_tl.rollen_id = b_tl.rollen_id
            WHERE sz.stundenzettel_id = :id
            AND sz.status = 'entwurf'
            AND sz.eingereicht_am IS NOT NULL
            -- Einreicher ist Projektleitung oder Mitarbeiter
            AND r_e.rollen_schluessel IN ('Projektleitung','Mitarbeiter')
            -- Freigeber ist Teamleitung
            AND r_tl.rollen_schluessel = 'Teamleitung'
        ";

        $stmt = $pdo->prepare($checkSql);
        $stmt->execute([
            ':id'  => $stundenzettelId,
            ':uid' => $teamleiterId,
        ]);

        if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new RuntimeException('Du darfst diesen Stundenzettel nicht freigeben.');
        }

        $updateSql = "
            UPDATE stundenzettel
            SET status        = :status,
                genehmigt_von = :uid,
                genehmigt_am  = NOW(),
                aktualisiert_am = NOW()
            WHERE stundenzettel_id = :id
            AND status = 'entwurf'
        ";

        $stUpdate = $pdo->prepare($updateSql);
        $stUpdate->execute([
            ':status' => $entscheidung,
            ':uid'    => $teamleiterId,
            ':id'     => $stundenzettelId,
        ]);
    }

}