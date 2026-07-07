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

        // Full episode list for the series, so the vertical feed can render
        // the current episode plus its neighbors without extra page loads.
        $stmt = $db->prepare("SELECT * FROM episodes WHERE series_id = ? ORDER BY episode_number ASC");
        $stmt->execute([$episode['series_id']]);
        $seriesEpisodes = $stmt->fetchAll();

        $unlockedIds = [];
        $episodeIds = array_column($seriesEpisodes, 'id');
        if (!empty($episodeIds)) {
            $placeholders = implode(',', array_fill(0, count($episodeIds), '?'));
            if ($userId) {
                $stmt = $db->prepare("SELECT episode_id FROM user_unlocked_episodes WHERE user_id = ? AND episode_id IN ($placeholders)");
                $stmt->execute(array_merge([$userId], $episodeIds));
            } else {
                $stmt = $db->prepare("SELECT episode_id FROM guest_unlocked_episodes WHERE guest_id = ? AND episode_id IN ($placeholders)");
                $stmt->execute(array_merge([$guestId], $episodeIds));
            }
            $unlockedIds = array_map('intval', array_column($stmt->fetchAll(), 'episode_id'));
        }

        $currentIndex = 0;
        foreach ($seriesEpisodes as $i => $ep) {
            if ($ep['slug'] === $slug) {
                $currentIndex = $i;
                break;
            }
        }

        $feedEpisodes = [];
        foreach ($seriesEpisodes as $ep) {
            $hasAccess = (bool) $ep['is_free'] || in_array((int) $ep['id'], $unlockedIds, true);
            $feedEpisodes[] = [
                'id' => (int) $ep['id'],
                'slug' => $ep['slug'],
                'title' => $ep['title'],
                'episode_number' => (int) $ep['episode_number'],
                'video_url' => $ep['video_url'],
                'thumbnail_url' => $ep['thumbnail_url'],
                'coin_cost' => (float) $ep['coin_cost'],
                'unlock_method' => $ep['unlock_method'] ?? 'coins',
                'has_access' => $hasAccess,
            ];
        }

        require __DIR__ . '/../../templates/pages/episode-player.php';
    }
}
