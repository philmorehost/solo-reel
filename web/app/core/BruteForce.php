<?php

namespace App\Core;

/**
 * Login brute-force protection shared by the web login (AuthController) and
 * the mobile API login (Api\AuthController) — the only two real login entry
 * points (admin accounts sign in through the same web form, distinguished by
 * role afterward). Thresholds are admin-configurable via Settings.
 */
class BruteForce {
    private static function config(): array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT setting_key, setting_value FROM site_config WHERE setting_key IN ('max_login_attempts', 'lockout_duration_minutes')");
        $cfg = [];
        while ($row = $stmt->fetch()) {
            $cfg[$row['setting_key']] = $row['setting_value'];
        }
        return [
            'max_attempts'    => (int)($cfg['max_login_attempts'] ?? 5),
            'lockout_minutes' => (int)($cfg['lockout_duration_minutes'] ?? 15),
        ];
    }

    /** Best-effort client IP; fine for a single-host cPanel deployment. */
    public static function clientIp(): string {
        $xff = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($xff !== '') {
            $parts = explode(',', $xff);
            return trim($parts[0]);
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /** True if this IP or username has hit the failed-attempt threshold within the lockout window. */
    public static function isLockedOut(string $ip, string $username): bool {
        $cfg = self::config();
        if ($cfg['max_attempts'] <= 0) return false; // 0 = protection disabled

        $db = Database::getInstance();
        $stmt = $db->prepare(
            "SELECT COUNT(*) AS c FROM login_attempts
             WHERE (ip_address = ? OR username = ?) AND is_success = 0
               AND created_at > (NOW() - INTERVAL ? MINUTE)"
        );
        $stmt->execute([$ip, $username, $cfg['lockout_minutes']]);
        $row = $stmt->fetch();
        return ((int)($row['c'] ?? 0)) >= $cfg['max_attempts'];
    }

    public static function record(string $ip, string $username, bool $success): void {
        $db = Database::getInstance();
        $stmt = $db->prepare("INSERT INTO login_attempts (ip_address, username, is_success) VALUES (?, ?, ?)");
        $stmt->execute([$ip, $username, $success ? 1 : 0]);
    }
}
