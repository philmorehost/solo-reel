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
        $stmt = $db->prepare("SELECT * FROM payment_transactions WHERE reference = ?");
        $stmt->execute([$ref]);
        $txn = $stmt->fetch();

        if (!$txn) {
            Session::setFlash('error', 'Transaction not found.');
            header("Location: /coin-shop");
            die();
        }

        if ($txn['status'] === 'successful') {
            Session::setFlash('success', 'Payment already verified.');
            header("Location: /coin-shop");
            die();
        }

        // Actual Payhub Verification call
        $settings = $this->getPayhubKeys();
        if (!$settings || empty($settings['payhub_secret_key'])) {
             Session::setFlash('error', 'Payment gateway not configured.');
             header("Location: /coin-shop");
             die();
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://merchant.payhub.com.ng/api/transaction/verify/" . urlencode($ref));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . $settings['payhub_secret_key'],
            "Cache-Control: no-cache",
        ]);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            Session::setFlash('error', 'Curl Error checking transaction: ' . $err);
            header("Location: /coin-shop");
            die();
        }

        $result = json_decode($response, true);

        if ($result && isset($result['status']) && $result['status'] === true && $result['data']['status'] === 'success') {
            $db->beginTransaction();
            try {
                $stmt = $db->prepare("UPDATE payment_transactions SET status = 'successful' WHERE id = ?");
                $stmt->execute([$txn['id']]);

                $stmt = $db->prepare("UPDATE users SET coin_balance = coin_balance + ? WHERE id = ?");
                $stmt->execute([$txn['coins_awarded'], $txn['user_id']]);

                $stmt = $db->prepare("INSERT INTO coin_transactions (user_id, type, amount, description, reference_id) VALUES (?, 'purchase', ?, ?, ?)");
                $stmt->execute([$txn['user_id'], $txn['coins_awarded'], 'Purchased via Web', $ref]);

                $db->commit();

                $stmt = $db->prepare("SELECT coin_balance FROM users WHERE id = ?");
                $stmt->execute([$txn['user_id']]);
                Session::set('user_coin_balance', (float) $stmt->fetchColumn());

                Session::setFlash('success', 'Payment successful. Coins added!');
                header("Location: /coin-shop");
                die();
            } catch (\Exception $e) {
                $db->rollBack();
                Session::setFlash('error', 'Database error during verification.');
                header("Location: /coin-shop");
                die();
            }
        }

        Session::setFlash('error', 'Transaction was not successful.');
        header("Location: /coin-shop");
        die();
    }

    public function webhook() {
        $payload = file_get_contents('php://input');
        $event = json_decode($payload, true);

        if (!$event || !isset($event['event'])) {
            http_response_code(400);
            die("Invalid payload");
        }

        $settings = $this->getPayhubKeys();

        // Very basic webhook handling structure for virtual accounts deposit
        if ($event['event'] === 'charge.success' && isset($event['data']['reference'])) {
            // Process the virtual account deposit or checkout success here securely
            // For now, return 200 OK
            http_response_code(200);
            echo json_encode(['status' => 'received']);
            die();
        }

        http_response_code(200);
        die();
    }
}
