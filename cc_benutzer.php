<?php

include 'bb_db.php'; //db.php statt pdo.php
include 'cc_stundenzettel.php';
include 'cc_VorgabenAuftraggeber.php';

class CBenutzer {

    //nur in dieser Klasse verfügbare Variablen
    private $benutzer_id;
    private $vorname;
    private $nachname;
    private $email;
    private $rollen_id;
    private $wochenstunden;
    private $urlaubstage;   // Anzahl Tage Urlaubsanspruch aktuelles Jahr
    private $Urlaubsantraege;
    private $einstellungsdatum;
    private $aktiv;
    private $erstellt_am;
    private $aktualisiert_am;
    private $StundenzettelGeladen;
    private $Stundenzettel = [];

    //die Funktion zur Erstellung
    //Benutzer_ID wird automatisch erstellt

    // Ggf. Konstruktor definieren, bei dem nur die ID übergeben wird und eine Funktion zum Laden der restlichen Daten aus der DB
    public function __construct($in_ID) {
        $this->benutzer_id = $in_ID;
        $this->vorname = "";
        $this->nachname = "";
        $this->email = "";
        $this->rollen_id = -1;
        $this->wochenstunden = -1;
        $this->urlaubstage = -1;
        $this->einstellungsdatum = "";
        // Automatisch gesetzte Werte:
        $this->aktiv = 1; // Nutzer ist standardmäßig aktiv
        $this->erstellt_am = date('Y-m-d H:i:s'); // aktuelles Datum + Uhrzeit
        $this->aktualisiert_am = date('Y-m-d H:i:s'); // aktuelles Datum + Uhrzeit
        $this->Stundenzettel = [];
        $this->StundenzettelGeladen = false;
    }

    // Funktion zum Laden bestehender Daten aus der Datenbank
    public function Init($in_Vorname, $in_Nachname, $in_EMail, $in_Rolle, $in_Wochenstunden, $in_Urlaubstage, $in_Einstellungsdatum) {
        $this->vorname = $in_Vorname;
        $this->nachname = $in_Nachname;
        $this->email = $in_EMail;
        $this->rollen_id = $in_Rolle;
        $this->wochenstunden = $in_Wochenstunden;
        $this->urlaubstage = $in_Urlaubstage;
        $this->einstellungsdatum = $in_Einstellungsdatum;
     }
    
    //einzelne Get Fumktionen
    public function GetID() {
        return $this->benutzer_id;
    }
    
    public function GetVorname() {
        return $this->vorname;
    }
    
    public function GetNachname() {
        return $this->nachname;
    }
    
    public function GetEMail() {
        return $this->email;
    }

    public function GetRolle() {
        return $this->rollen_id;
    }    

    public function GetWochenstunden() {
    return $this->wochenstunden;
}
// liefert den Resturlaub im aktuellen Jahr zurück --> Name verwirrend, ggf. umbenennen
public function GetUrlaubstage() {
    return $this->GetUrlaubsanspruch() - $this->GetUrlaubGenommen();
}

// liefert den Urlaubsanspruch im aktuellen Jahr zurück
public function GetUrlaubsanspruch() {
    return $this->urlaubstage;
}

private function LadeStundenzettel() {
    if ($this->StundenzettelGeladen == false) {
        $this->Stundenzettel = [];
        try {
            global $pdo;
            $statement = $pdo->prepare("
                SELECT 
                    stundenzettel_id,
                    benutzer_id,
                    monat,
                    jahr,
                    status,
                    soll_stunden,
                    ist_stunden
                FROM stundenzettel
                WHERE benutzer_id = :benutzer_id
            ");
            $statement->execute(['benutzer_id' => $this->benutzer_id]);
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $this->Stundenzettel[] = CStundenzettel::fromRow($row);
            }
            $this->StundenzettelGeladen = true;
        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }
    }
}

// liefert die genommenen Urlaubstage im aktuellen Jahr zurück
public function GetUrlaubGenommen() {
    $this->LadeStundenzettel();
    $UrlaubGenommen = 0;
    for( $i = 0; $i < count($this->Stundenzettel); $i++) {
        if( $this->Stundenzettel[$i]->IstUrlaub()) {
            $UrlaubGenommen++;
        }
    }
    return $UrlaubGenommen;
}

public function GetEinstellungsdatum() {
    return $this->einstellungsdatum;
}

public function GetAktiv() {
    return $this->aktiv;
}

public function GetErstelltAm() {
    return $this->erstellt_am;
}

public function GetAktualisiertAm() {
    return $this->aktualisiert_am;
}

// der Creator
public function Create() {
    global $pdo;
    $result = false; // Standardwert, falls SQL fehlschlägt
    
    try {
        $statement = $pdo->prepare("
            INSERT INTO benutzer (
                vorname,
                nachname,
                email,
                rollen_id,
                wochenstunden,
                urlaubstage,
                einstellungsdatum,
                aktiv,
                erstellt_am,
                aktualisiert_am
            ) VALUES (
                :vorname,
                :nachname,
                :email,
                :rollen_id,
                :wochenstunden,
                :urlaubstage,
                :einstellungsdatum,
                :aktiv,
                :erstellt_am,
                :aktualisiert_am
            )
        ");
        $result = $statement->execute(array(
            'vorname'           => $this->vorname,
            'nachname'          => $this->nachname,
            'email'             => $this->email,
            'rollen_id'         => $this->rollen_id,
            'wochenstunden'     => $this->wochenstunden,
            'urlaubstage'       => $this->urlaubstage,
            'einstellungsdatum' => $this->einstellungsdatum,
            'aktiv'             => $this->aktiv,
            'erstellt_am'       => $this->erstellt_am,
            'aktualisiert_am'    => $this->aktualisiert_am
        ));
    if ($result) { $this->benutzer_id = (int)$pdo->lastInsertId();}
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
    return $result;
}


//Die Update Funktion
public function Update() {
    global $pdo;
    $this->aktualisiert_am = date('Y-m-d H:i:s');
    $result = false;
    try {
        $statement = $pdo->prepare("
            UPDATE benutzer SET
                vorname = :vorname,
                nachname = :nachname,
                email = :email,
                rollen_id = :rollen_id,
                wochenstunden = :wochenstunden,
                urlaubstage = :urlaubstage,
                einstellungsdatum = :einstellungsdatum,
                aktiv = :aktiv,
                aktualisiert_am = :aktualisiert_am
            WHERE benutzer_id = :benutzer_id
        ");

        $result = $statement->execute(array(
            'benutzer_id'       => $this->benutzer_id,
            'vorname'           => $this->vorname,
            'nachname'          => $this->nachname,
            'email'             => $this->email,
            'rollen_id'         => $this->rollen_id,
            'wochenstunden'     => $this->wochenstunden,
            'urlaubstage'       => $this->urlaubstage,
            'einstellungsdatum' => $this->einstellungsdatum,
            'aktiv'             => $this->aktiv,
            'aktualisiert_am'   => $this->aktualisiert_am
        ));
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
    return $result;
}

public function LoadBenutzerById(PDO $pdo, int $benutzer_id): ?CBenutzer {
    $stmt = $pdo->prepare("SELECT * FROM benutzer WHERE benutzer_id = :id");
    $stmt->execute([':id' => $benutzer_id]);    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return null;
    $benutzer = new CBenutzer($row['benutzer_id']);
    $benutzer->Init(
        $row['vorname'],
        $row['nachname'],
        $row['email'],
        $row['rollen_id'],
        $row['wochenstunden'],
        $row['urlaubstage'],
        $row['einstellungsdatum']
    );
    return $benutzer;    
}

public function Load() {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM benutzer WHERE benutzer_id = :id");
    $stmt->execute([':id' => $this->benutzer_id]);    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return false;
    $this->Init(
        $row['vorname'],
        $row['nachname'],
        $row['email'],
        $row['rollen_id'],
        $row['wochenstunden'],
        $row['urlaubstage'],
        $row['einstellungsdatum']
    );
    $this->aktiv = $row['aktiv'];
    $this->erstellt_am = $row['erstellt_am'];
    $this->aktualisiert_am = $row['aktualisiert_am'];
    return true;
}

//---------------------------------
//Der Teil funktioniert noch nicht richtig


public function berechneArbeitstageMitFeiertagenImMonat(int $jahr, int $monat): int
{
    if ($monat < 1 || $monat > 12) {
        throw new InvalidArgumentException("Ungültiger Monat: $monat (gültig: 1–12)");
    }

    $feiertage = CVorgabenAuftraggeber::feiertageSH($jahr);

    $start = new DateTime(sprintf("%04d-%02d-01", $jahr, $monat));
    $ende  = (clone $start)->modify("last day of this month");

    $arbeitstage = 0;

    for ($d = clone $start; $d <= $ende; $d->modify("+1 day")) {
        $datum = $d->format("Y-m-d");
        $wochentag = (int)$d->format("N");

        if ($wochentag >= 6) continue;
        if (in_array($datum, $feiertage)) continue;

        $arbeitstage++;
    }

    return $arbeitstage;
}

public function GetSollStundenAktuellerMonat($SollWochenstunden): int {
    $heute = new DateTime();
    $monat = (int)$heute->format("n");
    $jahr = (int)$heute->format("Y");
    
    $StundenProTag = $SollWochenstunden / 5;
   
   // $arbeitstageImMonat = CBenutzer::berechneArbeitstageMitFeiertagenImMonat($jahr, $monat); // "mit Feiertagen" heißt , dass Feiertage nicht als Arbeitstage gezählt werden
    $arbeitstageImMonat = self::berechneArbeitstageMitFeiertagenImMonat($jahr, $monat);
    $sollStunden = $StundenProTag * $arbeitstageImMonat;
    return (int)$sollStunden;
    }
 

}

// passt hier eigentlich nicht richtig, aber benutzer.php wird eigentlich immer mit aufgerufen. Damit nicht auf html Seite
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

?>