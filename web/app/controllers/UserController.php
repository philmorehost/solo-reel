<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;

class UserController {
    public function profile() {
        \App\Core\Auth::requireLogin();

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([Session::get('user_id')]);
        $user = $stmt->fetch();

        require __DIR__ . '/../../templates/pages/profile.php';
    }

    public function watchHistory() {
        \App\Core\Auth::requireLogin();
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT wh.*, e.title as episode_title, e.thumbnail_url, s.title as series_title, s.slug as series_slug
            FROM watch_history wh
            JOIN episodes e ON wh.episode_id = e.id
            JOIN series s ON e.series_id = s.id
            WHERE wh.user_id = ?
            ORDER BY wh.last_watched_at DESC
        ");
        $stmt->execute([Session::get('user_id')]);
        $history = $stmt->fetchAll();

        require __DIR__ . '/../../templates/pages/watch-history.php';
    }

    public function favorites() {
        \App\Core\Auth::requireLogin();
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT f.*, s.title, s.slug, s.cover_image
            FROM favorites f
            JOIN series s ON f.series_id = s.id
            WHERE f.user_id = ?
            ORDER BY f.created_at DESC
        ");
        $stmt->execute([Session::get('user_id')]);
        $series = $stmt->fetchAll();

        require __DIR__ . '/../../templates/pages/favorites.php';
    }

    public function addFavorite(int $seriesId) {
        \App\Core\Auth::requireLogin();
        \App\Core\Security::validateCsrfPost();

        $db = Database::getInstance();
        $stmt = $db->prepare("INSERT IGNORE INTO favorites (user_id, series_id) VALUES (?, ?)");
        $stmt->execute([Session::get('user_id'), $seriesId]);

        Session::setFlash('success', 'Added to favorites.');
        header("Location: " . $_SERVER['HTTP_REFERER']);
        die();
    }

    public function removeFavorite(int $seriesId) {
        \App\Core\Auth::requireLogin();
        \App\Core\Security::validateCsrfPost();

        $db = Database::getInstance();
        $stmt = $db->prepare("DELETE FROM favorites WHERE user_id = ? AND series_id = ?");
        $stmt->execute([Session::get('user_id'), $seriesId]);

        Session::setFlash('success', 'Removed from favorites.');
        header("Location: " . $_SERVER['HTTP_REFERER']);
        die();
    }
}
