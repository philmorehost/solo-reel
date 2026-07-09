<?php

namespace App\Core;

use PDO;

/**
 * VIP subscription status — an alternative billing path alongside the
 * existing pay-per-coin unlock system (not a replacement). Registered users
 * only; see web/schema/021_vip_subscriptions.sql for why guests can't hold
 * a subscription.
 */
class Vip {
    /** The viewer's current active plan (with its perks), or null if they have none. */
    public static function activeSubscription(PDO $db, int $userId): ?array {
        $stmt = $db->prepare("
            SELECT vs.*, vp.name AS plan_name, vp.perk_free_unlocks, vp.perk_ad_free
            FROM vip_subscriptions vs
            JOIN vip_plans vp ON vp.id = vs.plan_id
            WHERE vs.user_id = ? AND vs.status = 'active' AND vs.expires_at > NOW()
            ORDER BY vs.expires_at DESC
            LIMIT 1
        ");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function isVip(PDO $db, int $userId): bool {
        return self::activeSubscription($db, $userId) !== null;
    }
}
