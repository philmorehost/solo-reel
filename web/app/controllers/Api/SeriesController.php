<?php

namespace App\Controllers\Api;

use App\Core\Database;

class SeriesController {

    private function respondJson($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        die();
    }

    public function index() {
        $db = Database::getInstance();
        $shelf = $_GET['shelf'] ?? null;
        $size = isset($_GET['size']) ? (int) $_GET['size'] : 12;

        $query = "SELECT s.id, s.title, s.slug, s.synopsis, s.cover_image, s.genre, s.status, sh.name as shelf_name
                  FROM series s
                  LEFT JOIN shelves sh ON s.shelf_id = sh.id";

        $params = [];
        if ($shelf) {
            $query .= " WHERE sh.slug = ?";
            $params[] = $shelf;
        }

        $query .= " ORDER BY s.created_at DESC LIMIT $size";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $series = $stmt->fetchAll();

        $this->respondJson(['data' => $series]);
    }

    public function show(string $slug) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM series WHERE slug = ?");
        $stmt->execute([$slug]);
        $series = $stmt->fetch();

        if (!$series) {
            $this->respondJson(['error' => 'Not found'], 404);
        }

        $stmt = $db->prepare("SELECT * FROM episodes WHERE series_id = ? ORDER BY episode_number ASC");
        $stmt->execute([$series['id']]);
        $series['episodes'] = $stmt->fetchAll();

        $this->respondJson(['data' => $series]);
    }
}
