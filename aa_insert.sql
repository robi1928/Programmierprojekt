-- Beispiel-Benutzer
-- um die neuen Variablen ergänzt

SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM zeiteintraege;
DELETE FROM urlaubsantraege;
DELETE FROM urlaubskonten;
DELETE FROM stundenzettel;
DELETE FROM benutzer;
ALTER TABLE benutzer AUTO_INCREMENT = 1;
DELETE FROM arbeitsorte;
DELETE FROM rollen;
DELETE FROM vorgabenAuftraggeber;

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO rollen(rollen_id, rollen_schluessel) VALUES
  (1,'Mitarbeiter'),(2,'Teamleitung'),(3,'Projektleitung');

INSERT INTO arbeitsorte(ort_id,bezeichnung) VALUES
  (0,'k. A.'),(1,'Zu Hause'),(2,'Beim Kunden'),(3,'Im Büro');

INSERT INTO benutzer
  (vorname, nachname, email, rollen_id, wochenstunden, einstellungsdatum, aktualisiert_am, aktiv)
VALUES
  ('Max','Meier','max.muster@example.com',1, 20.0,'2025-05-01','2025-05-01',1),
  ('Erika','Müller','erika.beispiel@example.com',1,35.5,'2024-01-01','2024-01-01',1),
  ('Lena','Deiters','teamleitung@example.com',2,40.0,'2023-01-01','2023-01-01',1),
  ('Frida','Schoppen','projektleitung@example.com',3,41.9,'2024-10-01','2024-10-01',1);

INSERT INTO stundenzettel
(benutzer_id, monat, jahr, status,
 eingereicht_am, eingereicht_von,
 genehmigt_von, genehmigt_am,
 soll_stunden, ist_stunden, urlaub_gesamt)
VALUES
    (1, 12, 2023, 'genehmigt', '2023-12-29 16:10:00', 1, 3, '2023-12-30 09:30:00', 170.00, 171.25, 1.00),
    (1, 1, 2024, 'genehmigt', '2024-02-02 10:15:00', 1, 3, '2024-02-03 09:00:00', 86.00, 92.50, 2.00),
    (1, 7, 2025, 'entwurf', '2025-07-31 11:55:00', 3, NULL, NULL, 90.00, 88.75, 0.00),
    (1, 10, 2025, 'genehmigt', '2025-10-31 16:30:00', 1, 3, '2025-11-02 08:10:00', 86.00, 86.00, 3.00),
    (1, 11, 2025, 'genehmigt', '2025-11-30 16:30:00', 1, 3, '2025-12-02 08:10:00', 86.00, 86.00, 2.00),
    (1, 12, 2025, 'entwurf', '2025-12-02 09:00:00', 1, NULL, NULL, 84.00, 10.00, 2.00),
    (2, 3, 2024, 'entwurf', '2024-03-28 14:20:00', 2, NULL, NULL, 150.00, 148.00, 1.50),
    (2, 8, 2023, 'genehmigt', '2023-08-30 13:40:00', 2, 4, '2023-08-31 10:00:00', 151.00, 152.80, 2.00),
    (2, 7, 2025, 'entwurf', '2025-07-31 11:55:00', 3, NULL, NULL, 90.00, 88.75, 0.00),
    (2, 10, 2025, 'genehmigt', '2025-10-31 16:30:00', 2, 3, '2025-11-02 08:10:00', 150.00, 150.00, 1.00),
    (2, 11, 2025, 'entwurf', '2025-11-29 17:00:00', 2, NULL, NULL, 152.00, 150.25, 3.00),
    (2, 12, 2025, 'entwurf', '2025-12-05 09:00:00', 2, NULL, NULL, 150.00, 0.00, 3.00),
    (3, 2, 2024, 'entwurf', '2024-02-27 12:00:00', 3, NULL, NULL, 165.00, 140.50, 0.00),
    (3, 10, 2025, 'genehmigt', '2025-10-31 16:30:00', 3, 4, '2025-11-02 08:10:00', 160.00, 160.00, 0.00),
    (3, 12, 2025, 'entwurf', '2025-12-03 09:00:00', 4, NULL, NULL, 160.00, 0.00, 0.00),
    (4, 10, 2024, 'entwurf', '2024-10-15 14:20:00', 4, NULL, NULL, 169.00, 0.00, 0.00),
    (4, 10, 2025, 'genehmigt', '2025-10-31 16:30:00', 4, 3, '2025-11-02 08:10:00', 169.00, 10.00, 1.00),
    (4, 12, 2025, 'entwurf', '2025-12-04 09:00:00', 3, NULL, NULL, 150.00, 7.00, 1.00),
    (1, 1, 2025, 'genehmigt','2025-01-31 16:00:00', 1, 3, '2025-02-01 09:00:00', 86.00, 86.00, 2.00),
    (1, 6, 2025, 'genehmigt', '2025-06-30 16:00:00', 1, 3, '2025-07-01 09:00:00', 90.00, 90.00, 3.00);

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 5, 1, 4.00, 'Homeoffice Projekt A'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 6, 1, 4.00, 'Homeoffice Projekt A'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 7, 3, 4.00, 'Im Büro Teammeeting'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 8, 3, 4.00, 'Im Büro Kundenvorbereitung'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 9, 2, 4.00, 'Beim Kunden Workshop'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 5, 3, 4.00, 'Im Büro Projektleitung'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='teamleitung@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 6, 3, 4.00, 'Im Büro Projektleitung'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='teamleitung@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 7, 3, 4.00, 'Im Büro Jour fixe'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='teamleitung@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 8, 1, 4.00, 'Homeoffice Konzeptarbeit'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='teamleitung@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 9, 1, 4.00, 'Homeoffice Konzeptarbeit'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='teamleitung@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 10, 2, 4.00, 'Beim Kunden Termin'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 11, 2, 4.00, 'Beim Kunden Termin'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 12, 3, 4.00, 'Im Büro Auswertung'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 13, 3, 4.00, 'Im Büro Planung'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 1, 3, 4.00, 'Im Büro Einarbeitung neues Tool'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=10 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 2, 3, 4.00, 'Im Büro Kundenmails bearbeiten'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=10 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 3, 1, 4.00, 'Homeoffice Dokumentation Projekt A'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=10 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 6, 1, 4.00, 'Homeoffice Auswertung Tickets'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=10 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 7, 3, 4.00, 'Im Büro Planung Projekt A Oktober'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=10 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 8, 2, 4.00, 'Beim Kunden Kickoff Oktober'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=10 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 9, 3, 4.00, 'Im Büro Ticketbearbeitung'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=10 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 10, 1, 4.00, 'Homeoffice Reporting Monat'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=10 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 13, 3, 4.00, 'Im Büro Jour fixe Team'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=10 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 14, 3, 4.00, 'Im Büro Nachbereitung Jour fixe'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=10 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 2, 3, 4.00, 'Im Büro Monatsplanung November'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=11 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 3, 1, 4.00, 'Homeoffice Dokumentation Tickets'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=11 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 4, 3, 4.00, 'Im Büro Kundenanfragen telefonisch'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=11 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 5, 2, 4.50, 'Beim Kunden Workshop Vorbereitung'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=11 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 9, 2, 4.50, 'Beim Kunden Workshop Durchführung'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=11 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 10, 3, 4.00, 'Im Büro Nachbereitung Workshop'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=11 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 11, 1, 4.00, 'Homeoffice Auswertung KPI'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=11 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 12, 3, 4.00, 'Im Büro Teammeeting November'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=11 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 16, 1, 4.00, 'Homeoffice Dokumentation Prozesse'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=11 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 17, 3, 4.00, 'Im Büro Abstimmung mit Teamleitung'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=11 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 1, 3, 4.00, 'Im Büro Monatsauftakt Dezember'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 2, 1, 4.00, 'Homeoffice Nachbearbeitung November'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 3, 3, 4.00, 'Im Büro Ticketbearbeitung Dezember'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 4, 3, 4.00, 'Im Büro Jour fixe Dezember'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 11, 1, 4.00, 'Homeoffice Reporting Quartal 4'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 12, 2, 4.00, 'Beim Kunden Jahresabschlussgespräch'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 13, 3, 4.00, 'Im Büro Nachbereitung Jahresabschluss'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 14, 1, 4.00, 'Homeoffice Dokumentation Jahresabschluss'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 15, 3, 4.00, 'Im Büro Teamabschlussrunde'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 1, 3, 7.00, 'Im Büro Projektsteuerung Oktober-Start'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=10 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 2, 3, 7.00, 'Im Büro Teamkoordination'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=10 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 3, 2, 7.00, 'Beim Kunden Abstimmung'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=10 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 4, 3, 7.00, 'Im Büro Auswertung KPI'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=10 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 5, 1, 7.00, 'Homeoffice Konzeptarbeit'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=10 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 8, 3, 7.00, 'Im Büro Jour fixe Bereich'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=10 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 9, 3, 7.00, 'Im Büro Nachbereitung Jour fixe'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=10 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 10, 2, 7.00, 'Beim Kunden Review Q3'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=10 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 11, 3, 7.00, 'Im Büro Planung Q4'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=10 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 12, 1, 7.00, 'Homeoffice Dokumentation Prozesse'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=10 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 2, 3, 7.00, 'Im Büro Monatsstart November'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=11 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 3, 3, 7.00, 'Im Büro Teamrunden moderieren'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=11 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 4, 2, 7.00, 'Beim Kunden Jour fixe'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=11 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 5, 3, 7.00, 'Im Büro Auswertung Tickets'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=11 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 6, 1, 7.00, 'Homeoffice Strategische Planung'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=11 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 9, 3, 7.00, 'Im Büro Bereichsmeeting'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=11 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 10, 3, 7.00, 'Im Büro Nachbereitung Bereichsmeeting'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=11 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 11, 2, 7.00, 'Beim Kunden Review November'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=11 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 12, 3, 7.00, 'Im Büro KPI-Review'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=11 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 13, 1, 7.00, 'Homeoffice Dokumentation November'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=11 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 1, 3, 7.00, 'Im Büro Jahresendplanung'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 2, 3, 7.00, 'Im Büro Abstimmung mit Projektleitung'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 3, 2, 7.00, 'Beim Kunden Jahresabschlussmeeting'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 4, 3, 7.00, 'Im Büro Nachbereitung Abschlussmeeting'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 5, 1, 7.00, 'Homeoffice Reporting Kunden'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 6, 3, 7.00, 'Im Büro KPI Jahr 2025'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 7, 3, 7.00, 'Im Büro Mitarbeitergespräche'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 8, 1, 7.00, 'Homeoffice Vorbereitung 2026'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 9, 3, 7.00, 'Im Büro Teamabschluss Dezember'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 14, 3, 7.00, 'Im Büro Jahresrückblick Präsentation'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 15, 2, 7.00, 'Beim Kunden Feedbackrunde'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 16, 3, 7.00, 'Im Büro Planung Q1 2026'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 17, 1, 7.00, 'Homeoffice Strategie-Feinschliff'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 18, 3, 7.00, 'Im Büro Teamworkshop Retrospektive'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 19, 3, 7.00, 'Im Büro Nachbereitung Workshop'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 20, 1, 7.00, 'Homeoffice Abschlussdokumentation 2025'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='erika.beispiel@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 21, 1, 7.00, 'Homeoffice Abschlussdokumentation 2025'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='projektleitung@example.com' AND s.monat=12 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 21, 1, 10.00, 'Homeoffice Abschlussdokumentation 2025'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='projektleitung@example.com' AND s.monat=10 AND s.jahr=2025;

INSERT INTO urlaubskonten (benutzer_id, jahr, anspruch_tage, genutzt_tage) VALUES
  (1, 2025, 23.0, 12.0),   -- Max Meier
  (2, 2025, 10.0, 4.0),   -- Erika Müller
  (3, 2025, 30.0, 3.0),   -- Teamleitung Lena Deiters
  (4, 2025, 5.0, 2.0);   -- Projektleitung Frida Schoppen


INSERT INTO urlaubsantraege
(benutzer_id, start_datum, ende_datum, tage, status,
 eingereicht_am, eingereicht_von,
 entschieden_von, entschieden_am, bemerkung)
VALUES

  (1, '2025-06-10', '2025-06-12', 3.0,
   'genehmigt', '2025-05-20 10:00:00', 1, 4, '2025-05-22 09:00:00',
   'Kurzurlaub Juni'),

  (2, '2025-03-15', '2025-03-15', 1.0,
   'entwurf', '2025-02-28 14:00:00', 3, NULL, NULL,
   'Privattermin'),

  (2, '2024-11-05', '2024-11-06', 2.0,
   'abgelehnt', '2024-10-30 16:00:00', 2, 4, '2024-10-31 09:30:00',
   'Fortbildung wurde nicht anerkannt'),

  (1, '2025-01-20', '2025-01-21', 2.0,
   'genehmigt', '2025-01-05 11:30:00', 4, 1, '2025-01-06 08:45:00',
   'Urlaub nach Feiertagsphase'),

  (1, '2025-10-14', '2025-10-16', 3.0,
   'genehmigt', '2025-09-20 09:15:00', 1, 3, '2025-09-22 10:00:00',
   'Herbsturlaub Oktober'),

  (1, '2025-12-22', '2025-12-23', 2.0,
   'genehmigt', '2025-11-30 11:20:00', 1, 3, '2025-12-01 09:30:00',
   'Vor Weihnachten frei'),

  (2, '2025-10-31', '2025-10-31', 1.0,
   'genehmigt', '2025-10-10 14:00:00', 2, 3, '2025-10-11 09:00:00',
   'Brückentag zu Feiertag'),

  (2, '2025-11-18', '2025-11-20', 3.0,
   'genehmigt', '2025-10-28 13:45:00', 2, 3, '2025-10-29 10:15:00',
   'Städtetrip November'),

  (3, '2025-12-28', '2025-12-30', 3.0,
   'genehmigt', '2025-12-05 15:10:00', 3, 4, '2025-12-06 09:40:00',
   'Jahresurlaub zwischen den Jahren'),

  (2, '2025-10-07', '2025-10-07', 1.0,
   'entwurf', '2025-09-25 09:30:00', 3, NULL, NULL,
   'Privattermin vormittags'),

  (1, '2025-11-12', '2025-11-13', 2.0,
   'genehmigt', '2025-10-20 16:00:00', 3, 1, '2025-10-21 09:10:00',
   'Kurzurlaub im November'),

  (4, '2025-10-21', '2025-10-21', 1.0,
   'genehmigt', '2025-10-01 10:30:00', 4, 3, '2025-10-02 09:00:00',
   'Familientermin'),

  (4, '2025-12-19', '2025-12-19', 1.0,
   'genehmigt', '2025-11-25 11:50:00', 4, 3, '2025-11-26 08:50:00',
   'Weihnachtsveranstaltung'),

  (1, '2026-01-01', '2026-01-01', 1.0,
   'entwurf', '2025-12-09 14:00:00', 3, NULL, NULL,
   'Sonderurlaub'),

  (1, '2025-02-10', '2025-02-12', 3.0,
   'abgelehnt', '2025-01-20 10:00:00', 1, 4, '2025-01-22 09:00:00',
   'Kurzurlaub Februar');


INSERT INTO vorgabenAuftraggeber
  (jahr, quartal, erwarteteKrankenquote, sollStunden, istStunden, toleranz)
VALUES
  (2025, 1, 4.50, 480, 0, 3.00),
  (2025, 2, 4.70, 490, 0, 3.00),
  (2025, 3, 4.20, 475, 0, 3.00),
  (2025, 4, 5.00, 500, 0, 3.00);