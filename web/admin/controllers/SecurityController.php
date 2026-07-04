<?php

namespace App\Admin\Controllers;

use App\Core\Database;

class SecurityController {
    public function index() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /admin");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM ip_whitelist ORDER BY created_at DESC");
        $whitelist = $stmt->fetchAll();

        $stmt = $db->query("SELECT * FROM ip_blacklist ORDER BY created_at DESC");
        $blacklist = $stmt->fetchAll();

        $stmt = $db->query("SELECT * FROM country_rules ORDER BY country_code ASC");
        $countryRules = $stmt->fetchAll();

        require __DIR__ . '/../templates/security.php';
    }

    public function addIp() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /admin");
            die();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \App\Core\Security::validateCsrfPost();
            $ip = trim($_POST['ip_address'] ?? '');
            $desc = trim($_POST['description'] ?? '');
            $type = $_POST['type'] ?? 'whitelist';

            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                \App\Core\Session::setFlash('error', 'Invalid IP Address format.');
                header("Location: /admin/security");
                die();
            }

            $db = Database::getInstance();
            $table = $type === 'whitelist' ? 'ip_whitelist' : 'ip_blacklist';

            try {
                $stmt = $db->prepare("INSERT INTO $table (ip_address, description) VALUES (?, ?)");
                $stmt->execute([$ip, $desc]);
                \App\Core\Session::setFlash('success', 'IP successfully added to ' . $table);
            } catch (\Exception $e) {
                \App\Core\Session::setFlash('error', 'IP might already exist or invalid input.');
            }

            header("Location: /admin/security");
            die();
        }
    }

    public function removeIp() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /admin");
            die();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \App\Core\Security::validateCsrfPost();
            $id = $_POST['id'] ?? 0;
            $type = $_POST['type'] ?? 'whitelist';

            $db = Database::getInstance();
            $table = $type === 'whitelist' ? 'ip_whitelist' : 'ip_blacklist';

            $stmt = $db->prepare("DELETE FROM $table WHERE id = ?");
            $stmt->execute([$id]);

            \App\Core\Session::setFlash('success', 'IP removed from ' . $table);
            header("Location: /admin/security");
            die();
        }
    }
}
