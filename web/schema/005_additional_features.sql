-- Missing SEO/Analytics configurations
INSERT INTO `site_config` (`setting_key`, `setting_value`) VALUES
('custom_header', ''),
('custom_footer', ''),
('seo_llms_enabled', '1'),
('seo_sitemap_enabled', '1');

-- Google Auth configuration
INSERT INTO `site_config` (`setting_key`, `setting_value`) VALUES
('google_client_id', ''),
('google_client_secret', ''),
('google_auth_enabled', '0');

-- OTP for users
ALTER TABLE `users` ADD COLUMN `is_verified` tinyint(1) DEFAULT 0 AFTER `email`;
ALTER TABLE `users` ADD COLUMN `otp_code` varchar(10) DEFAULT NULL AFTER `is_verified`;
ALTER TABLE `users` ADD COLUMN `otp_expires_at` timestamp NULL DEFAULT NULL AFTER `otp_code`;

-- Social Share settings
INSERT INTO `site_config` (`setting_key`, `setting_value`) VALUES
('social_facebook', ''),
('social_twitter', ''),
('social_instagram', ''),
('social_tiktok', '');
