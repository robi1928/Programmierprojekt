-- Falls Tabellen schon existieren → löschen (Reihenfolge wegen Foreign Keys wichtig)
DROP TABLE IF EXISTS timesheet_entries;
DROP TABLE IF EXISTS timesheets;
DROP TABLE IF EXISTS work_locations;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS roles;

-- Rollen-Tabelle: definiert, welche Arten von Usern es gibt
CREATE TABLE roles (
  role_id  TINYINT PRIMARY KEY,
  role_key ENUM('employee','teamlead','admin') NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Anfangsdaten für Rollen
INSERT INTO roles(role_id, role_key) VALUES
  (1,'employee'),(2,'teamlead'),(3,'admin');

-- User-Tabelle: speichert alle Benutzer
CREATE TABLE users (
  user_id    INT PRIMARY KEY AUTO_INCREMENT,
  first_name VARCHAR(100) NOT NULL,
  last_name  VARCHAR(100) NOT NULL,
  email      VARCHAR(255) NOT NULL UNIQUE,
  role_id    TINYINT NOT NULL, -- Verknüpfung zu roles
  is_active  TINYINT(1) NOT NULL DEFAULT 1, -- 1 = aktiv, 0 = deaktiviert
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(role_id) -- Beziehung: jeder User hat eine Rolle
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Beispiel-User
INSERT INTO users(first_name,last_name,email,role_id) VALUES
  ('Max','Muster','max.muster@example.com',1),
  ('Erika','Beispiel','erika.beispiel@example.com',1),
  ('Lena','Leitung','teamlead@example.com',2),
  ('Frida','Admin','admin@example.com',3);

-- Arbeitsorte (Dropdown-Auswahl) (Aktuell nicht verwendet. War im Erstentwurf für den Login. Evtl später nochmal gebrauchbar)
CREATE TABLE work_locations (
  location_id TINYINT PRIMARY KEY,
  label       VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Standard-Liste an Arbeitsorten
INSERT INTO work_locations(location_id,label) VALUES
  (0,'k. A.'),(1,'Zu Hause'),(2,'Beim Kunden'),(3,'Im Büro');

-- Stundenzettel-Kopf (ein Blatt pro Monat/Person)
CREATE TABLE timesheets (
  timesheet_id INT PRIMARY KEY AUTO_INCREMENT,
  user_id      INT NOT NULL,
  month        TINYINT NOT NULL CHECK (month BETWEEN 1 AND 12),
  year         SMALLINT NOT NULL CHECK (year BETWEEN 2000 AND 2100),
  status       ENUM('draft','submitted','approved','rejected') NOT NULL DEFAULT 'draft',
  submitted_at DATETIME NULL,
  approved_by  INT NULL,
  approved_at  DATETIME NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_user_month_year (user_id, month, year), -- pro User nur 1 Zettel pro Monat/Jahr
  FOREIGN KEY (user_id)     REFERENCES users(user_id) ON DELETE CASCADE,
  FOREIGN KEY (approved_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stundenzettel-Details (Einträge pro Tag)
CREATE TABLE timesheet_entries (
  timesheet_id INT NOT NULL,
  day          TINYINT NOT NULL CHECK (day BETWEEN 1 AND 31),
  location_id  TINYINT NOT NULL,
  hours        DECIMAL(4,2) NOT NULL DEFAULT 0.00, -- gearbeitete Stunden
  note         VARCHAR(500) NULL, -- optionaler Kommentar
  PRIMARY KEY (timesheet_id, day), -- pro Tag nur ein Eintrag
  FOREIGN KEY (timesheet_id) REFERENCES timesheets(timesheet_id) ON DELETE CASCADE,
  FOREIGN KEY (location_id)  REFERENCES work_locations(location_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
