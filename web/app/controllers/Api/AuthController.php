<?php

namespace App\Controllers\Api;

use App\Core\Database;

class AuthController {

    private function generateJWT(array $payload, string $secret): string {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    private function respondJson($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        die();
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->respondJson(['error' => 'Method Not Allowed'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            return $this->respondJson(['error' => 'Email and password are required'], 400);
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, username, email, password_hash, role, coin_balance, status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return $this->respondJson(['error' => 'Invalid credentials'], 401);
        }

        if ($user['status'] === 'blocked') {
            return $this->respondJson(['error' => 'Account is blocked'], 403);
        }

        // Generate Token
        $secret = getenv('JWT_SECRET') ?: 'default_secret_key_change_in_production';
        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'exp' => time() + (86400 * 30) // 30 days
        ];

        $token = $this->generateJWT($payload, $secret);

        $this->respondJson([
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'],
                'coin_balance' => (float) $user['coin_balance']
            ]
        ]);
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $this->respondJson(['error' => 'Method Not Allowed'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        $displayName = $input['displayName'] ?? '';

        if (empty($email) || empty($username) || empty($password)) {
            return $this->respondJson(['error' => 'Email, username, and password are required'], 400);
        }

        $db = Database::getInstance();

        // Check existing
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            return $this->respondJson(['error' => 'Email or username already exists'], 409);
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID);

        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, display_name) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$username, $email, $hash, $displayName])) {
            return $this->respondJson(['message' => 'Registration successful'], 201);
        }

        return $this->respondJson(['error' => 'Registration failed'], 500);
    }
}
