<?php

class CBenutzer {

    //nur in dieser Klasse verfügbare Variablen
    private $benutzer_id;
    private $vorname;
    private $nachname;
    private $email;
    private $rollen_id;
    private $wochenstunden;
    private $urlaubstage;
    private $einstellungsdatum;
    private $aktiv;
    private $erstellt_am;
 


    //die Funktion zur Erstellung
    //Benutzer_ID wird automatisch erstellt

    public function __construct($in_Vorname, $in_Nachname, $in_EMail, $in_Rolle, $in_Wochenstunden, $in_Urlaubstage, $in_Einstellungsdatum) {
        $this->vorname = $in_Vorname;
        $this->nachname = $in_Nachname;
        $this->email = $in_EMail;
        $this->rollen_id = $in_Rolle;
        $this->wochenstunden = $in_Wochenstunden;
        $this->urlaubstage = $in_Urlaubstage;
        $this->einstellungsdatum = $in_Einstellungsdatum;
        // Automatisch gesetzte Werte:
        $this->aktiv = 1; // Nutzer ist standardmäßig aktiv
        $this->erstellt_am = date('Y-m-d H:i:s'); // aktuelles Datum + Uhrzeit
        $this->aktualisiert_am = date('Y-m-d H:i:s'); // aktuelles Datum + Uhrzeit
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

public function GetUrlaubstage() {
    return $this->urlaubstage;
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
    include 'db.php'; //db.php statt pdo.php
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
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
    return $result;
}


//Die Update Funktion
public function Update() {
    include 'db.php'; //db.php statt pdo.php
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
                erstellt_am = :erstellt_am,
                einstellungsdatum = :einstellungsdatum
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
            'erstellt_am'       => $this->erstellt_am,
            'aktualisiert_am'    => $this->aktualisiert_am
        ));
    } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
    }
    return $result;
}

}
?>