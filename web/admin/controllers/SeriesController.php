<?php

namespace App\Admin\Controllers;

use App\Core\Database;

class SeriesController {
    public function index() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /login");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM series ORDER BY created_at DESC");
        $series = $stmt->fetchAll();

        require __DIR__ . '/../templates/series-list.php';
    }

    public function create() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /login");
            die();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \App\Core\Security::validateCsrfPost();
            $title = $_POST['title'] ?? '';
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
            $synopsis = $_POST['synopsis'] ?? '';
            $genre = $_POST['genre'] ?? '';
            $status = $_POST['status'] ?? 'ongoing';

            $db = Database::getInstance();
            $stmt = $db->prepare("INSERT INTO series (title, slug, synopsis, genre, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $slug, $synopsis, $genre, $status]);

            \App\Core\Session::setFlash('success', 'Series created successfully.');
            header("Location: /admin/series");
            die();
        }

        require __DIR__ . '/../templates/series-form.php';
    }

    public function edit(string $id) {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /login");
            die();
        }

        $db = Database::getInstance();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \App\Core\Security::validateCsrfPost();
            $title = $_POST['title'] ?? '';
            $slug = $_POST['slug'] ?? '';
            $synopsis = $_POST['synopsis'] ?? '';
            $genre = $_POST['genre'] ?? '';
            $status = $_POST['status'] ?? 'ongoing';

            $stmt = $db->prepare("UPDATE series SET title = ?, slug = ?, synopsis = ?, genre = ?, status = ? WHERE id = ?");
            $stmt->execute([$title, $slug, $synopsis, $genre, $status, $id]);

            \App\Core\Session::setFlash('success', 'Series updated successfully.');
            header("Location: /admin/series");
            die();
        }

        $stmt = $db->prepare("SELECT * FROM series WHERE id = ?");
        $stmt->execute([$id]);
        $series = $stmt->fetch();

        require __DIR__ . '/../templates/series-form.php';
    }
}
