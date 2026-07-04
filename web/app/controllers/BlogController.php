<?php

namespace App\Controllers;

use App\Core\Database;

class BlogController {
    public function index() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT b.*, u.username as author_name, c.name as category_name FROM blog_posts b LEFT JOIN users u ON b.author_id = u.id LEFT JOIN blog_categories c ON b.category_id = c.id ORDER BY b.created_at DESC");
        $posts = $stmt->fetchAll();

        require __DIR__ . '/../../templates/pages/blog-index.php';
    }

    public function detail(string $slug) {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT b.*, u.username as author_name, c.name as category_name FROM blog_posts b LEFT JOIN users u ON b.author_id = u.id LEFT JOIN blog_categories c ON b.category_id = c.id WHERE b.slug = ?");
        $stmt->execute([$slug]);
        $post = $stmt->fetch();

        if (!$post) {
            http_response_code(404);
            echo "Post not found.";
            return;
        }

        require __DIR__ . '/../../templates/pages/blog-detail.php';
    }
}
