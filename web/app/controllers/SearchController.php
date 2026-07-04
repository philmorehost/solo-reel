<?php

namespace App\Controllers;

use App\Core\Database;

class SearchController {
    public function index() {
        $db = Database::getInstance();

        // If AJAX request, return JSON
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            $q = $_GET['q'] ?? '';
            $genre = $_GET['genre'] ?? '';
            $status = $_GET['status'] ?? '';

            $query = "SELECT id, title, slug, cover_image, genre, status, (SELECT COUNT(*) FROM episodes WHERE series_id = series.id) as episode_count FROM series WHERE 1=1";
            $params = [];

            if (!empty($q)) {
                $query .= " AND (title LIKE ? OR synopsis LIKE ?)";
                $likeStr = "%{$q}%";
                $params[] = $likeStr;
                $params[] = $likeStr;
            }

            if (!empty($genre)) {
                $query .= " AND genre = ?";
                $params[] = $genre;
            }

            if (!empty($status)) {
                $query .= " AND status = ?";
                $params[] = $status;
            }

            $query .= " ORDER BY created_at DESC LIMIT 24";

            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $series = $stmt->fetchAll();

            header('Content-Type: application/json');
            echo json_encode(['data' => $series]);
            die();
        }

        // Fetch available genres for the filter dropdown
        $stmt = $db->query("SELECT name FROM genres ORDER BY name ASC");
        $genres = $stmt->fetchAll();

        require __DIR__ . '/../../templates/pages/search.php';
    }
}
