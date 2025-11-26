<?php

include 'bb_db.php'; //db.php statt pdo.php

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
    if( $this->StundenzettelGeladen == false ) {
        $this->Stundenzettel = [];
        try {
            include 'bb_db.php'; //db.php statt pdo.php
            $statement = $pdo->prepare("SELECT stundenzettel_id FROM stundenzettel WHERE benutzer_id = :benutzer_id");
            $statement->execute(array('benutzer_id' => $this->benutzer_id));
            $rows = $statement->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $row) {
                $stundenzettel = new CStundenzettel($row['stundenzettel_id']);
                $stundenzettel->LadeAusDB();
                $this->Stundenzettel[] = $stundenzettel;
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
        if( $this->Stundenzettel[i]->IstUrlaub()) {
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
}

// passt hier eigentlich nicht richtig, aber benutzer.php wird eigentlich immer mit aufgerufen. Damit nicht auf html Seite
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

?>