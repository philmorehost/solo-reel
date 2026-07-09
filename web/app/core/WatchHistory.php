<?php

namespace App\Core;

use PDO;

/**
 * Resolves "which episode should this viewer resume into" for a series,
 * shared by the web series-detail page, the mobile /resume endpoint, and the
 * Continue Watching home-page section. Guests and logged-in users are tracked
 * in separate tables (watch_history / guest_watch_history), mirroring the
 * user_unlocked_episodes / guest_unlocked_episodes split used elsewhere.
 */
class WatchHistory {
    /** Returns the slug of the most-recently-watched episode in $seriesId for
     * this identity, or null if they've never watched anything in it. */
    public static function resumeSlugFor(PDO $db, int $seriesId, ?int $userId, ?string $guestId): ?string {
        if ($userId) {
            $stmt = $db->prepare("
                SELECT e.slug FROM watch_history wh
                JOIN episodes e ON e.id = wh.episode_id
                WHERE wh.user_id = ? AND e.series_id = ?
                ORDER BY wh.last_watched_at DESC LIMIT 1
            ");
            $stmt->execute([$userId, $seriesId]);
        } elseif ($guestId) {
            $stmt = $db->prepare("
                SELECT e.slug FROM guest_watch_history wh
                JOIN episodes e ON e.id = wh.episode_id
                WHERE wh.guest_id = ? AND e.series_id = ?
                ORDER BY wh.last_watched_at DESC LIMIT 1
            ");
            $stmt->execute([$guestId, $seriesId]);
        } else {
            return null;
        }

        $slug = $stmt->fetchColumn();
        return $slug !== false ? $slug : null;
    }

    /** Records that $episodeId was just watched by this identity (upsert). */
    public static function record(PDO $db, int $episodeId, ?int $userId, ?string $guestId): void {
        if ($userId) {
            $db->prepare("
                INSERT INTO watch_history (user_id, episode_id, progress_seconds) VALUES (?, ?, 0)
                ON DUPLICATE KEY UPDATE last_watched_at = CURRENT_TIMESTAMP
            ")->execute([$userId, $episodeId]);
        } elseif ($guestId) {
            $db->prepare("
                INSERT INTO guest_watch_history (guest_id, episode_id, progress_seconds) VALUES (?, ?, 0)
                ON DUPLICATE KEY UPDATE last_watched_at = CURRENT_TIMESTAMP
            ")->execute([$guestId, $episodeId]);
        }
    }

    /** One row per series (its most-recently-watched episode), most-recent series first. */
    public static function continueWatchingFor(PDO $db, ?int $userId, ?string $guestId, int $limit = 12): array {
        if ($userId) {
            $stmt = $db->prepare("
                SELECT s.id, s.title, s.slug, s.cover_image, e.slug AS episode_slug, e.episode_number,
                       (SELECT COUNT(*) FROM episodes WHERE series_id = s.id) as episode_count,
                       wh.last_watched_at
                FROM watch_history wh
                JOIN episodes e ON e.id = wh.episode_id
                JOIN series s ON s.id = e.series_id
                WHERE wh.user_id = ?
                  AND wh.last_watched_at = (
                      SELECT MAX(wh2.last_watched_at) FROM watch_history wh2
                      JOIN episodes e2 ON e2.id = wh2.episode_id
                      WHERE wh2.user_id = ? AND e2.series_id = s.id
                  )
                GROUP BY s.id
                ORDER BY wh.last_watched_at DESC
                LIMIT $limit
            ");
            $stmt->execute([$userId, $userId]);
        } elseif ($guestId) {
            $stmt = $db->prepare("
                SELECT s.id, s.title, s.slug, s.cover_image, e.slug AS episode_slug, e.episode_number,
                       (SELECT COUNT(*) FROM episodes WHERE series_id = s.id) as episode_count,
                       wh.last_watched_at
                FROM guest_watch_history wh
                JOIN episodes e ON e.id = wh.episode_id
                JOIN series s ON s.id = e.series_id
                WHERE wh.guest_id = ?
                  AND wh.last_watched_at = (
                      SELECT MAX(wh2.last_watched_at) FROM guest_watch_history wh2
                      JOIN episodes e2 ON e2.id = wh2.episode_id
                      WHERE wh2.guest_id = ? AND e2.series_id = s.id
                  )
                GROUP BY s.id
                ORDER BY wh.last_watched_at DESC
                LIMIT $limit
            ");
            $stmt->execute([$guestId, $guestId]);
        } else {
            return [];
        }

        return $stmt->fetchAll();
    }

    /** Series ids ordered by total watch events (user + guest), a popularity
     * proxy powering the HOT tab and the "is_hot" badge shown across every
     * listing. Computed once per request and passed around, not recomputed
     * per card. */
    public static function hotSeriesIds(PDO $db, int $limit = 20): array {
        $limit = min(max(1, $limit), 200);
        $stmt = $db->prepare("
            SELECT e.series_id, COUNT(*) as watch_count
            FROM (
                SELECT episode_id FROM watch_history
                UNION ALL
                SELECT episode_id FROM guest_watch_history
            ) w
            JOIN episodes e ON e.id = w.episode_id
            GROUP BY e.series_id
            ORDER BY watch_count DESC
            LIMIT $limit
        ");
        $stmt->execute();
        return array_map('intval', array_column($stmt->fetchAll(), 'series_id'));
    }

    /** [series_id => resume_slug] for a batch of series in one page load — the
     * most-recently-watched episode's slug per series, falling back to
     * episode 1 for series this viewer has never watched. Two queries total
     * (not one per card): history lookup, then a MIN(episode_number)
     * fallback for whichever series ids didn't come back from the first. */
    public static function resumeSlugsForSeries(PDO $db, array $seriesIds, ?int $userId, ?string $guestId): array {
        if (empty($seriesIds)) return [];
        $result = [];
        $in = str_repeat('?,', count($seriesIds) - 1) . '?';

        if ($userId) {
            $stmt = $db->prepare("
                SELECT e.series_id, e.slug
                FROM watch_history wh
                JOIN episodes e ON e.id = wh.episode_id
                WHERE wh.user_id = ? AND e.series_id IN ($in)
                  AND wh.last_watched_at = (
                      SELECT MAX(wh2.last_watched_at) FROM watch_history wh2
                      JOIN episodes e2 ON e2.id = wh2.episode_id
                      WHERE wh2.user_id = ? AND e2.series_id = e.series_id
                  )
                GROUP BY e.series_id
            ");
            $stmt->execute(array_merge([$userId], $seriesIds, [$userId]));
            foreach ($stmt->fetchAll() as $row) { $result[(int)$row['series_id']] = $row['slug']; }
        } elseif ($guestId) {
            $stmt = $db->prepare("
                SELECT e.series_id, e.slug
                FROM guest_watch_history wh
                JOIN episodes e ON e.id = wh.episode_id
                WHERE wh.guest_id = ? AND e.series_id IN ($in)
                  AND wh.last_watched_at = (
                      SELECT MAX(wh2.last_watched_at) FROM guest_watch_history wh2
                      JOIN episodes e2 ON e2.id = wh2.episode_id
                      WHERE wh2.guest_id = ? AND e2.series_id = e.series_id
                  )
                GROUP BY e.series_id
            ");
            $stmt->execute(array_merge([$guestId], $seriesIds, [$guestId]));
            foreach ($stmt->fetchAll() as $row) { $result[(int)$row['series_id']] = $row['slug']; }
        }

        $missingIds = array_values(array_diff($seriesIds, array_keys($result)));
        if (!empty($missingIds)) {
            $missingIn = str_repeat('?,', count($missingIds) - 1) . '?';
            $stmt = $db->prepare("
                SELECT series_id, slug FROM episodes e
                WHERE series_id IN ($missingIn) AND episode_number = (
                    SELECT MIN(episode_number) FROM episodes e2 WHERE e2.series_id = e.series_id
                )
            ");
            $stmt->execute($missingIds);
            foreach ($stmt->fetchAll() as $row) { $result[(int)$row['series_id']] = $row['slug']; }
        }

        return $result;
    }
}
