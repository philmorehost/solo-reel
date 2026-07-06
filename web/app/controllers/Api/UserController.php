<?php

namespace App\Controllers\Api;

use App\Core\Database;

class UserController extends BaseApiController {

    public function profile() {
        $userId = $this->requireUserId();
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, username, email, display_name, coin_balance, is_verified FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) $this->respondJson(['status' => false, 'error' => 'User not found'], 404);

        $stmt = $db->prepare("SELECT account_number, bank_name, reference FROM virtual_bank_accounts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user['virtual_account'] = $stmt->fetch() ?: null;

        $this->respondJson(['status' => true, 'data' => $user]);
    }

    /** PUT /api/v1/user/profile */
    public function updateProfile() {
        $userId = $this->requireUserId();
        $input = json_decode(file_get_contents('php://input'), true);
        $displayName = trim($input['display_name'] ?? ($input['displayName'] ?? ''));
        $username = trim($input['username'] ?? '');

        $db = Database::getInstance();
        if ($username !== '') {
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $userId]);
            if ($stmt->fetch()) {
                $this->respondJson(['status' => false, 'error' => 'Username already taken'], 409);
            }
            $db->prepare("UPDATE users SET username = ? WHERE id = ?")->execute([$username, $userId]);
        }
        if ($displayName !== '') {
            $db->prepare("UPDATE users SET display_name = ? WHERE id = ?")->execute([$displayName, $userId]);
        }
        if (!empty($input['password'])) {
            $hash = password_hash($input['password'], PASSWORD_ARGON2ID);
            $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $userId]);
        }

        $this->respondJson(['status' => true, 'message' => 'Profile updated']);
    }

    /** GET /api/v1/user/watch-history */
    public function watchHistory() {
        $userId = $this->requireUserId();
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT wh.id, wh.progress_seconds, wh.last_watched_at as watched_at,
                                     e.title as episode_title, e.slug, e.thumbnail_url,
                                     s.title as series_title
                              FROM watch_history wh
                              JOIN episodes e ON wh.episode_id = e.id
                              JOIN series s ON e.series_id = s.id
                              WHERE wh.user_id = ?
                              ORDER BY wh.last_watched_at DESC
                              LIMIT 100");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$row) {
            $row['thumbnail_url'] = $this->absoluteUrl($row['thumbnail_url'] ?? null);
            $row['progress_seconds'] = (int)$row['progress_seconds'];
        }
        unset($row);

        $this->respondJson(['status' => true, 'data' => $rows]);
    }

    /** GET /api/v1/user/favorites */
    public function favorites() {
        $userId = $this->requireUserId();
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT f.id, s.id as series_id, s.title, s.slug, s.cover_image,
                                     (SELECT COUNT(*) FROM episodes WHERE series_id = s.id) as episode_count
                              FROM favorites f
                              JOIN series s ON f.series_id = s.id
                              WHERE f.user_id = ?
                              ORDER BY f.created_at DESC");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        $data = array_map(function ($row) {
            return [
                'id'              => (int)$row['id'],
                'title'           => $row['title'],
                'slug'            => $row['slug'],
                'cover_image_url' => $this->absoluteUrl($row['cover_image'] ?? null),
                'series'          => [
                    'id'              => (int)$row['series_id'],
                    'title'           => $row['title'],
                    'slug'            => $row['slug'],
                    'cover_image_url' => $this->absoluteUrl($row['cover_image'] ?? null),
                    'synopsis'        => null,
                    'genre'           => null,
                    'status'          => null,
                    'episode_count'   => (int)$row['episode_count'],
                ],
            ];
        }, $rows);

        $this->respondJson(['status' => true, 'data' => $data]);
    }

    /** POST /api/v1/user/favorites/{seriesId} */
    public function addFavorite(int $seriesId) {
        $userId = $this->requireUserId();
        $db = Database::getInstance();
        $stmt = $db->prepare("INSERT IGNORE INTO favorites (user_id, series_id) VALUES (?, ?)");
        $stmt->execute([$userId, $seriesId]);
        $this->respondJson(['status' => true, 'message' => 'Added to favorites']);
    }

    /** DELETE /api/v1/user/favorites/{seriesId} */
    public function removeFavorite(int $seriesId) {
        $userId = $this->requireUserId();
        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM favorites WHERE user_id = ? AND series_id = ?");
        $stmt->execute([$userId, $seriesId]);
        $this->respondJson(['status' => true, 'message' => 'Removed from favorites']);
    }
}
