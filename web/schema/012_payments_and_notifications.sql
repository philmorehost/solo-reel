-- v2.1: Guest coin purchases + in-app notifications
-- Run after 011_guest_and_new_features.sql

-- Allow payment transactions without a registered user (guest purchases).
ALTER TABLE `payment_transactions` MODIFY `user_id` int(11) DEFAULT NULL;
ALTER TABLE `payment_transactions` ADD COLUMN `guest_id` varchar(64) DEFAULT NULL AFTER `user_id`;
ALTER TABLE `payment_transactions` ADD INDEX `idx_pt_guest_id` (`guest_id`);

-- In-app notifications for registered users (user_id) and guests (guest_id).
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `guest_id` varchar(64) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `body` text,
  `type` varchar(50) DEFAULT 'general',
  `series_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_notif_user` (`user_id`),
  KEY `idx_notif_guest` (`guest_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`series_id`) REFERENCES `series`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
