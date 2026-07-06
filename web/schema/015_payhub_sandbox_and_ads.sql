-- v2.3: Separate Payhub sandbox/live key pairs (the "mode" toggle previously
-- did nothing — there was only one key slot) + custom admin-uploaded ads.

ALTER TABLE `payment_settings` ADD COLUMN `payhub_public_key_sandbox` varchar(255) DEFAULT NULL AFTER `payhub_secret_key`;
ALTER TABLE `payment_settings` ADD COLUMN `payhub_secret_key_sandbox` varchar(255) DEFAULT NULL AFTER `payhub_public_key_sandbox`;
ALTER TABLE `payment_settings` ADD COLUMN `payhub_public_key_live` varchar(255) DEFAULT NULL AFTER `payhub_secret_key_sandbox`;
ALTER TABLE `payment_settings` ADD COLUMN `payhub_secret_key_live` varchar(255) DEFAULT NULL AFTER `payhub_public_key_live`;

-- Seed the new live-key columns from the old single key pair so existing
-- deployments keep working in live mode after the migration; sandbox keys
-- must be entered fresh in Admin -> Payment Settings.
UPDATE `payment_settings`
SET `payhub_public_key_live` = `payhub_public_key`,
    `payhub_secret_key_live` = `payhub_secret_key`
WHERE `payhub_public_key_live` IS NULL;

CREATE TABLE IF NOT EXISTS `custom_ads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `media_type` enum('image','video') DEFAULT 'image',
  `media_url` varchar(500) DEFAULT NULL,
  `target_url` varchar(500) DEFAULT NULL,
  `placement` enum('banner','interstitial','both') DEFAULT 'both',
  `starts_at` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
