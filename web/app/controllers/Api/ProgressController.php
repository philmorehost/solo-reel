<?php

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\WatchHistory;

class ProgressController extends BaseApiController {
    /**
     * Records that an episode is being watched (upsert, idempotent). Called
     * from the web reel feed and both apps whenever an episode becomes the
     * active/playing card, and again when it finishes — this is what powers
     * "resume last-watched episode" and the Continue Watching shelf. No CSRF
     * dance here: unlike coin unlocks, this write is non-financial and safe
     * to fire-and-forget.
     */
    public function recordProgress(int $episodeId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respondJson(['status' => false, 'error' => 'Method Not Allowed'], 405);
        }

        $userId = $this->optionalUserId();
        $input = json_decode(file_get_contents('php://input'), true);
        $guestId = $userId ? null : trim((string)($input['guest_id'] ?? ($_GET['guest_id'] ?? '')));

        if (!$userId && $guestId === '') {
            $this->respondJson(['status' => false, 'error' => 'Missing identity'], 400);
        }

        WatchHistory::record(Database::getInstance(), $episodeId, $userId, $guestId ?: null);
        $this->respondJson(['status' => true]);
    }
}
