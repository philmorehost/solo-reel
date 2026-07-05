<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;

class PaymentController {

    private function getPayhubKeys() {
        $db = Database::getInstance();
        $stmt = $db->query("SELECT * FROM payment_settings LIMIT 1");
        return $stmt->fetch();
    }

    public function verify() {
        $ref = $_GET['reference'] ?? '';
        if (!$ref) {
            Session::setFlash('error', 'No reference provided.');
            header("Location: /coin-shop");
            die();
        }

        $db = Database::getInstance();
        $db->beginTransaction(); // Start transaction early to prevent race conditions

        try {
            // Use FOR UPDATE to lock the row and prevent concurrent verification exploits
            $stmt = $db->prepare("SELECT * FROM payment_transactions WHERE reference = ? FOR UPDATE");
            $stmt->execute([$ref]);
            $txn = $stmt->fetch();

            if (!$txn) {
                $db->rollBack();
                Session::setFlash('error', 'Transaction not found.');
                header("Location: /coin-shop");
                die();
            }

            if ($txn['status'] === 'successful') {
                $db->rollBack();
                Session::setFlash('success', 'Payment already verified.');
                header("Location: /coin-shop");
                die();
            }

            // Actual Payhub Verification call
            $settings = $this->getPayhubKeys();
            if (!$settings || empty($settings['payhub_secret_key'])) {
                 $db->rollBack();
                 Session::setFlash('error', 'Payment gateway not configured.');
                 header("Location: /coin-shop");
                 die();
            }

            $baseUrl = !empty($settings['payhub_base_url']) ? rtrim($settings['payhub_base_url'], '/') : 'https://payhub.pmhserver.name.ng';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $baseUrl . "/api/transaction/verify/" . urlencode($ref));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $settings['payhub_secret_key'],
                "Cache-Control: no-cache",
            ]);
            $response = curl_exec($ch);
            $err = curl_error($ch);
            curl_close($ch);

            if ($err) {
                $db->rollBack();
                Session::setFlash('error', 'Curl Error checking transaction: ' . $err);
                header("Location: /coin-shop");
                die();
            }

            $result = json_decode($response, true);

            if ($result && isset($result['status']) && $result['status'] === true && $result['data']['status'] === 'success') {
                $stmt = $db->prepare("UPDATE payment_transactions SET status = 'successful' WHERE id = ?");
                $stmt->execute([$txn['id']]);

                // Credit wallet balance (user can then use wallet to buy coins)
                $stmt = $db->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                $stmt->execute([$txn['amount'], $txn['user_id']]);

                $stmt = $db->prepare("INSERT INTO coin_transactions (user_id, type, amount, description, reference_id) VALUES (?, 'wallet_topup', ?, ?, ?)");
                $stmt->execute([$txn['user_id'], $txn['amount'], 'Card payment - Wallet topped up', $ref]);

                $db->commit();

                $stmt = $db->prepare("SELECT coin_balance, wallet_balance FROM users WHERE id = ?");
                $stmt->execute([$txn['user_id']]);
                $u = $stmt->fetch();
                Session::set('user_coin_balance', (float)$u['coin_balance']);

                Session::setFlash('success', 'Payment successful! &#8358;' . number_format((float)$txn['amount'], 2) . ' added to wallet. Use your wallet to buy coins.');
                header("Location: /coin-shop");
                die();
            }

            $db->rollBack();
            Session::setFlash('error', 'Transaction was not successful.');
            header("Location: /coin-shop");
            die();
        } catch (\Exception $e) {
            $db->rollBack();
            Session::setFlash('error', 'Database error during verification.');
            header("Location: /coin-shop");
            die();
        }
    }

    public function webhook() {
        $payload = file_get_contents('php://input');
        $event = json_decode($payload, true);

        if (!$event || !isset($event['event'])) {
            http_response_code(400);
            die("Invalid payload");
        }

        $settings = $this->getPayhubKeys();

        // HMAC Signature Validation for Security
        $signature = $_SERVER['HTTP_X_PAYHUB_SIGNATURE'] ?? '';
        if (!$settings || empty($settings['payhub_secret_key'])) {
            http_response_code(500);
            die("Gateway not configured");
        }

        $expectedSignature = hash_hmac('sha512', $payload, $settings['payhub_secret_key']);
        if (!hash_equals($expectedSignature, $signature)) {
            http_response_code(401);
            die("Invalid signature");
        }

        // Handle virtual account deposit webhook
        if ($event['event'] === 'charge.success' && isset($event['data']['reference'])) {
            $ref = $event['data']['reference'];
            $amount = (float)($event['data']['amount'] ?? 0) / 100; // Assuming kobo/cents conversion

            $db = Database::getInstance();
            $db->beginTransaction();
            try {
                // Find user by reference logic depends on how VBA reference was generated
                // Typically payhub passes metadata or the account_number. Let's assume account_number is in metadata
                $accountNum = $event['data']['metadata']['account_number'] ?? '';

                $stmt = $db->prepare("SELECT user_id FROM virtual_bank_accounts WHERE account_number = ?");
                $stmt->execute([$accountNum]);
                $vba = $stmt->fetch();

                if ($vba) {
                    $userId = $vba['user_id'];

                    // Prevent duplicate processing
                    $stmt = $db->prepare("SELECT id FROM coin_transactions WHERE reference_id = ?");
                    $stmt->execute([$ref]);
                    if (!$stmt->fetch()) {
                        // Credit wallet balance for bank transfer deposits
                        $stmt = $db->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                        $stmt->execute([$amount, $userId]);

                        $stmt = $db->prepare("INSERT INTO coin_transactions (user_id, type, amount, description, reference_id) VALUES (?, 'wallet_topup', ?, ?, ?)");
                        $stmt->execute([$userId, $amount, 'Virtual Bank Deposit', $ref]);
                    }
                }
                $db->commit();
            } catch (\Exception $e) {
                $db->rollBack();
                http_response_code(500);
                die("Internal Error");
            }
        }

        http_response_code(200);
        echo json_encode(['status' => 'received']);
        die();
    }
}
