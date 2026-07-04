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

            $fields = [
                'meta_title', 'meta_description', 'twitter_handle', 'ga_id', 'gtm_id',
                'custom_header', 'custom_footer', 'social_facebook', 'social_twitter',
                'social_instagram', 'social_tiktok', 'seo_llms_enabled', 'seo_sitemap_enabled',
                'google_client_id', 'google_client_secret', 'google_auth_enabled'
            ];

            $db->beginTransaction();
            try {
                foreach ($fields as $field) {
                    $val = $_POST[$field] ?? '';
                    // Check if it exists
                    $check = $db->prepare("SELECT id FROM site_config WHERE setting_key = ?");
                    $check->execute([$field]);

                    if ($check->fetch()) {
                        $stmt = $db->prepare("UPDATE site_config SET setting_value = ? WHERE setting_key = ?");
                        $stmt->execute([$val, $field]);
                    } else {
                        $stmt = $db->prepare("INSERT INTO site_config (setting_key, setting_value) VALUES (?, ?)");
                        $stmt->execute([$field, $val]);
                    }
                }
                $db->commit();
                \App\Core\Session::setFlash('success', 'Settings updated successfully.');
            } catch (\Exception $e) {
                $db->rollBack();
                \App\Core\Session::setFlash('error', 'Error updating settings.');
            }

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
