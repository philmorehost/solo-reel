<?php

namespace App\Admin\Controllers;

use App\Core\Database;

class BlogController {
    public function index() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /login");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->query("SELECT b.*, c.name as category_name FROM blog_posts b LEFT JOIN blog_categories c ON b.category_id = c.id ORDER BY created_at DESC");
        $posts = $stmt->fetchAll();

        require __DIR__ . '/../templates/blog-list.php';
    }

    public function create() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /login");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM blog_categories ORDER BY name ASC");
        $categories = $stmt->fetchAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \App\Core\Security::validateCsrfPost();
            $title = $_POST['title'] ?? '';
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title)));
            $categoryId = $_POST['category_id'] ?? null;
            $excerpt = $_POST['excerpt'] ?? '';
            $body = $_POST['body'] ?? '';
            $authorId = \App\Core\Session::get('user_id');

            $coverImage = '';
            if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['cover_image']['tmp_name'];
                $fileName = basename($_FILES['cover_image']['name']);
                $uploadDir = __DIR__ . '/../../assets/uploads';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

                $destPath = 'assets/uploads/blog_' . time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $fileName);
                if (move_uploaded_file($tmpName, __DIR__ . '/../../' . $destPath)) {
                    $coverImage = '/' . $destPath;
                }
            }

            $stmt = $db->prepare("INSERT INTO blog_posts (category_id, title, slug, excerpt, body, cover_image, author_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$categoryId, $title, $slug, $excerpt, $body, $coverImage, $authorId]);

            \App\Core\Session::setFlash('success', 'Blog post created successfully.');
            header("Location: /admin/blog");
            die();
        }

        require __DIR__ . '/../templates/blog-form.php';
    }

    public function delete($id) {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /admin/blog");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM blog_posts WHERE id = ?");
        $stmt->execute([$id]);

        \App\Core\Session::setFlash('success', 'Blog post deleted successfully.');
        header("Location: /admin/blog");
        die();
    }
}
