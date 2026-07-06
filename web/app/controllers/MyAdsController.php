<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;

class MyAdsController {
    /** GET /my-ads */
    public function index() {
        \App\Core\Auth::requireLogin();
        $userId = Session::get('user_id');

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM custom_ads WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$userId]);
        $ads = $stmt->fetchAll();

        require __DIR__ . '/../../templates/pages/my-ads.php';
    }
}
