<?php

namespace App\Core;

class Auth {
    public static function login(int $userId, string $username, string $email, string $role, float $coins) {
        Session::set('user_id', $userId);
        Session::set('user_name', $username);
        Session::set('user_email', $email);
        Session::set('user_role', $role);
        Session::set('user_coin_balance', $coins);
    }

    public static function logout() {
        Session::destroy();
    }

    public static function requireLogin() {
        if (!Session::isLoggedIn()) {
            header("Location: /login");
            die();
        }
    }
}
