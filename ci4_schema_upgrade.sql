-- SentryLink CI4 schema compatibility upgrade (safe for the current schema)

ALTER TABLE users
    MODIFY COLUMN role ENUM('student','ssg','admin','director') NOT NULL DEFAULT 'student',
    ADD COLUMN IF NOT EXISTS session_token VARCHAR(64) NULL,
    ADD COLUMN IF NOT EXISTS session_last_seen_at DATETIME NULL,
    ADD COLUMN IF NOT EXISTS email_verified TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN IF NOT EXISTS is_active TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN IF NOT EXISTS deleted_at DATETIME NULL,
    ADD COLUMN IF NOT EXISTS house VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS year_level VARCHAR(50) NULL,
    ADD COLUMN IF NOT EXISTS course VARCHAR(100) NULL,
    ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) NULL,
    ADD COLUMN IF NOT EXISTS created_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN IF NOT EXISTS updated_at DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

CREATE TABLE IF NOT EXISTS login_attempts (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  ip_address VARCHAR(45) NOT NULL,
  identifier VARCHAR(150) NOT NULL,
  attempt_count INT(11) NOT NULL DEFAULT 1,
  last_attempt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  locked_until DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY idx_ip_address (ip_address),
  KEY idx_identifier (identifier)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS receipts (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  receipt_no VARCHAR(120) NOT NULL,
  event_id INT(10) UNSIGNED NOT NULL,
  imported_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  imported_by INT(10) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_receipt_event (receipt_no, event_id),
  KEY idx_receipt_event (event_id),
  KEY idx_receipt_imported_by (imported_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id INT(10) UNSIGNED NOT NULL,
  role VARCHAR(20) NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_password_reset_token_hash (token_hash),
  KEY idx_password_reset_user (user_id),
  KEY idx_password_reset_expires (expires_at),
  KEY idx_password_reset_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
