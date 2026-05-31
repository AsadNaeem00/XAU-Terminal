-- ============================================================
-- XAUUSD Intelligence Terminal — Database Schema
-- MySQL 8.0+ compatible
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
SET time_zone = '+00:00';

-- ============================================================
-- Admin users table
-- ============================================================
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username`      VARCHAR(64)  NOT NULL UNIQUE,
  `email`         VARCHAR(255) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `last_login`    DATETIME     DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active`     TINYINT(1)  NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- API request logs
-- ============================================================
CREATE TABLE IF NOT EXISTS `api_logs` (
  `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `api_name`      VARCHAR(64)  NOT NULL,
  `endpoint`      VARCHAR(512) NOT NULL,
  `status_code`   SMALLINT     NOT NULL DEFAULT 0,
  `response_time` FLOAT        NOT NULL DEFAULT 0.0 COMMENT 'seconds',
  `success`       TINYINT(1)  NOT NULL DEFAULT 0,
  `error_message` TEXT         DEFAULT NULL,
  `created_at`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_api_name`   (`api_name`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Application event log (admin actions, errors)
-- ============================================================
CREATE TABLE IF NOT EXISTS `app_logs` (
  `id`         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `level`      ENUM('info','warning','error','critical') NOT NULL DEFAULT 'info',
  `context`    VARCHAR(128) NOT NULL DEFAULT 'system',
  `message`    TEXT NOT NULL,
  `ip_address` VARCHAR(45)  DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_level`      (`level`),
  INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Admin sessions
-- ============================================================
CREATE TABLE IF NOT EXISTS `admin_sessions` (
  `id`         VARCHAR(128) PRIMARY KEY,
  `user_id`    INT UNSIGNED NOT NULL,
  `ip_address` VARCHAR(45)  NOT NULL,
  `user_agent` VARCHAR(512) DEFAULT NULL,
  `expires_at` DATETIME     NOT NULL,
  `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `admin_users`(`id`) ON DELETE CASCADE,
  INDEX `idx_expires_at` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Site settings (key-value)
-- ============================================================
CREATE TABLE IF NOT EXISTS `settings` (
  `key`        VARCHAR(128) PRIMARY KEY,
  `value`      TEXT         DEFAULT NULL,
  `updated_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Seed: Default admin user
-- Password: Admin@Terminal2024  (change immediately after deploy)
-- Hash generated with password_hash('Admin@Terminal2024', PASSWORD_BCRYPT, ['cost'=>12])
-- ============================================================
INSERT IGNORE INTO `admin_users` (`username`, `email`, `password_hash`) VALUES (
  'admin',
  'admin@terminal.local',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
);

-- ============================================================
-- Seed: Default settings
-- ============================================================
INSERT IGNORE INTO `settings` (`key`, `value`) VALUES
  ('site_title',       'XAUUSD Intelligence Terminal'),
  ('alpha_vantage_key','YOUR_ALPHA_VANTAGE_KEY'),
  ('finnhub_key',      'YOUR_FINNHUB_KEY'),
  ('metals_api_key',   'YOUR_METALS_API_KEY'),
  ('news_api_key',     'YOUR_NEWS_API_KEY'),
  ('maintenance_mode', '0'),
  ('price_refresh_sec','30');

SET FOREIGN_KEY_CHECKS = 1;
