<?php

namespace App\Admin\Controllers;

use App\Core\Database;

class CoinController {
    public function index() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'super_admin' && \App\Core\Session::get('user_role') !== 'admin') {
            header("Location: /admin");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM coin_packages ORDER BY sort_order ASC");
        $packages = $stmt->fetchAll();

        require __DIR__ . '/../templates/coins-packages.php';
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
            $coins = $_POST['coins'] ?? 0;
            $price = $_POST['price'] ?? 0.00;
            $currency = $_POST['currency'] ?? 'NGN';
            $sortOrder = $_POST['sort_order'] ?? 0;
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            $db = Database::getInstance();
            $stmt = $db->prepare("INSERT INTO coin_packages (name, coins, price, currency, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$name, $coins, $price, $currency, $sortOrder, $isActive]);

            \App\Core\Session::setFlash('success', 'Coin package created successfully.');
            header("Location: /admin/coins");
            die();
        }

        require __DIR__ . '/../templates/coin-form.php';
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
            $coins = $_POST['coins'] ?? 0;
            $price = $_POST['price'] ?? 0.00;
            $currency = $_POST['currency'] ?? 'NGN';
            $sortOrder = $_POST['sort_order'] ?? 0;
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            $stmt = $db->prepare("UPDATE coin_packages SET name=?, coins=?, price=?, currency=?, sort_order=?, is_active=? WHERE id=?");
            $stmt->execute([$name, $coins, $price, $currency, $sortOrder, $isActive, $id]);

            \App\Core\Session::setFlash('success', 'Coin package updated successfully.');
            header("Location: /admin/coins");
            die();
        }

        $stmt = $db->prepare("SELECT * FROM coin_packages WHERE id = ?");
        $stmt->execute([$id]);
        $package = $stmt->fetch();

        require __DIR__ . '/../templates/coin-form.php';
    }

    public function delete($id) {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /admin/coins");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM coin_packages WHERE id = ?");
        $stmt->execute([$id]);

        \App\Core\Session::setFlash('success', 'Coin package deleted successfully.');
        header("Location: /admin/coins");
        die();
    }
}
