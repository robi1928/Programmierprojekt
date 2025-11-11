-- Beispiel-Benutzer
-- um die neuen Variablen ergänzt

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