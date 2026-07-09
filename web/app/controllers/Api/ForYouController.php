<?php

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\ForYou;
use App\Core\WatchHistory;

class ForYouController extends BaseApiController {

    /** GET /api/v1/for-you?guest_id=&limit= */
    public function feed() {
        $db = Database::getInstance();
        $userId = $this->optionalUserId();
        $guestId = $userId ? null : trim((string)($_GET['guest_id'] ?? ''));
        $limit = (int) ($_GET['limit'] ?? 20);

        $trailers = ForYou::randomTrailers($db, $limit);
        $seriesIds = array_values(array_unique(array_map(function ($t) { return (int) $t['series_id']; }, $trailers)));
        $resumeSlugs = WatchHistory::resumeSlugsForSeries($db, $seriesIds, $userId, $guestId !== '' ? $guestId : null);

        $items = array_map(function ($t) use ($resumeSlugs) {
            $seriesId = (int) $t['series_id'];
            return [
                'episode_id'   => (int) $t['episode_id'],
                'trailer_url'  => $this->absoluteUrl($t['trailer_url']),
                'series_id'    => $seriesId,
                'series_title' => $t['series_title'],
                'cover_image_url' => $this->absoluteUrl($t['cover_image']),
                'resume_slug'  => $resumeSlugs[$seriesId] ?? null,
            ];
        }, $trailers);

        $this->respondJson(['status' => true, 'data' => $items]);
    }
}
