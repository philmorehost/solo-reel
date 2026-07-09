<?php

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\EpisodeEngagement;

class EngagementController extends BaseApiController {

    private function guestIdFromRequest(): ?string {
        $input = json_decode(file_get_contents('php://input'), true);
        $guestId = trim((string)($input['guest_id'] ?? ($_GET['guest_id'] ?? '')));
        return $guestId !== '' ? $guestId : null;
    }

    /** POST /api/v1/episodes/{id}/like */
    public function toggleLike(int $episodeId) {
        $userId = $this->optionalUserId();
        $guestId = $userId ? null : $this->guestIdFromRequest();
        if (!$userId && !$guestId) {
            $this->respondJson(['status' => false, 'error' => 'Missing identity'], 400);
        }

        $result = EpisodeEngagement::toggleLike(Database::getInstance(), $episodeId, $userId, $guestId);
        $this->respondJson(['status' => true, 'data' => $result]);
    }

    /** POST /api/v1/episodes/{id}/save */
    public function toggleSave(int $episodeId) {
        $userId = $this->optionalUserId();
        $guestId = $userId ? null : $this->guestIdFromRequest();
        if (!$userId && !$guestId) {
            $this->respondJson(['status' => false, 'error' => 'Missing identity'], 400);
        }

        $result = EpisodeEngagement::toggleSave(Database::getInstance(), $episodeId, $userId, $guestId);
        $this->respondJson(['status' => true, 'data' => $result]);
    }

    /** GET /api/v1/episodes/{id}/comments?offset=&limit= */
    public function listComments(int $episodeId) {
        $offset = (int) ($_GET['offset'] ?? 0);
        $limit = (int) ($_GET['limit'] ?? 20);
        $result = EpisodeEngagement::listComments(Database::getInstance(), $episodeId, $offset, $limit);
        $this->respondJson(['status' => true, 'data' => $result]);
    }

    /** POST /api/v1/episodes/{id}/comments */
    public function postComment(int $episodeId) {
        $userId = $this->optionalUserId();
        $input = json_decode(file_get_contents('php://input'), true);
        $guestId = $userId ? null : trim((string)($input['guest_id'] ?? ''));
        $body = trim((string)($input['body'] ?? ''));

        if (!$userId && !$guestId) {
            $this->respondJson(['status' => false, 'error' => 'Missing identity'], 400);
        }
        if ($body === '') {
            $this->respondJson(['status' => false, 'error' => 'Comment cannot be empty'], 400);
        }

        $comment = EpisodeEngagement::addComment(Database::getInstance(), $episodeId, $userId, $guestId ?: null, $body);
        if (!$comment) {
            $this->respondJson(['status' => false, 'error' => 'Failed to post comment'], 500);
        }
        $this->respondJson(['status' => true, 'data' => $comment]);
    }

    /** POST /api/v1/episodes/{id}/share — fire-and-forget, always responds fast. */
    public function recordShare(int $episodeId) {
        $userId = $this->optionalUserId();
        $input = json_decode(file_get_contents('php://input'), true);
        $guestId = $userId ? null : trim((string)($input['guest_id'] ?? ''));
        $platform = trim((string)($input['platform'] ?? ''));

        EpisodeEngagement::recordShare(Database::getInstance(), $episodeId, $userId, $guestId ?: null, $platform ?: null);
        $this->respondJson(['status' => true]);
    }
}
