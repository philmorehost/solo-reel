<?php

namespace App\Controllers;

use App\Core\Database;

class FaviconController {
    public function index() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT setting_key, setting_value FROM site_config WHERE setting_key IN ('site_title', 'site_favicon')");
        $config = [];
        while ($row = $stmt->fetch()) {
            $config[$row['setting_key']] = $row['setting_value'];
        }

        // Serve uploaded favicon if it exists
        if (!empty($config['site_favicon'])) {
            $path = __DIR__ . '/../../' . ltrim($config['site_favicon'], '/');
            if (file_exists($path)) {
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $mimeTypes = [
                    'png' => 'image/png',
                    'jpg' => 'image/jpeg',
                    'jpeg' => 'image/jpeg',
                    'webp' => 'image/webp'
                ];
                header('Content-Type: ' . ($mimeTypes[$ext] ?? 'image/png'));
                readfile($path);
                die();
            }
        }

        // Fallback: Generate favicon
        $title = $config['site_title'] ?? 'S';
        $firstLetter = strtoupper(substr(trim($title), 0, 1));

        $image = imagecreate(64, 64);
        $bg = imagecolorallocate($image, 220, 38, 38); // Tailwind red-600
        $text_color = imagecolorallocate($image, 255, 255, 255);

        $font = 5;
        $x = 24;
        $y = 24;

        imagestring($image, $font, $x, $y, $firstLetter, $text_color);

        header('Content-Type: image/png');
        imagepng($image);
        imagedestroy($image);
        die();
    }
}
