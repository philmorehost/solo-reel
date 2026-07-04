<?php

namespace App\Middleware;

use App\Core\Session;
use App\Core\Database;

class AuthMiddleware {
    public static function checkVerified() {
        if (Session::isLoggedIn()) {
            // Bypass verify check for admin dashboard logic globally, restrict to specific roles
            $role = Session::get('user_role');
            if ($role === 'super_admin' || $role === 'admin') {
                return;
            }

            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT is_verified FROM users WHERE id = ?");
            $stmt->execute([Session::get('user_id')]);
            $isVerified = $stmt->fetchColumn();

            if ($isVerified == 0) {
                // Ensure they can access the verify-otp route
                $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
                if ($uri !== '/verify-otp' && $uri !== '/logout') {
                    Session::set('pending_verification', true);
                    header("Location: /verify-otp");
                    die();
                }
            }
        }
    }
}
