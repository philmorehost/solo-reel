<?php

namespace App\Controllers;

use App\Core\Database;
use App\Core\Session;

class PaymentController {
    public function verify() {
        // Simplified verify callback
        $ref = $_GET['reference'] ?? '';
        if (!$ref) {
            die("No reference provided.");
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM payment_transactions WHERE reference = ?");
        $stmt->execute([$ref]);
        $txn = $stmt->fetch();

        if (!$txn) {
            die("Transaction not found.");
        }

        if ($txn['status'] === 'successful') {
            Session::setFlash('success', 'Payment already verified.');
            header("Location: /coin-shop");
            die();
        }

        // Mocking verification call to Payhub...
        // Assuming success
        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE payment_transactions SET status = 'successful' WHERE id = ?");
            $stmt->execute([$txn['id']]);

            $stmt = $db->prepare("UPDATE users SET coin_balance = coin_balance + ? WHERE id = ?");
            $stmt->execute([$txn['coins_awarded'], $txn['user_id']]);

            $stmt = $db->prepare("INSERT INTO coin_transactions (user_id, type, amount, description, reference_id) VALUES (?, 'purchase', ?, ?, ?)");
            $stmt->execute([$txn['user_id'], $txn['coins_awarded'], 'Purchased via Web', $ref]);

            $db->commit();

            // Update session
            $stmt = $db->prepare("SELECT coin_balance FROM users WHERE id = ?");
            $stmt->execute([$txn['user_id']]);
            Session::set('user_coin_balance', (float) $stmt->fetchColumn());

            Session::setFlash('success', 'Payment successful. Coins added!');
            header("Location: /coin-shop");
            die();
        } catch (\Exception $e) {
            $db->rollBack();
            die("Payment verification failed.");
        }
    }

    public function webhook() {
        // Handle webhook from Payhub for Virtual Account transfers
        $payload = file_get_contents('php://input');
        $event = json_decode($payload, true);

        if (!$event) {
            http_response_code(400);
            die("Invalid payload");
        }

        // Log webhook, verify signature, process deposit...
        http_response_code(200);
        echo json_encode(['status' => 'received']);
        die();
    }
}
