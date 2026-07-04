<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\Auth;

class GoogleAuthController {

    private function getGoogleClient() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT setting_key, setting_value FROM site_config WHERE setting_key IN ('google_client_id', 'google_client_secret', 'google_auth_enabled')");
        $config = [];
        while ($row = $stmt->fetch()) {
            $config[$row['setting_key']] = $row['setting_value'];
        }
        return $config;
    }

    public function redirect() {
        $config = $this->getGoogleClient();
        if (empty($config['google_auth_enabled']) || $config['google_auth_enabled'] !== '1' || empty($config['google_client_id'])) {
            Session::setFlash('error', 'Google login is not enabled or configured.');
            header("Location: /login");
            die();
        }

        $redirectUri = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/auth/google/callback";
        $clientId = $config['google_client_id'];
        $scope = 'email profile';

        $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $scope,
            'access_type' => 'online',
            'prompt' => 'consent'
        ]);

        header("Location: " . $url);
        die();
    }

    public function callback() {
        $config = $this->getGoogleClient();
        $code = $_GET['code'] ?? null;

        if (!$code) {
            Session::setFlash('error', 'Google authentication cancelled.');
            header("Location: /login");
            die();
        }

        $redirectUri = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/auth/google/callback";

        // Exchange code for token
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://oauth2.googleapis.com/token');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'client_id' => $config['google_client_id'],
            'client_secret' => $config['google_client_secret'],
            'redirect_uri' => $redirectUri,
            'grant_type' => 'authorization_code',
            'code' => $code
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        $tokenData = json_decode($response, true);

        if (!isset($tokenData['access_token'])) {
            Session::setFlash('error', 'Failed to authenticate with Google.');
            header("Location: /login");
            die();
        }

        // Get user profile
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://www.googleapis.com/oauth2/v2/userinfo');
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $tokenData['access_token']]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $profileResponse = curl_exec($ch);
        curl_close($ch);

        $profileData = json_decode($profileResponse, true);

        if (!isset($profileData['email'])) {
            Session::setFlash('error', 'Could not retrieve email from Google.');
            header("Location: /login");
            die();
        }

        $email = $profileData['email'];
        $name = $profileData['name'] ?? explode('@', $email)[0];

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['status'] === 'blocked') {
                Session::setFlash('error', 'Your account is blocked.');
                header("Location: /login");
                die();
            }
            Auth::login($user['id'], $user['username'], $user['email'], $user['role'], (float) $user['coin_balance']);
        } else {
            // Auto register
            $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name)) . mt_rand(100, 999);
            $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_ARGON2ID); // Random dummy password

            $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, display_name, is_verified) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$username, $email, $hash, $name]);
            $userId = $db->lastInsertId();

            Auth::login($userId, $username, $email, 'user', 0.0);
        }

        header("Location: /");
        die();
    }
}
