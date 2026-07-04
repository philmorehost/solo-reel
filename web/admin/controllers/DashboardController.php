<?php

namespace App\Admin\Controllers;

use App\Core\Database;

class DashboardController {
    public function index() {
        // Simple authentication check for admin
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /login");
            die();
        }

        $db = Database::getInstance();
        $stats = [
            'users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'series' => $db->query("SELECT COUNT(*) FROM series")->fetchColumn(),
            'episodes' => $db->query("SELECT COUNT(*) FROM episodes")->fetchColumn(),
            'revenue' => $db->query("SELECT SUM(amount) FROM payment_transactions WHERE status = 'successful'")->fetchColumn() ?: 0,
        ];

        require __DIR__ . '/../templates/dashboard.php';
    }
}
