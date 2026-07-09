<?php

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\Vip;

class VipController extends BaseApiController {

    /** GET /api/v1/vip/plans */
    public function plans() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT id, name, price, currency, duration_days, perk_free_unlocks, perk_ad_free FROM vip_plans WHERE is_active = 1 ORDER BY sort_order ASC");
        // Explicit casts — tinyint(1) columns would otherwise serialize as raw
        // 1/0 rather than JSON booleans, which Gson (Android)/Codable (iOS)
        // fail to decode into Bool fields.
        $plans = array_map(function ($row) {
            return [
                'id'                => (int) $row['id'],
                'name'              => $row['name'],
                'price'             => (float) $row['price'],
                'currency'          => $row['currency'],
                'duration_days'     => (int) $row['duration_days'],
                'perk_free_unlocks' => (bool) $row['perk_free_unlocks'],
                'perk_ad_free'      => (bool) $row['perk_ad_free'],
            ];
        }, $stmt->fetchAll());
        $this->respondJson(['status' => true, 'data' => $plans]);
    }

    /** GET /api/v1/user/vip-status */
    public function status() {
        $userId = $this->optionalUserId();
        if (!$userId) {
            $this->respondJson(['status' => true, 'data' => ['is_vip' => false, 'expires_at' => null, 'plan_name' => null]]);
        }

        $db = Database::getInstance();
        $subscription = Vip::activeSubscription($db, $userId);
        $this->respondJson(['status' => true, 'data' => [
            'is_vip'     => $subscription !== null,
            'expires_at' => $subscription['expires_at'] ?? null,
            'plan_name'  => $subscription['plan_name'] ?? null,
        ]]);
    }
}
