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

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->respondError('Please enter a valid email address', 400);
        }
        $domain = substr(strrchr($email, "@"), 1);
        if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
            $this->respondError('Email domain does not exist or cannot receive emails', 400);
        }

        $db = Database::getInstance();

        // Check existing
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            $this->respondError('Email or username already exists', 409);
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID);

        $otp = str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', time() + 900); // 15 minutes

        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, display_name, otp_code, otp_expires_at) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt->execute([$username, $email, $hash, $displayName, $otp, $expiresAt])) {
            $this->respondError('Registration failed', 500);
        }
        $userId = $db->lastInsertId();

        \App\Core\Mailer::send($email, "SOLOREEL Verification Code", "Your verification code is: $otp");

        $this->respondJson([
            'status' => true,
            'requires_verification' => true,
            'message' => 'OTP sent to your email. Please verify to complete registration.',
            'user_id' => (int)$userId,
            'email' => $email
        ], 201);
    }

    public function verifyOtp() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respondError('Method Not Allowed', 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $userId = $input['user_id'] ?? 0;
        $otp = $input['otp'] ?? '';

        if (empty($userId) || empty($otp)) {
            $this->respondError('User ID and OTP are required', 400);
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, username, email, display_name, role, coin_balance, status, otp_code, otp_expires_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            $this->respondError('User not found', 404);
        }

        if ($user['otp_code'] !== $otp || strtotime($user['otp_expires_at']) <= time()) {
            $this->respondError('Invalid or expired OTP code', 400);
        }

        // Mark as verified
        $stmt = $db->prepare("UPDATE users SET is_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE id = ?");
        $stmt->execute([$userId]);

        // Migrate Guest Coins
        $guestId = trim((string)($input['guest_id'] ?? ''));
        if ($guestId !== '') {
            $stmt = $db->prepare("SELECT coin_balance FROM guest_wallets WHERE guest_id = ?");
            $stmt->execute([$guestId]);
            $guestWallet = $stmt->fetch();
            if ($guestWallet && (float)$guestWallet['coin_balance'] > 0) {
                $guestCoins = (float)$guestWallet['coin_balance'];
                
                $stmt = $db->prepare("UPDATE users SET coin_balance = coin_balance + ? WHERE id = ?");
                $stmt->execute([$guestCoins, $user['id']]);
                
                $stmt = $db->prepare("INSERT INTO coin_transactions (user_id, type, amount, description) VALUES (?, 'wallet_topup', ?, 'Migrated from guest wallet')");
                $stmt->execute([$user['id'], $guestCoins]);
                
                $stmt = $db->prepare("UPDATE guest_wallets SET coin_balance = 0 WHERE guest_id = ?");
                $stmt->execute([$guestId]);
                
                $user['coin_balance'] = (float)$user['coin_balance'] + $guestCoins;
            }
        }

        $token = $this->issueToken($user);
        $this->respondAuthSuccess($user, $token, 'Account verified and login successful', 200);
    }

    public function resendOtp() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respondError('Method Not Allowed', 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';

        if (empty($email)) {
            $this->respondError('Email is required', 400);
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, email, is_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $this->respondError('User not found', 404);
        }

        if ($user['is_verified'] == 1) {
            $this->respondError('Account is already verified', 400);
        }

        $otp = str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', time() + 900); // 15 minutes

        $stmt = $db->prepare("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?");
        $stmt->execute([$otp, $expiresAt, $user['id']]);

        \App\Core\Mailer::send($email, "SOLOREEL Verification Code", "Your verification code is: $otp");

        $this->respondJson([
            'status' => true,
            'message' => 'OTP sent to your email.'
        ]);
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

        // Migrate Guest Coins
        $guestId = trim((string)($input['guest_id'] ?? ''));
        if ($guestId !== '') {
            $stmt = $db->prepare("SELECT coin_balance FROM guest_wallets WHERE guest_id = ?");
            $stmt->execute([$guestId]);
            $guestWallet = $stmt->fetch();
            if ($guestWallet && (float)$guestWallet['coin_balance'] > 0) {
                $guestCoins = (float)$guestWallet['coin_balance'];
                
                // Add to user balance
                $stmt = $db->prepare("UPDATE users SET coin_balance = coin_balance + ? WHERE id = ?");
                $stmt->execute([$guestCoins, $user['id']]);
                
                // Log transaction
                $stmt = $db->prepare("INSERT INTO coin_transactions (user_id, type, amount, description) VALUES (?, 'wallet_topup', ?, 'Migrated from guest wallet')");
                $stmt->execute([$user['id'], $guestCoins]);
                
                // Clear guest wallet to prevent double-spending
                $stmt = $db->prepare("UPDATE guest_wallets SET coin_balance = 0 WHERE guest_id = ?");
                $stmt->execute([$guestId]);
                
                $user['coin_balance'] = (float)$user['coin_balance'] + $guestCoins;
            }
        }

        $this->respondAuthSuccess($user, $token, 'Login successful');
    }

    public function googleConfig() {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->respondError('Method Not Allowed', 405);
        }
        $db = Database::getInstance();
        $stmt = $db->query("SELECT setting_value FROM site_config WHERE setting_key = 'google_client_id'");
        $config = $stmt->fetch();
        $clientId = $config['setting_value'] ?? '';

        $this->respondJson([
            'status' => true,
            'client_id' => $clientId
        ]);
    }

    /**
     * GET /api/v1/ads-config — AdMob IDs for the mobile apps. Any field left
     * blank in Admin -> Settings -> Ads falls back to Google's public test ID
     * so ads keep working (labeled "Test Ad") until the admin configures real ones.
     */
    public function adsConfig() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT setting_key, setting_value FROM site_config WHERE setting_key IN (
            'admob_android_app_id', 'admob_android_rewarded_unit_id',
            'admob_ios_app_id', 'admob_ios_rewarded_unit_id'
        )");
        $config = [];
        while ($row = $stmt->fetch()) { $config[$row['setting_key']] = $row['setting_value']; }

        $testIds = [
            'admob_android_app_id'           => 'ca-app-pub-3940256099942544~3347511713',
            'admob_android_rewarded_unit_id' => 'ca-app-pub-3940256099942544/5224354917',
            'admob_ios_app_id'                => 'ca-app-pub-3940256099942544~1458002511',
            'admob_ios_rewarded_unit_id'      => 'ca-app-pub-3940256099942544/1712485313',
        ];

        $data = [];
        foreach ($testIds as $key => $testId) {
            $data[$key] = !empty($config[$key]) ? $config[$key] : $testId;
        }

        $this->respondJson(['status' => true, 'data' => $data] + $data);
    }
}
