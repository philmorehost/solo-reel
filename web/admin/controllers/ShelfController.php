<?php

namespace App\Admin\Controllers;

use App\Core\Database;

class ShelfController {
    public function index() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /login");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM shelves ORDER BY sort_order ASC");
        $shelves = $stmt->fetchAll();

        require __DIR__ . '/../templates/shelves-list.php';
    }

    public function create() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /login");
            die();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \App\Core\Security::validateCsrfPost();
            $name = $_POST['name'] ?? '';
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
            $emoji = $_POST['emoji'] ?? '';
            $sortOrder = (int)($_POST['sort_order'] ?? 0);

            $db = Database::getInstance();
            $stmt = $db->prepare("INSERT INTO shelves (name, slug, emoji, sort_order) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $emoji, $sortOrder]);

            \App\Core\Session::setFlash('success', 'Shelf created successfully.');
            header("Location: /admin/shelves");
            die();
        }

        require __DIR__ . '/../templates/shelf-form.php';
    }
}
