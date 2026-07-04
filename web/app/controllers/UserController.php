<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;

class UserController {
    public function profile() {
        \App\Core\Auth::requireLogin();

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([Session::get('user_id')]);
        $user = $stmt->fetch();

        require __DIR__ . '/../../templates/pages/profile.php';
    }

    public function watchHistory() {
        \App\Core\Auth::requireLogin();
        // Placeholder
        echo "Watch History implementation pending.";
    }

    public function favorites() {
        \App\Core\Auth::requireLogin();
        // Placeholder
        echo "Favorites implementation pending.";
    }

    public function addFavorite(int $seriesId) {
        \App\Core\Auth::requireLogin();
        // Placeholder
    }

    public function removeFavorite(int $seriesId) {
        \App\Core\Auth::requireLogin();
        // Placeholder
    }
}
