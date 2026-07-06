<?php

namespace App\Controllers\Api;

use App\Core\Database;

class BannerController extends BaseApiController {

    public function index() {
        $db = Database::getInstance();
        $active = isset($_GET['active']) && $_GET['active'] === 'true' ? 1 : 0;

        $query = "SELECT id, title, subtitle, image_url, link_url FROM banners";
        if ($active) {
            $query .= " WHERE is_active = 1";
        }
        $query .= " ORDER BY sort_order ASC";

        $stmt = $db->prepare($query);
        $stmt->execute();
        $banners = $stmt->fetchAll();

        $items = array_map(function ($banner) {
            return [
                'id'               => (int)$banner['id'],
                'title'            => $banner['title'],
                'subtitle'         => $banner['subtitle'],
                'image_url'        => $this->absoluteUrl($banner['image_url'] ?? null),
                'link_url'         => $banner['link_url'],
                'is_ad'            => false,
                'media_type'       => 'image',
                'duration_seconds' => 5,
            ];
        }, $banners);

        // Merge in custom ads (admin-uploaded or user-purchased via /advertise)
        // currently within their start/expiry window and targeted at the app
        // (platform_placement 'app' or 'both'), so the home carousel shows both
        // content banners and paid placements without a separate app call.
        $stmt = $db->prepare("SELECT * FROM custom_ads
                               WHERE is_active = 1 AND placement IN ('banner', 'both')
                                 AND platform_placement IN ('app', 'both')
                                 AND payment_status = 'paid'
                                 AND (starts_at IS NULL OR starts_at <= NOW())
                                 AND (expires_at IS NULL OR expires_at > NOW())
                               ORDER BY sort_order ASC");
        $stmt->execute();
        $ads = $stmt->fetchAll();

        foreach ($ads as $ad) {
            $items[] = [
                'id'               => (int)$ad['id'],
                'title'            => $ad['title'],
                'subtitle'         => null,
                'image_url'        => $this->absoluteUrl($ad['media_url'] ?? null),
                'link_url'         => $ad['target_url'],
                'is_ad'            => true,
                'media_type'       => $ad['media_type'],
                'duration_seconds' => (int)($ad['duration_seconds'] ?? 5),
            ];
        }

        $this->respondJson(['status' => true, 'data' => $items]);
    }
}
