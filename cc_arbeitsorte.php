<?php
// orientiert an cc_benutzer. Dringend klären, ob das so bleiben soll.

class CArbeitsort {
    private ?int $ort_id = null;
    private $bezeichnung;

    public function __construct(string $bezeichnung, ?int $ort_id = null) {
        $this->bezeichnung = $bezeichnung;
        $this->ort_id = $ort_id;
    }

    public function getId(): ?int { return $this->ort_id; }

    // automatisch zusätlich ausgespuckt von GTP bei Fehlerkorrektur
    // ist beides sinnvoll evtl, löschen, falls nicht benötigt
    public function update(PDO $pdo): bool {
        if ($this->ort_id === null) return false;
        $st = $pdo->prepare("UPDATE arbeitsorte SET bezeichnung = :b WHERE ort_id = :id");
        return $st->execute([':b'=>$this->bezeichnung, ':id'=>$this->ort_id]);
    }

    public function delete(PDO $pdo): bool {
        if ($this->ort_id === null) return false;
        $st = $pdo->prepare("DELETE FROM arbeitsorte WHERE ort_id = :id");
        return $st->execute([':id'=>$this->ort_id]);
    }
}

// wollen wir nach IDs Filtern? Für Dashboard schauen, ansonsten löschen
final class CArbeitsortRepository {
    public static function findeNachId(PDO $pdo, int $id): ?array {
        $st = $pdo->prepare("SELECT ort_id, bezeichnung FROM arbeitsorte WHERE ort_id = ? LIMIT 1");
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public static function alle(PDO $pdo): array {
        $st = $pdo->query("SELECT ort_id, bezeichnung FROM arbeitsorte ORDER BY bezeichnung ASC");
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
