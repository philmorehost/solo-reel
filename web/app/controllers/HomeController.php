<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\WatchHistory;

class HomeController {
    public function index() {
        $db = Database::getInstance();

        // Continue Watching — per-viewer, computed at request time, never
        // admin-curated. Rendered above Latest Releases in the template.
        $continueWatching = WatchHistory::continueWatchingFor(
            $db,
            Session::get('user_id'),
            Session::getGuestId()
        );

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
        foreach ($featuredSeries as &$fs) { $fs['is_ad'] = false; $fs['media_type'] = 'image'; $fs['duration_seconds'] = 5; }
        unset($fs);

        // Merge in custom ads (admin-uploaded or user-purchased via /advertise)
        // currently within their start/expiry window and targeted at the
        // website (platform_placement 'website' or 'both'), into the hero rotation.
        $stmt = $db->query("SELECT * FROM custom_ads
                             WHERE is_active = 1 AND placement IN ('banner', 'both')
                               AND platform_placement IN ('website', 'both')
                               AND payment_status = 'paid'
                               AND (starts_at IS NULL OR starts_at <= NOW())
                               AND (expires_at IS NULL OR expires_at > NOW())
                             ORDER BY sort_order ASC");
        foreach ($stmt->fetchAll() as $ad) {
            $featuredSeries[] = [
                'title'            => $ad['title'],
                'synopsis'         => '',
                'hero_image'       => $ad['media_url'],
                'slug'             => $ad['target_url'] ?: '#',
                'is_ad'            => true,
                'media_type'       => $ad['media_type'],
                'duration_seconds' => (int)($ad['duration_seconds'] ?? 5),
            ];
        }

        $stmt = $db->query("SELECT * FROM shelves WHERE is_active = 1 ORDER BY sort_order ASC");
        $shelves = $stmt->fetchAll();

        foreach ($shelves as &$shelf) {
            $stmt = $db->prepare("SELECT id, title, slug, cover_image, (SELECT COUNT(*) FROM episodes WHERE series_id = series.id) as episode_count FROM series WHERE shelf_id = ? ORDER BY created_at DESC LIMIT 6");
            $stmt->execute([$shelf['id']]);
            $shelf['series'] = $stmt->fetchAll();
        }
        unset($shelf);

        // Fetch latest releases (not assigned to shelves explicitly)
        $stmt = $db->query("SELECT id, title, slug, cover_image, (SELECT COUNT(*) FROM episodes WHERE series_id = series.id) as episode_count FROM series ORDER BY created_at DESC LIMIT 6");
        $latestSeries = $stmt->fetchAll();

        // Skip-to-player: resolve each card's resume episode (or episode 1)
        // in one batch, so tapping a card jumps straight into the reel feed
        // instead of a series-detail page first.
        $seriesIds = array_column($latestSeries, 'id');
        foreach ($shelves as $shelf) { $seriesIds = array_merge($seriesIds, array_column($shelf['series'], 'id')); }
        foreach ($featuredSeries as $fs) { if (isset($fs['id'])) $seriesIds[] = $fs['id']; }
        $seriesIds = array_values(array_unique(array_map('intval', $seriesIds)));

        $resumeSlugs = WatchHistory::resumeSlugsForSeries($db, $seriesIds, Session::get('user_id'), Session::getGuestId());

        foreach ($latestSeries as &$series) { $series['resume_slug'] = $resumeSlugs[(int)$series['id']] ?? null; }
        unset($series);
        foreach ($shelves as &$shelf) {
            foreach ($shelf['series'] as &$series) { $series['resume_slug'] = $resumeSlugs[(int)$series['id']] ?? null; }
            unset($series);
        }
        unset($shelf);
        foreach ($featuredSeries as &$fs) { if (isset($fs['id'])) $fs['resume_slug'] = $resumeSlugs[(int)$fs['id']] ?? null; }
        unset($fs);

        require __DIR__ . '/../../templates/pages/home.php';
    }
}
