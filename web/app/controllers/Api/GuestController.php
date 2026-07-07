<?php

namespace App\Controllers\Api;

use App\Core\Database;

class GuestController {

    private function respondJson($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        die();
    }

    /** POST /api/v1/guest/init  — create or fetch guest wallet */
    public function init() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respondJson(['error' => 'Method Not Allowed'], 405);
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $guestId = $input['guest_id'] ?? '';
        if (empty($guestId)) {
            $this->respondJson(['status' => false, 'error' => 'guest_id required'], 400);
        }

        $db = Database::getInstance();
        // Create if not exists
        $stmt = $db->prepare("INSERT IGNORE INTO guest_wallets (guest_id, coin_balance) VALUES (?, 0.00)");
        $stmt->execute([$guestId]);

        $stmt = $db->prepare("SELECT coin_balance FROM guest_wallets WHERE guest_id = ?");
        $stmt->execute([$guestId]);
        $wallet = $stmt->fetch();

        $this->respondJson(['status' => true, 'data' => ['guest_id' => $guestId, 'coin_balance' => (float)($wallet['coin_balance'] ?? 0)]]);
    }

    /** GET /api/v1/guest/balance?guest_id=xxx */
    public function balance() {
        $guestId = $_GET['guest_id'] ?? '';
        if (empty($guestId)) {
            $this->respondJson(['status' => false, 'error' => 'guest_id required'], 400);
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT coin_balance FROM guest_wallets WHERE guest_id = ?");
        $stmt->execute([$guestId]);
        $wallet = $stmt->fetch();

        $this->respondJson(['status' => true, 'data' => ['coin_balance' => (float)($wallet['coin_balance'] ?? 0)]]);
    }

    /** GET /api/v1/user/bonus-status  (requires auth) */
    public function bonusStatus() {
        $userId = $this->getUserIdFromToken();
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT bonus_coins, bonus_expires_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        $weeklyAmount = 0;
        $cfgStmt = $db->prepare("SELECT setting_value FROM site_config WHERE setting_key = 'weekly_bonus_coins'");
        $cfgStmt->execute();
        $cfg = $cfgStmt->fetch();
        if ($cfg) $weeklyAmount = (float)$cfg['setting_value'];

        $this->respondJson(['status' => true, 'data' => [
            'bonus_coins'      => (float)($user['bonus_coins'] ?? 0),
            'bonus_expires_at' => $user['bonus_expires_at'] ?? null,
            'weekly_amount'    => $weeklyAmount,
        ]]);
    }

    private function getUserIdFromToken() {
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
                        return $payload['user_id'];
                    }
                }
            }
        }
        $this->respondJson(['error' => 'Unauthorized'], 401);
    }
}
