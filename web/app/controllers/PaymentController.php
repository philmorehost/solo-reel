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
            $secretKey = trim($settings['payhub_secret_key'] ?? '');
            if (!$settings || empty($secretKey)) {
                 $db->rollBack();
                 Session::setFlash('error', 'Payment gateway not configured.');
                 header("Location: /coin-shop");
                 die();
            }
            $baseUrl = 'https://merchant.payhub.com.ng';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $baseUrl . "/api/transaction/verify/" . urlencode($ref));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "Authorization: Bearer " . $secretKey,
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

            if ($result && isset($result['status']) && $result['status'] === true) {
                $dataStatus = $result['data']['status'] ?? '';
                if ($dataStatus === 'success' || $dataStatus === 'successful') {
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
            }

            $db->rollBack();
            $debugResponse = is_string($response) ? substr($response, 0, 300) : 'Invalid Response';
            Session::setFlash('error', 'Transaction was not successful. Status: ' . ($result['data']['status'] ?? 'unknown') . ' | Debug: ' . htmlspecialchars($debugResponse));
            header("Location: /coin-shop");
            die();
        } catch (\Exception $e) {
            $db->rollBack();
            Session::setFlash('error', 'Database error during verification.');
            header("Location: /coin-shop");
            die();
        }
    }

    /**
     * GET /pay/checkout?reference=X
     * Hosted checkout for the mobile apps. Looks up the pending transaction by
     * reference (no session required) and renders the Payhub inline popup.
     */
    public function mobileCheckout() {
        $reference = trim((string)($_GET['reference'] ?? ''));
        if ($reference === '') {
            http_response_code(400);
            die('Missing payment reference.');
        }

        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM payment_transactions WHERE reference = ?");
        $stmt->execute([$reference]);
        $txn = $stmt->fetch();

        if (!$txn) {
            http_response_code(404);
            die('Transaction not found.');
        }
        if ($txn['status'] === 'successful') {
            header("Location: /pay/verify?reference=" . urlencode($reference));
            die();
        }

        $settings = $this->getPayhubKeys();
        $publicKey = trim($settings['payhub_public_key'] ?? '');
        if (!$settings || $publicKey === '') {
            http_response_code(500);
            die('Payment gateway is not configured.');
        }

        // Resolve a payer email: registered user's email or a stable guest alias.
        if (!empty($txn['user_id'])) {
            $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
            $stmt->execute([$txn['user_id']]);
            $u = $stmt->fetch();
            $email = $u['email'] ?? ('user_' . $txn['user_id'] . '@soloreel.tv');
        } else {
            $email = 'guest_' . substr(md5((string)($txn['guest_id'] ?? $reference)), 0, 10) . '@soloreel.tv';
        }

        $amount = (float)$txn['amount'];
        require __DIR__ . '/../../templates/pages/checkout-mobile.php';
        die();
    }

    /**
     * GET /pay/verify?reference=X
     * Post-payment landing for the mobile checkout. The apps intercept this URL
     * in their WebView before it loads; if it does load (fallback), it verifies
     * via the JSON API and shows a friendly result page.
     */
    public function mobileVerify() {
        $reference = trim((string)($_GET['reference'] ?? ''));
        require __DIR__ . '/../../templates/pages/checkout-mobile-result.php';
        die();
    }

    /** GET /pay/closed — shown when the user closes the checkout without paying. */
    public function mobileClosed() {
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html><head><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Payment Cancelled</title></head>'
           . '<body style="background:#000;color:#fff;font-family:sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;text-align:center;margin:0">'
           . '<div><div style="font-size:56px">&#128683;</div><h2 style="font-size:20px">Payment Cancelled</h2>'
           . '<p style="color:#9ca3af;font-size:14px">No charge was made. Close this window to return to the app.</p></div></body></html>';
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
        $secretKey = trim($settings['payhub_secret_key'] ?? '');

        // HMAC Signature Validation for Security
        $signature = $_SERVER['HTTP_X_PAYHUB_SIGNATURE'] ?? '';
        if (!$settings || empty($secretKey)) {
            http_response_code(500);
            die("Gateway not configured");
        }

        $expectedSignature = hash_hmac('sha512', $payload, $secretKey);
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
