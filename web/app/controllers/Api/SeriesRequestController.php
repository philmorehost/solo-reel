<?php

namespace App\Controllers\Api;

use App\Core\Database;

class SeriesRequestController extends BaseApiController {

    /** POST /api/v1/series-requests */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respondJson(['status' => false, 'error' => 'Method Not Allowed'], 405);
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $title    = trim($input['title'] ?? '');
        $desc     = trim($input['description'] ?? '');
        $guestId  = trim($input['guest_id'] ?? '');
        $reqEmail = trim($input['email'] ?? '');

        if (empty($title)) {
            $this->respondJson(['status' => false, 'error' => 'Title is required'], 400);
        }

        $userId = $this->optionalUserId();
        $requesterType = $userId ? 'registered' : 'guest';

        $db = Database::getInstance();
        if ($userId) {
            $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $u = $stmt->fetch();
            if (!empty($u['email'])) $reqEmail = $u['email'];
        }

        // Check if same title already requested
        $stmt = $db->prepare("SELECT id, request_count FROM series_requests WHERE LOWER(title) = LOWER(?) AND status = 'pending' LIMIT 1");
        $stmt->execute([$title]);
        $existing = $stmt->fetch();

        if ($existing) {
            $newCount = (int)$existing['request_count'] + 1;
            $isHot = $newCount >= 3 ? 1 : 0;
            $stmt = $db->prepare("UPDATE series_requests SET request_count = ?, is_hot = ? WHERE id = ?");
            $stmt->execute([$newCount, $isHot, $existing['id']]);
            $this->respondJson(['status' => true, 'message' => 'Your request has been added to the existing request.', 'is_hot' => (bool)$isHot]);
        }

        $stmt = $db->prepare("INSERT INTO series_requests (title, description, requester_email, requester_type, user_id, guest_id, request_count) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$title, $desc, $reqEmail, $requesterType, $userId, $guestId ?: null]);

        $this->respondJson(['status' => true, 'message' => 'Series request submitted successfully!']);
    }

    /** GET /api/v1/series-requests — admin only */
    public function index() {
        $this->requireAdmin();
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM series_requests ORDER BY is_hot DESC, request_count DESC, created_at DESC LIMIT 100");
        $requests = $stmt->fetchAll();
        $this->respondJson(['status' => true, 'data' => $requests]);
    }

    /** PUT /api/v1/series-requests/{id}/mark-available — admin only */
    public function markAvailable(int $id) {
        $this->requireAdmin();
        $db = Database::getInstance();
        $input = json_decode(file_get_contents('php://input'), true);
        $seriesId = $input['series_id'] ?? null;

        $this->doMarkAvailable($db, $id, $seriesId ? (int)$seriesId : null);

        $this->respondJson(['status' => true, 'message' => 'Marked as available and notifications queued.']);
    }

    /**
     * Shared mark-available logic (also called by the admin panel controller):
     * flags the request available + hot, queues emails, and creates in-app
     * notifications for the requesters.
     */
    public function doMarkAvailable(\PDO $db, int $id, ?int $seriesId): bool {
        $stmt = $db->prepare("SELECT * FROM series_requests WHERE id = ?");
        $stmt->execute([$id]);
        $req = $stmt->fetch();
        if (!$req) {
            return false;
        }

        // A fulfilled request is always surfaced as a hot request.
        $stmt = $db->prepare("UPDATE series_requests SET status = 'available', series_id = ?, is_hot = 1, notified = 0 WHERE id = ?");
        $stmt->execute([$seriesId, $id]);

        $baseUrl = $this->baseUrl();
        $notifTitle = "'{$req['title']}' is now available!";
        $notifBody = "Good news! The series you requested, \"{$req['title']}\", is now available on SOLOREEL. Open the app and search for it to start watching.";

        // Notify the registered requester (email + in-app)
        if (!empty($req['user_id'])) {
            $stmt = $db->prepare("SELECT id, email, display_name FROM users WHERE id = ?");
            $stmt->execute([$req['user_id']]);
            $user = $stmt->fetch();
            if ($user && !empty($user['email'])) {
                $subject = "Good news! '{$req['title']}' is now available on SOLOREEL";
                $body = "<p>Hi {$user['display_name']},</p><p>The series you requested, <strong>{$req['title']}</strong>, is now available on SOLOREEL. <a href='{$baseUrl}/search'>Watch it now!</a></p>";
                $db->prepare("INSERT INTO email_queue (to_email, subject, body_html) VALUES (?, ?, ?)")
                   ->execute([$user['email'], $subject, $body]);
            }
            $db->prepare("INSERT INTO notifications (user_id, title, body, type, series_id) VALUES (?, ?, ?, 'series_available', ?)")
               ->execute([$req['user_id'], $notifTitle, $notifBody, $seriesId]);
        }

        // Notify the guest requester in-app
        if (!empty($req['guest_id'])) {
            $db->prepare("INSERT INTO notifications (guest_id, title, body, type, series_id) VALUES (?, ?, ?, 'series_available', ?)")
               ->execute([$req['guest_id'], $notifTitle, $notifBody, $seriesId]);
        }

        // Notify all guest requesters of the same title that left an email
        $stmt = $db->prepare("SELECT DISTINCT requester_email FROM series_requests WHERE LOWER(title) = LOWER(?) AND requester_email != '' AND requester_email IS NOT NULL AND requester_type = 'guest'");
        $stmt->execute([$req['title']]);
        $guests = $stmt->fetchAll();
        foreach ($guests as $guest) {
            $subject = "'{$req['title']}' is now on SOLOREEL!";
            $body = "<p>Hi there! The series you requested, <strong>{$req['title']}</strong>, is now available. <a href='{$baseUrl}/search'>Watch it now!</a></p>";
            $db->prepare("INSERT INTO email_queue (to_email, subject, body_html) VALUES (?, ?, ?)")
               ->execute([$guest['requester_email'], $subject, $body]);
        }

        $db->prepare("UPDATE series_requests SET notified = 1 WHERE id = ?")->execute([$id]);
        return true;
    }
}
