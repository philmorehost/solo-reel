<?php

namespace App\Controllers\Api;

use App\Core\Database;
use App\Core\PayhubKeys;

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

    /** GET /api/v1/ads/pricing — the 9 duration x platform_placement price combinations. */
    public function pricing() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT duration_seconds, platform_placement, price, currency FROM ad_pricing");
        $rows = $stmt->fetchAll();

        $data = array_map(function ($row) {
            return [
                'duration_seconds'   => (int)$row['duration_seconds'],
                'platform_placement' => $row['platform_placement'],
                'price'              => (float)$row['price'],
                'currency'           => $row['currency'],
            ];
        }, $rows);

        $this->respondJson(['status' => true, 'data' => $data]);
    }

    /**
     * POST /api/v1/ads/subscribe (multipart/form-data) — registered users only.
     * Fields: title, target_url (optional), duration_seconds, platform_placement, media_file.
     * Mirrors AdvertiseController::subscribe() (web) so both surfaces share the
     * same pricing/upload/Payhub-initialize logic.
     */
    public function subscribe() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respondJson(['status' => false, 'error' => 'Method Not Allowed'], 405);
        }

        $userId = $this->requireUserId();
        $title = trim($_POST['title'] ?? '');
        $targetUrl = trim($_POST['target_url'] ?? '');
        $durationSeconds = (int)($_POST['duration_seconds'] ?? 0);
        $platformPlacement = $_POST['platform_placement'] ?? '';

        if ($title === '' || !in_array($durationSeconds, [5, 10, 15], true) || !in_array($platformPlacement, ['website', 'app', 'both'], true)) {
            $this->respondJson(['status' => false, 'error' => 'title, duration_seconds (5/10/15) and platform_placement (website/app/both) are required'], 400);
        }

        $mediaType = '';
        $mediaUrl = '';
        if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['media_file']['tmp_name'];
            $fileName = basename($_FILES['media_file']['name']);
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $imageExts = ['png', 'jpg', 'jpeg', 'webp'];
            $videoExts = ['mp4', 'webm'];

            if (in_array($ext, $imageExts) || in_array($ext, $videoExts)) {
                $mediaType = in_array($ext, $videoExts) ? 'video' : 'image';
                $uploadDir = __DIR__ . '/../../../assets/uploads';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

                $destPath = 'assets/uploads/useradv_' . time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $fileName);
                if (move_uploaded_file($tmpName, __DIR__ . '/../../../' . $destPath)) {
                    $mediaUrl = '/' . $destPath;
                }
            } else {
                $this->respondJson(['status' => false, 'error' => 'Invalid file format. Use png/jpg/webp for images or mp4/webm for video.'], 400);
            }
        }

        if (!$mediaUrl) {
            $this->respondJson(['status' => false, 'error' => 'A banner image or video is required.'], 400);
        }

        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM ad_pricing");
        $prices = [];
        foreach ($stmt->fetchAll() as $row) {
            $prices[$row['duration_seconds']][$row['platform_placement']] = (float)$row['price'];
        }
        $price = $prices[(string)$durationSeconds][$platformPlacement] ?? null;
        if ($price === null) {
            $this->respondJson(['status' => false, 'error' => 'Pricing is not configured for that combination.'], 500);
        }

        $stmt = $db->query("SELECT * FROM payment_settings LIMIT 1");
        $settings = $stmt->fetch();
        $keys = $settings ? PayhubKeys::active($settings) : ['public' => '', 'secret' => ''];
        if (!$settings || $keys['public'] === '' || $keys['secret'] === '') {
            $this->respondJson(['status' => false, 'error' => 'Payment gateway is not configured.'], 500);
        }

        $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $email = $user['email'] ?? ('user_' . $userId . '@soloreel.tv');

        try {
            $stmt = $db->prepare("INSERT INTO custom_ads (user_id, title, media_type, media_url, target_url, placement, duration_seconds, platform_placement, is_active, payment_status)
                                  VALUES (?, ?, ?, ?, ?, 'banner', ?, ?, 0, 'pending')");
            $stmt->execute([$userId, $title, $mediaType, $mediaUrl, $targetUrl, $durationSeconds, $platformPlacement]);
            $adId = (int)$db->lastInsertId();

            $payhubTxn = PayhubKeys::initialize($settings, $keys['secret'], $price, $email);
            $reference = $payhubTxn['reference'];

            $stmt = $db->prepare("INSERT INTO payment_transactions (user_id, type, ad_id, reference, amount, currency, status, coins_awarded) VALUES (?, 'ad_placement', ?, ?, ?, 'NGN', 'pending', 0)");
            $stmt->execute([$userId, $adId, $reference, $price]);

            $db->prepare("UPDATE custom_ads SET payment_reference = ? WHERE id = ?")->execute([$reference, $adId]);

            $data = [
                'authorization_url' => $this->baseUrl() . '/pay/checkout?reference=' . urlencode($reference),
                'reference'         => $reference,
                'ad_id'             => $adId,
            ];
            $this->respondJson(['status' => true, 'message' => 'Ad created, payment initialized', 'data' => $data] + $data);
        } catch (\Throwable $e) {
            $this->respondJson(['status' => false, 'error' => 'Could not start payment: ' . $e->getMessage()], 500);
        }
    }

    /** GET /api/v1/ads/my-ads — the logged-in user's ads with status/expiry. */
    public function myAds() {
        $userId = $this->requireUserId();
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM custom_ads WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$userId]);
        $rows = $stmt->fetchAll();

        $data = array_map(function ($ad) {
            $expired = !empty($ad['expires_at']) && strtotime($ad['expires_at']) <= time();
            return [
                'id'                 => (int)$ad['id'],
                'title'              => $ad['title'],
                'media_url'          => $this->absoluteUrl($ad['media_url'] ?? null),
                'media_type'         => $ad['media_type'],
                'target_url'         => $ad['target_url'],
                'duration_seconds'   => (int)$ad['duration_seconds'],
                'platform_placement' => $ad['platform_placement'],
                'payment_status'     => $ad['payment_status'],
                'is_active'          => (bool)$ad['is_active'],
                'is_expired'         => $expired,
                'expires_at'         => $ad['expires_at'],
            ];
        }, $rows);

        $this->respondJson(['status' => true, 'data' => $data]);
    }

    /** POST /api/v1/ads/renew/{id} — re-initiates payment for an existing ad, extending its campaign on success. */
    public function renew(int $id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respondJson(['status' => false, 'error' => 'Method Not Allowed'], 405);
        }

        $userId = $this->requireUserId();
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM custom_ads WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $ad = $stmt->fetch();
        if (!$ad) {
            $this->respondJson(['status' => false, 'error' => 'Ad not found'], 404);
        }

        $stmt = $db->query("SELECT * FROM ad_pricing");
        $prices = [];
        foreach ($stmt->fetchAll() as $row) {
            $prices[$row['duration_seconds']][$row['platform_placement']] = (float)$row['price'];
        }
        $price = $prices[(string)$ad['duration_seconds']][$ad['platform_placement']] ?? null;
        if ($price === null) {
            $this->respondJson(['status' => false, 'error' => 'Pricing is not configured for that combination.'], 500);
        }

        $stmt = $db->query("SELECT * FROM payment_settings LIMIT 1");
        $settings = $stmt->fetch();
        $keys = $settings ? PayhubKeys::active($settings) : ['public' => '', 'secret' => ''];
        if (!$settings || $keys['public'] === '' || $keys['secret'] === '') {
            $this->respondJson(['status' => false, 'error' => 'Payment gateway is not configured.'], 500);
        }

        $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $email = $user['email'] ?? ('user_' . $userId . '@soloreel.tv');

        try {
            $payhubTxn = PayhubKeys::initialize($settings, $keys['secret'], $price, $email);
            $reference = $payhubTxn['reference'];

            $stmt = $db->prepare("INSERT INTO payment_transactions (user_id, type, ad_id, reference, amount, currency, status, coins_awarded) VALUES (?, 'ad_placement', ?, ?, ?, 'NGN', 'pending', 0)");
            $stmt->execute([$userId, $ad['id'], $reference, $price]);

            $db->prepare("UPDATE custom_ads SET payment_reference = ?, payment_status = 'pending' WHERE id = ?")->execute([$reference, $ad['id']]);

            $data = [
                'authorization_url' => $this->baseUrl() . '/pay/checkout?reference=' . urlencode($reference),
                'reference'         => $reference,
            ];
            $this->respondJson(['status' => true, 'message' => 'Payment initialized', 'data' => $data] + $data);
        } catch (\Throwable $e) {
            $this->respondJson(['status' => false, 'error' => 'Could not start payment: ' . $e->getMessage()], 500);
        }
    }
}
