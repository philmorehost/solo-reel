-- Episode-level engagement: likes, saves (bookmarks), comments, shares.
-- Likes/saves follow the established user_X/guest_X dual-table split (see
-- user_unlocked_episodes/guest_unlocked_episodes, watch_history/
-- guest_watch_history) rather than a nullable-column single table, since
-- MySQL's UNIQUE index treats every NULL as distinct and would break dedup
-- for guests sharing a NULL user_id.

CREATE TABLE IF NOT EXISTS `episode_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `episode_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_episode` (`user_id`, `episode_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `guest_episode_likes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guest_id` varchar(64) NOT NULL,
  `episode_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_guest_episode` (`guest_id`, `episode_id`),
  FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `episode_saves` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `episode_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_user_episode` (`user_id`, `episode_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `guest_episode_saves` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `guest_id` varchar(64) NOT NULL,
  `episode_id` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `idx_guest_episode` (`guest_id`, `episode_id`),
  FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Comments: a single table (not dual-split) is safe here because a comment's
-- own auto-increment id is its natural key — there's no UNIQUE-on-identity
-- constraint that would run into the NULL-distinctness problem the way
-- likes/saves would. Guests can comment (first-class everywhere else in this
-- app), shown under guest_display_name (defaults to "Guest" at render time
-- if not set — no separate name-collection step in this pass).
CREATE TABLE IF NOT EXISTS `episode_comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `episode_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_id` varchar(64) DEFAULT NULL,
  `guest_display_name` varchar(64) DEFAULT NULL,
  `body` varchar(500) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_episode_created` (`episode_id`, `created_at`),
  FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Shares: a log table (not a bare counter), so the platform label is
-- retained for future analytics. No OS share sheet reports back which
-- target app the user actually picked, so `platform` is just which client
-- fired the share ("web"/"android"/"ios"), not the destination app.
CREATE TABLE IF NOT EXISTS `episode_shares` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `episode_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_id` varchar(64) DEFAULT NULL,
  `platform` varchar(32) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_episode` (`episode_id`),
  FOREIGN KEY (`episode_id`) REFERENCES `episodes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
