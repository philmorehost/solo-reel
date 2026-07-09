-- Guest-side watch history, mirroring the existing user_unlocked_episodes /
-- guest_unlocked_episodes split (a nullable guest_id column on watch_history
-- wouldn't dedupe correctly: MySQL treats every NULL as distinct in a
-- UNIQUE index).
CREATE TABLE IF NOT EXISTS `guest_watch_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guest_id` varchar(64) NOT NULL,
  `episode_id` int(11) NOT NULL,
  `progress_seconds` int(11) DEFAULT 0,
  `last_watched_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_guest_episode` (`guest_id`, `episode_id`),
  FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default genres for a ReelShort/DramaBox-style short-drama catalogue.
INSERT IGNORE INTO `genres` (`name`, `slug`) VALUES
('Romance', 'romance'),
('Revenge', 'revenge'),
('Billionaire', 'billionaire'),
('CEO', 'ceo'),
('Werewolf & Fantasy', 'werewolf-fantasy'),
('Mafia', 'mafia'),
('Comedy', 'comedy'),
('Action', 'action'),
('Thriller', 'thriller'),
('Family', 'family'),
('Historical', 'historical'),
('Workplace', 'workplace');

-- Default shelves. 'trending-now' (1) and 'binge-worthy-romance' (2) already
-- exist from 008_demo_content.sql; INSERT IGNORE skips them harmlessly.
-- "Continue Watching" is deliberately NOT a shelves row here — it's computed
-- per-viewer at request time from watch history, never admin-curated.
INSERT IGNORE INTO `shelves` (`name`, `slug`, `emoji`, `sort_order`) VALUES
('New Releases', 'new-releases', '🆕', 3),
('Editor''s Picks', 'editors-picks', '⭐', 4),
('Most Popular', 'most-popular', '📈', 5);
