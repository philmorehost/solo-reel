<?php

namespace App\Middleware;

use App\Core\Database;

class SecurityMiddleware {
    public static function checkBlacklist() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        if (empty($ip)) return;

        $db = Database::getInstance();

        // Ensure table exists (may not if installer hasn't run)
        try {
            $stmt = $db->prepare("SELECT id FROM ip_blacklist WHERE ip_address = ?");
            $stmt->execute([$ip]);
            if ($stmt->fetch()) {
                http_response_code(403);
                die("Access Denied: Your IP address is blocked.");
            }
        } catch (\PDOException $e) {
            // Ignore if tables don't exist yet
        }
    }
}
