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

        foreach ($banners as &$banner) {
            $banner['image_url'] = $this->absoluteUrl($banner['image_url'] ?? null);
        }
        unset($banner);

        $this->respondJson(['status' => true, 'data' => $banners]);
    }
}
