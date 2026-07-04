<?php

namespace App\Admin\Controllers;

use App\Core\Database;

class BlogCategoryController {
    public function index() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /login");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM blog_categories ORDER BY name ASC");
        $categories = $stmt->fetchAll();

        require __DIR__ . '/../templates/blog-categories-list.php';
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
            $stmt = $db->prepare("INSERT IGNORE INTO blog_categories (name, slug) VALUES (?, ?)");
            $stmt->execute([$name, $slug]);

            \App\Core\Session::setFlash('success', 'Category created successfully.');
            header("Location: /admin/blog-categories");
            die();
        }
    }
}
