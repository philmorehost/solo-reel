<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\Security;

class PasswordController {

    public function showForgot() {
        if (Session::isLoggedIn()) {
            header("Location: /");
            die();
        }
        require __DIR__ . '/../../templates/pages/forgot-password.php';
    }

    public function forgot() {
        Security::validateCsrfPost();

        $email = $_POST['email'] ?? '';
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            $stmt = $db->prepare("UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE id = ?");
            $stmt->execute([$token, $expires, $user['id']]);

            $resetLink = (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/reset-password?token=" . $token;
            \App\Core\Mailer::send($email, "SOLOREEL Password Reset", "Hi " . $user['username'] . ",\n\nClick the link below to reset your password:\n" . $resetLink);
        }

        // Always show success to prevent email enumeration
        Session::setFlash('success', 'If an account with that email exists, a password reset link has been sent.');
        header("Location: /forgot-password");
        die();
    }

    public function showReset() {
        $token = $_GET['token'] ?? '';
        if (!$token) {
            Session::setFlash('error', 'Invalid or missing reset token.');
            header("Location: /forgot-password");
            die();
        }

        require __DIR__ . '/../../templates/pages/reset-password.php';
    }

    public function reset() {
        Security::validateCsrfPost();

        $token = $_POST['token'] ?? '';
        $password = $_POST['password'] ?? '';

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT id FROM users WHERE otp_code = ? AND otp_expires_at > NOW()");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            $hash = password_hash($password, PASSWORD_ARGON2ID);
            $stmt = $db->prepare("UPDATE users SET password_hash = ?, otp_code = NULL, otp_expires_at = NULL WHERE id = ?");
            $stmt->execute([$hash, $user['id']]);

            Session::setFlash('success', 'Password reset successfully. You can now login.');
            header("Location: /login");
            die();
        }

        Session::setFlash('error', 'Invalid or expired reset token.');
        header("Location: /forgot-password");
        die();
    }
}
