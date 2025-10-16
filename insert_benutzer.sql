-- Beispiel-Benutzer
-- um die neuen Variablen ergänzt
-- erstellt_am/aktualisiert_am erstmal raus
INSERT INTO benutzer
  (vorname, nachname, email, rollen_id, wochenstunden, urlaubstage, einstellungsdatum, aktiv)
VALUES
  ('Max','Meier','max.muster@example.com',1, 20.0, 23.0, '2025-05-01', 1),
  ('Erika','Müller','erika.beispiel@example.com',1, 35.5, 10.0, '2024-01-01', 0),
  ('Lena','Deiters','teamleitung@example.com',2, 40.0, 30.0, '2023-01-01', 1),
  ('Frida','Schoppen','admin@example.com',3, 41.9,  5.0, '2024-10-01', 1);