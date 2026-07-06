<?php

namespace App\Core;

/**
 * Picks the sandbox or live Payhub key pair based on `payment_settings.mode`.
 * Shared by both the mobile API (Api\TransactionController) and the web
 * checkout (CoinController/PaymentController) so the two flows can't drift
 * out of sync on how "mode" is interpreted.
 */
class PayhubKeys {
    public static function active(array $settings): array {
        $mode = $settings['mode'] ?? 'sandbox';
        if ($mode === 'live') {
            $public = trim($settings['payhub_public_key_live'] ?? '');
            $secret = trim($settings['payhub_secret_key_live'] ?? '');
        } else {
            $public = trim($settings['payhub_public_key_sandbox'] ?? '');
            $secret = trim($settings['payhub_secret_key_sandbox'] ?? '');
        }
        // Fall back to the legacy single-key columns (pre-v2.3 data).
        if ($public === '') $public = trim($settings['payhub_public_key'] ?? '');
        if ($secret === '') $secret = trim($settings['payhub_secret_key'] ?? '');
        return ['public' => $public, 'secret' => $secret];
    }

    /**
     * Registers the transaction with Payhub itself (server-to-server) before
     * showing checkout. Payhub decides sandbox vs live per-transaction from
     * which secret key (sk_test_/sk_live_) initialized it — without this call,
     * Payhub never learns about the transaction and always renders live/Paystack
     * regardless of what mode is configured here.
     *
     * @return array Payhub's response `data` (contains `reference`, `authorization_url`, ...)
     * @throws \RuntimeException on network failure or a rejected transaction
     */
    public static function initialize(array $settings, string $secretKey, float $amount, string $email): array {
        $payhubBase = rtrim($settings['payhub_base_url'] ?: 'https://merchant.payhub.com.ng', '/');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $payhubBase . '/api/transaction/initialize');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $secretKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => $email, 'amount' => $amount]));
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            throw new \RuntimeException('Could not reach payment gateway: ' . $err);
        }
        $result = json_decode($response, true);
        if (!$result || ($result['status'] ?? false) !== true || empty($result['data']['reference'])) {
            throw new \RuntimeException('Payment gateway rejected the transaction.');
        }
        return $result['data'];
    }
}
