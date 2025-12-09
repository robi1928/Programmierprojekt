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
    private ?int $genehmigt_von = null;
    private ?string $genehmigt_am = null;
    private float $soll_stunden = 0.0;
    private float $ist_stunden = 0.0;
    private float $saldo_stunden = 0.0;
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

    public function IstUrlaub(): bool {
    return $this->status === 'urlaub';
}
    public static function fromRow(array $row): self {
        $obj = new self(
            (int)$row['benutzer_id'],
            (int)$row['monat'],
            (int)$row['jahr']
        );

        $obj->stundenzettel_id = (int)$row['stundenzettel_id'];
        $obj->status           = $row['status'] ?? 'entwurf';
        $obj->soll_stunden     = (float)($row['soll_stunden'] ?? 0);
        $obj->ist_stunden      = (float)($row['ist_stunden'] ?? 0);
        $obj->saldo_stunden    = isset($row['saldo_stunden']) ? (float)$row['saldo_stunden'] : 0.0;
        $obj->erstellt_am      = $row['erstellt_am'] ?? date('Y-m-d H:i:s');
        $obj->aktualisiert_am  = $row['aktualisiert_am'] ?? date('Y-m-d H:i:s');

        return $obj;
    }

    public function getId(): ?int { return $this->stundenzettel_id; }

    // das Folgende war eigentlich alles in Erfassung Mitarbeiter und ist jetzt für die Fachobjekte hierrein gezogen. Ursprungscode geschrieben von mir, jetzt angepasst mit GTP für die Fachobjekte
    public function create(PDO $pdo): bool {
        // wollen wir bei den vier Statuus bleiben?
        $allowed = ['entwurf','eingereicht','genehmigt','abgelehnt'];
        if (!in_array($this->status, $allowed, true)) {
            $this->status = 'entwurf';
        }

        // DB verwaltet erstellt_am/aktualisiert_am via DEFAULT/NOW()
        $sql = "
            INSERT INTO stundenzettel
            (benutzer_id, monat, jahr, status, soll_stunden, ist_stunden)
            VALUES (:b,:m,:j,:s,:soll,:ist)
        ";
        $st = $pdo->prepare($sql);
        $ok = $st->execute([
            ':b'    => $this->benutzer_id,
            ':m'    => $this->monat,
            ':j'    => $this->jahr,
            ':s'    => $this->status,
            ':soll' => $this->soll_stunden,
            ':ist'  => $this->ist_stunden,
        ]);
        if ($ok) {
            $this->stundenzettel_id = (int)$pdo->lastInsertId();
        }
        return $ok;
    }
}

function holeIststundenAktuellerMonat(PDO $pdo, int $benutzerId, int $monat, int $jahr): float
{
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

// beschreibt das wie (laden, aktualisieren)
final class CStundenzettelRepository {
    public static function findeOderErstelle(PDO $pdo, int $benutzerId, int $monat, int $jahr): int {
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
        $st->execute([':b'=>$benutzerId, ':m'=>$monat, ':j'=>$jahr]);
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
        $st->execute([':id'=>$szId]);
    }
}