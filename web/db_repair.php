<?php
/**
 * Emergency DB repair script: creates the series_requests table if it doesn't exist.
 * Upload this file to your server root and run it ONCE via browser:
 *    https://yourdomain.com/db_repair.php
 * Delete it immediately after running.
 */

// Load database connection from the project
define('DB_REPAIR_MODE', true);
require_once __DIR__ . '/app/core/Database.php';

// Load .env or config
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            putenv(trim($name) . '=' . trim($value));
            $_ENV[trim($name)] = trim($value);
        }
    }
}

echo '<pre>';

try {
    $db = \App\Core\Database::getInstance();

    // -------------------------------------------------------------------------
    // FIX 1: series_requests table
    // -------------------------------------------------------------------------
    $sql = "CREATE TABLE IF NOT EXISTS `series_requests` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `title` varchar(255) NOT NULL,
      `description` text DEFAULT NULL,
      `requester_email` varchar(255) DEFAULT NULL,
      `requester_type` enum('guest','registered') DEFAULT 'guest',
      `user_id` int(11) DEFAULT NULL,
      `guest_id` varchar(64) DEFAULT NULL,
      `status` enum('pending','available') DEFAULT 'pending',
      `request_count` int(11) DEFAULT 1,
      `is_hot` tinyint(1) DEFAULT 0,
      `series_id` int(11) DEFAULT NULL,
      `notified` tinyint(1) DEFAULT 0,
      `created_at` timestamp NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      CONSTRAINT `fk_sr_user_id` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
      CONSTRAINT `fk_sr_series_id` FOREIGN KEY (`series_id`) REFERENCES `series`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $db->exec($sql);
    echo "[OK] Table 'series_requests' is ready.\n";

    // Re-mark the migration as applied in case it was partially applied
    $db->exec("INSERT IGNORE INTO migrations (file) VALUES ('011_guest_and_new_features.sql')");
    echo "[OK] Migration 011 recorded.\n";

    // -------------------------------------------------------------------------
    // FIX 2: payment_settings sandbox/live columns (from migration 015)
    // -------------------------------------------------------------------------
    $cols = $db->query("SHOW COLUMNS FROM `payment_settings`")->fetchAll(\PDO::FETCH_COLUMN);
    $needsCols = [];
    if (!in_array('payhub_public_key_sandbox', $cols))  $needsCols[] = "ADD COLUMN `payhub_public_key_sandbox` varchar(255) DEFAULT NULL";
    if (!in_array('payhub_secret_key_sandbox', $cols))  $needsCols[] = "ADD COLUMN `payhub_secret_key_sandbox` varchar(255) DEFAULT NULL";
    if (!in_array('payhub_public_key_live', $cols))     $needsCols[] = "ADD COLUMN `payhub_public_key_live` varchar(255) DEFAULT NULL";
    if (!in_array('payhub_secret_key_live', $cols))     $needsCols[] = "ADD COLUMN `payhub_secret_key_live` varchar(255) DEFAULT NULL";
    if (!in_array('payhub_base_url', $cols))            $needsCols[] = "ADD COLUMN `payhub_base_url` varchar(255) DEFAULT NULL";

    if ($needsCols) {
        $db->exec("ALTER TABLE `payment_settings` " . implode(', ', $needsCols));
        echo "[OK] Added missing payment_settings columns: " . implode(', ', $needsCols) . "\n";
        // Seed live key columns from the old single-key columns
        $db->exec("UPDATE `payment_settings` SET `payhub_public_key_live` = `payhub_public_key`, `payhub_secret_key_live` = `payhub_secret_key` WHERE `payhub_public_key_live` IS NULL");
        echo "[OK] Seeded live key columns from legacy columns.\n";
    } else {
        echo "[OK] payment_settings columns already present.\n";
    }

    // -------------------------------------------------------------------------
    // FIX 3: Correct the Payhub base URL
    // -------------------------------------------------------------------------
    $db->exec("UPDATE `payment_settings` SET `payhub_base_url` = 'https://merchant.payhub.com.ng'");
    echo "[OK] payhub_base_url corrected to https://merchant.payhub.com.ng\n";
    $settings = $db->query("SELECT mode, payhub_public_key, payhub_secret_key, payhub_public_key_sandbox, payhub_secret_key_sandbox, payhub_public_key_live, payhub_secret_key_live, payhub_base_url FROM payment_settings LIMIT 1")->fetch(\PDO::FETCH_ASSOC);
    if ($settings) {
        echo "\n[DEBUG] Current payment settings:\n";
        foreach ($settings as $k => $v) {
            if (strpos($k, 'secret') !== false && !empty($v)) {
                echo "  $k = " . substr($v, 0, 10) . "..." . substr($v, -4) . " (hidden)\n";
            } else {
                echo "  $k = " . ($v ?: '(empty)') . "\n";
            }
        }
    } else {
        echo "[WARNING] No payment_settings row found. Please configure Payhub keys in Admin -> Payment Settings.\n";
    }

    echo "\n[DONE] All repairs applied. DELETE this file from your server now!\n";
} catch (\Throwable $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
echo '</pre>';
