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

    private function mapSeries(array $series): array {
        return [
            'id'              => (int)$series['id'],
            'title'           => $series['title'],
            'slug'            => $series['slug'],
            'synopsis'        => $series['synopsis'] ?? null,
            'cover_image_url' => $series['cover_image'] ?? null,
            'genre'           => $series['genre'] ?? null,
            'status'          => $series['status'] ?? null,
            'episode_count'   => (int)($series['episode_count'] ?? 0),
        ];
    }

    public function index() {
        $db = Database::getInstance();
        $shelf = $_GET['shelf'] ?? null;
        $size = isset($_GET['size']) ? (int) $_GET['size'] : 20;

        $query = "SELECT s.id, s.title, s.slug, s.synopsis, s.cover_image, s.genre, s.status,
                         sh.name as shelf_name,
                         (SELECT COUNT(*) FROM episodes WHERE series_id = s.id) as episode_count
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
        $rows = $stmt->fetchAll();

        $series = array_map([$this, 'mapSeries'], $rows);
        $this->respondJson(['status' => true, 'data' => $series]);
    }

    public function show(string $slug) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT s.*, (SELECT COUNT(*) FROM episodes WHERE series_id = s.id) as episode_count FROM series s WHERE s.slug = ?");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();

        if (!$row) {
            $this->respondJson(['status' => false, 'error' => 'Not found'], 404);
        }

        $series = $this->mapSeries($row);

        $stmt = $db->prepare("SELECT id, episode_number, title, slug, thumbnail_url, is_free, coin_cost, duration_seconds as video_duration_seconds FROM episodes WHERE series_id = ? ORDER BY episode_number ASC");
        $stmt->execute([$row['id']]);
        $episodes = $stmt->fetchAll();
        $series['episodes'] = array_map(function($ep) {
            return [
                'id'                     => (int)$ep['id'],
                'episode_number'         => (int)$ep['episode_number'],
                'title'                  => $ep['title'],
                'slug'                   => $ep['slug'],
                'thumbnail_url'          => $ep['thumbnail_url'],
                'is_free'                => (bool)$ep['is_free'],
                'coin_cost'              => (float)$ep['coin_cost'],
                'video_duration_seconds' => (int)($ep['video_duration_seconds'] ?? 0),
            ];
        }, $episodes);

        $this->respondJson(['status' => true, 'data' => $series]);
    }

    public function showBySlug(string $slug) {
        $this->show($slug);
    }

    public function search() {
        $db = Database::getInstance();
        $q = $_GET['q'] ?? '';
        $size = isset($_GET['size']) ? (int) $_GET['size'] : 20;

        if (empty(trim($q))) {
            $this->respondJson(['status' => true, 'data' => []]);
        }

        $like = "%{$q}%";
        $stmt = $db->prepare("SELECT s.id, s.title, s.slug, s.synopsis, s.cover_image, s.genre, s.status,
                                      (SELECT COUNT(*) FROM episodes WHERE series_id = s.id) as episode_count
                               FROM series s
                               WHERE s.title LIKE ? OR s.synopsis LIKE ?
                               ORDER BY s.created_at DESC LIMIT ?");
        $stmt->execute([$like, $like, $size]);
        $rows = $stmt->fetchAll();

        $series = array_map([$this, 'mapSeries'], $rows);
        $this->respondJson(['status' => true, 'data' => $series]);
    }
}
