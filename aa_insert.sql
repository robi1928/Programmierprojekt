-- Beispiel-Benutzer
-- um die neuen Variablen ergänzt


SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM zeiteintraege;
DELETE FROM urlaubsantraege;
DELETE FROM urlaubskonten;
DELETE FROM stundenzettel;
DELETE FROM benutzer;
DELETE FROM urlaubsarten;
DELETE FROM arbeitsorte;
DELETE FROM rollen;

SET FOREIGN_KEY_CHECKS = 1;


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




INSERT INTO stundenzettel
  (benutzer_id, monat, jahr, status,
   eingereicht_am, genehmigt_von, genehmigt_am,
   soll_stunden, ist_stunden,
   urlaub_bezahlt, urlaub_unbezahlt, urlaub_sonder)
SELECT
  b.benutzer_id,
  5 AS monat,
  2025 AS jahr,
  'genehmigt' AS status,
  '2025-06-01 09:00:00' AS eingereicht_am,
  (SELECT benutzer_id
     FROM benutzer
     WHERE email = 'admin@example.com') AS genehmigt_von,
  '2025-06-02 10:00:00' AS genehmigt_am,
  20.00 AS soll_stunden,
  20.00 AS ist_stunden,
  0.00 AS urlaub_bezahlt,
  0.00 AS urlaub_unbezahlt,
  0.00 AS urlaub_sonder
FROM benutzer b
WHERE b.email = 'max.muster@example.com';

INSERT INTO stundenzettel
  (benutzer_id, monat, jahr, status,
   eingereicht_am, genehmigt_von, genehmigt_am,
   soll_stunden, ist_stunden,
   urlaub_bezahlt, urlaub_unbezahlt, urlaub_sonder)
SELECT
  b.benutzer_id,
  5 AS monat,
  2025 AS jahr,
  'eingereicht' AS status,
  '2025-06-01 09:30:00' AS eingereicht_am,
  NULL AS genehmigt_von,
  NULL AS genehmigt_am,
  40.00 AS soll_stunden,
  36.00 AS ist_stunden,
  0.00 AS urlaub_bezahlt,
  0.00 AS urlaub_unbezahlt,
  0.00 AS urlaub_sonder
FROM benutzer b
WHERE b.email = 'teamleitung@example.com';


INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 5, 1, 4.00, 'Homeoffice Projekt A'
FROM stundenzettel s
JOIN benutzer b ON s.benutzer_id = b.benutzer_id
WHERE b.email = 'max.muster@example.com'
  AND s.monat = 5 AND s.jahr = 2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 6, 1, 4.00, 'Homeoffice Projekt A'
FROM stundenzettel s
JOIN benutzer b ON s.benutzer_id = b.benutzer_id
WHERE b.email = 'max.muster@example.com'
  AND s.monat = 5 AND s.jahr = 2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 7, 3, 4.00, 'Im Büro Teammeeting'
FROM stundenzettel s
JOIN benutzer b ON s.benutzer_id = b.benutzer_id
WHERE b.email = 'max.muster@example.com'
  AND s.monat = 5 AND s.jahr = 2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 8, 3, 4.00, 'Im Büro Kundenvorbereitung'
FROM stundenzettel s
JOIN benutzer b ON s.benutzer_id = b.benutzer_id
WHERE b.email = 'max.muster@example.com'
  AND s.monat = 5 AND s.jahr = 2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 9, 2, 4.00, 'Beim Kunden Workshop'
FROM stundenzettel s
JOIN benutzer b ON s.benutzer_id = b.benutzer_id
WHERE b.email = 'max.muster@example.com'
  AND s.monat = 5 AND s.jahr = 2025;



INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 5, 3, 4.00, 'Im Büro Projektleitung'
FROM stundenzettel s
JOIN benutzer b ON s.benutzer_id = b.benutzer_id
WHERE b.email = 'teamleitung@example.com'
  AND s.monat = 5 AND s.jahr = 2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 6, 3, 4.00, 'Im Büro Projektleitung'
FROM stundenzettel s
JOIN benutzer b ON s.benutzer_id = b.benutzer_id
WHERE b.email = 'teamleitung@example.com'
  AND s.monat = 5 AND s.jahr = 2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 7, 3, 4.00, 'Im Büro Jour fixe'
FROM stundenzettel s
JOIN benutzer b ON s.benutzer_id = b.benutzer_id
WHERE b.email = 'teamleitung@example.com'
  AND s.monat = 5 AND s.jahr = 2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 8, 1, 4.00, 'Homeoffice Konzeptarbeit'
FROM stundenzettel s
JOIN benutzer b ON s.benutzer_id = b.benutzer_id
WHERE b.email = 'teamleitung@example.com'
  AND s.monat = 5 AND s.jahr = 2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 9, 1, 4.00, 'Homeoffice Konzeptarbeit'
FROM stundenzettel s
JOIN benutzer b ON s.benutzer_id = b.benutzer_id
WHERE b.email = 'teamleitung@example.com'
  AND s.monat = 5 AND s.jahr = 2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 10, 2, 4.00, 'Beim Kunden Termin'
FROM stundenzettel s
JOIN benutzer b ON s.benutzer_id = b.benutzer_id
WHERE b.email = 'teamleitung@example.com'
  AND s.monat = 5 AND s.jahr = 2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 11, 2, 4.00, 'Beim Kunden Termin'
FROM stundenzettel s
JOIN benutzer b ON s.benutzer_id = b.benutzer_id
WHERE b.email = 'teamleitung@example.com'
  AND s.monat = 5 AND s.jahr = 2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 12, 3, 4.00, 'Im Büro Auswertung'
FROM stundenzettel s
JOIN benutzer b ON s.benutzer_id = b.benutzer_id
WHERE b.email = 'teamleitung@example.com'
  AND s.monat = 5 AND s.jahr = 2025;

INSERT INTO zeiteintraege (stundenzettel_id, tag, ort_id, stunden, bemerkung)
SELECT s.stundenzettel_id, 13, 3, 4.00, 'Im Büro Planung'
FROM stundenzettel s
JOIN benutzer b ON s.benutzer_id = b.benutzer_id
WHERE b.email = 'teamleitung@example.com'
  AND s.monat = 5 AND s.jahr = 2025;