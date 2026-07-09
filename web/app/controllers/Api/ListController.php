<?php

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\EpisodeEngagement;
use App\Core\WatchHistory;

class ListController extends BaseApiController {

    private function guestIdFromRequest(): ?string {
        $guestId = trim((string) ($_GET['guest_id'] ?? ''));
        return $guestId !== '' ? $guestId : null;
    }

    /** GET /api/v1/me/list?guest_id=... -> {history, liked, saved} */
    public function myList() {
        $db = Database::getInstance();
        $userId = $this->optionalUserId();
        $guestId = $userId ? null : $this->guestIdFromRequest();

        $this->respondJson(['status' => true, 'data' => [
            'history' => WatchHistory::continueWatchingFor($db, $userId, $guestId, 50),
            'liked'   => EpisodeEngagement::likedSeriesFor($db, $userId, $guestId, 50),
            'saved'   => EpisodeEngagement::savedSeriesFor($db, $userId, $guestId, 50),
        ]]);
    }

    /** DELETE /api/v1/me/list/saved/{seriesId}?guest_id=... — remove a series from My List. */
    public function removeSaved(int $seriesId) {
        $db = Database::getInstance();
        $userId = $this->optionalUserId();
        $guestId = $userId ? null : $this->guestIdFromRequest();

        if (!$userId && !$guestId) {
            $this->respondJson(['status' => false, 'error' => 'Missing identity'], 400);
        }

        EpisodeEngagement::unsaveSeries($db, $seriesId, $userId, $guestId);
        $this->respondJson(['status' => true]);
    }

    /** GET /api/v1/ranking?limit=50 — RANKING tab: series ordered by total like count. */
    public function ranking() {
        $db = Database::getInstance();
        $limit = (int) ($_GET['limit'] ?? 50);
        $this->respondJson(['status' => true, 'data' => EpisodeEngagement::topLikedSeries($db, $limit)]);
    }
}
