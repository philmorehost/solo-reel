-- v2.2: Let guests unlock episodes with coins or ads, same as registered users.
CREATE TABLE IF NOT EXISTS `guest_unlocked_episodes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guest_id` varchar(64) NOT NULL,
  `episode_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_guest_episode` (`guest_id`, `episode_id`),
  FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
