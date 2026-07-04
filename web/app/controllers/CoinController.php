<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\Security;

class CoinController {
    public function shop() {
        \App\Core\Auth::requireLogin();

        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM coin_packages WHERE is_active = 1 ORDER BY sort_order ASC");
        $packages = $stmt->fetchAll();

        $userId = Session::get('user_id');
        $stmt = $db->prepare("SELECT * FROM virtual_bank_accounts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $virtualAccount = $stmt->fetch();

        $stmtConfig = $db->query("SELECT setting_value FROM site_config WHERE setting_key = 'bank_transfer_instruction'");
        $instruction = $stmtConfig->fetchColumn() ?: 'Transfer funds to the dedicated virtual account below to instantly fund your wallet.';

        if (!$virtualAccount) {
            // Live Payhub Virtual Account Integration
            $stmtSet = $db->query("SELECT * FROM payment_settings LIMIT 1");
            $settings = $stmtSet->fetch();

            if ($settings && !empty($settings['payhub_secret_key'])) {
                $email = Session::get('user_email');
                $name = Session::get('user_name');
                $displayName = $name;
                $lastName = '';
                if (str_contains($name, ' ')) {
                    [$displayName, $lastName] = explode(' ', $name, 2);
                }

                $payload = json_encode([
                    'email' => $email,
                    'first_name' => $displayName,
                    'last_name' => $lastName ?: 'User',
                    'permanent' => true,
                ]);

                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => 'https://merchant.payhub.com.ng/api/virtual-accounts/initialize',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $payload,
                    CURLOPT_TIMEOUT => 15,
                    CURLOPT_HTTPHEADER => [
                        'Authorization: Bearer ' . $settings['payhub_secret_key'],
                        'Content-Type: application/json',
                        'Accept: application/json',
                    ],
                ]);
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $result = json_decode($response, true);

                if ($result && isset($result['status']) && $result['status'] === true && isset($result['data']['account_number'])) {
                    $accNum = $result['data']['account_number'];
                    $bankName = $result['data']['bank_name'] ?? 'Payhub Bank';
                    $ref = $result['data']['reference'] ?? uniqid('vba_');

                    $stmt = $db->prepare("INSERT INTO virtual_bank_accounts (user_id, account_number, bank_name, reference) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$userId, $accNum, $bankName, $ref]);

                    $stmt = $db->prepare("SELECT * FROM virtual_bank_accounts WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    $virtualAccount = $stmt->fetch();
                }
            }
        }

        // If Payhub API failed or not configured, fallback to mock to prevent errors on UI
        if (!$virtualAccount) {
             $virtualAccount = [
                 'account_number' => 'Setup Required',
                 'bank_name' => 'Please ask Admin to configure Payhub keys.',
                 'reference' => 'N/A'
             ];
        }

        require __DIR__ . '/../../templates/pages/coin-shop.php';
    }

    public function unlock(int $episodeId) {
        \App\Core\Auth::requireLogin();
        Security::validateCsrfPost();
        $userId = Session::get('user_id');

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT coin_cost, slug FROM episodes WHERE id = ? FOR UPDATE");
            $stmt->execute([$episodeId]);
            $episode = $stmt->fetch();

            if (!$episode) throw new \Exception("Episode not found");

            $stmt = $db->prepare("SELECT coin_balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if ((float)$user['coin_balance'] < (float)$episode['coin_cost']) {
                throw new \Exception("Insufficient coins");
            }

            $newBalance = (float)$user['coin_balance'] - (float)$episode['coin_cost'];
            $stmt = $db->prepare("UPDATE users SET coin_balance = ? WHERE id = ?");
            $stmt->execute([$newBalance, $userId]);

            $stmt = $db->prepare("INSERT IGNORE INTO user_unlocked_episodes (user_id, episode_id) VALUES (?, ?)");
            $stmt->execute([$userId, $episodeId]);

            $stmt = $db->prepare("INSERT INTO coin_transactions (user_id, type, amount, description) VALUES (?, 'unlock', ?, ?)");
            $stmt->execute([$userId, -$episode['coin_cost'], 'Unlocked episode ' . $episodeId]);

            $db->commit();
            Session::set('user_coin_balance', $newBalance);

            header("Location: /episodes/" . $episode['slug']);
            die();

        } catch (\Exception $e) {
            $db->rollBack();
            Session::setFlash('error', $e->getMessage());
            if (isset($episode['slug'])) {
                header("Location: /episodes/" . $episode['slug']);
            } else {
                header("Location: /");
            }
            die();
        }
    }

    public function purchase() {
        \App\Core\Auth::requireLogin();
        Security::validateCsrfPost();

        $packageId = $_POST['package_id'] ?? 0;
        if (!$packageId) {
            Session::setFlash('error', 'Invalid package.');
            header("Location: /coin-shop");
            die();
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM coin_packages WHERE id = ? AND is_active = 1");
        $stmt->execute([$packageId]);
        $package = $stmt->fetch();

        if (!$package) {
            Session::setFlash('error', 'Package not available.');
            header("Location: /coin-shop");
            die();
        }

        $stmt = $db->query("SELECT * FROM payment_settings LIMIT 1");
        $settings = $stmt->fetch();

        if (!$settings || empty($settings['payhub_public_key'])) {
             Session::setFlash('error', 'Payment gateway is not configured.');
             header("Location: /coin-shop");
             die();
        }

        $userId = Session::get('user_id');
        $email = Session::get('user_email');
        $reference = 'trx_' . uniqid() . '_' . time();
        $amount = $package['price'];

        $stmt = $db->prepare("INSERT INTO payment_transactions (user_id, package_id, reference, amount, currency, status, coins_awarded) VALUES (?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->execute([$userId, $package['id'], $reference, $amount, $package['currency'], $package['coins']]);

        $publicKey = $settings['payhub_public_key'];
        require __DIR__ . '/../../templates/pages/checkout.php';
        die();
    }
}
