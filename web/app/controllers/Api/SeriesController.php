<?php

namespace App\Controllers\Api;

use App\Core\Database;

class SeriesController extends BaseApiController {

    private function mapSeries(array $series): array {
        return [
            'id'              => (int)$series['id'],
            'title'           => $series['title'],
            'slug'            => $series['slug'],
            'synopsis'        => $series['synopsis'] ?? null,
            'cover_image_url' => $this->absoluteUrl($series['cover_image'] ?? null),
            'genre'           => $series['genre'] ?? null,
            'status'          => $series['status'] ?? null,
            'episode_count'   => (int)($series['episode_count'] ?? 0),
        ];
    }

    private function mapEpisode(array $ep): array {
        return [
            'id'                     => (int)$ep['id'],
            'episode_number'         => (int)($ep['episode_number'] ?? 0),
            'title'                  => $ep['title'],
            'slug'                   => $ep['slug'],
            'series_id'              => isset($ep['series_id']) ? (int)$ep['series_id'] : null,
            'series_title'           => $ep['series_title'] ?? null,
            'thumbnail_url'          => $this->absoluteUrl($ep['thumbnail_url'] ?? null),
            'video_hls_url'          => $this->absoluteUrl($ep['video_url'] ?? null),
            'is_free'                => (bool)($ep['is_free'] ?? false),
            'coin_cost'              => (float)($ep['coin_cost'] ?? 0),
            'video_duration_seconds' => (int)($ep['duration_seconds'] ?? $ep['video_duration_seconds'] ?? 0),
        ];
    }

    public function index() {
        $db = Database::getInstance();
        $shelf = $_GET['shelf'] ?? null;
        $size = isset($_GET['size']) ? (int) $_GET['size'] : 20;
        if ($size < 1 || $size > 200) $size = 20;

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

        $stmt = $db->prepare("SELECT id, series_id, episode_number, title, slug, thumbnail_url, video_url, is_free, coin_cost, duration_seconds FROM episodes WHERE series_id = ? ORDER BY episode_number ASC");
        $stmt->execute([$row['id']]);
        $episodes = $stmt->fetchAll();
        $series['episodes'] = array_map([$this, 'mapEpisode'], $episodes);

        $this->respondJson(['status' => true, 'data' => $series]);
    }

    public function showBySlug(string $slug) {
        $this->show($slug);
    }

    /** GET /api/v1/series/{id}/episodes */
    public function episodes(int $id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT e.id, e.series_id, e.episode_number, e.title, e.slug, e.thumbnail_url, e.video_url, e.is_free, e.coin_cost, e.duration_seconds, s.title as series_title
                              FROM episodes e
                              JOIN series s ON e.series_id = s.id
                              WHERE e.series_id = ?
                              ORDER BY e.episode_number ASC");
        $stmt->execute([$id]);
        $rows = $stmt->fetchAll();

        $this->respondJson(['status' => true, 'data' => array_map([$this, 'mapEpisode'], $rows)]);
    }

    /** GET /api/v1/episodes/{slug}/by-slug */
    public function episodeBySlug(string $slug) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT e.id, e.series_id, e.episode_number, e.title, e.slug, e.thumbnail_url, e.video_url, e.is_free, e.coin_cost, e.duration_seconds, s.title as series_title
                              FROM episodes e
                              JOIN series s ON e.series_id = s.id
                              WHERE e.slug = ?");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();

        if (!$row) {
            $this->respondJson(['status' => false, 'error' => 'Not found'], 404);
        }

        $this->respondJson(['status' => true, 'data' => $this->mapEpisode($row)]);
    }

    /** GET /api/v1/shelves */
    public function shelves() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT id, name, slug, emoji FROM shelves ORDER BY sort_order ASC");
        $this->respondJson(['status' => true, 'data' => $stmt->fetchAll()]);
    }

    public function search() {
        $db = Database::getInstance();
        $q = trim($_GET['q'] ?? '');
        $size = isset($_GET['size']) ? (int) $_GET['size'] : 100;
        if ($size < 1 || $size > 200) $size = 100;

        if ($q === '') {
            // Empty query lists the full catalogue so the apps' search page
            // can show everything and filter live as the user types.
            $stmt = $db->prepare("SELECT s.id, s.title, s.slug, s.synopsis, s.cover_image, s.genre, s.status,
                                          (SELECT COUNT(*) FROM episodes WHERE series_id = s.id) as episode_count
                                   FROM series s
                                   ORDER BY s.created_at DESC LIMIT $size");
            $stmt->execute();
        } else {
            $like = "%{$q}%";
            $stmt = $db->prepare("SELECT s.id, s.title, s.slug, s.synopsis, s.cover_image, s.genre, s.status,
                                          (SELECT COUNT(*) FROM episodes WHERE series_id = s.id) as episode_count
                                   FROM series s
                                   WHERE s.title LIKE ? OR s.synopsis LIKE ?
                                   ORDER BY s.created_at DESC LIMIT $size");
            $stmt->execute([$like, $like]);
        }
        $rows = $stmt->fetchAll();

        $series = array_map([$this, 'mapSeries'], $rows);
        $this->respondJson(['status' => true, 'data' => $series]);
    }
}
