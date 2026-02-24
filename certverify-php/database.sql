-- ============================================================
--  CertVerify — MySQL Database Setup
--  Run this file ONCE to create the database and tables.
--  Command: mysql -u root -p < database.sql
-- ============================================================

-- 1. Create & select database
CREATE DATABASE IF NOT EXISTS certverify_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE certverify_db;

-- ============================================================
-- 2. ADMINS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS admins (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username    VARCHAR(80)  NOT NULL UNIQUE,
  email       VARCHAR(150) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,          -- bcrypt hash
  created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin: username=admin | password=Admin@123
INSERT IGNORE INTO admins (username, email, password) VALUES (
  'admin',
  'admin@certverify.com',
  '$2y$12$3sGfFBmQ0l.kMzY5fLv.Ouq2Y/6j3.HPZW/QLJH4BK1vfMlPmf1m'
  -- bcrypt of "Admin@123" — regenerate in production!
);

-- ============================================================
-- 3. DOMAINS TABLE  (lookup / reference)
-- ============================================================
CREATE TABLE IF NOT EXISTS domains (
  id    TINYINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name  VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT IGNORE INTO domains (name) VALUES
  ('Web Development'),
  ('Data Science'),
  ('UI/UX Design'),
  ('Android Development'),
  ('Machine Learning'),
  ('Cybersecurity'),
  ('Cloud Computing'),
  ('Blockchain'),
  ('Embedded Systems'),
  ('Full Stack Development');

-- ============================================================
-- 4. CERTIFICATES TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS certificates (
  id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cert_id        VARCHAR(50)  NOT NULL UNIQUE,   -- e.g. CERT-2024-001
  student_name   VARCHAR(150) NOT NULL,
  domain         VARCHAR(100) NOT NULL,
  start_date     DATE         NOT NULL,
  end_date       DATE         NOT NULL,
  issued_by      INT UNSIGNED,                   -- FK → admins.id
  created_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  CONSTRAINT fk_issued_by FOREIGN KEY (issued_by) REFERENCES admins(id) ON DELETE SET NULL,
  CONSTRAINT chk_dates    CHECK (end_date > start_date),
  INDEX idx_cert_id (cert_id),
  INDEX idx_student (student_name),
  INDEX idx_domain  (domain)
) ENGINE=InnoDB;

-- ============================================================
-- 5. VERIFICATION_LOGS TABLE
-- ============================================================
CREATE TABLE IF NOT EXISTS verification_logs (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cert_id      VARCHAR(50) NOT NULL,
  ip_address   VARCHAR(45),
  user_agent   TEXT,
  verified_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_vlog_cert (cert_id),
  INDEX idx_vlog_time (verified_at)
) ENGINE=InnoDB;

-- ============================================================
-- 6. SAMPLE DATA (10 certificates)
-- ============================================================
INSERT IGNORE INTO certificates (cert_id, student_name, domain, start_date, end_date, issued_by) VALUES
  ('CERT-2024-001', 'Aarav Sharma',    'Web Development',       '2024-01-15', '2024-04-15', 1),
  ('CERT-2024-002', 'Priya Patel',     'Data Science',          '2024-02-01', '2024-05-01', 1),
  ('CERT-2024-003', 'Rohan Mehta',     'UI/UX Design',          '2024-03-10', '2024-06-10', 1),
  ('CERT-2024-004', 'Sneha Verma',     'Android Development',   '2024-01-20', '2024-04-20', 1),
  ('CERT-2024-005', 'Arjun Nair',      'Machine Learning',      '2024-02-15', '2024-08-15', 1),
  ('CERT-2024-006', 'Kavya Reddy',     'Cybersecurity',         '2024-04-01', '2024-07-01', 1),
  ('CERT-2024-007', 'Vikram Singh',    'Full Stack Development', '2024-03-01', '2024-06-01', 1),
  ('CERT-2024-008', 'Ananya Iyer',     'Cloud Computing',       '2024-05-01', '2024-08-01', 1),
  ('CERT-2024-009', 'Raj Kulkarni',    'Android Development',   '2024-01-05', '2024-04-05', 1),
  ('CERT-2024-010', 'Divya Bansal',    'UI/UX Design',          '2024-06-01', '2024-09-01', 1);

-- ============================================================
-- 7. USEFUL VIEWS
-- ============================================================
CREATE OR REPLACE VIEW v_certificates AS
SELECT
  c.id,
  c.cert_id,
  c.student_name,
  c.domain,
  c.start_date,
  c.end_date,
  TIMESTAMPDIFF(MONTH, c.start_date, c.end_date) AS duration_months,
  a.username AS issued_by,
  c.created_at,
  c.updated_at
FROM certificates c
LEFT JOIN admins a ON c.issued_by = a.id;

CREATE OR REPLACE VIEW v_stats AS
SELECT
  (SELECT COUNT(*) FROM certificates)          AS total_certs,
  (SELECT COUNT(DISTINCT domain) FROM certificates) AS total_domains,
  (SELECT COUNT(*) FROM verification_logs
   WHERE DATE(verified_at) = CURDATE())        AS verifications_today,
  (SELECT COUNT(*) FROM verification_logs)     AS total_verifications;

-- Done!
SELECT 'CertVerify database setup complete!' AS status;
