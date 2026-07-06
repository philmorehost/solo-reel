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

        $virtualAccount = false;
        $instruction = '';
        $vbaAttempted = false;

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
            $keys = $settings ? \App\Core\PayhubKeys::active($settings) : ['public' => '', 'secret' => ''];

            if (!$settings || $keys['public'] === '' || $keys['secret'] === '') {
                $mode = $settings['mode'] ?? 'sandbox';
                Session::setFlash('error', "Card payment is not configured for {$mode} mode. Use Bank Transfer instead or contact support.");
                header("Location: /coin-shop");
                die();
            }

            $amount = $package['price'];

            // Register with Payhub itself so it recognizes the reference and
            // applies the correct sandbox/live mode at checkout (see PayhubKeys::initialize doc).
            $payhubTxn = \App\Core\PayhubKeys::initialize($settings, $keys['secret'], (float)$amount, $email);
            $reference = $payhubTxn['reference'];

            $stmt = $db->prepare("INSERT INTO payment_transactions (user_id, package_id, reference, amount, currency, status, coins_awarded) VALUES (?, ?, ?, ?, ?, 'pending', ?)");
            $stmt->execute([$userId, $package['id'], $reference, $amount, $package['currency'], $package['coins']]);

            $publicKey = $keys['public'];
            require __DIR__ . '/../../templates/pages/checkout.php';
            die();

        } catch (\Throwable $e) {
            Session::setFlash('error', 'Checkout error: ' . $e->getMessage());
            header("Location: /coin-shop");
            die();
        }
    }
}
