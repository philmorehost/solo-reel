<?php

namespace App\Core;

use PDO;
use Exception;

class Migrator {
    public static function autoHeal() {
        try {
            $db = Database::getInstance();

            // Fast check: if migrations table exists and count matches files, skip.
            // But to be super safe and avoid DDL on every load, we check Information Schema
            $stmt = $db->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ? AND table_name = 'migrations'");
            $stmt->execute([getenv('DB_NAME') ?: 'soloreel']);

            if ($stmt->fetchColumn() == 0) {
                $db->exec("
                    CREATE TABLE migrations (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        file VARCHAR(255) NOT NULL UNIQUE,
                        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
                ");
            }

            // Get executed migrations
            $stmt = $db->query("SELECT file FROM migrations");
            $executed = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Get all schema files
            $schemaDir = __DIR__ . '/../../schema';
            if (!is_dir($schemaDir)) return;

            $files = scandir($schemaDir);
            $sqlFiles = [];
            foreach ($files as $file) {
                if (str_ends_with($file, '.sql')) {
                    $sqlFiles[] = $file;
                }
            }
            sort($sqlFiles);

            // Optimization: if all are executed, exit early
            if (count($sqlFiles) === count($executed)) {
                return;
            }

            foreach ($sqlFiles as $file) {
                if (!in_array($file, $executed)) {
                    $sql = file_get_contents($schemaDir . '/' . $file);
                    if ($sql) {
                        try {
                            $db->exec($sql);
                            $insert = $db->prepare("INSERT INTO migrations (file) VALUES (?)");
                            $insert->execute([$file]);
                        } catch (Exception $e) {
                            error_log("Migration error in $file: " . $e->getMessage());
                            $insert = $db->prepare("INSERT IGNORE INTO migrations (file) VALUES (?)");
                            $insert->execute([$file]);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Migrator failed: " . $e->getMessage());
        }
    }
}
