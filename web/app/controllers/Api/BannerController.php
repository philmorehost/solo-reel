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
                'id'         => (int)$banner['id'],
                'title'      => $banner['title'],
                'subtitle'   => $banner['subtitle'],
                'image_url'  => $this->absoluteUrl($banner['image_url'] ?? null),
                'link_url'   => $banner['link_url'],
                'is_ad'      => false,
                'media_type' => 'image',
            ];
        }, $banners);

        // Merge in admin-uploaded custom ads (image or video) currently within
        // their start/expiry window, so the home carousel shows both content
        // banners and paid placements without the apps needing a separate call.
        $stmt = $db->prepare("SELECT * FROM custom_ads
                               WHERE is_active = 1 AND placement IN ('banner', 'both')
                                 AND (starts_at IS NULL OR starts_at <= NOW())
                                 AND (expires_at IS NULL OR expires_at > NOW())
                               ORDER BY sort_order ASC");
        $stmt->execute();
        $ads = $stmt->fetchAll();

        foreach ($ads as $ad) {
            $items[] = [
                'id'         => (int)$ad['id'],
                'title'      => $ad['title'],
                'subtitle'   => null,
                'image_url'  => $this->absoluteUrl($ad['media_url'] ?? null),
                'link_url'   => $ad['target_url'],
                'is_ad'      => true,
                'media_type' => $ad['media_type'],
            ];
        }

        $this->respondJson(['status' => true, 'data' => $items]);
    }
}
