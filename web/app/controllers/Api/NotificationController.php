<?php

namespace App\Controllers\Api;

use App\Core\Database;

class NotificationController extends BaseApiController {

    /**
     * GET /api/v1/notifications
     * Registered users are identified by their Bearer token; guests pass ?guest_id=.
     * Optional ?unread=1 to fetch only unread notifications.
     */
    public function index() {
        $userId = $this->optionalUserId();
        $guestId = trim((string)($_GET['guest_id'] ?? ''));

        if (!$userId && $guestId === '') {
            $this->respondJson(['status' => false, 'error' => 'Authentication or guest_id required'], 401);
        }

        $db = Database::getInstance();
        $onlyUnread = isset($_GET['unread']) && $_GET['unread'] === '1';

        if ($userId) {
            $sql = "SELECT id, title, body, type, series_id, is_read, created_at FROM notifications WHERE user_id = ?";
            $params = [$userId];
        } else {
            $sql = "SELECT id, title, body, type, series_id, is_read, created_at FROM notifications WHERE guest_id = ?";
            $params = [$guestId];
        }
        if ($onlyUnread) {
            $sql .= " AND is_read = 0";
        }
        $sql .= " ORDER BY created_at DESC LIMIT 50";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $unreadCount = 0;
        foreach ($rows as &$row) {
            $row['id'] = (int)$row['id'];
            $row['series_id'] = $row['series_id'] !== null ? (int)$row['series_id'] : null;
            $row['is_read'] = (bool)$row['is_read'];
            if (!$row['is_read']) $unreadCount++;
        }
        unset($row);

        $this->respondJson(['status' => true, 'data' => $rows, 'unread_count' => $unreadCount]);
    }

    /** POST /api/v1/notifications/{id}/read — mark one notification as read. */
    public function markRead(int $id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respondJson(['status' => false, 'error' => 'Method Not Allowed'], 405);
        }

        $userId = $this->optionalUserId();
        $input = json_decode(file_get_contents('php://input'), true);
        $guestId = trim((string)($input['guest_id'] ?? ($_GET['guest_id'] ?? '')));

        if (!$userId && $guestId === '') {
            $this->respondJson(['status' => false, 'error' => 'Authentication or guest_id required'], 401);
        }

        $db = Database::getInstance();
        if ($userId) {
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $userId]);
        } else {
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND guest_id = ?");
            $stmt->execute([$id, $guestId]);
        }

        $this->respondJson(['status' => true, 'message' => 'Notification marked as read']);
    }
}
