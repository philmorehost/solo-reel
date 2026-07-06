-- Guest wallets for coin storage without registration
CREATE TABLE IF NOT EXISTS `guest_wallets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guest_id` varchar(64) NOT NULL,
  `coin_balance` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_guest_id` (`guest_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Weekly bonus tracking per user per week
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

-- Weekly bonus columns on users
ALTER TABLE `users`
  ADD COLUMN IF NOT EXISTS `bonus_coins` decimal(10,2) DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS `bonus_expires_at` timestamp NULL DEFAULT NULL;

-- Series requests from users and guests
CREATE TABLE IF NOT EXISTS `series_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `requester_email` varchar(255) DEFAULT NULL,
  `requester_type` enum('guest','registered') DEFAULT 'guest',
  `user_id` int(11) DEFAULT NULL,
  `guest_id` varchar(64) DEFAULT NULL,
  `status` enum('pending','available') DEFAULT 'pending',
  `request_count` int(11) DEFAULT 1,
  `is_hot` tinyint(1) DEFAULT 0,
  `series_id` int(11) DEFAULT NULL,
  `notified` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`series_id`) REFERENCES `series`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Add weekly bonus setting if not exists
INSERT IGNORE INTO `site_config` (`setting_key`, `setting_value`) VALUES ('weekly_bonus_coins', '50');
