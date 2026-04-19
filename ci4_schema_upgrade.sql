-- SentryLink CI4 schema compatibility upgrade (MySQL/MariaDB safe)
-- Use this after importing database_schema.sql if older deployments are missing
-- newer columns or tables.

SET @schema_name := DATABASE();

SET @users_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'users'
);

SET @sql := IF(
    @users_exists = 1,
    "ALTER TABLE `users` MODIFY COLUMN `role` ENUM('student','ssg','admin','director') NOT NULL DEFAULT 'student'",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'session_token'
);
SET @sql := IF(
    @users_exists = 1 AND @column_exists = 0,
    "ALTER TABLE `users` ADD COLUMN `session_token` VARCHAR(64) NULL",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'session_last_seen_at'
);
SET @sql := IF(
    @users_exists = 1 AND @column_exists = 0,
    "ALTER TABLE `users` ADD COLUMN `session_last_seen_at` DATETIME NULL",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'email_verified'
);
SET @sql := IF(
    @users_exists = 1 AND @column_exists = 0,
    "ALTER TABLE `users` ADD COLUMN `email_verified` TINYINT(1) NOT NULL DEFAULT 0",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'is_active'
);
SET @sql := IF(
    @users_exists = 1 AND @column_exists = 0,
    "ALTER TABLE `users` ADD COLUMN `is_active` TINYINT(1) NOT NULL DEFAULT 1",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'deleted_at'
);
SET @sql := IF(
    @users_exists = 1 AND @column_exists = 0,
    "ALTER TABLE `users` ADD COLUMN `deleted_at` DATETIME NULL",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'house'
);
SET @sql := IF(
    @users_exists = 1 AND @column_exists = 0,
    "ALTER TABLE `users` ADD COLUMN `house` VARCHAR(100) NULL",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'year_level'
);
SET @sql := IF(
    @users_exists = 1 AND @column_exists = 0,
    "ALTER TABLE `users` ADD COLUMN `year_level` VARCHAR(50) NULL",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'course'
);
SET @sql := IF(
    @users_exists = 1 AND @column_exists = 0,
    "ALTER TABLE `users` ADD COLUMN `course` VARCHAR(100) NULL",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'profile_photo'
);
SET @sql := IF(
    @users_exists = 1 AND @column_exists = 0,
    "ALTER TABLE `users` ADD COLUMN `profile_photo` VARCHAR(255) NULL",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'created_at'
);
SET @sql := IF(
    @users_exists = 1 AND @column_exists = 0,
    "ALTER TABLE `users` ADD COLUMN `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'users'
      AND COLUMN_NAME = 'updated_at'
);
SET @sql := IF(
    @users_exists = 1 AND @column_exists = 0,
    "ALTER TABLE `users` ADD COLUMN `updated_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @users_id_unique_positive := IF(
    @users_exists = 1,
    (
        SELECT CASE
            WHEN COUNT(*) = 0 THEN 1
            WHEN COUNT(DISTINCT id) = COUNT(*) AND MIN(id) > 0 THEN 1
            ELSE 0
        END
        FROM users
    ),
    0
);

SET @users_has_pk := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'users'
      AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);
SET @sql := IF(
    @users_exists = 1 AND @users_has_pk = 0 AND @users_id_unique_positive = 1,
    "ALTER TABLE `users` ADD PRIMARY KEY (`id`)",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @users_exists = 1 AND @users_id_unique_positive = 1,
    "ALTER TABLE `users` MODIFY COLUMN `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'idx_role'
);
SET @sql := IF(
    @users_exists = 1 AND @index_exists = 0,
    "ALTER TABLE `users` ADD KEY `idx_role` (`role`)",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'idx_student_id'
);
SET @sql := IF(
    @users_exists = 1 AND @index_exists = 0,
    "ALTER TABLE `users` ADD KEY `idx_student_id` (`student_id`)",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'idx_email'
);
SET @sql := IF(
    @users_exists = 1 AND @index_exists = 0,
    "ALTER TABLE `users` ADD KEY `idx_email` (`email`)",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'idx_is_active'
);
SET @sql := IF(
    @users_exists = 1 AND @index_exists = 0,
    "ALTER TABLE `users` ADD KEY `idx_is_active` (`is_active`)",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'idx_session_token'
);
SET @sql := IF(
    @users_exists = 1 AND @index_exists = 0,
    "ALTER TABLE `users` ADD KEY `idx_session_token` (`session_token`)",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @can_unique_student := IF(
    @users_exists = 1,
    (
        SELECT CASE
            WHEN COUNT(*) = 0 THEN 1
            WHEN SUM(student_id IS NOT NULL AND student_id <> '') = COUNT(DISTINCT NULLIF(student_id, '')) THEN 1
            ELSE 0
        END
        FROM users
    ),
    0
);
SET @index_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'student_id'
);
SET @sql := IF(
    @users_exists = 1 AND @index_exists = 0 AND @can_unique_student = 1,
    "ALTER TABLE `users` ADD UNIQUE KEY `student_id` (`student_id`)",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @can_unique_email := IF(
    @users_exists = 1,
    (
        SELECT CASE
            WHEN COUNT(*) = 0 THEN 1
            WHEN SUM(email IS NOT NULL AND email <> '') = COUNT(DISTINCT NULLIF(email, '')) THEN 1
            ELSE 0
        END
        FROM users
    ),
    0
);
SET @index_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'users'
      AND INDEX_NAME = 'email'
);
SET @sql := IF(
    @users_exists = 1 AND @index_exists = 0 AND @can_unique_email = 1,
    "ALTER TABLE `users` ADD UNIQUE KEY `email` (`email`)",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @ci_sessions_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'ci_sessions'
);
SET @ci_sessions_has_pk := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'ci_sessions'
      AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);
SET @sql := IF(
    @ci_sessions_exists = 1 AND @ci_sessions_has_pk = 0,
    "ALTER TABLE `ci_sessions` ADD PRIMARY KEY (`id`)",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @index_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'ci_sessions'
      AND INDEX_NAME = 'idx_timestamp'
);
SET @sql := IF(
    @ci_sessions_exists = 1 AND @index_exists = 0,
    "ALTER TABLE `ci_sessions` ADD KEY `idx_timestamp` (`timestamp`)",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @login_attempts_exists := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'login_attempts'
);
SET @login_attempts_has_pk := (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = @schema_name
      AND TABLE_NAME = 'login_attempts'
      AND CONSTRAINT_TYPE = 'PRIMARY KEY'
);
SET @sql := IF(
    @login_attempts_exists = 1 AND @login_attempts_has_pk = 0,
    "ALTER TABLE `login_attempts` ADD PRIMARY KEY (`id`)",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql := IF(
    @login_attempts_exists = 1,
    "ALTER TABLE `login_attempts` MODIFY COLUMN `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT",
    "SELECT 1"
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

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
