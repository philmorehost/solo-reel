<?php

namespace App\Admin\Controllers;

use App\Core\Database;

class SettingsController {
    public function index() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /login");
            die();
        }

        $db = Database::getInstance();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \App\Core\Security::validateCsrfPost();
            $metaTitle = $_POST['meta_title'] ?? '';
            $metaDesc = $_POST['meta_description'] ?? '';

            $stmt = $db->prepare("UPDATE site_config SET setting_value = ? WHERE setting_key = ?");
            $stmt->execute([$metaTitle, 'meta_title']);
            $stmt->execute([$metaDesc, 'meta_description']);

            \App\Core\Session::setFlash('success', 'Settings updated successfully.');
            header("Location: /admin/settings");
            die();
        }

        $stmt = $db->query("SELECT setting_key, setting_value FROM site_config");
        $configRows = $stmt->fetchAll();
        $settings = [];
        foreach($configRows as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        require __DIR__ . '/../templates/settings.php';
    }
}
