<?php

namespace App\Controllers\Api;

use App\Core\Database;

class UserController {

    private function respondJson($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        die();
    }

    private function getUserIdFromToken() {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? '';
        if (preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
            $token = $matches[1];
            $parts = explode('.', $token);
            if (count($parts) === 3) {
                // Cryptographically verify signature
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
        $this->respondJson(['error' => 'Unauthorized or invalid token'], 401);
    }

    public function profile() {
        $userId = $this->getUserIdFromToken();
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, username, email, display_name, coin_balance, is_verified FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) $this->respondJson(['error' => 'User not found'], 404);

        $stmt = $db->prepare("SELECT account_number, bank_name, reference FROM virtual_bank_accounts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user['virtual_account'] = $stmt->fetch() ?: null;

        $this->respondJson(['data' => $user]);
    }
}
