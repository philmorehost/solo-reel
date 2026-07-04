<?php

namespace App\Admin\Controllers;

use App\Core\Database;

class ReportController {
    public function index() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /login");
            die();
        }

        $db = Database::getInstance();

        // Detailed Episode Traffic
        $stmt = $db->query("
            SELECT e.title as episode_title, s.title as series_title, e.episode_number, COUNT(wh.id) as total_views, SUM(wh.progress_seconds) as total_watch_time
            FROM episodes e
            JOIN series s ON e.series_id = s.id
            LEFT JOIN watch_history wh ON wh.episode_id = e.id
            GROUP BY e.id
            ORDER BY total_views DESC
            LIMIT 50
        ");
        $episodeStats = $stmt->fetchAll();

        // Recent Coin Transactions
        $stmt = $db->query("
            SELECT ct.*, u.username
            FROM coin_transactions ct
            JOIN users u ON ct.user_id = u.id
            ORDER BY ct.created_at DESC
            LIMIT 50
        ");
        $transactions = $stmt->fetchAll();

        require __DIR__ . '/../templates/reports.php';
    }
}
