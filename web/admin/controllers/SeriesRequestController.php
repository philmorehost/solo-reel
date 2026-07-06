<?php

namespace App\Admin\Controllers;

use App\Core\Database;

class SeriesRequestController {

    private function requireAdmin() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'super_admin' && \App\Core\Session::get('user_role') !== 'admin') {
            header("Location: /admin");
            die();
        }
    }

    public function index() {
        $this->requireAdmin();

        $db = Database::getInstance();
        $filter = $_GET['status'] ?? '';

        $sql = "SELECT sr.*, s.title as linked_series_title
                FROM series_requests sr
                LEFT JOIN series s ON sr.series_id = s.id";
        $params = [];
        if ($filter === 'pending' || $filter === 'available') {
            $sql .= " WHERE sr.status = ?";
            $params[] = $filter;
        }
        $sql .= " ORDER BY sr.is_hot DESC, sr.request_count DESC, sr.created_at DESC LIMIT 200";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $requests = $stmt->fetchAll();

        $stmt = $db->query("SELECT id, title FROM series ORDER BY title ASC");
        $allSeries = $stmt->fetchAll();

        require __DIR__ . '/../templates/series-requests.php';
    }

    public function markAvailable($id) {
        $this->requireAdmin();
        \App\Core\Security::validateCsrfPost();

        $db = Database::getInstance();
        $seriesId = (int)($_POST['series_id'] ?? 0);

        $api = new \App\Controllers\Api\SeriesRequestController();
        $ok = $api->doMarkAvailable($db, (int)$id, $seriesId ?: null);

        if ($ok) {
            \App\Core\Session::setFlash('success', 'Request marked as available. Users will be notified by email and in the app.');
        } else {
            \App\Core\Session::setFlash('error', 'Series request not found.');
        }
        header("Location: /admin/series-requests");
        die();
    }
}
