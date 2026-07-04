<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;

class CoinController {
    public function shop() {
        \App\Core\Auth::requireLogin();

        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM coin_packages WHERE is_active = 1 ORDER BY sort_order ASC");
        $packages = $stmt->fetchAll();

        // Ensure virtual account exists
        $userId = Session::get('user_id');
        $stmt = $db->prepare("SELECT * FROM virtual_bank_accounts WHERE user_id = ?");
        $stmt->execute([$userId]);
        $virtualAccount = $stmt->fetch();

        if (!$virtualAccount) {
            // Mocking virtual account generation logic
            $accNum = '90' . mt_rand(10000000, 99999999);
            $stmt = $db->prepare("INSERT INTO virtual_bank_accounts (user_id, account_number, bank_name, reference) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $accNum, 'Payhub Bank', uniqid('vba_')]);

            $stmt = $db->prepare("SELECT * FROM virtual_bank_accounts WHERE user_id = ?");
            $stmt->execute([$userId]);
            $virtualAccount = $stmt->fetch();
        }

        require __DIR__ . '/../../templates/pages/coin-shop.php';
    }

    public function unlock(int $episodeId) {
        \App\Core\Auth::requireLogin();
        $userId = Session::get('user_id');

        $db = Database::getInstance();
        $db->beginTransaction();

        try {
            // Get episode
            $stmt = $db->prepare("SELECT coin_cost, slug FROM episodes WHERE id = ? FOR UPDATE");
            $stmt->execute([$episodeId]);
            $episode = $stmt->fetch();

            if (!$episode) throw new \Exception("Episode not found");

            // Get user
            $stmt = $db->prepare("SELECT coin_balance FROM users WHERE id = ? FOR UPDATE");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if ((float)$user['coin_balance'] < (float)$episode['coin_cost']) {
                throw new \Exception("Insufficient coins");
            }

            // Deduct coins
            $newBalance = (float)$user['coin_balance'] - (float)$episode['coin_cost'];
            $stmt = $db->prepare("UPDATE users SET coin_balance = ? WHERE id = ?");
            $stmt->execute([$newBalance, $userId]);

            // Record unlock
            $stmt = $db->prepare("INSERT IGNORE INTO user_unlocked_episodes (user_id, episode_id) VALUES (?, ?)");
            $stmt->execute([$userId, $episodeId]);

            // Record transaction
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
}
