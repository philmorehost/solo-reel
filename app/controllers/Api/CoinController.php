<?php

namespace App\Controllers\Api;

use App\Core\Database;

class CoinController {

    private function respondJson($data, $statusCode = 200) {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        die();
    }

    public function packages() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT id, name, coins, price, currency, color_code FROM coin_packages WHERE is_active = 1 ORDER BY sort_order ASC");
        $this->respondJson(['data' => $stmt->fetchAll()]);
    }
}
