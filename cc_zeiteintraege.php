<?php

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

    /**
     * Liefert die Summe der Stunden für einen Benutzer an einem Tag.
     */
    public static function summeStundenProTag(PDO $pdo, int $benutzerId, DateTimeInterface $tag): float
    {
        $jahr   = (int)$tag->format('Y');
        $monat  = (int)$tag->format('n'); // 1–12
        $tagNum = (int)$tag->format('j'); // 1–31

        $sql = "
            SELECT SUM(z.stunden) AS summe
            FROM zeiteintraege z
            JOIN stundenzettel s ON z.stundenzettel_id = s.stundenzettel_id
            WHERE s.benutzer_id = :bid
              AND s.jahr        = :jahr
              AND s.monat       = :monat
              AND z.tag         = :tag
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':bid'   => $benutzerId,
            ':jahr'  => $jahr,
            ':monat' => $monat,
            ':tag'   => $tagNum,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row && $row['summe'] !== null ? (float)$row['summe'] : 0.0;
    }

    public static function ermittleKrankheitstagefuerMonatsuebersicht(
        PDO $pdo,
        int $benutzerId,
        int $monat,
        int $jahr
    ): array {
        $sql = "
            SELECT
                z.tag AS tag,
                CASE
                    WHEN SUM(CASE WHEN z.stunden = 0 THEN 1 ELSE 0 END) > 0
                    THEN 1
                    ELSE 0
                END AS krank
            FROM zeiteintraege z
            JOIN stundenzettel s ON z.stundenzettel_id = s.stundenzettel_id
            WHERE s.benutzer_id = :bid
              AND s.jahr        = :jahr
              AND s.monat       = :monat
            GROUP BY z.tag
            ORDER BY z.tag
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':bid'   => $benutzerId,
            ':jahr'  => $jahr,
            ':monat' => $monat,
        ]);

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tagNum = (int)$row['tag'];
            // Datum im Format Y-m-d bauen, passend zu $t['datum_sql']
            $datum = sprintf('%04d-%02d-%02d', $jahr, $monat, $tagNum);
            $result[$datum] = (int)$row['krank']; // 0 oder 1
        }

        return $result;
    }

}

class CErfassungVerarbeitung
{
    // Validiert POST, berechnet Stunden, liefert [Date, ort_id, stunden, bemerkung]
    public static function validateInput(array $post, array $orte, string $maxDate): array
    {
        $datum = $post['datum'] ?? '';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datum)) {
            throw new RuntimeException('Ungültiges Datum.');
        }

        $status    = $post['status'] ?? 'none';
        $bemerkung = trim($post['bemerkung'] ?? '') ?: null;

        // Nur für Arbeitstag/Krank begrenzen, Urlaub darf in der Zukunft liegen
        if ($status !== 'urlaub' && $datum > $maxDate) {
            throw new RuntimeException('Datum muss in der Vergangenheit liegen.');
        }

        // Krank / Urlaub: keine Zeiten, 0 Stunden, Ort auf "k. A." (0)
        if (in_array($status, ['krank', 'urlaub'], true)) {
            $ortId   = 0;
            $stunden = 0.0;
            return [$datum, $status, $ortId, $stunden, $bemerkung];
        }

        // Ab hier nur noch Arbeitstag
        $start = trim($post['start'] ?? '');
        $ende  = trim($post['ende'] ?? '');
        if (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $ende)) {
            throw new RuntimeException('Bitte Start- und Endzeit angeben.');
        }

        [$sh, $sm] = array_map('intval', explode(':', $start));
        [$eh, $em] = array_map('intval', explode(':', $ende));
        $startMin  = $sh * 60 + $sm;
        $endeMin   = $eh * 60 + $em;

        if ($endeMin <= $startMin) {
            throw new RuntimeException('Endzeit muss nach Startzeit liegen.');
        }

        $stunden = round(($endeMin - $startMin) / 60, 2);
        if ($stunden > 24) {
            throw new RuntimeException('So viele Stunden hat kein Tag.');
        }

        $validOrte = array_column($orte, 'ort_id');
        $ortId     = (int)($post['ort_id'] ?? 0);
        if (!in_array($ortId, $validOrte, true)) {
            throw new RuntimeException('Ungültiger Arbeitsort.');
        }

        return [$datum, $status, $ortId, $stunden, $bemerkung];
    }

    // Komplettablauf: validieren, Stundenzettel sicherstellen, upsert, recalc. Rückgabe: stundenzettel_id
    public static function erfasse(PDO $pdo, array $post, array $orte, int $benutzerId, string $maxDate): int
    {
        [$datum, $status, $ortId, $stunden, $bemerkung] = self::validateInput($post, $orte, $maxDate);

        $d     = new DateTimeImmutable($datum);
        $monat = (int)$d->format('n');
        $jahr  = (int)$d->format('Y');
        $tag   = (int)$d->format('j');

        // Nur für Urlaub: optionales Enddatum einlesen
        $dEnd = null;
        $urlaubTage = 0.0;

        if ($status === 'urlaub') {
            $datumEndeStr = trim($post['datum_ende'] ?? '');

            if ($datumEndeStr !== '') {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $datumEndeStr)) {
                    throw new RuntimeException('Ungültiges Enddatum für Urlaub.');
                }

                $dEnd = new DateTimeImmutable($datumEndeStr);

                if ($dEnd < $d) {
                    throw new RuntimeException('Enddatum darf nicht vor dem Startdatum liegen.');
                }

                $diff = $d->diff($dEnd);
                $urlaubTage = (float)($diff->days + 1);   // inkl. Start- und Endtag
            } else {
                // kein Enddatum angegeben → 1 Tag Urlaub
                $urlaubTage = 1.0;
                $dEnd = null; // erzeugeGenehmigtenTag macht dann Start == Ende
            }
        }

        $pdo->beginTransaction();
        try {
            // Stundenzettel finden/erstellen
            $szId = CStundenzettelRepository::findeOderErstelle($pdo, $benutzerId, $monat, $jahr);


            // Zeiteintrag speichern (auch bei Urlaub/Krank → 0 Stunden & Ort 0)
            CZeiteintragRepository::upsert($pdo, $szId, $tag, $ortId, $stunden, $bemerkung);

            // Ist-Stunden neu berechnen
            CStundenzettelRepository::recalcIst($pdo, $szId);

            if ($status === 'urlaub') {
                // Nur Urlaubsantrag anlegen – KEIN direktes Buchen mehr
                CUrlaubsantragRepository::erzeugeGenehmigtenTag(
                    $pdo,
                    $benutzerId,
                    $d,      // Startdatum
                    $dEnd,   // kann null sein → Methode setzt Ende = Start
                    $bemerkung
                );
            }

            CStundenzettelRepository::reicheEin($pdo, $szId, $benutzerId);

            $pdo->commit();
            return $szId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}