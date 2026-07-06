<?php

namespace App\Controllers\Api;

use App\Core\Database;

class AuthController extends BaseApiController {

    private function issueToken(array $user): string {
        $secret = getenv('JWT_SECRET') ?: 'default_secret_key_change_in_production';
        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'user',
            'exp' => time() + (86400 * 30) // 30 days
        ];
        return $this->generateJWT($payload, $secret);
    }

    /**
     * Success envelope carrying the token both nested under `data` (Android
     * ApiResponse wrapper) and flat at the top level (iOS flat decoding).
     */
    private function respondAuthSuccess(array $user, string $token, string $message, int $statusCode = 200) {
        $userOut = [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'display_name' => $user['display_name'] ?? null,
            'role' => $user['role'] ?? 'user',
            'coin_balance' => (float)($user['coin_balance'] ?? 0)
        ];
        $this->respondJson([
            'status' => true,
            'message' => $message,
            'token' => $token,
            'user' => $userOut,
            'data' => [
                'token' => $token,
                'user' => $userOut
            ]
        ], $statusCode);
    }

    private function respondError(string $message, int $statusCode) {
        $this->respondJson(['status' => false, 'error' => $message, 'message' => $message], $statusCode);
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respondError('Method Not Allowed', 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            $this->respondError('Email and password are required', 400);
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, username, email, display_name, password_hash, role, coin_balance, status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->respondError('Invalid credentials', 401);
        }

        if ($user['status'] === 'blocked') {
            $this->respondError('Account is blocked', 403);
        }

        $token = $this->issueToken($user);
        $this->respondAuthSuccess($user, $token, 'Login successful');
    }

    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respondError('Method Not Allowed', 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        $displayName = $input['displayName'] ?? ($input['display_name'] ?? '');

        if (empty($email) || empty($username) || empty($password)) {
            $this->respondError('Email, username, and password are required', 400);
        }

        $db = Database::getInstance();

        // Check existing
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $this->respondError('Email or username already exists', 409);
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID);

        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, display_name) VALUES (?, ?, ?, ?)");
        if (!$stmt->execute([$username, $email, $hash, $displayName])) {
            $this->respondError('Registration failed', 500);
        }

        // Auto-login: return a token right away so new users land in their account.
        $stmt = $db->prepare("SELECT id, username, email, display_name, role, coin_balance, status FROM users WHERE id = ?");
        $stmt->execute([$db->lastInsertId()]);
        $user = $stmt->fetch();

        $token = $this->issueToken($user);
        $this->respondAuthSuccess($user, $token, 'Registration successful', 201);
    }

    public function googleLogin() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respondError('Method Not Allowed', 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $displayName = $input['displayName'] ?? ($input['display_name'] ?? '');

        if (empty($email)) {
            $this->respondError('Email is required for Google login', 400);
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, username, email, display_name, password_hash, role, coin_balance, status FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['status'] === 'blocked') {
                $this->respondError('Account is blocked', 403);
            }
        } else {
            // Register new user
            $username = strtolower(str_replace(' ', '', $displayName)) . rand(1000, 9999);
            if (empty($displayName)) {
                $username = 'user' . rand(10000, 99999);
                $displayName = $username;
            }
            // Generate a random password since they use Google
            $randomPassword = bin2hex(random_bytes(10));
            $hash = password_hash($randomPassword, PASSWORD_ARGON2ID);

            $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, display_name) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $hash, $displayName]);

            // Fetch newly created user
            $stmt = $db->prepare("SELECT id, username, email, display_name, password_hash, role, coin_balance, status FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
        }

        $token = $this->issueToken($user);
        $this->respondAuthSuccess($user, $token, 'Login successful');
    }
}
