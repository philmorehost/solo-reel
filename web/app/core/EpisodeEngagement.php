<?php

namespace App\Core;

use PDO;

/**
 * Episode-level like/save/comment/share logic, shared by the web
 * session-based controller and the mobile JWT/guest-based API controller —
 * mirrors how WatchHistory.php is the one place resume/continue-watching SQL
 * lives. Guest vs. user identity follows the same dual-table split used
 * throughout this app (see WatchHistory, unlocked-episodes).
 */
class EpisodeEngagement {
    public static function toggleLike(PDO $db, int $episodeId, ?int $userId, ?string $guestId): array {
        $liked = self::toggle($db, 'episode_likes', 'guest_episode_likes', $episodeId, $userId, $guestId);
        return ['liked' => $liked, 'count' => self::likeCount($db, $episodeId)];
    }

    public static function toggleSave(PDO $db, int $episodeId, ?int $userId, ?string $guestId): array {
        $saved = self::toggle($db, 'episode_saves', 'guest_episode_saves', $episodeId, $userId, $guestId);
        return ['saved' => $saved, 'count' => self::saveCount($db, $episodeId)];
    }

    /** Delete-if-present else insert. Returns the new state (true = now active). */
    private static function toggle(PDO $db, string $userTable, string $guestTable, int $episodeId, ?int $userId, ?string $guestId): bool {
        if ($userId) {
            $del = $db->prepare("DELETE FROM `$userTable` WHERE user_id = ? AND episode_id = ?");
            $del->execute([$userId, $episodeId]);
            $active = $del->rowCount() === 0;
            if ($active) {
                $db->prepare("INSERT IGNORE INTO `$userTable` (user_id, episode_id) VALUES (?, ?)")->execute([$userId, $episodeId]);
            }
            return $active;
        }
        if ($guestId) {
            $del = $db->prepare("DELETE FROM `$guestTable` WHERE guest_id = ? AND episode_id = ?");
            $del->execute([$guestId, $episodeId]);
            $active = $del->rowCount() === 0;
            if ($active) {
                $db->prepare("INSERT IGNORE INTO `$guestTable` (guest_id, episode_id) VALUES (?, ?)")->execute([$guestId, $episodeId]);
            }
            return $active;
        }
        return false;
    }

    public static function likeCount(PDO $db, int $episodeId): int {
        return self::sumCount($db, 'episode_likes', 'guest_episode_likes', $episodeId);
    }

    public static function saveCount(PDO $db, int $episodeId): int {
        return self::sumCount($db, 'episode_saves', 'guest_episode_saves', $episodeId);
    }

    public static function commentCount(PDO $db, int $episodeId): int {
        $stmt = $db->prepare("SELECT COUNT(*) FROM episode_comments WHERE episode_id = ?");
        $stmt->execute([$episodeId]);
        return (int) $stmt->fetchColumn();
    }

    public static function shareCount(PDO $db, int $episodeId): int {
        $stmt = $db->prepare("SELECT COUNT(*) FROM episode_shares WHERE episode_id = ?");
        $stmt->execute([$episodeId]);
        return (int) $stmt->fetchColumn();
    }

    /** Series-wide totals (summed across every episode) for the reelshort-style
     * sidebar header — a single aggregate figure, not per-episode. */
    public static function seriesTotals(PDO $db, int $seriesId): array {
        $stmt = $db->prepare("
            SELECT
                (SELECT COUNT(*) FROM episode_likes el JOIN episodes e ON e.id = el.episode_id WHERE e.series_id = ?) +
                (SELECT COUNT(*) FROM guest_episode_likes gel JOIN episodes e ON e.id = gel.episode_id WHERE e.series_id = ?) AS like_count,
                (SELECT COUNT(*) FROM episode_saves es JOIN episodes e ON e.id = es.episode_id WHERE e.series_id = ?) +
                (SELECT COUNT(*) FROM guest_episode_saves ges JOIN episodes e ON e.id = ges.episode_id WHERE e.series_id = ?) AS save_count,
                (SELECT COUNT(*) FROM episode_comments ec JOIN episodes e ON e.id = ec.episode_id WHERE e.series_id = ?) AS comment_count,
                (SELECT COUNT(*) FROM episode_shares esh JOIN episodes e ON e.id = esh.episode_id WHERE e.series_id = ?) AS share_count
        ");
        $stmt->execute([$seriesId, $seriesId, $seriesId, $seriesId, $seriesId, $seriesId]);
        $row = $stmt->fetch();
        return [
            'like_count' => (int) ($row['like_count'] ?? 0),
            'save_count' => (int) ($row['save_count'] ?? 0),
            'comment_count' => (int) ($row['comment_count'] ?? 0),
            'share_count' => (int) ($row['share_count'] ?? 0),
        ];
    }

    private static function sumCount(PDO $db, string $userTable, string $guestTable, int $episodeId): int {
        $stmt = $db->prepare("SELECT
            (SELECT COUNT(*) FROM `$userTable` WHERE episode_id = ?) +
            (SELECT COUNT(*) FROM `$guestTable` WHERE episode_id = ?)
        ");
        $stmt->execute([$episodeId, $episodeId]);
        return (int) $stmt->fetchColumn();
    }

    /** [episode_id => ['like_count'=>, 'comment_count'=>, 'save_count'=>, 'share_count'=>]] for a batch of episodes. */
    public static function batchCounts(PDO $db, array $episodeIds): array {
        if (empty($episodeIds)) return [];
        $result = [];
        foreach ($episodeIds as $id) {
            $result[$id] = ['like_count' => 0, 'comment_count' => 0, 'save_count' => 0, 'share_count' => 0];
        }

        $in = str_repeat('?,', count($episodeIds) - 1) . '?';
        $tables = [
            'like_count' => ['episode_likes', 'guest_episode_likes'],
            'save_count' => ['episode_saves', 'guest_episode_saves'],
        ];
        foreach ($tables as $key => [$userTable, $guestTable]) {
            $stmt = $db->prepare("SELECT episode_id, COUNT(*) as c FROM `$userTable` WHERE episode_id IN ($in) GROUP BY episode_id");
            $stmt->execute($episodeIds);
            foreach ($stmt->fetchAll() as $row) { $result[(int)$row['episode_id']][$key] += (int)$row['c']; }

            $stmt = $db->prepare("SELECT episode_id, COUNT(*) as c FROM `$guestTable` WHERE episode_id IN ($in) GROUP BY episode_id");
            $stmt->execute($episodeIds);
            foreach ($stmt->fetchAll() as $row) { $result[(int)$row['episode_id']][$key] += (int)$row['c']; }
        }

        $stmt = $db->prepare("SELECT episode_id, COUNT(*) as c FROM episode_comments WHERE episode_id IN ($in) GROUP BY episode_id");
        $stmt->execute($episodeIds);
        foreach ($stmt->fetchAll() as $row) { $result[(int)$row['episode_id']]['comment_count'] = (int)$row['c']; }

        $stmt = $db->prepare("SELECT episode_id, COUNT(*) as c FROM episode_shares WHERE episode_id IN ($in) GROUP BY episode_id");
        $stmt->execute($episodeIds);
        foreach ($stmt->fetchAll() as $row) { $result[(int)$row['episode_id']]['share_count'] = (int)$row['c']; }

        return $result;
    }

    /** [episode_id => ['is_liked'=>bool, 'is_saved'=>bool]] for this viewer, across a batch of episodes. */
    public static function batchViewerState(PDO $db, array $episodeIds, ?int $userId, ?string $guestId): array {
        $result = [];
        foreach ($episodeIds as $id) { $result[$id] = ['is_liked' => false, 'is_saved' => false]; }
        if (empty($episodeIds) || (!$userId && !$guestId)) return $result;

        $in = str_repeat('?,', count($episodeIds) - 1) . '?';
        $lookups = [
            'is_liked' => ['episode_likes', 'guest_episode_likes'],
            'is_saved' => ['episode_saves', 'guest_episode_saves'],
        ];
        foreach ($lookups as $key => [$userTable, $guestTable]) {
            if ($userId) {
                $stmt = $db->prepare("SELECT episode_id FROM `$userTable` WHERE user_id = ? AND episode_id IN ($in)");
                $stmt->execute(array_merge([$userId], $episodeIds));
            } else {
                $stmt = $db->prepare("SELECT episode_id FROM `$guestTable` WHERE guest_id = ? AND episode_id IN ($in)");
                $stmt->execute(array_merge([$guestId], $episodeIds));
            }
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $episodeId) {
                $result[(int) $episodeId][$key] = true;
            }
        }
        return $result;
    }

    public static function addComment(PDO $db, int $episodeId, ?int $userId, ?string $guestId, string $body): ?array {
        $body = trim($body);
        if ($body === '' || (!$userId && !$guestId)) return null;
        if (mb_strlen($body) > 500) $body = mb_substr($body, 0, 500);

        $stmt = $db->prepare("INSERT INTO episode_comments (episode_id, user_id, guest_id, guest_display_name, body) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$episodeId, $userId, $userId ? null : $guestId, $userId ? null : 'Guest', $body]);
        $id = (int) $db->lastInsertId();

        return self::formatComment($db, $id);
    }

    private static function formatComment(PDO $db, int $commentId): ?array {
        $stmt = $db->prepare("
            SELECT c.id, c.body, c.created_at, c.guest_display_name, u.username
            FROM episode_comments c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.id = ?
        ");
        $stmt->execute([$commentId]);
        $row = $stmt->fetch();
        if (!$row) return null;
        return [
            'id' => (int) $row['id'],
            'author' => $row['username'] ?? ($row['guest_display_name'] ?? 'Guest'),
            'body' => $row['body'],
            'created_at' => $row['created_at'],
        ];
    }

    /** ['items' => [...], 'total' => int] — flat list, newest first, paginated. */
    public static function listComments(PDO $db, int $episodeId, int $offset = 0, int $limit = 20): array {
        $offset = max(0, $offset);
        $limit = min(max(1, $limit), 100);

        $stmt = $db->prepare("
            SELECT c.id, c.body, c.created_at, c.guest_display_name, u.username
            FROM episode_comments c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.episode_id = ?
            ORDER BY c.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute([$episodeId]);
        $items = array_map(function ($row) {
            return [
                'id' => (int) $row['id'],
                'author' => $row['username'] ?? ($row['guest_display_name'] ?? 'Guest'),
                'body' => $row['body'],
                'created_at' => $row['created_at'],
            ];
        }, $stmt->fetchAll());

        return ['items' => $items, 'total' => self::commentCount($db, $episodeId)];
    }

    public static function recordShare(PDO $db, int $episodeId, ?int $userId, ?string $guestId, ?string $platform): void {
        $db->prepare("INSERT INTO episode_shares (episode_id, user_id, guest_id, platform) VALUES (?, ?, ?, ?)")
           ->execute([$episodeId, $userId, $userId ? null : $guestId, $platform]);
    }

    // --- Series-level rollups for "My List" and the RANKING tab. A series
    // counts as liked/saved if the viewer has liked/saved ANY of its episodes
    // — reusing the same per-episode toggle the action rail already writes
    // to, rather than introducing a separate series-level save concept. ---

    /** [series_id => bool] batch "has this viewer liked any episode of this series" — for card grids. */
    public static function batchSeriesLiked(PDO $db, array $seriesIds, ?int $userId, ?string $guestId): array {
        return self::batchSeriesFlag($db, $seriesIds, 'episode_likes', 'guest_episode_likes', $userId, $guestId);
    }

    /** [series_id => bool] batch "has this viewer saved any episode of this series" — for card grids. */
    public static function batchSeriesSaved(PDO $db, array $seriesIds, ?int $userId, ?string $guestId): array {
        return self::batchSeriesFlag($db, $seriesIds, 'episode_saves', 'guest_episode_saves', $userId, $guestId);
    }

    private static function batchSeriesFlag(PDO $db, array $seriesIds, string $userTable, string $guestTable, ?int $userId, ?string $guestId): array {
        $result = [];
        foreach ($seriesIds as $id) { $result[(int) $id] = false; }
        if (empty($seriesIds) || (!$userId && !$guestId)) return $result;

        $in = str_repeat('?,', count($seriesIds) - 1) . '?';
        if ($userId) {
            $stmt = $db->prepare("SELECT DISTINCT e.series_id FROM `$userTable` t JOIN episodes e ON e.id = t.episode_id WHERE t.user_id = ? AND e.series_id IN ($in)");
            $stmt->execute(array_merge([$userId], $seriesIds));
        } else {
            $stmt = $db->prepare("SELECT DISTINCT e.series_id FROM `$guestTable` t JOIN episodes e ON e.id = t.episode_id WHERE t.guest_id = ? AND e.series_id IN ($in)");
            $stmt->execute(array_merge([$guestId], $seriesIds));
        }
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $seriesId) { $result[(int) $seriesId] = true; }
        return $result;
    }

    /** Series this viewer has liked at least one episode of — My List > Liked. */
    public static function likedSeriesFor(PDO $db, ?int $userId, ?string $guestId, int $limit = 50): array {
        return self::seriesForEngagement($db, 'episode_likes', 'guest_episode_likes', $userId, $guestId, $limit);
    }

    /** Series this viewer has saved at least one episode of — My List > Saved (= "My List" proper). */
    public static function savedSeriesFor(PDO $db, ?int $userId, ?string $guestId, int $limit = 50): array {
        return self::seriesForEngagement($db, 'episode_saves', 'guest_episode_saves', $userId, $guestId, $limit);
    }

    private static function seriesForEngagement(PDO $db, string $userTable, string $guestTable, ?int $userId, ?string $guestId, int $limit): array {
        if (!$userId && !$guestId) return [];
        $limit = min(max(1, $limit), 200);
        $table = $userId ? $userTable : $guestTable;
        $identityColumn = $userId ? 'user_id' : 'guest_id';
        $identityValue = $userId ?: $guestId;

        $stmt = $db->prepare("
            SELECT s.id, s.title, s.slug, s.cover_image,
                   (SELECT COUNT(*) FROM episodes WHERE series_id = s.id) as episode_count,
                   MAX(t.created_at) as last_engaged_at
            FROM `$table` t
            JOIN episodes e ON e.id = t.episode_id
            JOIN series s ON s.id = e.series_id
            WHERE t.$identityColumn = ?
            GROUP BY s.id
            ORDER BY last_engaged_at DESC
            LIMIT $limit
        ");
        $stmt->execute([$identityValue]);
        return $stmt->fetchAll();
    }

    /** Removes every saved-episode row this viewer has in $seriesId — "remove from My List". */
    public static function unsaveSeries(PDO $db, int $seriesId, ?int $userId, ?string $guestId): void {
        if ($userId) {
            $db->prepare("DELETE es FROM episode_saves es JOIN episodes e ON e.id = es.episode_id WHERE es.user_id = ? AND e.series_id = ?")
               ->execute([$userId, $seriesId]);
        } elseif ($guestId) {
            $db->prepare("DELETE ges FROM guest_episode_saves ges JOIN episodes e ON e.id = ges.episode_id WHERE ges.guest_id = ? AND e.series_id = ?")
               ->execute([$guestId, $seriesId]);
        }
    }

    /** RANKING tab: series ordered by summed like count across all their episodes, with the counter shown per row. */
    public static function topLikedSeries(PDO $db, int $limit = 50): array {
        $limit = min(max(1, $limit), 200);
        $stmt = $db->prepare("
            SELECT s.id, s.title, s.slug, s.cover_image,
                   (SELECT COUNT(*) FROM episodes WHERE series_id = s.id) as episode_count,
                   (
                     (SELECT COUNT(*) FROM episode_likes el JOIN episodes e ON e.id = el.episode_id WHERE e.series_id = s.id) +
                     (SELECT COUNT(*) FROM guest_episode_likes gel JOIN episodes e ON e.id = gel.episode_id WHERE e.series_id = s.id)
                   ) AS like_count
            FROM series s
            ORDER BY like_count DESC, s.id ASC
            LIMIT $limit
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
