<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\Security;

class CoinController {
    public function shop() {
        \App\Core\Auth::requireLogin();
        $userId = Session::get('user_id');

        $db = Database::getInstance();
        $packages = $db->query("SELECT * FROM coin_packages WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

        $walletBalance = 0;
        $coinsBalance = 0;
        try {
            $stmt = $db->prepare("SELECT wallet_balance, coin_balance FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userBal = $stmt->fetch();
            if ($userBal) {
                $walletBalance = (float)($userBal['wallet_balance'] ?? 0);
                $coinsBalance = (float)($userBal['coin_balance'] ?? 0);
            }
        } catch (\Throwable $e) {
            try {
                $stmt = $db->prepare("SELECT coin_balance FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $userBal = $stmt->fetch();
                $coinsBalance = (float)($userBal['coin_balance'] ?? 0);
            } catch (\Throwable $e2) {}
        }
        Session::set('user_coin_balance', $coinsBalance);

        $stmt = $db->prepare("SELECT * FROM virtual_bank_accounts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $virtualAccount = $stmt->fetch();

        $instruction = 'Transfer funds to the dedicated virtual account below to instantly fund your wallet.';

        $debugLog = [];
        $vbaAttempted = false;

        if (!$virtualAccount) {
            $debugLog[] = 'No existing VBA found for user ' . $userId . '. Attempting to create one.';
            $vbaAttempted = true;

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

                $debugLog[] = 'Payhub keys found. Calling VBA API for email=' . $email;

                $payload = json_encode([
                    'email' => $email,
                    'first_name' => $displayName,
                    'last_name' => $lastName ?: 'User',
                    'permanent' => true,
                ]);

                $baseUrl = !empty($settings['payhub_base_url']) ? rtrim($settings['payhub_base_url'], '/') : 'https://payhub.pmhserver.name.ng';

                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $baseUrl . '/api/virtual-accounts/initialize',
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
                $curlError = curl_error($ch);
                curl_close($ch);

                $result = json_decode($response, true);

                $debugLog[] = 'VBA API responded with HTTP ' . $httpCode;
                if ($curlError) $debugLog[] = 'cURL error: ' . $curlError;
                $debugLog[] = 'Response body: ' . (is_string($response) ? substr($response, 0, 500) : 'empty');

                if ($result && isset($result['status']) && $result['status'] === true && isset($result['data']['account_number'])) {
                    $accNum = $result['data']['account_number'];
                    $bankName = $result['data']['bank_name'] ?? 'Payhub Bank';
                    $ref = $result['data']['reference'] ?? uniqid('vba_');

                    $debugLog[] = 'VBA created: ' . $accNum . ' at ' . $bankName;

                    $stmt = $db->prepare("INSERT INTO virtual_bank_accounts (user_id, account_number, bank_name, reference) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$userId, $accNum, $bankName, $ref]);

                    $stmt = $db->prepare("SELECT * FROM virtual_bank_accounts WHERE user_id = ?");
                    $stmt->execute([$userId]);
                    $virtualAccount = $stmt->fetch();
                } else {
                    $debugLog[] = 'VBA API did not return success. Status: ' . ($result['status'] ?? 'N/A');
                    $debugLog[] = 'Message: ' . ($result['message'] ?? ($result['data']['message'] ?? 'No message'));
                }
            } else {
                $debugLog[] = 'No payment_settings found or payhub_secret_key is empty.';
                if (!$settings) $debugLog[] = 'payment_settings table empty';
                else $debugLog[] = 'payhub_secret_key is empty or not set';
            }
        } else {
            $debugLog[] = 'Existing VBA found: ' . $virtualAccount['account_number'];
        }

        if (!$virtualAccount) {
             $virtualAccount = [
                 'account_number' => 'Setup Required',
                 'bank_name' => 'Configuration required',
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

        try {
            Security::validateCsrfPost();

            $packageId = (int)($_POST['package_id'] ?? 0);
            if (!$packageId) {
                Session::setFlash('error', 'Invalid package selected.');
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

            $userId = Session::get('user_id');
            $email = Session::get('user_email');

            // Check if user has sufficient wallet balance to buy directly
            $stmt = $db->prepare("SELECT wallet_balance, coin_balance FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if ($user && (float)$user['wallet_balance'] >= (float)$package['price']) {
                // Buy with wallet balance
                $newWallet = (float)$user['wallet_balance'] - (float)$package['price'];
                $newCoins = (float)$user['coin_balance'] + (float)$package['coins'];
                $db->prepare("UPDATE users SET wallet_balance = ?, coin_balance = ? WHERE id = ?")->execute([$newWallet, $newCoins, $userId]);

                $stmt = $db->prepare("INSERT INTO coin_transactions (user_id, type, amount, description) VALUES (?, 'purchase', ?, 'Purchased with wallet')");
                $stmt->execute([$userId, $package['coins']]);

                Session::set('user_coin_balance', $newCoins);
                Session::setFlash('success', number_format((int)$package['coins']) . ' coins added to your account!');
                header("Location: /coin-shop");
                die();
            }

            // Process card payment through Payhub
            $stmt = $db->query("SELECT * FROM payment_settings LIMIT 1");
            $settings = $stmt->fetch();

            if (!$settings || empty($settings['payhub_public_key'])) {
                Session::setFlash('error', 'Card payment is not configured. Use Bank Transfer instead or contact support.');
                header("Location: /coin-shop");
                die();
            }

            $reference = 'trx_' . uniqid() . '_' . time();
            $amount = $package['price'];

            $stmt = $db->prepare("INSERT INTO payment_transactions (user_id, package_id, reference, amount, currency, status, coins_awarded) VALUES (?, ?, ?, ?, ?, 'pending', ?)");
            $stmt->execute([$userId, $package['id'], $reference, $amount, $package['currency'], $package['coins']]);

            $publicKey = $settings['payhub_public_key'];
            require __DIR__ . '/../../templates/pages/checkout.php';
            die();

        } catch (\Throwable $e) {
            Session::setFlash('error', 'Checkout error: ' . $e->getMessage());
            header("Location: /coin-shop");
            die();
        }
    }
}
