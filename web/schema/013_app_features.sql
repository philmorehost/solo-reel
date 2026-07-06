-- Add app_install_bonus to site_config
INSERT IGNORE INTO `site_config` (`setting_key`, `setting_value`) VALUES ('app_install_bonus', '50');

-- Ensure guest_wallets table exists
CREATE TABLE IF NOT EXISTS `guest_wallets` (
  `guest_id` varchar(255) NOT NULL,
  `coin_balance` decimal(10,2) DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`guest_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add app_bonus_claimed to users table
ALTER TABLE `users` ADD COLUMN `app_bonus_claimed` tinyint(1) DEFAULT 0 AFTER `coin_balance`;

-- Add unlock_method to episodes table (coins, ads, both)
ALTER TABLE `episodes` ADD COLUMN `unlock_method` enum('coins','ads','both') DEFAULT 'coins' AFTER `coin_cost`;
