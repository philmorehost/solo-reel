<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;

class EpisodeController {
    public function player(string $slug) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT e.*, s.title as series_title, s.slug as series_slug
            FROM episodes e
            JOIN series s ON e.series_id = s.id
            WHERE e.slug = ?
        ");
        $stmt->execute([$slug]);
        $episode = $stmt->fetch();

        if (!$episode) {
            http_response_code(404);
            echo "Episode not found.";
            return;
        }

        $userId = Session::get('user_id');
        $guestId = Session::getGuestId();
        $hasAccess = (bool) $episode['is_free'];

        if (!$hasAccess) {
            if ($userId) {
                $checkStmt = $db->prepare("SELECT id FROM user_unlocked_episodes WHERE user_id = ? AND episode_id = ?");
                $checkStmt->execute([$userId, $episode['id']]);
                if ($checkStmt->fetch()) {
                    $hasAccess = true;
                }
            } else {
                $checkStmt = $db->prepare("SELECT id FROM guest_unlocked_episodes WHERE guest_id = ? AND episode_id = ?");
                $checkStmt->execute([$guestId, $episode['id']]);
                if ($checkStmt->fetch()) {
                    $hasAccess = true;
                }
            }
        }

        // Fetch next episode
        $nextStmt = $db->prepare("SELECT slug FROM episodes WHERE series_id = ? AND episode_number > ? ORDER BY episode_number ASC LIMIT 1");
        $nextStmt->execute([$episode['series_id'], $episode['episode_number']]);
        $nextEpisode = $nextStmt->fetch();

        require __DIR__ . '/../../templates/pages/episode-player.php';
    }
}
