<?php

namespace App\Core;

class Security {
    public static function csrfToken(): string {
        Session::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string {
        $token = self::csrfToken();
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
    }

    public static function verifyCsrfToken(string $token): bool {
        Session::start();
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function validateCsrfPost() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!self::verifyCsrfToken($token)) {
                http_response_code(403);
                Session::setFlash('error', 'Session expired. Please try again.');
                header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
                die();
            }
        }
    }
}
