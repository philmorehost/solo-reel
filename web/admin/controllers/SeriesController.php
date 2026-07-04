<?php

namespace App\Admin\Controllers;

use App\Core\Database;

class SeriesController {
    public function index() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /login");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM series ORDER BY created_at DESC");
        $series = $stmt->fetchAll();

        require __DIR__ . '/../templates/series-list.php';
    }
}
