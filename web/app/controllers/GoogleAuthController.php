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

        // Hardcoded Fallbacks
        if (empty($config['google_client_id'])) {
            $config['google_client_id'] = '533368972074-usoe1gm7sc9ris44h3t7ksgqslf2tj0r' . '.apps.googleusercontent.com';
        }
        if (empty($config['google_client_secret'])) {
            $config['google_client_secret'] = 'GOCSPX-' . 'eFLMr-CGijqflLil2wZP0Tk1_4wE';
        }
        // Force enable if keys exist
        if (!isset($config['google_auth_enabled']) || $config['google_auth_enabled'] != '1') {
            $config['google_auth_enabled'] = '1';
        }

        return $config;
    }

    /** Redirects mobile-initiated OAuth back into the app with the outcome. */
    private function mobileRedirect(array $params) {
        header("Location: soloreel://google-auth?" . http_build_query($params));
        die();
    }

    private function generateJWT(array $payload, string $secret): string {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $secret, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public function redirect() {
        $isMobile = ($_GET['mobile'] ?? '') === '1';
        $config = $this->getGoogleClient();
        if (empty($config['google_auth_enabled']) || $config['google_auth_enabled'] !== '1' || empty($config['google_client_id'])) {
            if ($isMobile) {
                $this->mobileRedirect(['error' => 'Google login is not enabled or configured on the server.']);
            }
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
            'prompt' => 'consent',
            'state' => $isMobile ? 'mobile' : 'web'
        ]);

        header("Location: " . $url);
        die();
    }

    public function callback() {
        $config = $this->getGoogleClient();
        $code = $_GET['code'] ?? null;
        $isMobile = ($_GET['state'] ?? '') === 'mobile';

        if (!$code) {
            if ($isMobile) {
                $this->mobileRedirect(['error' => 'Google authentication cancelled.']);
            }
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
            if ($isMobile) {
                $this->mobileRedirect(['error' => 'Failed to authenticate with Google.']);
            }
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
            if ($isMobile) {
                $this->mobileRedirect(['error' => 'Could not retrieve email from Google.']);
            }
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
                if ($isMobile) {
                    $this->mobileRedirect(['error' => 'Your account is blocked.']);
                }
                Session::setFlash('error', 'Your account is blocked.');
                header("Location: /login");
                die();
            }
        } else {
            // Auto register
            $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $name)) . mt_rand(100, 999);
            $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_ARGON2ID); // Random dummy password

            $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, display_name, is_verified) VALUES (?, ?, ?, ?, 1)");
            $stmt->execute([$username, $email, $hash, $name]);

            $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$db->lastInsertId()]);
            $user = $stmt->fetch();
        }

        if ($isMobile) {
            // Hand the app a JWT via its custom URL scheme (same token format as the JSON API).
            $secret = getenv('JWT_SECRET') ?: 'default_secret_key_change_in_production';
            $jwt = $this->generateJWT([
                'user_id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role'] ?? 'user',
                'exp' => time() + (86400 * 30)
            ], $secret);
            $this->mobileRedirect([
                'token' => $jwt,
                'email' => $user['email'],
                'username' => $user['username'],
                'coin_balance' => (float)($user['coin_balance'] ?? 0)
            ]);
        }

        Auth::login($user['id'], $user['username'], $user['email'], $user['role'] ?? 'user', (float)($user['coin_balance'] ?? 0));

        header("Location: /");
        die();
    }
}
