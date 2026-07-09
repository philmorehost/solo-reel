<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\WatchHistory;

/**
 * Web session-authenticated counterpart to Api\ProgressController — the web
 * reel feed uses a PHP session, not a JWT bearer token, so it can't identify
 * a logged-in user through the API's optionalUserId(). Mirrors the existing
 * CoinController::unlock() / UserController::addFavorite() pattern of having
 * separate session-based (web) and JWT-based (mobile) endpoints for the same
 * action, both writing through the same WatchHistory helper.
 */
class ProgressController {
    public function record(int $episodeId) {
        WatchHistory::record(
            Database::getInstance(),
            $episodeId,
            Session::get('user_id'),
            Session::getGuestId()
        );

        header('Content-Type: application/json');
        echo json_encode(['status' => true]);
    }
}
