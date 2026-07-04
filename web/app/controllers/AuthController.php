<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\Auth;

class AuthController {

    public function showLogin() {
        if (Session::isLoggedIn()) {
            header("Location: /");
            die();
        }
        require __DIR__ . '/../../templates/pages/login.php';
    }

    public function login() {
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
            header("Location: /");
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
        $stmt = $db->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $hash]);

        \App\Core\Mailer::send($email, "Welcome to SOLOREEL", "Thank you for registering, $username!");
        Auth::login($db->lastInsertId(), $username, $email, 'user', 0.0);
        header("Location: /");
        die();
    }

    public function logout() {
        Auth::logout();
        header("Location: /");
        die();
    }
}
