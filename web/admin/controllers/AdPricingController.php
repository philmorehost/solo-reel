<?php

namespace App\Admin\Controllers;

use App\Core\Database;

class AdPricingController {
    private const DURATIONS = ['5', '10', '15'];
    private const PLACEMENTS = ['website', 'app', 'both'];

    private function requireAdmin() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /login");
            die();
        }
    }

    public function index() {
        $this->requireAdmin();
        $db = Database::getInstance();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \App\Core\Security::validateCsrfPost();

            foreach (self::DURATIONS as $duration) {
                foreach (self::PLACEMENTS as $placement) {
                    $field = "price_{$duration}_{$placement}";
                    $price = (float)($_POST[$field] ?? 0);
                    $stmt = $db->prepare("INSERT INTO ad_pricing (duration_seconds, platform_placement, price) VALUES (?, ?, ?)
                                          ON DUPLICATE KEY UPDATE price = VALUES(price)");
                    $stmt->execute([$duration, $placement, $price]);
                }
            }

            \App\Core\Session::setFlash('success', 'Ad pricing updated successfully.');
            header("Location: /admin/ad-pricing");
            die();
        }

        $stmt = $db->query("SELECT * FROM ad_pricing");
        $rows = $stmt->fetchAll();
        $prices = [];
        foreach ($rows as $row) {
            $prices[$row['duration_seconds']][$row['platform_placement']] = (float)$row['price'];
        }

        require __DIR__ . '/../templates/ad-pricing.php';
    }
}
