<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\WatchHistory;

class SeriesController {
    public function detail(string $slug) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM series WHERE slug = ?");
        $stmt->execute([$slug]);
        $series = $stmt->fetch();

        if (!$series) {
            http_response_code(404);
            echo "Series not found.";
            return;
        }

        $stmt = $db->prepare("SELECT * FROM episodes WHERE series_id = ? ORDER BY episode_number ASC");
        $stmt->execute([$series['id']]);
        $episodes = $stmt->fetchAll();

        $userId = Session::get('user_id');
        $guestId = Session::getGuestId();
        $resumeSlug = WatchHistory::resumeSlugFor($db, $series['id'], $userId, $guestId);
        $hasHistory = $resumeSlug !== null;
        if (!$hasHistory && !empty($episodes)) {
            $resumeSlug = $episodes[0]['slug'];
        }

        require __DIR__ . '/../../templates/pages/series-detail.php';
    }
}
