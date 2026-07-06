-- v2.4: Self-serve banner ad marketplace (duration tiers, website/app/both
-- placement, admin-set pricing) built on top of the custom_ads system.

CREATE TABLE IF NOT EXISTS `ad_pricing` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `duration_seconds` enum('5','10','15') NOT NULL,
  `platform_placement` enum('website','app','both') NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(10) DEFAULT 'NGN',
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_duration_placement` (`duration_seconds`, `platform_placement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `ad_pricing` (`duration_seconds`, `platform_placement`, `price`) VALUES
  ('5',  'website', 5000.00),
  ('5',  'app',     5000.00),
  ('5',  'both',    9000.00),
  ('10', 'website', 8000.00),
  ('10', 'app',     8000.00),
  ('10', 'both',    14000.00),
  ('15', 'website', 12000.00),
  ('15', 'app',     12000.00),
  ('15', 'both',    20000.00);

ALTER TABLE `custom_ads` ADD COLUMN `user_id` int(11) DEFAULT NULL AFTER `id`;
ALTER TABLE `custom_ads` ADD COLUMN `duration_seconds` int(11) DEFAULT 5 AFTER `placement`;
ALTER TABLE `custom_ads` ADD COLUMN `platform_placement` enum('website','app','both') DEFAULT 'both' AFTER `duration_seconds`;
ALTER TABLE `custom_ads` ADD COLUMN `payment_status` enum('pending','paid','failed') DEFAULT 'paid' AFTER `is_active`;
ALTER TABLE `custom_ads` ADD COLUMN `payment_reference` varchar(255) DEFAULT NULL AFTER `payment_status`;
ALTER TABLE `custom_ads` ADD CONSTRAINT `fk_custom_ads_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;

ALTER TABLE `payment_transactions` ADD COLUMN `type` enum('coin_purchase','ad_placement') DEFAULT 'coin_purchase' AFTER `package_id`;
ALTER TABLE `payment_transactions` ADD COLUMN `ad_id` int(11) DEFAULT NULL AFTER `type`;
ALTER TABLE `payment_transactions` ADD CONSTRAINT `fk_payment_transactions_ad` FOREIGN KEY (`ad_id`) REFERENCES `custom_ads`(`id`) ON DELETE SET NULL;

INSERT IGNORE INTO `site_config` (`setting_key`, `setting_value`) VALUES ('ad_campaign_days', '30');
