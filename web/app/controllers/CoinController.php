<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;
use App\Core\Security;

class CoinController {
    public function shop() {
        $userId = Session::get('user_id');
        $guestId = Session::getGuestId();

        $db = Database::getInstance();
        $packages = $db->query("SELECT * FROM coin_packages WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll();

        $walletBalance = 0;
        $coinsBalance = 0;
        
        if ($userId) {
            try {
                $stmt = $db->prepare("SELECT wallet_balance, coin_balance FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $userBal = $stmt->fetch();
                if ($userBal) {
                    $walletBalance = (float)($userBal['wallet_balance'] ?? 0);
                    $coinsBalance = (float)($userBal['coin_balance'] ?? 0);
                }
            } catch (\Throwable $e) {}
        } else {
            $stmt = $db->prepare("SELECT coin_balance FROM guest_wallets WHERE guest_id = ?");
            $stmt->execute([$guestId]);
            $guestBal = $stmt->fetch();
            if ($guestBal) {
                $coinsBalance = (float)($guestBal['coin_balance'] ?? 0);
            }
        }
        
        Session::set('user_coin_balance', $coinsBalance);

        $virtualAccount = false;
        $instruction = '';
        $vbaAttempted = false;

        require __DIR__ . '/../../templates/pages/coin-shop.php';
    }

    private function isAjax(): bool {
        return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
    }

    public function unlock(int $episodeId) {
        $isAjax = $this->isAjax();

        if ($isAjax) {
            // The reel feed unlocks episodes inline via fetch(); respond with JSON
            // instead of the redirect-based flow used by the no-JS form fallback.
            if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['status' => false, 'error' => 'Session expired. Please refresh and try again.']);
                die();
            }
        } else {
            Security::validateCsrfPost();
        }

        $userId = Session::get('user_id');
        $guestId = Session::getGuestId();

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            $stmt = $db->prepare("SELECT coin_cost, slug FROM episodes WHERE id = ? FOR UPDATE");
            $stmt->execute([$episodeId]);
            $episode = $stmt->fetch();

            if (!$episode) throw new \Exception("Episode not found");

            $newBalance = 0;
            if ($userId) {
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
            } else {
                $stmt = $db->prepare("SELECT coin_balance FROM guest_wallets WHERE guest_id = ? FOR UPDATE");
                $stmt->execute([$guestId]);
                $guestWallet = $stmt->fetch();

                if (!$guestWallet || (float)$guestWallet['coin_balance'] < (float)$episode['coin_cost']) {
                    throw new \Exception("Insufficient coins");
                }

                $newBalance = (float)$guestWallet['coin_balance'] - (float)$episode['coin_cost'];
                $stmt = $db->prepare("UPDATE guest_wallets SET coin_balance = ? WHERE guest_id = ?");
                $stmt->execute([$newBalance, $guestId]);

                $stmt = $db->prepare("INSERT IGNORE INTO guest_unlocked_episodes (guest_id, episode_id) VALUES (?, ?)");
                $stmt->execute([$guestId, $episodeId]);
            }

            $db->commit();
            Session::set('user_coin_balance', $newBalance);

            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => true, 'coin_balance' => $newBalance, 'episode_id' => $episodeId]);
                die();
            }

            header("Location: /episodes/" . $episode['slug']);
            die();

        } catch (\Exception $e) {
            $db->rollBack();

            if ($isAjax) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['status' => false, 'error' => $e->getMessage()]);
                die();
            }

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
            $guestId = Session::getGuestId();
            
            $email = Session::get('user_email');
            if (empty($email)) {
                $email = 'guest_' . substr(md5($guestId), 0, 10) . '@soloreel.tv';
            }

            // Check if user has sufficient wallet balance to buy directly (only for registered users)
            if ($userId) {
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

            $stmt = $db->prepare("INSERT INTO payment_transactions (user_id, guest_id, package_id, reference, amount, currency, status, coins_awarded) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)");
            $stmt->execute([$userId, $userId ? null : $guestId, $package['id'], $reference, $amount, $package['currency'], $package['coins']]);

            $publicKey = $keys['public'];
            $payhubBaseUrl = \App\Core\PayhubKeys::baseUrl($settings);
            require __DIR__ . '/../../templates/pages/checkout.php';
            die();

        } catch (\Throwable $e) {
            Session::setFlash('error', 'Checkout error: ' . $e->getMessage());
            header("Location: /coin-shop");
            die();
        }
    }
}
