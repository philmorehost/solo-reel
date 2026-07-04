<?php

namespace App\Controllers;

use App\Core\Database;

class ShelfController {
    public function index(string $slug) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM shelves WHERE slug = ? AND is_active = 1");
        $stmt->execute([$slug]);
        $shelf = $stmt->fetch();

        if (!$shelf) {
            http_response_code(404);
            echo "Shelf not found.";
            return;
        }

        $stmt = $db->prepare("SELECT * FROM series WHERE shelf_id = ? ORDER BY created_at DESC");
        $stmt->execute([$shelf['id']]);
        $series = $stmt->fetchAll();

        require __DIR__ . '/../../templates/pages/shelf.php';
    }
}
