<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\EpisodeEngagement;
use App\Core\Security;
use App\Core\Session;

/**
 * Web session-authenticated counterpart to Api\EngagementController — the
 * web reel feed uses a PHP session, not a JWT bearer token, matching the
 * existing split between session-based (web) and JWT-based (mobile)
 * endpoints for the same action (see CoinController::unlock() /
 * Api\TransactionController::unlock(), ProgressController / Api\ProgressController).
 */
class EngagementController {
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

    public function toggleLike(int $episodeId) {
        $this->requireCsrf();
        $result = EpisodeEngagement::toggleLike(Database::getInstance(), $episodeId, Session::get('user_id'), Session::getGuestId());
        $this->respondJson(['status' => true, 'data' => $result]);
    }

    public function toggleSave(int $episodeId) {
        $this->requireCsrf();
        $result = EpisodeEngagement::toggleSave(Database::getInstance(), $episodeId, Session::get('user_id'), Session::getGuestId());
        $this->respondJson(['status' => true, 'data' => $result]);
    }

    public function listComments(int $episodeId) {
        $offset = (int) ($_GET['offset'] ?? 0);
        $limit = (int) ($_GET['limit'] ?? 20);
        $result = EpisodeEngagement::listComments(Database::getInstance(), $episodeId, $offset, $limit);
        $this->respondJson(['status' => true, 'data' => $result]);
    }

    public function postComment(int $episodeId) {
        $this->requireCsrf();
        $body = trim((string) ($_POST['body'] ?? ''));
        if ($body === '') {
            $this->respondJson(['status' => false, 'error' => 'Comment cannot be empty'], 400);
        }

        $comment = EpisodeEngagement::addComment(Database::getInstance(), $episodeId, Session::get('user_id'), Session::getGuestId(), $body);
        if (!$comment) {
            $this->respondJson(['status' => false, 'error' => 'Failed to post comment'], 500);
        }
        $this->respondJson(['status' => true, 'data' => $comment]);
    }

    public function recordShare(int $episodeId) {
        $this->requireCsrf();
        $platform = trim((string) ($_POST['platform'] ?? 'web'));
        EpisodeEngagement::recordShare(Database::getInstance(), $episodeId, Session::get('user_id'), Session::getGuestId(), $platform ?: 'web');
        $this->respondJson(['status' => true]);
    }
}
