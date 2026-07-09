<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\ForYou;
use App\Core\Session;
use App\Core\WatchHistory;

/** Web counterpart to Api\ForYouController — vertical trailer feed, "Watch Now" resumes the series. */
class ForYouController {
    public function index() {
        $db = Database::getInstance();
        $userId = Session::get('user_id');
        $guestId = Session::getGuestId();

        $trailers = ForYou::randomTrailers($db, 20);
        $seriesIds = array_values(array_unique(array_map(function ($t) { return (int) $t['series_id']; }, $trailers)));
        $resumeSlugs = WatchHistory::resumeSlugsForSeries($db, $seriesIds, $userId, $guestId);

        foreach ($trailers as &$t) {
            $t['resume_slug'] = $resumeSlugs[(int) $t['series_id']] ?? null;
        }
        unset($t);

        require __DIR__ . '/../../templates/pages/for-you.php';
    }
}
