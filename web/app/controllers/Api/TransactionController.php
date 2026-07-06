<?php

namespace App\Controllers\Api;

use App\Core\Database;

class TransactionController extends BaseApiController {

    private function paymentSettings(): array {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM payment_settings LIMIT 1");
        $settings = $stmt->fetch();
        if (!$settings) {
            $this->respondJson(['status' => false, 'error' => 'Payment gateway is not configured.'], 500);
        }
        $keys = $this->activeKeys($settings);
        if ($keys['public'] === '' || $keys['secret'] === '') {
            $mode = $settings['mode'] ?? 'sandbox';
            $this->respondJson(['status' => false, 'error' => "Payment gateway is not configured for {$mode} mode."], 500);
        }
        return $settings;
    }

    private function activeKeys(array $settings): array {
        return \App\Core\PayhubKeys::active($settings);
    }

    /** Wraps PayhubKeys::initialize(), converting failures into the API's JSON error format. */
    private function initializeWithPayhub(array $settings, string $secretKey, float $amount, string $email): array {
        try {
            return \App\Core\PayhubKeys::initialize($settings, $secretKey, $amount, $email);
        } catch (\RuntimeException $e) {
            $this->respondJson(['status' => false, 'error' => $e->getMessage()], 502);
        }
    }

    private function loadPackage(int $packageId): array {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM coin_packages WHERE id = ? AND is_active = 1");
        $stmt->execute([$packageId]);
        $package = $stmt->fetch();
        if (!$package) {
            $this->respondJson(['status' => false, 'error' => 'Package not available'], 400);
        }
        return $package;
    }

    private function respondPaymentInit(array $package, string $reference, string $publicKey, string $email) {
        $data = [
            'authorization_url' => $this->baseUrl() . '/pay/checkout?reference=' . urlencode($reference),
            'reference'         => $reference,
            'public_key'        => $publicKey,
            'amount'            => (float)$package['price'] * 100, // Kobo
            'email'             => $email,
        ];
        $this->respondJson([
            'status'  => true,
            'message' => 'Payment initialized',
            'data'    => $data,
        ] + $data);
    }

    /** Registered user id from the JWT, or null. Used by unlock endpoints that also accept guests. */
    private function guestIdFromRequest(): ?string {
        $input = json_decode(file_get_contents('php://input'), true);
        $guestId = trim((string)($input['guest_id'] ?? ($_GET['guest_id'] ?? '')));
        return $guestId !== '' ? $guestId : null;
    }

    public function unlock(int $episodeId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respondJson(['status' => false, 'error' => 'Method Not Allowed'], 405);
        }

        $userId = $this->optionalUserId();
        $guestId = $userId ? null : $this->guestIdFromRequest();
        if (!$userId && !$guestId) {
            $this->respondJson(['status' => false, 'error' => 'Unauthorized or invalid token'], 401);
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT coin_cost FROM episodes WHERE id = ? FOR UPDATE");
            $stmt->execute([$episodeId]);
            $episode = $stmt->fetch();

            if (!$episode) {
                $db->rollBack();
                $this->respondJson(['status' => false, 'error' => 'Episode not found'], 404);
            }

            if ($userId) {
                $stmt = $db->prepare("SELECT coin_balance FROM users WHERE id = ? FOR UPDATE");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();

                if ((float)$user['coin_balance'] < (float)$episode['coin_cost']) {
                    $db->rollBack();
                    $this->respondJson(['status' => false, 'error' => 'Insufficient coins'], 400);
                }

                $newBalance = (float)$user['coin_balance'] - (float)$episode['coin_cost'];
                $stmt = $db->prepare("UPDATE users SET coin_balance = ? WHERE id = ?");
                $stmt->execute([$newBalance, $userId]);

                $stmt = $db->prepare("INSERT IGNORE INTO user_unlocked_episodes (user_id, episode_id) VALUES (?, ?)");
                $stmt->execute([$userId, $episodeId]);

                $stmt = $db->prepare("INSERT INTO coin_transactions (user_id, type, amount, description) VALUES (?, 'unlock', ?, ?)");
                $stmt->execute([$userId, -$episode['coin_cost'], 'Unlocked episode ' . $episodeId]);
            } else {
                $stmt = $db->prepare("INSERT IGNORE INTO guest_wallets (guest_id, coin_balance) VALUES (?, 0)");
                $stmt->execute([$guestId]);

                $stmt = $db->prepare("SELECT coin_balance FROM guest_wallets WHERE guest_id = ? FOR UPDATE");
                $stmt->execute([$guestId]);
                $wallet = $stmt->fetch();

                if ((float)$wallet['coin_balance'] < (float)$episode['coin_cost']) {
                    $db->rollBack();
                    $this->respondJson(['status' => false, 'error' => 'Insufficient coins'], 400);
                }

                $newBalance = (float)$wallet['coin_balance'] - (float)$episode['coin_cost'];
                $stmt = $db->prepare("UPDATE guest_wallets SET coin_balance = ? WHERE guest_id = ?");
                $stmt->execute([$newBalance, $guestId]);

                $stmt = $db->prepare("INSERT IGNORE INTO guest_unlocked_episodes (guest_id, episode_id) VALUES (?, ?)");
                $stmt->execute([$guestId, $episodeId]);
            }

            $db->commit();
            $this->respondJson(['status' => true, 'message' => 'Episode unlocked successfully', 'coin_balance' => $newBalance, 'data' => ['coin_balance' => $newBalance]]);
        } catch (\Throwable $e) {
            $db->rollBack();
            $this->respondJson(['status' => false, 'error' => 'Transaction failed: ' . $e->getMessage()], 500);
        }
    }

    /** POST /api/v1/coins/purchase â€” registered users (JWT). */
    public function purchase() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respondJson(['status' => false, 'error' => 'Method Not Allowed'], 405);
        }

        try {
            $userId = $this->requireUserId();
            $input = json_decode(file_get_contents('php://input'), true);
            $package = $this->loadPackage((int)($input['package_id'] ?? 0));
            $settings = $this->paymentSettings();

            $db = Database::getInstance();
            $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            $email = $user['email'] ?? ('user_' . $userId . '@soloreel.tv');

            $keys = $this->activeKeys($settings);
            $payhubTxn = $this->initializeWithPayhub($settings, $keys['secret'], (float)$package['price'], $email);
            $reference = $payhubTxn['reference']; // Payhub's own reference — required so its checkout page recognizes the transaction and applies the correct sandbox/live mode.

            $stmt = $db->prepare("INSERT INTO payment_transactions (user_id, package_id, reference, amount, currency, status, coins_awarded) VALUES (?, ?, ?, ?, ?, 'pending', ?)");
            $stmt->execute([$userId, $package['id'], $reference, $package['price'], $package['currency'], $package['coins']]);

            $this->respondPaymentInit($package, $reference, $keys['public'], $email);
        } catch (\Throwable $e) {
            $this->respondJson(['status' => false, 'error' => 'Could not initiate payment: ' . $e->getMessage()], 500);
        }
    }

    /** POST /api/v1/coins/guest-purchase â€” guests identified by guest_id (no JWT). */
    public function guestPurchase() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respondJson(['status' => false, 'error' => 'Method Not Allowed'], 405);
        }

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $guestId = trim((string)($input['guest_id'] ?? ''));
            if ($guestId === '') {
                $this->respondJson(['status' => false, 'error' => 'guest_id is required'], 400);
            }

            $package = $this->loadPackage((int)($input['package_id'] ?? 0));
            $settings = $this->paymentSettings();

            $db = Database::getInstance();
            // Make sure the guest has a wallet to credit after payment.
            $stmt = $db->prepare("INSERT IGNORE INTO guest_wallets (guest_id, coin_balance) VALUES (?, 0)");
            $stmt->execute([$guestId]);

            $email = trim((string)($input['email'] ?? ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $email = 'guest_' . substr(md5($guestId), 0, 10) . '@soloreel.tv';
            }

            $keys = $this->activeKeys($settings);
            $payhubTxn = $this->initializeWithPayhub($settings, $keys['secret'], (float)$package['price'], $email);
            $reference = $payhubTxn['reference']; // Payhub's own reference — required so its checkout page recognizes the transaction and applies the correct sandbox/live mode.

            $stmt = $db->prepare("INSERT INTO payment_transactions (user_id, guest_id, package_id, reference, amount, currency, status, coins_awarded) VALUES (NULL, ?, ?, ?, ?, ?, 'pending', ?)");
            $stmt->execute([$guestId, $package['id'], $reference, $package['price'], $package['currency'], $package['coins']]);

            $this->respondPaymentInit($package, $reference, $keys['public'], $email);
        } catch (\Throwable $e) {
            $this->respondJson(['status' => false, 'error' => 'Could not initiate payment: ' . $e->getMessage()], 500);
        }
    }

    public function unlockWithAd(int $episodeId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->respondJson(['status' => false, 'error' => 'Method Not Allowed'], 405);
        }

        $userId = $this->optionalUserId();
        $guestId = $userId ? null : $this->guestIdFromRequest();
        if (!$userId && !$guestId) {
            $this->respondJson(['status' => false, 'error' => 'Unauthorized or invalid token'], 401);
        }

        $db = Database::getInstance();

        try {
            $stmt = $db->prepare("SELECT unlock_method FROM episodes WHERE id = ?");
            $stmt->execute([$episodeId]);
            $episode = $stmt->fetch();

            if (!$episode) {
                $this->respondJson(['status' => false, 'error' => 'Episode not found'], 404);
            }

            $unlockMethod = $episode['unlock_method'] ?? 'coins';
            if (!in_array($unlockMethod, ['ads', 'both'], true)) {
                $this->respondJson(['status' => false, 'error' => 'This episode cannot be unlocked with ads'], 400);
            }

            if ($userId) {
                $stmt = $db->prepare("INSERT IGNORE INTO user_unlocked_episodes (user_id, episode_id) VALUES (?, ?)");
                $stmt->execute([$userId, $episodeId]);
            } else {
                $stmt = $db->prepare("INSERT IGNORE INTO guest_unlocked_episodes (guest_id, episode_id) VALUES (?, ?)");
                $stmt->execute([$guestId, $episodeId]);
            }

            $this->respondJson(['status' => true, 'message' => 'Episode unlocked successfully with ad']);
        } catch (\Throwable $e) {
            $this->respondJson(['status' => false, 'error' => 'Failed to unlock episode: ' . $e->getMessage()], 500);
        }
    }

    /** GET /api/v1/payment/verify?reference=X â€” verifies with Payhub and credits coins. */
    public function verifyPayment() {
        $ref = trim((string)($_GET['reference'] ?? ''));
        if ($ref === '') {
            $this->respondJson(['status' => false, 'error' => 'No reference provided'], 400);
        }

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT * FROM payment_transactions WHERE reference = ? FOR UPDATE");
            $stmt->execute([$ref]);
            $txn = $stmt->fetch();

            if (!$txn) {
                $db->rollBack();
                $this->respondJson(['status' => false, 'error' => 'Transaction not found'], 404);
            }

            if ($txn['status'] === 'successful') {
                $db->rollBack();
                $this->respondJson(['status' => true, 'message' => 'Payment already verified', 'data' => $this->balanceFor($txn)]);
            }

            $stmt = $db->query("SELECT * FROM payment_settings LIMIT 1");
            $settings = $stmt->fetch();
            $secretKey = $settings ? $this->activeKeys($settings)['secret'] : '';
            if (!$settings || $secretKey === '') {
                $db->rollBack();
                $this->respondJson(['status' => false, 'error' => 'Payment gateway not configured'], 500);
            }
            $payhubBase = rtrim($settings['payhub_base_url'] ?: 'https://merchant.payhub.com.ng', '/');

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $payhubBase . "/api/transaction/verify/" . urlencode($ref));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $secretKey,
                "Cache-Control: no-cache",
            ]);
            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                $db->rollBack();
                $this->respondJson(['status' => false, 'error' => 'Could not reach payment gateway: ' . $err], 502);
            }

            $result = json_decode($response, true);
            $dataStatus = $result['data']['status'] ?? '';
            $verified = $result && ($result['status'] ?? false) === true
                && ($dataStatus === 'success' || $dataStatus === 'successful');

            if (!$verified) {
                $db->rollBack();
                $this->respondJson(['status' => false, 'error' => 'Payment not successful yet', 'gateway_status' => $dataStatus ?: 'unknown'], 402);
            }

            $stmt = $db->prepare("UPDATE payment_transactions SET status = 'successful' WHERE id = ?");
            $stmt->execute([$txn['id']]);

            if (($txn['type'] ?? 'coin_purchase') === 'ad_placement') {
                $campaignDays = (int)\App\Helpers\Site::getConfig('ad_campaign_days', 30);
                $stmt = $db->prepare("UPDATE custom_ads SET is_active = 1, payment_status = 'paid', expires_at = DATE_ADD(NOW(), INTERVAL ? DAY) WHERE id = ?");
                $stmt->execute([$campaignDays, $txn['ad_id']]);

                $db->commit();
                $this->respondJson(['status' => true, 'message' => 'Payment verified. Your ad is now live!', 'data' => ['ad_id' => (int)$txn['ad_id'], 'campaign_days' => $campaignDays]]);
            }

            $coins = (float)$txn['coins_awarded'];
            if (!empty($txn['user_id'])) {
                $stmt = $db->prepare("UPDATE users SET coin_balance = coin_balance + ? WHERE id = ?");
                $stmt->execute([$coins, $txn['user_id']]);
                $stmt = $db->prepare("INSERT INTO coin_transactions (user_id, type, amount, description, reference_id) VALUES (?, 'purchase', ?, ?, ?)");
                $stmt->execute([$txn['user_id'], $coins, 'Coin purchase via card', $ref]);
            } elseif (!empty($txn['guest_id'])) {
                $stmt = $db->prepare("INSERT INTO guest_wallets (guest_id, coin_balance) VALUES (?, ?) ON DUPLICATE KEY UPDATE coin_balance = coin_balance + VALUES(coin_balance)");
                $stmt->execute([$txn['guest_id'], $coins]);
            }

            $db->commit();
            $this->respondJson(['status' => true, 'message' => 'Payment verified. Coins added!', 'data' => $this->balanceFor($txn)]);
        } catch (\Throwable $e) {
            $db->rollBack();
            $this->respondJson(['status' => false, 'error' => 'Verification failed: ' . $e->getMessage()], 500);
        }
    }

    private function balanceFor(array $txn): array {
        $db = Database::getInstance();
        if (!empty($txn['user_id'])) {
            $stmt = $db->prepare("SELECT coin_balance FROM users WHERE id = ?");
            $stmt->execute([$txn['user_id']]);
            $row = $stmt->fetch();
            return ['coin_balance' => (float)($row['coin_balance'] ?? 0), 'coins_awarded' => (float)$txn['coins_awarded']];
        }
        if (!empty($txn['guest_id'])) {
            $stmt = $db->prepare("SELECT coin_balance FROM guest_wallets WHERE guest_id = ?");
            $stmt->execute([$txn['guest_id']]);
            $row = $stmt->fetch();
            return ['coin_balance' => (float)($row['coin_balance'] ?? 0), 'coins_awarded' => (float)$txn['coins_awarded']];
        }
        return ['coin_balance' => 0, 'coins_awarded' => (float)$txn['coins_awarded']];
    }
}
