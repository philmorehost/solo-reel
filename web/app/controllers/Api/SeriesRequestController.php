<?php

namespace App\Controllers\Api;

use App\Core\Database;

class SeriesRequestController {

    private function respondJson($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        die();
    }

    private function getUserIdFromToken(?string &$userEmail = null): ?int {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? '';
        if (preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
            $token = $matches[1];
            $parts = explode('.', $token);
            if (count($parts) === 3) {
                $secret = getenv('JWT_SECRET') ?: 'default_secret_key_change_in_production';
                $signature = hash_hmac('sha256', $parts[0] . "." . $parts[1], $secret, true);
                $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
                if (hash_equals($expectedSignature, $parts[2])) {
                    $payload = json_decode(base64_decode($parts[1]), true);
                    if (isset($payload['user_id']) && $payload['exp'] > time()) {
                        $db = Database::getInstance();
                        $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
                        $stmt->execute([$payload['user_id']]);
                        $u = $stmt->fetch();
                        $userEmail = $u['email'] ?? null;
                        return $payload['user_id'];
                    }
                }
            }
        }
        return null;
    }

    /** POST /api/v1/series-requests */
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respondJson(['error' => 'Method Not Allowed'], 405);
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $title    = trim($input['title'] ?? '');
        $desc     = trim($input['description'] ?? '');
        $guestId  = trim($input['guest_id'] ?? '');
        $reqEmail = trim($input['email'] ?? '');

        if (empty($title)) {
            $this->respondJson(['status' => false, 'error' => 'Title is required'], 400);
        }

        $userEmail = null;
        $userId = $this->getUserIdFromToken($userEmail);
        $requesterType = $userId ? 'registered' : 'guest';
        if ($userEmail) $reqEmail = $userEmail;

        $db = Database::getInstance();

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
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM series_requests ORDER BY is_hot DESC, request_count DESC, created_at DESC LIMIT 100");
        $requests = $stmt->fetchAll();
        $this->respondJson(['status' => true, 'data' => $requests]);
    }

    /** PUT /api/v1/series-requests/{id}/mark-available — admin only */
    public function markAvailable(int $id) {
        $db = Database::getInstance();
        $input = json_decode(file_get_contents('php://input'), true);
        $seriesId = $input['series_id'] ?? null;

        $stmt = $db->prepare("UPDATE series_requests SET status = 'available', series_id = ?, notified = 0 WHERE id = ?");
        $stmt->execute([$seriesId, $id]);

        // Queue email notifications for all unique requesters
        $stmt = $db->prepare("SELECT title FROM series_requests WHERE id = ?");
        $stmt->execute([$id]);
        $req = $stmt->fetch();

        // Notify registered users
        $stmt = $db->prepare("SELECT DISTINCT u.email, u.display_name FROM series_requests sr LEFT JOIN users u ON sr.user_id = u.id WHERE sr.id = ? AND u.email IS NOT NULL");
        $stmt->execute([$id]);
        $users = $stmt->fetchAll();

        foreach ($users as $user) {
            $subject = "Good news! '{$req['title']}' is now available on SOLOREEL";
            $body = "<p>Hi {$user['display_name']},</p><p>The series you requested, <strong>{$req['title']}</strong>, is now available on SOLOREEL. <a href='https://soloshort.pmhserver.name.ng/search'>Watch it now!</a></p>";
            $insertStmt = $db->prepare("INSERT INTO email_queue (to_email, subject, body_html) VALUES (?, ?, ?)");
            $insertStmt->execute([$user['email'], $subject, $body]);
        }

        // Notify guest users with email
        $stmt = $db->prepare("SELECT DISTINCT requester_email FROM series_requests WHERE LOWER(title) = LOWER(?) AND requester_email != '' AND requester_type = 'guest'");
        $stmt->execute([$req['title']]);
        $guests = $stmt->fetchAll();
        foreach ($guests as $guest) {
            $subject = "'{$req['title']}' is now on SOLOREEL!";
            $body = "<p>Hi there! The series you requested, <strong>{$req['title']}</strong>, is now available. <a href='https://soloshort.pmhserver.name.ng/search'>Watch it now!</a></p>";
            $insertStmt = $db->prepare("INSERT INTO email_queue (to_email, subject, body_html) VALUES (?, ?, ?)");
            $insertStmt->execute([$guest['requester_email'], $subject, $body]);
        }

        $stmt = $db->prepare("UPDATE series_requests SET notified = 1 WHERE id = ?");
        $stmt->execute([$id]);

        $this->respondJson(['status' => true, 'message' => 'Marked as available and notifications queued.']);
    }
}
