<?php

namespace App\Controllers\Api;

use App\Core\Database;

class CustomAdController extends BaseApiController {

    /** GET /api/v1/ads/interstitial — one random currently-active interstitial ad, or null. */
    public function interstitial() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM custom_ads
                             WHERE is_active = 1 AND placement IN ('interstitial', 'both')
                               AND (starts_at IS NULL OR starts_at <= NOW())
                               AND (expires_at IS NULL OR expires_at > NOW())
                             ORDER BY RAND() LIMIT 1");
        $ad = $stmt->fetch();

        if (!$ad) {
            $this->respondJson(['status' => true, 'data' => null]);
        }

        $this->respondJson(['status' => true, 'data' => [
            'id'         => (int)$ad['id'],
            'title'      => $ad['title'],
            'media_url'  => $this->absoluteUrl($ad['media_url'] ?? null),
            'media_type' => $ad['media_type'],
            'target_url' => $ad['target_url'],
        ]]);
    }
}
