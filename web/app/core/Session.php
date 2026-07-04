<?php

namespace App\Core;

class Session {
    public static function start() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function set(string $key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function remove(string $key) {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function destroy() {
        self::start();
        session_destroy();
    }

    public static function isLoggedIn(): bool {
        return self::get('user_id') !== null;
    }

    public static function setFlash(string $key, string $message) {
        self::start();
        $_SESSION['_flash'][$key] = $message;
    }

    public static function getFlash(string $key) {
        self::start();
        if (isset($_SESSION['_flash'][$key])) {
            $message = $_SESSION['_flash'][$key];
            unset($_SESSION['_flash'][$key]);
            return $message;
        }
        return null;
    }
}
