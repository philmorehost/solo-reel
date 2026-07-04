CREATE TABLE `video_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `episode_id` int(11) NOT NULL,
  `original_file` varchar(500) NOT NULL,
  `status` enum('pending','processing','done','failed') DEFAULT 'pending',
  `progress` tinyint(3) DEFAULT 0,
  `hls_path` varchar(500) DEFAULT NULL,
  `hls_url` varchar(500) DEFAULT NULL,
  `duration_seconds` int(11) DEFAULT 0,
  `file_size_bytes` bigint(20) DEFAULT 0,
  `error_message` text,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_episode_id` (`episode_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
