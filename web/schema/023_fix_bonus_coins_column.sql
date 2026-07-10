-- Re-applies 011_guest_and_new_features.sql's effects. That file had a stray
-- UTF-8 BOM at its start, which broke it as a single exec() batch on
-- production — the exception was caught by the self-healing migrator and the
-- file was marked "executed" anyway, so `users.bonus_coins`/`bonus_expires_at`
-- (and possibly guest_wallets/weekly_bonus_log) never actually got created.
-- This file is deliberately all IF NOT EXISTS / no-conflict so it's a safe
-- no-op anywhere 011 did partially succeed.

CREATE TABLE IF NOT EXISTS `guest_wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guest_id` varchar(64) NOT NULL,
  `coin_balance` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_guest_id` (`guest_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `weekly_bonus_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `coins_awarded` decimal(10,2) NOT NULL,
  `week_start` date NOT NULL,
  `expires_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_week` (`user_id`, `week_start`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Version-agnostic idempotent column add (information_schema guard + dynamic
-- SQL) rather than "ADD COLUMN IF NOT EXISTS" — that clause needs a newer
-- MySQL/MariaDB than may be available here, and a plain unconditional
-- ADD COLUMN would break this file all over again on any environment where
-- 011 (or this file, re-run) already succeeded.
SET @bonus_coins_sql = (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'bonus_coins') = 0,
  'ALTER TABLE `users` ADD COLUMN `bonus_coins` decimal(10,2) DEFAULT 0.00',
  'SELECT 1'
));
PREPARE stmt FROM @bonus_coins_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @bonus_expires_sql = (SELECT IF(
  (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'bonus_expires_at') = 0,
  'ALTER TABLE `users` ADD COLUMN `bonus_expires_at` timestamp NULL DEFAULT NULL',
  'SELECT 1'
));
PREPARE stmt FROM @bonus_expires_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO `site_config` (`setting_key`, `setting_value`) VALUES ('weekly_bonus_coins', '50');
