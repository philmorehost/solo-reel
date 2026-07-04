<?php

namespace App\Controllers;

use App\Core\Database;

class FaviconController {
    public function index() {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT setting_value FROM site_config WHERE setting_key = 'site_title'");
        $stmt->execute();
        $title = $stmt->fetchColumn() ?: 'S';

        $firstLetter = strtoupper(substr(trim($title), 0, 1));

        $image = imagecreate(64, 64);
        $bg = imagecolorallocate($image, 220, 38, 38); // Tailwind red-600
        $text_color = imagecolorallocate($image, 255, 255, 255);

        // Font size and position, centering the letter roughly
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
