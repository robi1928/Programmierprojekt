-- Funktioniert, indem:
-- 1. phpMyAdmin starten
-- 2. Datenbank "db" anlegen
-- 3. db.sql importieren

-- Grundgerüst kopiert von Dr. Heimann. Erweitert mit maria.db, Fehlerkorrekturen mit ChatGTP
-- Falls Tabellen schon existieren → löschen (Reihenfolge wegen Fremdschlüsseln wichtig (Fehler im Heimann Code))
DROP TABLE IF EXISTS zeiteintraege;
DROP TABLE IF EXISTS urlaubsantraege;
DROP TABLE IF EXISTS urlaubskonten;
DROP TABLE IF EXISTS stundenzettel;
DROP TABLE IF EXISTS arbeitsorte;
DROP TABLE IF EXISTS benutzer;
DROP TABLE IF EXISTS rollen;
DROP TABLE IF EXISTS vorgabenAuftraggeber;

-- Rollen-Tabelle: definiert, welche Arten von Benutzer*innen es gibt
CREATE TABLE rollen (
  rollen_id       TINYINT PRIMARY KEY,
  rollen_schluessel ENUM('mitarbeiter','teamleitung','projektleitung') NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; -- nicht in Vorlage von Heimann. Eingebaut, damit wirklich alle Zeichen möglich. Überlegen, ob als Datenbank default setzen (spart wahrscheinlich Zeilen)

-- Benutzer-Tabelle
CREATE TABLE benutzer (
  benutzer_id     INT AUTO_INCREMENT PRIMARY KEY,
  vorname         VARCHAR(100) NOT NULL,
  nachname        VARCHAR(100) NOT NULL,
  email           VARCHAR(255) NOT NULL UNIQUE,
  rollen_id       TINYINT NOT NULL,
  wochenstunden DECIMAL(4,1) NOT NULL,
  einstellungsdatum TIMESTAMP NOT NULL,
  aktiv           TINYINT(1) NOT NULL DEFAULT 1 /*1 = aktiv, 0 = deaktiviert*/,
  erstellt_am     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  aktualisiert_am TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (rollen_id) REFERENCES rollen(rollen_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- die Variante mit "Source" funktioniert speziell bei MySQL
-- SOURCE insert_benutzer.sql;

-- Arbeitsorte (Dropdown-Auswahl, optional)
CREATE TABLE arbeitsorte (
  ort_id      TINYINT PRIMARY KEY,
  bezeichnung VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stundenzettel (Kopf, pro Monat/Person)
CREATE TABLE stundenzettel (
  stundenzettel_id INT PRIMARY KEY AUTO_INCREMENT,
  benutzer_id      INT NOT NULL,
  monat            TINYINT  NOT NULL CHECK (monat BETWEEN 1 AND 12),
  jahr             SMALLINT NOT NULL CHECK (jahr BETWEEN 2000 AND 2040) /*check sicherte Wertebereich*/,
  status           ENUM('entwurf','eingereicht','genehmigt','abgelehnt') NOT NULL DEFAULT 'entwurf',
  eingereicht_am   DATETIME NULL,
  genehmigt_von    INT NULL,
  genehmigt_am     DATETIME NULL,
  erstellt_am      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  aktualisiert_am  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  soll_stunden     DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  ist_stunden      DECIMAL(6,2) NOT NULL DEFAULT 0.00,
  saldo_stunden    DECIMAL(6,2) AS (ist_stunden - soll_stunden) STORED,
  urlaub_gesamt    DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  UNIQUE KEY uq_benutzer_monat_jahr (benutzer_id, monat, jahr) /*verhindert doppelte Einträge*/,
  FOREIGN KEY (benutzer_id)   REFERENCES benutzer(benutzer_id) ON DELETE CASCADE,
  FOREIGN KEY (genehmigt_von) REFERENCES benutzer(benutzer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Zeiteinträge (Details pro Tag)
CREATE TABLE zeiteintraege (
  stundenzettel_id INT NOT NULL,
  tag              TINYINT NOT NULL CHECK (tag BETWEEN 1 AND 31),
  ort_id           TINYINT NOT NULL,
  stunden          DECIMAL(4,2) NOT NULL DEFAULT 0.00,
  bemerkung        VARCHAR(500) NULL,
  PRIMARY KEY (stundenzettel_id, tag),
  FOREIGN KEY (stundenzettel_id) REFERENCES stundenzettel(stundenzettel_id) ON DELETE CASCADE,
  FOREIGN KEY (ort_id)           REFERENCES arbeitsorte(ort_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Urlaubskonten (jährlicher Anspruch pro Benutzer)
CREATE TABLE urlaubskonten (
  konto_id       INT PRIMARY KEY AUTO_INCREMENT /*automatisch +1*/,
  benutzer_id    INT NOT NULL,
  jahr           SMALLINT NOT NULL CHECK (jahr BETWEEN 2000 AND 2040),
  anspruch_tage  DECIMAL(5,2) NOT NULL,
  uebertrag_tage DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  genutzt_tage   DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  UNIQUE KEY uq_benutzer_jahr (benutzer_id, jahr),
  FOREIGN KEY (benutzer_id) REFERENCES benutzer(benutzer_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Urlaubsanträge
CREATE TABLE urlaubsantraege (
  antrag_id      INT PRIMARY KEY AUTO_INCREMENT,
  benutzer_id    INT NOT NULL,
  start_datum    DATE NOT NULL,
  ende_datum     DATE NOT NULL,
  tage           DECIMAL(5,2) NOT NULL,
  status         ENUM('entwurf','eingereicht','genehmigt','abgelehnt','storniert') NOT NULL DEFAULT 'entwurf',
  eingereicht_am DATETIME NULL,
  entschieden_von INT NULL,
  entschieden_am  DATETIME NULL,
  bemerkung       VARCHAR(500) NULL,
  erstellt_am     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  aktualisiert_am TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CHECK (ende_datum >= start_datum),
  FOREIGN KEY (benutzer_id)     REFERENCES benutzer(benutzer_id) ON DELETE CASCADE,
  FOREIGN KEY (entschieden_von) REFERENCES benutzer(benutzer_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabelle der Quartals Vorgaben.
CREATE TABLE vorgabenAuftraggeber (
     jahr SMALLINT NOT NULL CHECK (jahr BETWEEN 2000 AND 2040),
    quartal TINYINT NOT NULL CHECK (quartal BETWEEN 1 AND 4),
    erwarteteKrankenquote DECIMAL(5,2) NOT NULL CHECK (erwarteteKrankenquote BETWEEN 0 AND 100),
    sollStunden INT NOT NULL CHECK (sollStunden > 0),
    istStunden INT NOT NULL CHECK (istStunden >= 0),
    toleranz DECIMAL(5,2) NOT NULL CHECK (toleranz BETWEEN 0 AND 100),

    -- Quartal pro Jahr darf nur EINMAL vorkommen
    UNIQUE KEY uq_jahr_quartal (jahr, quartal)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;