<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\Auth;
use App\Core\Security;

class AuthController {

    public function showLogin() {
        if (Session::isLoggedIn()) {
            header("Location: /");
            die();
        }
        require __DIR__ . '/../../templates/pages/login.php';
    }

    public function login() {
        Security::validateCsrfPost();

        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['status'] === 'blocked') {
                Session::setFlash('error', 'Your account is blocked.');
                header("Location: /login");
                die();
            }
            Auth::login($user['id'], $user['username'], $user['email'], $user['role'], (float) $user['coin_balance']);
            if ($user['role'] === 'admin' || $user['role'] === 'super_admin') {
                header('Location: /admin');
            } else {
                header('Location: /');
            }
            die();
        }

        Session::setFlash('error', 'Invalid email or password.');
        header("Location: /login");
        die();
    }

    public function showRegister() {
        if (Session::isLoggedIn()) {
            header("Location: /");
            die();
        }
        require __DIR__ . '/../../templates/pages/register.php';
    }

    public function register() {
        Security::validateCsrfPost();

        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            Session::setFlash('error', 'Email or username already exists.');
            header("Location: /register");
            die();
        }

        $hash = password_hash($password, PASSWORD_ARGON2ID);
        $otp = str_pad((string)mt_rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', time() + 900); // 15 minutes

        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash, otp_code, otp_expires_at) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$username, $email, $hash, $otp, $expiresAt]);
        $userId = $db->lastInsertId();

        \App\Core\Mailer::send($email, "SOLOREEL Verification Code", "Your verification code is: $otp");

        Auth::login($userId, $username, $email, 'user', 0.0);
        Session::set('pending_verification', true);

        header("Location: /verify-otp");
        die();
    }

    public function showVerifyOtp() {
        if (!Session::get('pending_verification')) {
            header("Location: /");
            die();
        }
        require __DIR__ . '/../../templates/pages/verify-otp.php';
    }

    public function verifyOtp() {
        Security::validateCsrfPost();
        $otp = $_POST['otp'] ?? '';
        $userId = Session::get('user_id');

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT otp_code, otp_expires_at FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user && $user['otp_code'] === $otp && strtotime($user['otp_expires_at']) > time()) {
            $stmt = $db->prepare("UPDATE users SET is_verified = 1, otp_code = NULL, otp_expires_at = NULL WHERE id = ?");
            $stmt->execute([$userId]);
            Session::remove('pending_verification');
            Session::setFlash('success', 'Account verified successfully!');
            header("Location: /");
            die();
        }

        Session::setFlash('error', 'Invalid or expired OTP code.');
        header("Location: /verify-otp");
        die();
    }

    public function logout() {
        Auth::logout();
        header("Location: /");
        die();
    }
}
