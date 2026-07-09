<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\EpisodeEngagement;
use App\Core\Security;
use App\Core\Session;
use App\Core\WatchHistory;

/**
 * Web session-authenticated counterpart to Api\ListController — same
 * session+CSRF vs. JWT+guest-id split used throughout this app (see
 * EngagementController / Api\EngagementController).
 */
class MyListController {
    private function respondJson(array $data, int $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        die();
    }

    private function requireCsrf() {
        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $this->respondJson(['status' => false, 'error' => 'Session expired. Please refresh and try again.'], 403);
        }
    }

    public function index() {
        $db = Database::getInstance();
        $userId = Session::get('user_id');
        $guestId = Session::getGuestId();

        $history = WatchHistory::continueWatchingFor($db, $userId, $guestId, 50);
        $liked = EpisodeEngagement::likedSeriesFor($db, $userId, $guestId, 50);
        $saved = EpisodeEngagement::savedSeriesFor($db, $userId, $guestId, 50);

        require __DIR__ . '/../../templates/pages/my-list.php';
    }

    public function removeSaved(int $seriesId) {
        $this->requireCsrf();
        EpisodeEngagement::unsaveSeries(Database::getInstance(), $seriesId, Session::get('user_id'), Session::getGuestId());
        $this->respondJson(['status' => true]);
    }
}
