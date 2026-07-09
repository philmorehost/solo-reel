<?php

namespace App\Core;

use PDO;

/**
 * The "For You" trailer feed: a random pool of admin-uploaded trailers,
 * auto-played one after another. "Watch Now" resolves via the already-
 * existing WatchHistory::resumeSlugsForSeries() — no new resolution logic.
 */
class ForYou {
    /** @return array<int, array{episode_id:int, trailer_url:string, series_id:int, series_title:string, series_slug:string, cover_image:?string}> */
    public static function randomTrailers(PDO $db, int $limit = 20): array {
        $limit = min(max(1, $limit), 100);
        // ORDER BY RAND() only scans episodes that actually have a trailer_url
        // set (a small, admin-curated subset), not the whole episodes table —
        // acceptable at this app's scale. Revisit with a different random-
        // sampling scheme if the trailer pool ever exceeds ~5-10k rows.
        $stmt = $db->prepare("
            SELECT e.id AS episode_id, e.trailer_url, e.series_id, s.title AS series_title, s.slug AS series_slug, s.cover_image
            FROM episodes e
            JOIN series s ON s.id = e.series_id
            WHERE e.trailer_url IS NOT NULL
            ORDER BY RAND()
            LIMIT $limit
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
