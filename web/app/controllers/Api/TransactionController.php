<?php

namespace App\Controllers\Api;

use App\Core\Database;

class TransactionController {

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

    public function unlock(int $episodeId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respondJson(['error' => 'Method Not Allowed'], 405);
        }

        $userId = $this->getUserIdFromToken();
        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT coin_cost FROM episodes WHERE id = ? FOR UPDATE");
            $stmt->execute([$episodeId]);
            $episode = $stmt->fetch();

            if (!$episode) {
                $db->rollBack();
                $this->respondJson(['error' => 'Episode not found'], 404);
            }

            $stmt = $db->prepare("SELECT coin_balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if ((float)$user['coin_balance'] < (float)$episode['coin_cost']) {
                $db->rollBack();
                $this->respondJson(['error' => 'Insufficient coins'], 400);
            }

            $newBalance = (float)$user['coin_balance'] - (float)$episode['coin_cost'];
            $stmt = $db->prepare("UPDATE users SET coin_balance = ? WHERE id = ?");
            $stmt->execute([$newBalance, $userId]);

            $stmt = $db->prepare("INSERT IGNORE INTO user_unlocked_episodes (user_id, episode_id) VALUES (?, ?)");
            $stmt->execute([$userId, $episodeId]);

            $stmt = $db->prepare("INSERT INTO coin_transactions (user_id, type, amount, description) VALUES (?, 'unlock', ?, ?)");
            $stmt->execute([$userId, -$episode['coin_cost'], 'Unlocked episode ' . $episodeId]);

            $db->commit();
            $this->respondJson(['message' => 'Episode unlocked successfully', 'coin_balance' => $newBalance]);
        } catch (\Exception $e) {
            $db->rollBack();
            $this->respondJson(['error' => 'Transaction failed: ' . $e->getMessage()], 500);
        }
    }

    public function purchase() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respondJson(['error' => 'Method Not Allowed'], 405);
        }

        $userId = $this->getUserIdFromToken();
        $input = json_decode(file_get_contents('php://input'), true);
        $packageId = $input['package_id'] ?? 0;

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM coin_packages WHERE id = ? AND is_active = 1");
        $stmt->execute([$packageId]);
        $package = $stmt->fetch();

        if (!$package) {
            $this->respondJson(['error' => 'Package not available'], 400);
        }

        $stmt = $db->query("SELECT * FROM payment_settings LIMIT 1");
        $settings = $stmt->fetch();

        if (!$settings || empty($settings['payhub_public_key'])) {
             $this->respondJson(['error' => 'Payment gateway is not configured.'], 500);
        }

        $reference = 'trx_' . uniqid() . '_' . time();
        $amount = $package['price'];

        $stmt = $db->prepare("INSERT INTO payment_transactions (user_id, package_id, reference, amount, currency, status, coins_awarded) VALUES (?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->execute([$userId, $package['id'], $reference, $amount, $package['currency'], $package['coins']]);

        $this->respondJson([
            'message' => 'Payment initialized',
            'public_key' => $settings['payhub_public_key'],
            'amount' => (float)$amount * 100, // Kobo
            'reference' => $reference,
            'email' => 'user_' . $userId . '@soloreel.tv' // In a real app fetch their email
        ]);
    }
}
