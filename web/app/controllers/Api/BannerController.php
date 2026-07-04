<?php

namespace App\Controllers\Api;

use App\Core\Database;

class BannerController {

    private function respondJson($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        die();
    }

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

        $this->respondJson(['data' => $banners]);
    }
}
