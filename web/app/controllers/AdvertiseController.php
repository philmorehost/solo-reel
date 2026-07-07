<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\Security;
use App\Core\PayhubKeys;

class AdvertiseController {

    private function loadPricing(\PDO $db): array {
        $stmt = $db->query("SELECT * FROM ad_pricing");
        $prices = [];
        foreach ($stmt->fetchAll() as $row) {
            $prices[$row['duration_seconds']][$row['platform_placement']] = (float)$row['price'];
        }
        return $prices;
    }

    /** GET /advertise */
    public function index() {
        \App\Core\Auth::requireLogin();
        $db = Database::getInstance();
        $prices = $this->loadPricing($db);

        require __DIR__ . '/../../templates/pages/advertise.php';
    }

    /** POST /advertise/subscribe */
    public function subscribe() {
        \App\Core\Auth::requireLogin();

        try {
            Security::validateCsrfPost();

            $userId = Session::get('user_id');
            $email = Session::get('user_email');
            $title = trim($_POST['title'] ?? '');
            $targetUrl = trim($_POST['target_url'] ?? '');
            $durationSeconds = (int)($_POST['duration_seconds'] ?? 0);
            $platformPlacement = $_POST['platform_placement'] ?? '';

            if (!in_array($durationSeconds, [5, 10, 15], true) || !in_array($platformPlacement, ['website', 'app', 'both'], true) || $title === '') {
                Session::setFlash('error', 'Please choose a duration, placement, and title.');
                header("Location: /advertise");
                die();
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
                    $uploadDir = __DIR__ . '/../../assets/uploads';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

                    $destPath = 'assets/uploads/useradv_' . time() . '_' . preg_replace('/[^a-zA-Z0-9.\-_]/', '', $fileName);
                    if (move_uploaded_file($tmpName, __DIR__ . '/../../' . $destPath)) {
                        $mediaUrl = '/' . $destPath;
                    }
                } else {
                    Session::setFlash('error', 'Invalid file format. Use png/jpg/webp for images or mp4/webm for video.');
                    header("Location: /advertise");
                    die();
                }
            }

            if (!$mediaUrl) {
                Session::setFlash('error', 'A banner image or video is required.');
                header("Location: /advertise");
                die();
            }

            $db = Database::getInstance();
            $prices = $this->loadPricing($db);
            $price = $prices[(string)$durationSeconds][$platformPlacement] ?? null;
            if ($price === null) {
                Session::setFlash('error', 'Pricing is not configured for that combination. Contact support.');
                header("Location: /advertise");
                die();
            }

            $stmt = $db->query("SELECT * FROM payment_settings LIMIT 1");
            $settings = $stmt->fetch();
            $keys = $settings ? PayhubKeys::active($settings) : ['public' => '', 'secret' => ''];
            if (!$settings || $keys['public'] === '' || $keys['secret'] === '') {
                Session::setFlash('error', 'Payment gateway is not configured. Contact support.');
                header("Location: /advertise");
                die();
            }

            // Ad starts inactive/unpaid; payment verification (PaymentController::verify)
            // activates it and sets the campaign expiry.
            $stmt = $db->prepare("INSERT INTO custom_ads (user_id, title, media_type, media_url, target_url, placement, duration_seconds, platform_placement, is_active, payment_status)
                                  VALUES (?, ?, ?, ?, ?, 'banner', ?, ?, 0, 'pending')");
            $stmt->execute([$userId, $title, $mediaType, $mediaUrl, $targetUrl, $durationSeconds, $platformPlacement]);
            $adId = (int)$db->lastInsertId();

            $payhubTxn = PayhubKeys::initialize($settings, $keys['secret'], $price, $email);
            $reference = $payhubTxn['reference'];

            $stmt = $db->prepare("INSERT INTO payment_transactions (user_id, type, ad_id, reference, amount, currency, status, coins_awarded) VALUES (?, 'ad_placement', ?, ?, ?, 'NGN', 'pending', 0)");
            $stmt->execute([$userId, $adId, $reference, $price]);

            $db->prepare("UPDATE custom_ads SET payment_reference = ? WHERE id = ?")->execute([$reference, $adId]);

            $publicKey = $keys['public'];
            $amount = $price;
            $payhubBaseUrl = PayhubKeys::baseUrl($settings);
            $cancelUrl = '/my-ads';
            require __DIR__ . '/../../templates/pages/checkout.php';
            die();
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Could not start payment: ' . $e->getMessage());
            header("Location: /advertise");
            die();
        }
    }

    /** POST /advertise/renew/{id} — re-runs payment for an existing ad's same spec, extending its campaign. */
    public function renew($id) {
        \App\Core\Auth::requireLogin();
        Security::validateCsrfPost();

        $userId = Session::get('user_id');
        $email = Session::get('user_email');
        $db = Database::getInstance();

        $stmt = $db->prepare("SELECT * FROM custom_ads WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
        $ad = $stmt->fetch();

        if (!$ad) {
            Session::setFlash('error', 'Ad not found.');
            header("Location: /my-ads");
            die();
        }

        try {
            $prices = $this->loadPricing($db);
            $price = $prices[(string)$ad['duration_seconds']][$ad['platform_placement']] ?? null;
            if ($price === null) {
                Session::setFlash('error', 'Pricing is not configured for that combination. Contact support.');
                header("Location: /my-ads");
                die();
            }

            $stmt = $db->query("SELECT * FROM payment_settings LIMIT 1");
            $settings = $stmt->fetch();
            $keys = $settings ? PayhubKeys::active($settings) : ['public' => '', 'secret' => ''];
            if (!$settings || $keys['public'] === '' || $keys['secret'] === '') {
                Session::setFlash('error', 'Payment gateway is not configured. Contact support.');
                header("Location: /my-ads");
                die();
            }

            $payhubTxn = PayhubKeys::initialize($settings, $keys['secret'], $price, $email);
            $reference = $payhubTxn['reference'];

            $stmt = $db->prepare("INSERT INTO payment_transactions (user_id, type, ad_id, reference, amount, currency, status, coins_awarded) VALUES (?, 'ad_placement', ?, ?, ?, 'NGN', 'pending', 0)");
            $stmt->execute([$userId, $ad['id'], $reference, $price]);

            $db->prepare("UPDATE custom_ads SET payment_reference = ?, payment_status = 'pending' WHERE id = ?")->execute([$reference, $ad['id']]);

            $publicKey = $keys['public'];
            $amount = $price;
            $payhubBaseUrl = PayhubKeys::baseUrl($settings);
            $cancelUrl = '/my-ads';
            require __DIR__ . '/../../templates/pages/checkout.php';
            die();
        } catch (\Throwable $e) {
            Session::setFlash('error', 'Could not start payment: ' . $e->getMessage());
            header("Location: /my-ads");
            die();
        }
    }
}
