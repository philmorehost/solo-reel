<?php

namespace App\Admin\Controllers;

use App\Core\Database;

class GenreController {
    public function index() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /login");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM genres ORDER BY name ASC");
        $genres = $stmt->fetchAll();

        require __DIR__ . '/../templates/genres-list.php';
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

            $db = Database::getInstance();
            $stmt = $db->prepare("INSERT INTO genres (name, slug) VALUES (?, ?)");
            try {
                $stmt->execute([$name, $slug]);
                \App\Core\Session::setFlash('success', 'Genre created successfully.');
            } catch (\Exception $e) {
                \App\Core\Session::setFlash('error', 'Genre creation failed. Ensure name is unique.');
            }

            header("Location: /admin/genres");
            die();
        }
    }

    public function delete($id) {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /admin/genres");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM genres WHERE id = ?");
        $stmt->execute([$id]);

        \App\Core\Session::setFlash('success', 'Genre deleted successfully.');
        header("Location: /admin/genres");
        die();
    }
}
