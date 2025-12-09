<?php

class CArbeitsort {
    private ?int $ort_id = null;
    private $bezeichnung;

    public function __construct(string $bezeichnung, ?int $ort_id = null) {
        $this->bezeichnung = $bezeichnung;
        $this->ort_id = $ort_id;
    }

    public function getId(): ?int { return $this->ort_id; }
}

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
