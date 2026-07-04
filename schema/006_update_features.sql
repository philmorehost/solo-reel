CREATE TABLE `genres` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE `series` ADD COLUMN `hero_image` varchar(500) DEFAULT NULL AFTER `cover_image`;
ALTER TABLE `series` ADD COLUMN `is_featured` tinyint(1) DEFAULT 0 AFTER `status`;

INSERT INTO `site_config` (`setting_key`, `setting_value`) VALUES ('meta_keywords', 'short drama, mini series, vertical video, streaming');
