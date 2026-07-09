<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\EpisodeEngagement;
use App\Core\Session;

class EpisodeController {
    public function player(string $slug) {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT e.*, s.title as series_title, s.slug as series_slug, s.synopsis as series_synopsis, s.genre as series_genre
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

        $counts = EpisodeEngagement::batchCounts($db, $episodeIds);
        $viewerState = EpisodeEngagement::batchViewerState($db, $episodeIds, $userId, $guestId);

        $feedEpisodes = [];
        foreach ($seriesEpisodes as $ep) {
            $id = (int) $ep['id'];
            $episodeNumber = (int) $ep['episode_number'];
            $hasAccess = (bool) $ep['is_free'] || in_array($id, $unlockedIds, true);
            $epCounts = $counts[$id] ?? [];
            $epViewerState = $viewerState[$id] ?? [];
            $feedEpisodes[] = [
                'id' => $id,
                'slug' => $ep['slug'],
                'title' => $ep['title'],
                'episode_number' => $episodeNumber,
                'video_url' => $ep['video_url'],
                'thumbnail_url' => $ep['thumbnail_url'],
                'coin_cost' => (float) $ep['coin_cost'],
                'unlock_method' => $ep['unlock_method'] ?? 'coins',
                'has_access' => $hasAccess,
                'like_count' => (int) ($epCounts['like_count'] ?? 0),
                'comment_count' => (int) ($epCounts['comment_count'] ?? 0),
                'save_count' => (int) ($epCounts['save_count'] ?? 0),
                'share_count' => (int) ($epCounts['share_count'] ?? 0),
                'is_liked_by_viewer' => (bool) ($epViewerState['is_liked'] ?? false),
                'is_saved_by_viewer' => (bool) ($epViewerState['is_saved'] ?? false),
                'can_share' => $episodeNumber <= 2,
            ];
        }

        // Series-wide totals for the desktop sidebar's like/save/share header
        // (a single aggregate figure across every episode, not per-episode).
        $seriesTotals = EpisodeEngagement::seriesTotals($db, (int) $episode['series_id']);

        // VIP plans + coin packages for the unlock-offers modal shown when a
        // locked episode can't be unlocked with the viewer's current balance.
        $vipPlans = $db->query("SELECT id, name, price, currency, duration_days, perk_free_unlocks, perk_ad_free FROM vip_plans WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();
        $coinPackages = $db->query("SELECT id, name, coins, price, currency, color_code FROM coin_packages WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

        require __DIR__ . '/../../templates/pages/episode-player.php';
    }
}
