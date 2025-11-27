-- Funktioniert, indem:
-- 1. phpMyAdmin starten
-- 2. Datenbank "db" anlegen
-- 3. db.sql importieren

-- Grundgerüst kopiert von Dr. Heimann. Erweitert mit maria.db, Fehlerkorrekturen mit ChatGTP
-- Falls Tabellen schon existieren → löschen (Reihenfolge wegen Fremdschlüsseln wichtig (Fehler im Heimann Code))
DROP TABLE IF EXISTS zeiteintraege;
DROP TABLE IF EXISTS urlaubsantraege;
DROP TABLE IF EXISTS urlaubskonten;
DROP TABLE IF EXISTS urlaubsarten;
DROP TABLE IF EXISTS stundenzettel;
DROP TABLE IF EXISTS arbeitsorte;
DROP TABLE IF EXISTS benutzer;
DROP TABLE IF EXISTS rollen;
DROP TABLE IF EXISTS vorgabenAuftraggeber;

-- Rollen-Tabelle: definiert, welche Arten von Benutzer*innen es gibt
CREATE TABLE rollen (
  rollen_id       TINYINT PRIMARY KEY,
  rollen_schluessel ENUM('mitarbeiter','teamleitung','admin') NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; -- nicht in Vorlage von Heimann. Eingebaut, damit wirklich alle Zeichen möglich. Überlegen, ob als Datenbank default setzen (spart wahrscheinlich Zeilen)

-- Benutzer-Tabelle
CREATE TABLE benutzer (
  benutzer_id     INT AUTO_INCREMENT PRIMARY KEY,
  vorname         VARCHAR(100) NOT NULL,
  nachname        VARCHAR(100) NOT NULL,
  email           VARCHAR(255) NOT NULL UNIQUE,
  rollen_id       TINYINT NOT NULL,
  wochenstunden DECIMAL(4,1) NOT NULL,
  urlaubstage     DECIMAL(3,1) NOT NULL,
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
  urlaub_bezahlt   DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  urlaub_unbezahlt DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  urlaub_sonder    DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  urlaub_gesamt    DECIMAL(5,2) AS (urlaub_bezahlt + urlaub_unbezahlt + urlaub_sonder) STORED,
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

-- Urlaubsarten
CREATE TABLE urlaubsarten (
  urlaubsart_id  TINYINT PRIMARY KEY,
  art_schluessel ENUM('bezahlt','unbezahlt','sonder') NOT NULL UNIQUE,
  beschreibung   VARCHAR(200) NULL
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
  urlaubsart_id  TINYINT NOT NULL,
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
  FOREIGN KEY (urlaubsart_id)   REFERENCES urlaubsarten(urlaubsart_id),
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



INSERT INTO rollen(rollen_id, rollen_schluessel) VALUES
  (1,'mitarbeiter'),(2,'teamleitung'),(3,'admin');

INSERT INTO arbeitsorte(ort_id,bezeichnung) VALUES
  (0,'k. A.'),(1,'Zu Hause'),(2,'Beim Kunden'),(3,'Im Büro');

INSERT INTO urlaubsarten(urlaubsart_id, art_schluessel, beschreibung) VALUES
  (1,'bezahlt','Bezahlter Erholungsurlaub'),
  (2,'unbezahlt','Unbezahlter Urlaub'),
  (3,'sonder','Sonderurlaub');

INSERT INTO benutzer
  (vorname, nachname, email, rollen_id, wochenstunden, urlaubstage, einstellungsdatum, aktualisiert_am, aktiv)
VALUES
  ('Max','Meier','max.muster@example.com',1, 20.0,23.0,'2025-05-01','2025-05-01',1),
  ('Erika','Müller','erika.beispiel@example.com',1,35.5, 10.0,'2024-01-01','2024-01-01',0),
  ('Lena','Deiters','teamleitung@example.com',2,40.0, 30.0,'2023-01-01','2023-01-01',1),
  ('Frida','Schoppen','admin@example.com',3,41.9,5.0,'2024-10-01','2024-10-01',1);



-- mit ChatGPT erstellte Datengrundlage zum Arbeiten mit den Stundenzetteln.
  INSERT INTO stundenzettel
(benutzer_id, monat, jahr, status, eingereicht_am, genehmigt_von, genehmigt_am,
 soll_stunden, ist_stunden, urlaub_bezahlt, urlaub_unbezahlt, urlaub_sonder)
VALUES
(1, 1, 2024, 'genehmigt', '2024-02-02 10:15:00', 3, '2024-02-03 09:00:00', 86.00, 92.50, 2.00, 0.00, 0.00),
(2, 3, 2024, 'eingereicht', '2024-03-28 14:20:00', NULL, NULL, 150.00, 148.00, 0.00, 0.00, 1.50),
(3, 5, 2025, 'entwurf', NULL, NULL, NULL, 160.00, 0.00, 0.00, 0.00, 0.00),
(4, 12, 2023, 'genehmigt', '2023-12-29 16:10:00', 3, '2023-12-30 09:30:00', 170.00, 171.25, 1.00, 0.00, 0.00),
(1, 7, 2025, 'eingereicht', '2025-07-31 11:55:00', NULL, NULL, 90.00, 88.75, 0.00, 0.00, 0.00),
(2, 8, 2023, 'genehmigt', '2023-08-30 13:40:00', 4, '2023-08-31 10:00:00', 151.00, 152.80, 0.00, 2.00, 0.00),
(3, 2, 2024, 'abgelehnt', '2024-02-27 12:00:00', NULL, NULL, 165.00, 140.50, 0.00, 0.00, 0.00),
(4, 10, 2024, 'entwurf', NULL, NULL, NULL, 169.00, 0.00, 0.00, 0.00, 0.00),
(1, 11, 2023, 'genehmigt', '2023-11-29 15:00:00', 3, '2023-11-30 08:10:00', 88.00, 90.00, 0.00, 0.00, 0.00),
(2, 11, 2025, 'eingereicht', '2025-11-29 17:00:00', NULL, NULL, 152.00, 150.25, 0.00, 1.00, 0.00);

-- ChatGPT erstellte beispielhafte Vorgaben
INSERT INTO vorgabenAuftraggeber
  (jahr, quartal, erwarteteKrankenquote, sollStunden, istStunden, toleranz)
VALUES
  (2025, 1, 4.50, 480, 0, 3.00),
  (2025, 2, 4.70, 490, 0, 3.00),
  (2025, 3, 4.20, 475, 0,  3.00),
  (2025, 4, 5.00, 500, 0, 3.00);

