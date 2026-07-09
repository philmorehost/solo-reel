<?php

namespace App\Admin\Controllers;

use App\Core\Database;

class VipPlanController {
    public function index() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'super_admin' && \App\Core\Session::get('user_role') !== 'admin') {
            header("Location: /admin");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM vip_plans ORDER BY sort_order ASC");
        $plans = $stmt->fetchAll();

        require __DIR__ . '/../templates/vip-plans.php';
    }

    public function create() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'super_admin' && \App\Core\Session::get('user_role') !== 'admin') {
            header("Location: /admin");
            die();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \App\Core\Security::validateCsrfPost();
            $name = $_POST['name'] ?? '';
            $price = $_POST['price'] ?? 0.00;
            $currency = $_POST['currency'] ?? 'NGN';
            $durationDays = $_POST['duration_days'] ?? 30;
            $perkFreeUnlocks = isset($_POST['perk_free_unlocks']) ? 1 : 0;
            $perkAdFree = isset($_POST['perk_ad_free']) ? 1 : 0;
            $sortOrder = $_POST['sort_order'] ?? 0;
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            $db = Database::getInstance();
            $stmt = $db->prepare("INSERT INTO vip_plans (name, price, currency, duration_days, perk_free_unlocks, perk_ad_free, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $price, $currency, $durationDays, $perkFreeUnlocks, $perkAdFree, $sortOrder, $isActive]);

            \App\Core\Session::setFlash('success', 'VIP plan created successfully.');
            header("Location: /admin/vip-plans");
            die();
        }

        require __DIR__ . '/../templates/vip-plan-form.php';
    }

    public function edit($id) {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'super_admin' && \App\Core\Session::get('user_role') !== 'admin') {
            header("Location: /admin");
            die();
        }

        $db = Database::getInstance();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \App\Core\Security::validateCsrfPost();
            $name = $_POST['name'] ?? '';
            $price = $_POST['price'] ?? 0.00;
            $currency = $_POST['currency'] ?? 'NGN';
            $durationDays = $_POST['duration_days'] ?? 30;
            $perkFreeUnlocks = isset($_POST['perk_free_unlocks']) ? 1 : 0;
            $perkAdFree = isset($_POST['perk_ad_free']) ? 1 : 0;
            $sortOrder = $_POST['sort_order'] ?? 0;
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            $stmt = $db->prepare("UPDATE vip_plans SET name=?, price=?, currency=?, duration_days=?, perk_free_unlocks=?, perk_ad_free=?, sort_order=?, is_active=? WHERE id=?");
            $stmt->execute([$name, $price, $currency, $durationDays, $perkFreeUnlocks, $perkAdFree, $sortOrder, $isActive, $id]);

            \App\Core\Session::setFlash('success', 'VIP plan updated successfully.');
            header("Location: /admin/vip-plans");
            die();
        }

        $stmt = $db->prepare("SELECT * FROM vip_plans WHERE id = ?");
        $stmt->execute([$id]);
        $plan = $stmt->fetch();

        require __DIR__ . '/../templates/vip-plan-form.php';
    }

    public function delete($id) {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /admin/vip-plans");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM vip_plans WHERE id = ?");
        $stmt->execute([$id]);

        \App\Core\Session::setFlash('success', 'VIP plan deleted successfully.');
        header("Location: /admin/vip-plans");
        die();
    }
}
