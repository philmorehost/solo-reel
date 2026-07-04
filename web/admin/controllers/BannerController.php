<?php

namespace App\Admin\Controllers;

use App\Core\Database;

class BannerController {
    public function index() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /login");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM banners ORDER BY sort_order ASC");
        $banners = $stmt->fetchAll();

        require __DIR__ . '/../templates/banners-list.php';
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
            $subtitle = $_POST['subtitle'] ?? '';
            $linkUrl = $_POST['link_url'] ?? '';
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $isActive = isset($_POST['is_active']) ? 1 : 0;

                        $imageUrl = '';
            if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['image_file']['tmp_name'];
                $fileName = basename($_FILES['image_file']['name']);
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp'])) {
                    $uploadDir = __DIR__ . '/../../assets/uploads';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

                    $destPath = 'assets/uploads/banner_' . time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $fileName);
                    if (move_uploaded_file($tmpName, __DIR__ . '/../../' . $destPath)) {
                        $imageUrl = '/' . $destPath;
                    }
                } else {
                    \App\Core\Session::setFlash('error', 'Invalid image format.');
                    header("Location: /admin/banners/create");
                    die();
                }
            }

            $db = Database::getInstance();
            $stmt = $db->prepare("INSERT INTO banners (title, subtitle, image_url, link_url, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $subtitle, $imageUrl, $linkUrl, $sortOrder, $isActive]);

            \App\Core\Session::setFlash('success', 'Banner created successfully.');
            header("Location: /admin/banners");
            die();
        }

        require __DIR__ . '/../templates/banner-form.php';
    }

    public function delete($id) {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'super_admin' && \App\Core\Session::get('user_role') !== 'admin') {
            header("Location: /admin/banners");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM banners WHERE id = ?");
        $stmt->execute([$id]);

        \App\Core\Session::setFlash('success', 'Banner deleted successfully.');
        header("Location: /admin/banners");
        die();
    }
}
