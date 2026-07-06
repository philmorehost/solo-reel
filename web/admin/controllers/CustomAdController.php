<?php

namespace App\Admin\Controllers;

use App\Core\Database;

class CustomAdController {
    private function requireAdmin() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /login");
            die();
        }
    }

    public function index() {
        $this->requireAdmin();

        $db = Database::getInstance();
        $ads = $db->query("SELECT * FROM custom_ads ORDER BY sort_order ASC, id DESC")->fetchAll();

        require __DIR__ . '/../templates/custom-ads-list.php';
    }

    public function create() {
        $this->requireAdmin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \App\Core\Security::validateCsrfPost();
            $title = trim($_POST['title'] ?? '');
            $targetUrl = trim($_POST['target_url'] ?? '');
            $placement = $_POST['placement'] ?? 'both';
            if (!in_array($placement, ['banner', 'interstitial', 'both'], true)) $placement = 'both';
            $sortOrder = (int)($_POST['sort_order'] ?? 0);
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            $startsAt = trim($_POST['starts_at'] ?? '') ?: null;
            $expiresAt = trim($_POST['expires_at'] ?? '') ?: null;

            $mediaType = '';
            $mediaUrl = '';
            if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['media_file']['tmp_name'];
                $fileName = basename($_FILES['media_file']['name']);
                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                $imageExts = ['png', 'jpg', 'jpeg', 'webp'];
                $videoExts = ['mp4', 'webm'];

                if (in_array($ext, $imageExts) || in_array($ext, $videoExts)) {
                    $mediaType = in_array($ext, $videoExts) ? 'video' : 'image';
                    $uploadDir = __DIR__ . '/../../assets/uploads';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

                    $destPath = 'assets/uploads/customad_' . time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $fileName);
                    if (move_uploaded_file($tmpName, __DIR__ . '/../../' . $destPath)) {
                        $mediaUrl = '/' . $destPath;
                    }
                } else {
                    \App\Core\Session::setFlash('error', 'Invalid file format. Use png/jpg/webp for images or mp4/webm for video.');
                    header("Location: /admin/custom-ads/create");
                    die();
                }
            }

            if (!$mediaUrl) {
                \App\Core\Session::setFlash('error', 'A media file (image or video) is required.');
                header("Location: /admin/custom-ads/create");
                die();
            }

            $db = Database::getInstance();
            $stmt = $db->prepare("INSERT INTO custom_ads (title, media_type, media_url, target_url, placement, starts_at, expires_at, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $mediaType, $mediaUrl, $targetUrl, $placement, $startsAt, $expiresAt, $isActive, $sortOrder]);

            \App\Core\Session::setFlash('success', 'Ad created successfully.');
            header("Location: /admin/custom-ads");
            die();
        }

        require __DIR__ . '/../templates/custom-ad-form.php';
    }

    public function delete($id) {
        $this->requireAdmin();

        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM custom_ads WHERE id = ?");
        $stmt->execute([$id]);

        \App\Core\Session::setFlash('success', 'Ad deleted successfully.');
        header("Location: /admin/custom-ads");
        die();
    }
}
