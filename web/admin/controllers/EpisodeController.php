<?php

namespace App\Admin\Controllers;

use App\Core\Database;

class EpisodeController {
    public function index() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /login");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->query("SELECT e.*, s.title as series_title FROM episodes e JOIN series s ON e.series_id = s.id ORDER BY e.created_at DESC");
        $episodes = $stmt->fetchAll();

        require __DIR__ . '/../templates/episodes-list.php';
    }
}
