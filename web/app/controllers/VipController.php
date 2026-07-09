<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\Vip;

/** Web session page for VIP plans — the alternative billing path alongside coin-shop.php. */
class VipController {
    public function plans() {
        if (!Session::isLoggedIn()) {
            Session::setFlash('error', 'Please sign in to view VIP plans.');
            header('Location: /login');
            die();
        }

        $db = Database::getInstance();
        $userId = Session::get('user_id');

        $plans = $db->query("SELECT * FROM vip_plans WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();
        $activeSubscription = Vip::activeSubscription($db, $userId);

        require __DIR__ . '/../../templates/pages/vip.php';
    }
}
