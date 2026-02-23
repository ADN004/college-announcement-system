-- College Announcement System — Database Setup
-- Run this once before starting the app.

CREATE DATABASE IF NOT EXISTS college_announcement
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE college_announcement;

-- ─────────────────────────────────────────────
--  USERS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(255),
    username   VARCHAR(255) UNIQUE,
    password   VARCHAR(255),
    role       ENUM('admin','staff') DEFAULT 'staff',
    status     ENUM('active','blocked','deleted') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─────────────────────────────────────────────
--  LOCATIONS  (DB-driven — admin can add/edit/delete)
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS locations (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    slug       VARCHAR(50)  UNIQUE NOT NULL,   -- url-safe key: 'office', 'block_a'
    label      VARCHAR(100) NOT NULL,          -- display name: 'Office', 'Block A'
    password   VARCHAR(255) NOT NULL,          -- bcrypt hash
    is_active  TINYINT(1)   DEFAULT 1,
    created_at TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
);

-- ─────────────────────────────────────────────
--  ANNOUNCEMENTS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS announcements (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    staff_id     INT,
    title        VARCHAR(255),
    location     VARCHAR(100),
    type         ENUM('record','audio','tts'),
    text_content TEXT,
    file_path    VARCHAR(500),
    status       ENUM('pending','approved','rejected') DEFAULT 'pending',
    reject_reason TEXT,
    scheduled_at DATETIME,
    play_limit   INT DEFAULT 1,
    play_count   INT DEFAULT 0,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at  DATETIME,
    FOREIGN KEY (staff_id) REFERENCES users(id)
);

-- ─────────────────────────────────────────────
--  NOTIFICATIONS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS notifications (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT,
    announcement_id INT,
    message         TEXT,
    is_read         BOOLEAN DEFAULT 0,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)         REFERENCES users(id),
    FOREIGN KEY (announcement_id) REFERENCES announcements(id)
);

-- ─────────────────────────────────────────────
--  PLAY LOGS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS play_logs (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    announcement_id INT,
    location        VARCHAR(100),
    played_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (announcement_id) REFERENCES announcements(id)
);

-- ─────────────────────────────────────────────
--  ACTIVITY LOGS
-- ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS staff_activity_logs (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT,
    role       VARCHAR(50),
    action     TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- ─────────────────────────────────────────────
--  SEED: Default admin account  (password: admin123)
--  IMPORTANT — change this password after first login!
-- ─────────────────────────────────────────────
INSERT IGNORE INTO users (name, username, password, role, status) VALUES (
    'Admin',
    'admin',
    '$2y$10$qwrikp9yZAAyMHUh0KgbO.2y.pmswzV4E.6L2fQR1.Dl9d6l1Qp82',
    'admin',
    'active'
);

-- ─────────────────────────────────────────────
--  SEED: Default locations
--  Passwords: office123 / blocka123 / library123
--  Change via Admin → Manage Locations after first login.
-- ─────────────────────────────────────────────
INSERT IGNORE INTO locations (slug, label, password) VALUES
  ('office',  'Office',  '$2y$10$jJfHvPhgSVoPhkRuUIPehOG41bMTo64diuWcUB5sM.EUdCznLS0ri'),
  ('block_a', 'Block A', '$2y$10$TYLwAN.dNkAunBEi2GhByuEjmaIIH8qH8vlvE9uvBiaKBpmWIqbh2'),
  ('library', 'Library', '$2y$10$NP2R8ZD2j2IV3L36XMs/tuGZqZ5YZxMXsgtMu0eq9G0Jju3KUXOD.');
