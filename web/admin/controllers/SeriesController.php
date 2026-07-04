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

                $stmt = $db->query("SELECT id, name FROM genres ORDER BY name ASC");
        $genres = $stmt->fetchAll();
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

            $stmt_sel = $db->prepare("SELECT cover_image FROM series WHERE id = ?");
            $stmt_sel->execute([$id]);
            $series = $stmt_sel->fetch();

                        $coverImage = $series['cover_image'] ?? null;
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['cover_image']['tmp_name'];
                $fileName = basename($_FILES['cover_image']['name']);
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                    $uploadDir = __DIR__ . '/../../assets/uploads';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);
                    $destPath = 'assets/uploads/cover_' . time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $fileName);
                    if (move_uploaded_file($tmpName, __DIR__ . '/../../' . $destPath)) {
                        $coverImage = '/' . $destPath;
                    }
                }
            }

            $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
            $heroImage = $series['hero_image'] ?? null;
            if (isset($_FILES['hero_image']) && $_FILES['hero_image']['error'] === UPLOAD_ERR_OK) {
                $tmpNameHero = $_FILES['hero_image']['tmp_name'];
                $fileNameHero = basename($_FILES['hero_image']['name']);
                $extHero = strtolower(pathinfo($fileNameHero, PATHINFO_EXTENSION));
                if (in_array($extHero, ['png', 'jpg', 'jpeg', 'webp'])) {
                    $destPathHero = 'assets/uploads/hero_' . time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $fileNameHero);
                    if (move_uploaded_file($tmpNameHero, __DIR__ . '/../../' . $destPathHero)) {
                        $heroImage = '/' . $destPathHero;
                    }
                }
            }
            $stmt = $db->prepare("UPDATE series SET title = ?, slug = ?, synopsis = ?, genre = ?, status = ?, cover_image = ?, hero_image = ?, is_featured = ? WHERE id = ?");
            $stmt->execute([$title, $slug, $synopsis, $genre, $status, $coverImage, $heroImage, $isFeatured, $id]);

            \App\Core\Session::setFlash('success', 'Series updated successfully.');
            header("Location: /admin/series");
            die();
        }

        $stmt = $db->prepare("SELECT * FROM series WHERE id = ?");
        $stmt->execute([$id]);
        $series = $stmt->fetch();

                $stmt = $db->query("SELECT id, name FROM genres ORDER BY name ASC");
        $genres = $stmt->fetchAll();
        require __DIR__ . '/../templates/series-form.php';
    }
}
