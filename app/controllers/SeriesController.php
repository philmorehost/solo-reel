<?php

namespace App\Controllers;

use App\Core\Database;

class SeriesController {
    public function detail(string $slug) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM series WHERE slug = ?");
        $stmt->execute([$slug]);
        $series = $stmt->fetch();

        if (!$series) {
            http_response_code(404);
            echo "Series not found.";
            return;
        }

        $stmt = $db->prepare("SELECT * FROM episodes WHERE series_id = ? ORDER BY episode_number ASC");
        $stmt->execute([$series['id']]);
        $episodes = $stmt->fetchAll();

        require __DIR__ . '/../../templates/pages/series-detail.php';
    }
}
