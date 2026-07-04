<?php

namespace App\Admin\Controllers;

use App\Core\Database;

class DashboardController {
    public function index() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /login");
            die();
        }

        $db = Database::getInstance();
        $stats = [
            'users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'new_users_this_month' => $db->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
            'series' => $db->query("SELECT COUNT(*) FROM series")->fetchColumn(),
            'ongoing_series' => $db->query("SELECT COUNT(*) FROM series WHERE status = 'ongoing'")->fetchColumn(),
            'episodes' => $db->query("SELECT COUNT(*) FROM episodes")->fetchColumn(),
            'revenue' => $db->query("SELECT COALESCE(SUM(amount), 0) FROM payment_transactions WHERE status = 'successful'")->fetchColumn(),
            'transactions_today' => $db->query("SELECT COUNT(*) FROM payment_transactions WHERE status = 'successful' AND DATE(created_at) = CURDATE()")->fetchColumn(),
            'pending_videos' => $db->query("SELECT COUNT(*) FROM video_queue WHERE status IN ('pending','processing')")->fetchColumn() ?: 0,
        ];

        $totalSec = $db->query("SELECT COALESCE(SUM(video_duration_seconds), 0) FROM episodes")->fetchColumn();
        $h = floor($totalSec / 3600);
        $m = floor(($totalSec % 3600) / 60);
        $stats['total_duration_formatted'] = $h . 'h ' . $m . 'm';

        $maintenance = $db->query("SELECT maintenance_mode FROM site_config WHERE id = 1")->fetchColumn();
        $paymentConfigured = (bool)$db->query("SELECT payhub_secret_key FROM payment_settings WHERE payhub_secret_key IS NOT NULL AND payhub_secret_key != '' LIMIT 1")->fetchColumn();

        require __DIR__ . '/../templates/dashboard.php';
    }
}
