<?php

namespace App\Controllers;

use App\Core\Database;

class HomeController {
    public function index() {
        $db = Database::getInstance();

        // Fetch featured series for slider
        $stmt = $db->query("SELECT id, title, slug, synopsis, hero_image FROM series WHERE is_featured = 1 AND hero_image IS NOT NULL ORDER BY created_at DESC LIMIT 5");
        $featuredSeries = $stmt->fetchAll();

        // If no featured series, fallback to banners table for legacy support
        if (empty($featuredSeries)) {
            $stmt = $db->query("SELECT title, subtitle as synopsis, image_url as hero_image, link_url as slug FROM banners WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 5");
            $featuredSeries = $stmt->fetchAll();
            // Format slug properly for legacy banners
            foreach ($featuredSeries as &$fs) {
                if (str_starts_with($fs['slug'], '/movie/')) {
                    $fs['slug'] = substr($fs['slug'], 7);
                }
            }
        }
        foreach ($featuredSeries as &$fs) { $fs['is_ad'] = false; $fs['media_type'] = 'image'; }
        unset($fs);

        // Merge in admin-uploaded custom ads (image or video) currently within
        // their start/expiry window, into the same hero rotation.
        $stmt = $db->query("SELECT * FROM custom_ads
                             WHERE is_active = 1 AND placement IN ('banner', 'both')
                               AND (starts_at IS NULL OR starts_at <= NOW())
                               AND (expires_at IS NULL OR expires_at > NOW())
                             ORDER BY sort_order ASC");
        foreach ($stmt->fetchAll() as $ad) {
            $featuredSeries[] = [
                'title'      => $ad['title'],
                'synopsis'   => '',
                'hero_image' => $ad['media_url'],
                'slug'       => $ad['target_url'] ?: '#',
                'is_ad'      => true,
                'media_type' => $ad['media_type'],
            ];
        }

        $stmt = $db->query("SELECT * FROM shelves WHERE is_active = 1 ORDER BY sort_order ASC");
        $shelves = $stmt->fetchAll();

        foreach ($shelves as &$shelf) {
            $stmt = $db->prepare("SELECT id, title, slug, cover_image, (SELECT COUNT(*) FROM episodes WHERE series_id = series.id) as episode_count FROM series WHERE shelf_id = ? ORDER BY created_at DESC LIMIT 6");
            $stmt->execute([$shelf['id']]);
            $shelf['series'] = $stmt->fetchAll();
        }

        // Fetch latest releases (not assigned to shelves explicitly)
        $stmt = $db->query("SELECT id, title, slug, cover_image, (SELECT COUNT(*) FROM episodes WHERE series_id = series.id) as episode_count FROM series ORDER BY created_at DESC LIMIT 6");
        $latestSeries = $stmt->fetchAll();

        require __DIR__ . '/../../templates/pages/home.php';
    }
}
