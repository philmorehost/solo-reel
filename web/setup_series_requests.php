<?php
require_once __DIR__ . '/app/core/Database.php';

try {
    $db = \App\Core\Database::getInstance();
    
    $sql = "CREATE TABLE IF NOT EXISTS `series_requests` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `title` varchar(255) NOT NULL,
      `description` text,
      `requester_email` varchar(255) DEFAULT NULL,
      `requester_type` varchar(50) DEFAULT 'guest',
      `user_id` int(11) DEFAULT NULL,
      `guest_id` varchar(255) DEFAULT NULL,
      `request_count` int(11) DEFAULT 1,
      `status` varchar(50) DEFAULT 'pending',
      `series_id` int(11) DEFAULT NULL,
      `is_hot` tinyint(1) DEFAULT 0,
      `notified` tinyint(1) DEFAULT 0,
      `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $db->exec($sql);
    echo "Table 'series_requests' created successfully!";
} catch (\PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
