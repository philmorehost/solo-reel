<?php

namespace App\Controllers;

use App\Core\Database;

class HomeController {
    public function index() {
        $db = Database::getInstance();

        $stmt = $db->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY sort_order ASC");
        $banners = $stmt->fetchAll();

        $stmt = $db->query("SELECT * FROM shelves WHERE is_active = 1 ORDER BY sort_order ASC");
        $shelves = $stmt->fetchAll();

        foreach ($shelves as &$shelf) {
            $stmt = $db->prepare("SELECT * FROM series WHERE shelf_id = ? ORDER BY created_at DESC LIMIT 6");
            $stmt->execute([$shelf['id']]);
            $shelf['series'] = $stmt->fetchAll();
        }

        require __DIR__ . '/../../templates/pages/home.php';
    }
}
