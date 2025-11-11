<?php
// orientiert an cc_benutzer. Dringend klären, ob das so bleiben soll.

class CZeiteintrag {
    private int $stundenzettel_id;
    private int $tag;
    private int $ort_id;
    private float $stunden;
    private ?string $bemerkung;

    public function __construct(int $stundenzettel_id, int $tag, int $ort_id, float $stunden, ?string $bemerkung = null) {
        $this->stundenzettel_id = $stundenzettel_id;
        $this->tag = $tag;
        $this->ort_id = $ort_id;
        $this->stunden = $stunden;
        $this->bemerkung = $bemerkung;
    }

    public function save(PDO $pdo): bool {
        $sql = "
            INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
            VALUES (:sid, :tag, :ort, :std, :bem)
            ON DUPLICATE KEY UPDATE
                ort_id=VALUES(ort_id),
                stunden=VALUES(stunden),
                bemerkung=VALUES(bemerkung)
        ";
        $st = $pdo->prepare($sql);
        return $st->execute([
            ':sid'=>$this->stundenzettel_id,
            ':tag'=>$this->tag,
            ':ort'=>$this->ort_id,
            ':std'=>$this->stunden,
            ':bem'=>$this->bemerkung
        ]);
    }

    // künftig sinnvoll
    public function delete(PDO $pdo): bool {
        $st = $pdo->prepare("DELETE FROM zeiteintraege WHERE stundenzettel_id=:sid AND tag=:tag");
        return $st->execute([':sid'=>$this->stundenzettel_id, ':tag'=>$this->tag]);
    }
}

// war ursprünglich in Erfassung mitarbeiter
final class CZeiteintragRepository {
    public static function alleZuZettel(PDO $pdo, int $stundenzettel_id): array {
        $st = $pdo->prepare("
            SELECT tag, ort_id, stunden, bemerkung
            FROM zeiteintraege
            WHERE stundenzettel_id = ?
            ORDER BY tag
        ");
        $st->execute([$stundenzettel_id]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function upsert(PDO $pdo, int $stundenzettel_id, int $tag, int $ort_id, float $stunden, ?string $bemerkung = null): bool {
        $z = new CZeiteintrag($stundenzettel_id, $tag, $ort_id, $stunden, $bemerkung);
        return $z->save($pdo);
    }

}

class CErfassungVerarbeitung
{
    // Validiert POST, berechnet Stunden, liefert [Date, ort_id, stunden, bemerkung]
    public static function validateInput(array $post, array $orte, string $maxDate): array
    {
        $datum = $post['datum'] ?? '';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datum)) throw new RuntimeException('Ungültiges Datum.');
        if ($datum > $maxDate) throw new RuntimeException('Datum muss in der Vergangenheit liegen');

        $status = $post['status'] ?? 'none';
        $bemerkung = trim($post['bemerkung'] ?? '') ?: null;

        if (in_array($status, ['krank','urlaub'], true)) {
            return [$datum, 0, 0.0, $status];
        }

        $start = trim($post['start'] ?? '');
        $ende  = trim($post['ende'] ?? '');
        if (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $ende)) {
            throw new RuntimeException('Bitte Start- und Endzeit angeben.');
        }

        [$sh,$sm] = array_map('intval', explode(':',$start));
        [$eh,$em] = array_map('intval', explode(':',$ende));
        $startMin = $sh*60 + $sm; $endeMin = $eh*60 + $em;
        if ($endeMin <= $startMin) throw new RuntimeException('Endzeit muss nach Startzeit liegen.');
        $stunden = round(($endeMin - $startMin)/60, 2);
        if ($stunden > 24) throw new RuntimeException('So viele Stunden hat kein Tag.');

        $validOrte = array_column($orte, 'ort_id');
        $ortId = (int)($post['ort_id'] ?? 0);
        if (!in_array($ortId, $validOrte, true)) throw new RuntimeException('Ungültiger Arbeitsort.');

        return [$datum, $ortId, $stunden, $bemerkung];
    }

    // Komplettablauf: validieren, Stundenzettel sicherstellen, upsert, recalc. Rückgabe: stundenzettel_id
    public static function erfasse(PDO $pdo, array $post, array $orte, int $benutzerId, string $maxDate): int
    {
        [$datum, $ortId, $stunden, $bemerkung] = self::validateInput($post, $orte, $maxDate);

        $d = new DateTimeImmutable($datum);
        $monat = (int)$d->format('n');
        $jahr  = (int)$d->format('Y');
        $tag   = (int)$d->format('j');

        // Stundenzettel finden/erstellen
        $szId = CStundenzettelRepository::findeOderErstelle($pdo, $benutzerId, $monat, $jahr);

        // Zeiteintrag speichern
        CZeiteintragRepository::upsert($pdo, $szId, $tag, $ortId, $stunden, $bemerkung);

        // Ist-Stunden neu berechnen
        CStundenzettelRepository::recalcIst($pdo, $szId);

        return $szId;
    }
}
