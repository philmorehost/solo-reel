<?php

namespace App\Controllers;

use App\Core\Database;

class SearchController {
    public function index() {
        $q = $_GET['q'] ?? '';
        $series = [];

        if (!empty($q)) {
            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT * FROM series WHERE title LIKE ? OR synopsis LIKE ? ORDER BY created_at DESC LIMIT 20");
            $likeStr = "%{$q}%";
            $stmt->execute([$likeStr, $likeStr]);
            $series = $stmt->fetchAll();
        }

        require __DIR__ . '/../../templates/pages/search.php';
    }
}
