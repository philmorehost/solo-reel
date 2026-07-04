<?php

namespace App\Helpers;

use App\Core\Database;

class Site {
    private static $config = null;

    public static function getConfig(string $key, $default = null) {
        if (self::$config === null) {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT setting_key, setting_value FROM site_config");
            self::$config = [];
            while ($row = $stmt->fetch()) {
                self::$config[$row['setting_key']] = $row['setting_value'];
            }
        }
        return self::$config[$key] ?? $default;
    }

    public static function getLogoHtml() {
        $logoUrl = self::getConfig('site_logo');
        $title = htmlspecialchars(self::getConfig('meta_title', 'SOLOREEL'));
        if (!empty($logoUrl)) {
            return '<img src="' . htmlspecialchars($logoUrl) . '" alt="' . $title . '" class="h-12 object-contain">';
        }
        return '<span class="text-red-600 font-bold text-2xl tracking-tighter">' . $title . '</span>';
    }
}
