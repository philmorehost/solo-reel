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
        $stmt = $db->query("
            SELECT e.*, s.title as series_title
            FROM episodes e
            JOIN series s ON e.series_id = s.id
            ORDER BY e.created_at DESC
        ");
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
            $unlockMethod = $_POST['unlock_method'] ?? 'coins';
            $thumbnailUrl = $this->handleThumbnailUpload();
            $videoUrl = $this->handleVideoUpload();

            if ($videoUrl === false) {
                // handleVideoUpload() already set a flash error
                header("Location: /admin/episodes/create");
                die();
            }
            if ($videoUrl === null) {
                \App\Core\Session::setFlash('error', 'A video file is required.');
                header("Location: /admin/episodes/create");
                die();
            }

            $db->beginTransaction();
            try {
                $stmt = $db->prepare("INSERT INTO episodes (series_id, episode_number, title, slug, is_free, coin_cost, unlock_method, thumbnail_url, video_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$seriesId, $episodeNumber, $title, $slug, $isFree, $coinCost, $unlockMethod, $thumbnailUrl, $videoUrl]);

                $db->commit();

                \App\Core\Session::setFlash('success', 'Episode created and published.');
                header("Location: /admin/episodes");
                die();
            } catch (\Exception $e) {
                $db->rollBack();
                \App\Core\Session::setFlash('error', 'Failed to save episode: ' . $e->getMessage());
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
            $unlockMethod = $_POST['unlock_method'] ?? 'coins';
            $seriesId = $_POST['series_id'] ?? 0;
            $thumbnailUrl = $this->handleThumbnailUpload();
            $videoUrl = $this->handleVideoUpload();

            if ($videoUrl === false) {
                // handleVideoUpload() already set a flash error
                header("Location: /admin/episodes/edit/" . $id);
                die();
            }

            $fields = ['title = ?', 'episode_number = ?', 'is_free = ?', 'coin_cost = ?', 'unlock_method = ?', 'series_id = ?'];
            $params = [$title, $episodeNumber, $isFree, $coinCost, $unlockMethod, $seriesId];

            if ($thumbnailUrl !== null) {
                $fields[] = 'thumbnail_url = ?';
                $params[] = $thumbnailUrl;
            }
            if ($videoUrl !== null) {
                $fields[] = 'video_url = ?';
                $params[] = $videoUrl;
            }
            $params[] = $id;

            $stmt = $db->prepare("UPDATE episodes SET " . implode(', ', $fields) . " WHERE id = ?");
            $stmt->execute($params);

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

    /**
     * Saves an optional admin-uploaded cover image so a broken thumbnail isn't
     * the only option while a video is still waiting on the ffmpeg queue (which
     * is the only other place thumbnail_url gets set, once transcoding finishes).
     * Returns the stored relative URL, or null when no file was submitted
     * (callers must treat null as "leave the existing thumbnail_url alone").
     */
    private function handleThumbnailUpload(): ?string {
        if (!isset($_FILES['thumbnail_file']) || $_FILES['thumbnail_file']['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($_FILES['thumbnail_file']['error'] !== UPLOAD_ERR_OK) {
            \App\Core\Session::setFlash('error', 'Cover image upload failed; the rest of the episode was saved.');
            return null;
        }

        $tmpName = $_FILES['thumbnail_file']['tmp_name'];
        $allowedMimeToExt = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpName);
        finfo_close($finfo);

        if (!isset($allowedMimeToExt[$mimeType])) {
            \App\Core\Session::setFlash('error', 'Cover image must be a JPG, PNG, or WebP; the rest of the episode was saved.');
            return null;
        }

        $uploadDir = __DIR__ . '/../../assets/uploads/thumbnails';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $fileName = bin2hex(random_bytes(16)) . '.' . $allowedMimeToExt[$mimeType];
        if (!move_uploaded_file($tmpName, $uploadDir . '/' . $fileName)) {
            \App\Core\Session::setFlash('error', 'Failed to save cover image; the rest of the episode was saved.');
            return null;
        }

        return '/assets/uploads/thumbnails/' . $fileName;
    }

    /**
     * Validates and stores an uploaded MP4 directly to public storage — videos
     * are published immediately, with no ffmpeg/HLS transcoding step (removed:
     * the production host has no ffmpeg and no way to install it). Returns the
     * public relative URL on success, null if no file was submitted (edit()
     * treats null as "keep the existing video_url"), or false if a file was
     * submitted but rejected/failed (a flash error is already set in that case).
     *
     * @return string|null|false
     */
    private function handleVideoUpload() {
        if (!isset($_FILES['video_file']) || $_FILES['video_file']['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
            \App\Core\Session::setFlash('error', 'Video file upload failed.');
            return false;
        }

        $tmpName = $_FILES['video_file']['tmp_name'];
        $fileName = basename($_FILES['video_file']['name']);

        // Backend security validation: verify extension and MIME type
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $tmpName);
        finfo_close($finfo);

        if ($extension !== 'mp4' || $mimeType !== 'video/mp4') {
            \App\Core\Session::setFlash('error', 'Invalid file type. Only MP4 videos are allowed.');
            return false;
        }

        $videoDir = __DIR__ . '/../../storage/videos';
        if (!is_dir($videoDir)) {
            mkdir($videoDir, 0775, true);
        }

        $destName = bin2hex(random_bytes(16)) . '.mp4';
        if (!move_uploaded_file($tmpName, $videoDir . '/' . $destName)) {
            \App\Core\Session::setFlash('error', 'Failed to save uploaded video.');
            return false;
        }

        return '/storage/videos/' . $destName;
    }
}
