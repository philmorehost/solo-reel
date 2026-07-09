<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\WatchHistory;

class ShelfController {
    public function index(string $slug) {
        $db = Database::getInstance();

        if ($slug === 'top') {
            $shelf = [
                'name' => 'Top Series',
                'description' => 'The most popular series on our platform.',
                'slug' => 'top',
                'emoji' => '🏆'
            ];

            $stmt = $db->query("SELECT s.*, (SELECT COUNT(*) FROM episodes WHERE series_id = s.id) as episode_count FROM series s ORDER BY s.id DESC LIMIT 30");
            $series = $stmt->fetchAll();
            $this->attachResumeSlugs($db, $series);
            require __DIR__ . '/../../templates/pages/shelf.php';
            return;
        }

        $stmt = $db->prepare("SELECT * FROM shelves WHERE slug = ? AND is_active = 1");
        $stmt->execute([$slug]);
        $shelf = $stmt->fetch();

        if (!$shelf) {
            http_response_code(404);
            echo "Shelf not found.";
            return;
        }

        $stmt = $db->prepare("SELECT s.*, (SELECT COUNT(*) FROM episodes WHERE series_id = s.id) as episode_count FROM series s WHERE s.shelf_id = ? ORDER BY s.created_at DESC");
        $stmt->execute([$shelf['id']]);
        $series = $stmt->fetchAll();
        $this->attachResumeSlugs($db, $series);

        require __DIR__ . '/../../templates/pages/shelf.php';
    }

    private function attachResumeSlugs(\PDO $db, array &$series): void {
        $seriesIds = array_map('intval', array_column($series, 'id'));
        $resumeSlugs = WatchHistory::resumeSlugsForSeries($db, $seriesIds, Session::get('user_id'), Session::getGuestId());
        foreach ($series as &$s) { $s['resume_slug'] = $resumeSlugs[(int)$s['id']] ?? null; }
        unset($s);
    }
}
