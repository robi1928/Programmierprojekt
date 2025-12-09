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
  (1,'mitarbeiter'),(2,'teamleitung'),(3,'projektleitung');

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
(benutzer_id, monat, jahr, status, eingereicht_am, genehmigt_von, genehmigt_am,
 soll_stunden, ist_stunden, urlaub_gesamt)
VALUES
(1, 1, 2024, 'genehmigt', '2024-02-02 10:15:00', 3, '2024-02-03 09:00:00', 86.00, 92.50, 2.00),
(2, 3, 2024, 'eingereicht', '2024-03-28 14:20:00', NULL, NULL, 150.00, 148.00, 1.50),
(4, 12, 2023, 'genehmigt', '2023-12-29 16:10:00', 3, '2023-12-30 09:30:00', 170.00, 171.25, 1.00),
(1, 7, 2025, 'eingereicht', '2025-07-31 11:55:00', NULL, NULL, 90.00, 88.75, 0.00),
(2, 8, 2023, 'genehmigt', '2023-08-30 13:40:00', 4, '2023-08-31 10:00:00', 151.00, 152.80, 2.00),
(3, 2, 2024, 'abgelehnt', '2024-02-27 12:00:00', NULL, NULL, 165.00, 140.50, 0.00),
(4, 10, 2024, 'entwurf', NULL, NULL, NULL, 169.00, 0.00, 0.00),
(1, 11, 2023, 'genehmigt', '2023-11-29 15:00:00', 3, '2023-11-30 08:10:00', 88.00, 90.00, 0.00),
(2, 11, 2025, 'eingereicht', '2025-11-29 17:00:00', NULL, NULL, 152.00, 150.25, 1.00),
(1, 12, 2025, 'entwurf', NULL, NULL, NULL, 84.00, 10.00, 0.00);

INSERT INTO stundenzettel
  (benutzer_id, monat, jahr, status,
   eingereicht_am, genehmigt_von, genehmigt_am,
   soll_stunden, ist_stunden, urlaub_gesamt)
SELECT
  b.benutzer_id,
  5, 2025, 'genehmigt',
  '2025-06-01 09:00:00',
  (SELECT benutzer_id FROM benutzer WHERE email='projektleitung@example.com'),
  '2025-06-02 10:00:00',
  20.00, 20.00, 0.00
FROM benutzer b
WHERE b.email = 'max.muster@example.com';

-- eingereichter SZ Teamleitung
INSERT INTO stundenzettel
  (benutzer_id, monat, jahr, status,
   eingereicht_am, genehmigt_von, genehmigt_am,
   soll_stunden, ist_stunden, urlaub_gesamt)
SELECT
  b.benutzer_id,
  5, 2025, 'eingereicht',
  '2025-06-01 09:30:00',
  NULL, NULL,
  40.00, 36.00, 0.00
FROM benutzer b
WHERE b.email = 'teamleitung@example.com';


INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 5, 1, 4.00, 'Homeoffice Projekt A'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=5 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 6, 1, 4.00, 'Homeoffice Projekt A'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=5 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 7, 3, 4.00, 'Im Büro Teammeeting'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=5 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 8, 3, 4.00, 'Im Büro Kundenvorbereitung'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=5 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 9, 2, 4.00, 'Beim Kunden Workshop'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='max.muster@example.com' AND s.monat=5 AND s.jahr=2025;


INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 5, 3, 4.00, 'Im Büro Projektleitung'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='teamleitung@example.com' AND s.monat=5 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 6, 3, 4.00, 'Im Büro Projektleitung'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='teamleitung@example.com' AND s.monat=5 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 7, 3, 4.00, 'Im Büro Jour fixe'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='teamleitung@example.com' AND s.monat=5 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 8, 1, 4.00, 'Homeoffice Konzeptarbeit'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='teamleitung@example.com' AND s.monat=5 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 9, 1, 4.00, 'Homeoffice Konzeptarbeit'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='teamleitung@example.com' AND s.monat=5 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 10, 2, 4.00, 'Beim Kunden Termin'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='teamleitung@example.com' AND s.monat=5 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 11, 2, 4.00, 'Beim Kunden Termin'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='teamleitung@example.com' AND s.monat=5 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 12, 3, 4.00, 'Im Büro Auswertung'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='teamleitung@example.com' AND s.monat=5 AND s.jahr=2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 13, 3, 4.00, 'Im Büro Planung'
FROM stundenzettel s JOIN benutzer b USING(benutzer_id)
WHERE b.email='teamleitung@example.com' AND s.monat=5 AND s.jahr=2025;

INSERT INTO urlaubskonten (benutzer_id, jahr, anspruch_tage, uebertrag_tage, genutzt_tage) VALUES
  (1, 2025, 23.0, 0.0, 2.0),   -- Max Meier
  (2, 2025, 10.0, 0.0, 0.0),   -- Erika Müller
  (3, 2025, 30.0, 0.0, 0.0),   -- Teamleitung Lena Deiters
  (4, 2025, 5.0,  0.0, 1.0);   -- Projektleitung Frida Schoppen


INSERT INTO urlaubsantraege
(benutzer_id, start_datum, ende_datum, tage, status,
 eingereicht_am, entschieden_von, entschieden_am, bemerkung)
VALUES

  (1, '2025-06-10', '2025-06-12', 3.0,
   'genehmigt', '2025-05-20 10:00:00', 4, '2025-05-22 09:00:00',
   'Kurzurlaub Juni'),


  (2, '2025-03-15', '2025-03-15', 1.0,
   'eingereicht', '2025-02-28 14:00:00', NULL, NULL,
   'Privattermin'),

  (3, '2024-11-05', '2024-11-06', 2.0,
   'abgelehnt', '2024-10-30 16:00:00', 4, '2024-10-31 09:30:00',
   'Fortbildung wurde nicht anerkannt'),

  (4, '2025-01-20', '2025-01-21', 2.0,
   'genehmigt', '2025-01-05 11:30:00', 3, '2025-01-06 08:45:00',
   'Urlaub nach Feiertagsphase');


INSERT INTO vorgabenAuftraggeber
  (jahr, quartal, erwarteteKrankenquote, sollStunden, istStunden, toleranz)
VALUES
  (2025, 1, 4.50, 480, 0, 3.00),
  (2025, 2, 4.70, 490, 0, 3.00),
  (2025, 3, 4.20, 475, 0, 3.00),
  (2025, 4, 5.00, 500, 0, 3.00);