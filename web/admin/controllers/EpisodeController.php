<?php

namespace App\Admin\Controllers;

use App\Core\Database;

class EpisodeController {
    public function index() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /login");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->query("SELECT e.*, s.title as series_title FROM episodes e JOIN series s ON e.series_id = s.id ORDER BY e.created_at DESC");
        $episodes = $stmt->fetchAll();

        require __DIR__ . '/../templates/episodes-list.php';
    }

    public function create() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'admin' && \App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /login");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->query("SELECT id, title FROM series ORDER BY title ASC");
        $seriesList = $stmt->fetchAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \App\Core\Security::validateCsrfPost();
            $seriesId = $_POST['series_id'] ?? 0;
            $title = $_POST['title'] ?? '';
            $episodeNumber = $_POST['episode_number'] ?? 1;
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title))) . '-' . time();
            $isFree = isset($_POST['is_free']) ? 1 : 0;
            $coinCost = $_POST['coin_cost'] ?? 0.00;

            // Handle file upload
            if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['video_file']['tmp_name'];
                $fileName = basename($_FILES['video_file']['name']);

                // Backend security validation: Verify extension and MIME type
                $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $tmpName);
                finfo_close($finfo);

                if ($extension !== 'mp4' || $mimeType !== 'video/mp4') {
                    \App\Core\Session::setFlash('error', 'Invalid file type. Only MP4 videos are allowed.');
                    header("Location: /admin/episodes/create");
                    die();
                }

                // Ensure storage/temp exists
                $tempDir = __DIR__ . '/../../storage/temp';
                if (!is_dir($tempDir)) {
                    mkdir($tempDir, 0775, true);
                }

                // Obfuscate the filename for extra safety and to prevent collisions
                $destPath = 'storage/temp/' . bin2hex(random_bytes(16)) . '.mp4';
                $fullDestPath = __DIR__ . '/../../' . $destPath;

                if (move_uploaded_file($tmpName, $fullDestPath)) {
                    $db->beginTransaction();
                    try {
                        $stmt = $db->prepare("INSERT INTO episodes (series_id, episode_number, title, slug, is_free, coin_cost) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$seriesId, $episodeNumber, $title, $slug, $isFree, $coinCost]);
                        $episodeId = $db->lastInsertId();

                        // Add to video_queue for HLS processing
                        $queueStmt = $db->prepare("INSERT INTO video_queue (episode_id, original_file, status) VALUES (?, ?, 'pending')");
                        $queueStmt->execute([$episodeId, $destPath]);

                        $db->commit();

                        \App\Core\Session::setFlash('success', 'Episode created and queued for processing.');
                        header("Location: /admin/episodes");
                        die();
                    } catch (\Exception $e) {
                        $db->rollBack();
                        \App\Core\Session::setFlash('error', 'Failed to save episode: ' . $e->getMessage());
                    }
                } else {
                    \App\Core\Session::setFlash('error', 'Failed to move uploaded file.');
                }
            } else {
                 \App\Core\Session::setFlash('error', 'Video file upload failed.');
            }
        }

        require __DIR__ . '/../templates/episode-form.php';
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
            $episodeNumber = $_POST['episode_number'] ?? 1;
            $isFree = isset($_POST['is_free']) ? 1 : 0;
            $coinCost = $_POST['coin_cost'] ?? 0.00;
            $seriesId = $_POST['series_id'] ?? 0;

            $stmt = $db->prepare("UPDATE episodes SET title = ?, episode_number = ?, is_free = ?, coin_cost = ?, series_id = ? WHERE id = ?");
            $stmt->execute([$title, $episodeNumber, $isFree, $coinCost, $seriesId, $id]);

            \App\Core\Session::setFlash('success', 'Episode updated successfully.');
            header("Location: /admin/episodes");
            die();
        }

        $stmt = $db->prepare("SELECT * FROM episodes WHERE id = ?");
        $stmt->execute([$id]);
        $episode = $stmt->fetch();

        $stmt = $db->query("SELECT id, title FROM series ORDER BY title ASC");
        $seriesList = $stmt->fetchAll();

        require __DIR__ . '/../templates/episode-form.php';
    }

    public function delete($id) {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /admin/episodes");
            die();
        }

        $db = \App\Core\Database::getInstance();
        $stmt = $db->prepare("DELETE FROM episodes WHERE id = ?");
        $stmt->execute([$id]);

        \App\Core\Session::setFlash('success', 'Episode deleted successfully.');
        header("Location: /admin/episodes");
        die();
    }
}
