<?php

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\EpisodeEngagement;

class SeriesController extends BaseApiController {

    /** $hotIds: series ids currently in the top-N most-watched (see
     * WatchHistory::hotSeriesIds()) — computed once per request by the
     * caller, not per row. is_new/is_hot are shown as badges everywhere a
     * series card renders, not just on the HOT/NEW tabs themselves. */
    private function mapSeries(array $series, array $hotIds = []): array {
        $isNew = !empty($series['created_at']) && strtotime($series['created_at']) >= strtotime('-14 days');
        return [
            'id'              => (int)$series['id'],
            'title'           => $series['title'],
            'slug'            => $series['slug'],
            'synopsis'        => $series['synopsis'] ?? null,
            'cover_image_url' => $this->absoluteUrl($series['cover_image'] ?? null),
            'genre'           => $series['genre'] ?? null,
            'status'          => $series['status'] ?? null,
            'episode_count'   => (int)($series['episode_count'] ?? 0),
            'is_hot'          => in_array((int)$series['id'], $hotIds, true),
            'is_new'          => $isNew,
        ];
    }

    private function mapEpisode(array $ep, bool $isUnlocked = false, array $counts = [], array $viewerState = []): array {
        $isFree = (bool)($ep['is_free'] ?? false);
        $hasAccess = $isFree || $isUnlocked;
        $episodeNumber = (int)($ep['episode_number'] ?? 0);
        return [
            'id'                     => (int)$ep['id'],
            'episode_number'         => $episodeNumber,
            'title'                  => $ep['title'],
            'slug'                   => $ep['slug'],
            'series_id'              => isset($ep['series_id']) ? (int)$ep['series_id'] : null,
            'series_title'           => $ep['series_title'] ?? null,
            'series_slug'            => $ep['series_slug'] ?? null,
            'thumbnail_url'          => $this->absoluteUrl($ep['thumbnail_url'] ?? null),
            'video_hls_url'          => $hasAccess ? $this->absoluteUrl($ep['video_url'] ?? null) : null,
            'is_free'                => $isFree,
            'is_unlocked'            => $hasAccess,
            'coin_cost'              => (float)($ep['coin_cost'] ?? 0),
            'unlock_method'          => $ep['unlock_method'] ?? 'coins',
            'video_duration_seconds' => (int)($ep['duration_seconds'] ?? $ep['video_duration_seconds'] ?? 0),
            'like_count'             => (int)($counts['like_count'] ?? 0),
            'comment_count'          => (int)($counts['comment_count'] ?? 0),
            'save_count'             => (int)($counts['save_count'] ?? 0),
            'share_count'            => (int)($counts['share_count'] ?? 0),
            'is_liked_by_viewer'     => (bool)($viewerState['is_liked'] ?? false),
            'is_saved_by_viewer'     => (bool)($viewerState['is_saved'] ?? false),
            'can_share'              => $episodeNumber <= 2,
        ];
    }

    /** Builds the "?category=movies|tv_series" WHERE-fragment shared by index()/search().
     * MOVIES = every series NOT tagged "TV Series" (no separate Movies genre needed). */
    private function categoryClause(): string {
        $category = $_GET['category'] ?? null;
        if ($category === 'tv_series') return " AND s.genre = 'TV Series'";
        if ($category === 'movies') return " AND (s.genre != 'TV Series' OR s.genre IS NULL)";
        return '';
    }

    public function index() {
        $db = Database::getInstance();
        $shelf = $_GET['shelf'] ?? null;
        $size = isset($_GET['size']) ? (int) $_GET['size'] : 20;
        if ($size < 1 || $size > 200) $size = 20;
        $categoryClause = $this->categoryClause();

        if ($shelf === 'top') {
            $query = "SELECT s.id, s.title, s.slug, s.synopsis, s.cover_image, s.genre, s.status, s.created_at,
                             'Top Series' as shelf_name,
                             (SELECT COUNT(*) FROM episodes WHERE series_id = s.id) as episode_count
                      FROM series s
                      WHERE 1=1 $categoryClause
                      ORDER BY s.id DESC LIMIT $size";
            $params = [];
        } else {
            $query = "SELECT s.id, s.title, s.slug, s.synopsis, s.cover_image, s.genre, s.status, s.created_at,
                             sh.name as shelf_name,
                             (SELECT COUNT(*) FROM episodes WHERE series_id = s.id) as episode_count
                      FROM series s
                      LEFT JOIN shelves sh ON s.shelf_id = sh.id
                      WHERE 1=1 $categoryClause";

            $params = [];
            if ($shelf) {
                $query .= " AND sh.slug = ?";
                $params[] = $shelf;
            }

            $query .= " ORDER BY s.created_at DESC LIMIT $size";
        }

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $hotIds = \App\Core\WatchHistory::hotSeriesIds($db, 20);
        $series = array_map(function ($row) use ($hotIds) { return $this->mapSeries($row, $hotIds); }, $rows);
        $this->respondJson(['status' => true, 'data' => $series]);
    }

    /** GET /api/v1/series/hot?size= — HOT tab: most-watched series. */
    public function hot() {
        $db = Database::getInstance();
        $size = isset($_GET['size']) ? (int) $_GET['size'] : 20;
        if ($size < 1 || $size > 200) $size = 20;

        $hotIds = \App\Core\WatchHistory::hotSeriesIds($db, $size);
        if (empty($hotIds)) {
            $this->respondJson(['status' => true, 'data' => []]);
        }

        $in = str_repeat('?,', count($hotIds) - 1) . '?';
        $stmt = $db->prepare("SELECT s.id, s.title, s.slug, s.synopsis, s.cover_image, s.genre, s.status, s.created_at,
                                      (SELECT COUNT(*) FROM episodes WHERE series_id = s.id) as episode_count
                               FROM series s WHERE s.id IN ($in)");
        $stmt->execute($hotIds);
        $rows = $stmt->fetchAll();

        // Preserve hotIds' popularity order — the IN() clause doesn't guarantee it.
        $byId = [];
        foreach ($rows as $row) { $byId[(int) $row['id']] = $row; }
        $ordered = array_values(array_filter(array_map(function ($id) use ($byId) { return $byId[$id] ?? null; }, $hotIds)));

        $series = array_map(function ($row) use ($hotIds) { return $this->mapSeries($row, $hotIds); }, $ordered);
        $this->respondJson(['status' => true, 'data' => $series]);
    }

    /** GET /api/v1/series/new — NEW tab: {coming_soon, all_new}. */
    public function newReleases() {
        $db = Database::getInstance();

        $stmt = $db->query("SELECT s.id, s.title, s.slug, s.synopsis, s.cover_image, s.genre, s.status, s.created_at,
                                    (SELECT COUNT(*) FROM episodes WHERE series_id = s.id) as episode_count
                             FROM series s WHERE s.status = 'coming_soon' ORDER BY s.created_at DESC LIMIT 50");
        $comingSoon = $stmt->fetchAll();

        $stmt = $db->query("SELECT s.id, s.title, s.slug, s.synopsis, s.cover_image, s.genre, s.status, s.created_at,
                                    (SELECT COUNT(*) FROM episodes WHERE series_id = s.id) as episode_count
                             FROM series s WHERE s.status != 'coming_soon' AND s.created_at >= DATE_SUB(NOW(), INTERVAL 14 DAY)
                             ORDER BY s.created_at DESC LIMIT 50");
        $allNew = $stmt->fetchAll();

        $hotIds = \App\Core\WatchHistory::hotSeriesIds($db, 20);
        $mapRow = function ($row) use ($hotIds) { return $this->mapSeries($row, $hotIds); };
        $this->respondJson(['status' => true, 'data' => [
            'coming_soon' => array_map($mapRow, $comingSoon),
            'all_new'     => array_map($mapRow, $allNew),
        ]]);
    }

    /** GET /api/v1/series/categories — CATEGORIES tab: one shelf per genre. */
    public function categories() {
        $db = Database::getInstance();
        $genres = $db->query("SELECT name FROM genres ORDER BY name ASC")->fetchAll(\PDO::FETCH_COLUMN);
        $hotIds = \App\Core\WatchHistory::hotSeriesIds($db, 20);

        $result = [];
        foreach ($genres as $genre) {
            $stmt = $db->prepare("SELECT s.id, s.title, s.slug, s.synopsis, s.cover_image, s.genre, s.status, s.created_at,
                                          (SELECT COUNT(*) FROM episodes WHERE series_id = s.id) as episode_count
                                   FROM series s WHERE s.genre = ? ORDER BY s.created_at DESC LIMIT 20");
            $stmt->execute([$genre]);
            $rows = $stmt->fetchAll();
            if (empty($rows)) continue;

            $result[] = [
                'genre'  => $genre,
                'series' => array_map(function ($row) use ($hotIds) { return $this->mapSeries($row, $hotIds); }, $rows),
            ];
        }

        $this->respondJson(['status' => true, 'data' => $result]);
    }

    public function show(string $slug) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT s.*, (SELECT COUNT(*) FROM episodes WHERE series_id = s.id) as episode_count FROM series s WHERE s.slug = ?");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();

        if (!$row) {
            $this->respondJson(['status' => false, 'error' => 'Not found'], 404);
        }

        $series = $this->mapSeries($row, \App\Core\WatchHistory::hotSeriesIds($db, 20));

        $stmt = $db->prepare("SELECT id, series_id, episode_number, title, slug, thumbnail_url, video_url, is_free, coin_cost, unlock_method, duration_seconds FROM episodes WHERE series_id = ? ORDER BY episode_number ASC");
        $stmt->execute([$row['id']]);
        $episodes = $stmt->fetchAll();
        $series['episodes'] = $this->mapEpisodesWithAccess($episodes);

        $this->respondJson(['status' => true, 'data' => $series]);
    }

    public function showBySlug(string $slug) {
        $this->show($slug);
    }

    /** Helper to map a list of episodes and check access for the current registered user and/or guest. */
    private function mapEpisodesWithAccess(array $episodes): array {
        if (empty($episodes)) return [];
        $userId = $this->optionalUserId();
        $guestId = trim((string)($_GET['guest_id'] ?? ''));
        $unlockedIds = [];
        $db = Database::getInstance();

        if ($userId || $guestId !== '') {
            $epIds = array_map(function($e) { return (int)$e['id']; }, $episodes);
            $in = str_repeat('?,', count($epIds) - 1) . '?';

            if ($userId) {
                $stmt = $db->prepare("SELECT episode_id FROM user_unlocked_episodes WHERE user_id = ? AND episode_id IN ($in)");
                $stmt->execute(array_merge([$userId], $epIds));
                $unlockedIds = array_merge($unlockedIds, $stmt->fetchAll(\PDO::FETCH_COLUMN));
            }
            if ($guestId !== '') {
                $stmt = $db->prepare("SELECT episode_id FROM guest_unlocked_episodes WHERE guest_id = ? AND episode_id IN ($in)");
                $stmt->execute(array_merge([$guestId], $epIds));
                $unlockedIds = array_merge($unlockedIds, $stmt->fetchAll(\PDO::FETCH_COLUMN));
            }
        }

        $epIds = array_map(function($e) { return (int)$e['id']; }, $episodes);
        $counts = EpisodeEngagement::batchCounts($db, $epIds);
        $viewerState = EpisodeEngagement::batchViewerState($db, $epIds, $userId, $guestId !== '' ? $guestId : null);

        return array_map(function($ep) use ($unlockedIds, $counts, $viewerState) {
            $id = (int)$ep['id'];
            $isUnlocked = in_array($id, $unlockedIds);
            return $this->mapEpisode($ep, $isUnlocked, $counts[$id] ?? [], $viewerState[$id] ?? []);
        }, $episodes);
    }

    /** GET /api/v1/series/{id}/episodes?guest_id=... */
    public function episodes(int $id) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT e.id, e.series_id, e.episode_number, e.title, e.slug, e.thumbnail_url, e.video_url, e.is_free, e.coin_cost, e.unlock_method, e.duration_seconds, s.title as series_title, s.slug as series_slug
                              FROM episodes e
                              JOIN series s ON e.series_id = s.id
                              WHERE e.series_id = ?
                              ORDER BY e.episode_number ASC");
        $stmt->execute([$id]);
        $rows = $stmt->fetchAll();

        $this->respondJson(['status' => true, 'data' => $this->mapEpisodesWithAccess($rows)]);
    }

    /** GET /api/v1/episodes/{slug}/by-slug?guest_id=... */
    public function episodeBySlug(string $slug) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT e.id, e.series_id, e.episode_number, e.title, e.slug, e.thumbnail_url, e.video_url, e.is_free, e.coin_cost, e.unlock_method, e.duration_seconds, s.title as series_title, s.slug as series_slug
                              FROM episodes e
                              JOIN series s ON e.series_id = s.id
                              WHERE e.slug = ?");
        $stmt->execute([$slug]);
        $row = $stmt->fetch();

        if (!$row) {
            $this->respondJson(['status' => false, 'error' => 'Not found'], 404);
        }

        $mapped = $this->mapEpisodesWithAccess([$row]);
        $this->respondJson(['status' => true, 'data' => $mapped[0]]);
    }

    /** GET /api/v1/series/{id}/resume?guest_id=... — which episode should this viewer resume into. */
    public function resumeEpisode(int $id) {
        $db = Database::getInstance();
        $userId = $this->optionalUserId();
        $guestId = trim((string)($_GET['guest_id'] ?? ''));

        $slug = \App\Core\WatchHistory::resumeSlugFor($db, $id, $userId, $guestId !== '' ? $guestId : null);
        $isFirstWatch = $slug === null;

        if ($isFirstWatch) {
            $stmt = $db->prepare("SELECT slug, episode_number FROM episodes WHERE series_id = ? ORDER BY episode_number ASC LIMIT 1");
            $stmt->execute([$id]);
            $row = $stmt->fetch();
        } else {
            $stmt = $db->prepare("SELECT slug, episode_number FROM episodes WHERE slug = ?");
            $stmt->execute([$slug]);
            $row = $stmt->fetch();
        }

        if (!$row) {
            $this->respondJson(['status' => false, 'error' => 'No episodes found for this series'], 404);
        }

        $this->respondJson(['status' => true, 'data' => [
            'slug' => $row['slug'],
            'episode_number' => (int)$row['episode_number'],
            'is_first_watch' => $isFirstWatch,
        ]]);
    }

    /** GET /api/v1/series/resume-batch?ids=1,2,3&guest_id=... — batch resume-slug
     * lookup so card lists (home/search) can skip straight to the player
     * without one request per card. */
    public function resumeBatch() {
        $db = Database::getInstance();
        $userId = $this->optionalUserId();
        $guestId = trim((string)($_GET['guest_id'] ?? ''));

        $ids = array_filter(array_map('intval', explode(',', (string)($_GET['ids'] ?? ''))));
        $ids = array_values(array_unique($ids));

        $resumeSlugs = \App\Core\WatchHistory::resumeSlugsForSeries($db, $ids, $userId, $guestId !== '' ? $guestId : null);
        $this->respondJson(['status' => true, 'data' => $resumeSlugs]);
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

        $categoryClause = $this->categoryClause();

        if ($q === '') {
            // Empty query lists the full catalogue so the apps' search page
            // can show everything and filter live as the user types.
            $stmt = $db->prepare("SELECT s.id, s.title, s.slug, s.synopsis, s.cover_image, s.genre, s.status, s.created_at,
                                          (SELECT COUNT(*) FROM episodes WHERE series_id = s.id) as episode_count
                                   FROM series s
                                   WHERE 1=1 $categoryClause
                                   ORDER BY s.created_at DESC LIMIT $size");
            $stmt->execute();
        } else {
            $like = "%{$q}%";
            $stmt = $db->prepare("SELECT s.id, s.title, s.slug, s.synopsis, s.cover_image, s.genre, s.status, s.created_at,
                                          (SELECT COUNT(*) FROM episodes WHERE series_id = s.id) as episode_count
                                   FROM series s
                                   WHERE (s.title LIKE ? OR s.synopsis LIKE ?) $categoryClause
                                   ORDER BY s.created_at DESC LIMIT $size");
            $stmt->execute([$like, $like]);
        }
        $rows = $stmt->fetchAll();

        $hotIds = \App\Core\WatchHistory::hotSeriesIds($db, 20);
        $series = array_map(function ($row) use ($hotIds) { return $this->mapSeries($row, $hotIds); }, $rows);
        $this->respondJson(['status' => true, 'data' => $series]);
    }
}
