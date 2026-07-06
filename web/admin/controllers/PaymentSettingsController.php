<?php

namespace App\Admin\Controllers;

use App\Core\Database;

class PaymentSettingsController {
    public function index() {
        \App\Core\Session::start();
        if (\App\Core\Session::get('user_role') !== 'super_admin') {
            header("Location: /admin");
            die();
        }

        $db = Database::getInstance();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            \App\Core\Security::validateCsrfPost();
            $publicKeySandbox = trim($_POST['payhub_public_key_sandbox'] ?? '');
            $secretKeySandbox = trim($_POST['payhub_secret_key_sandbox'] ?? '');
            $publicKeyLive = trim($_POST['payhub_public_key_live'] ?? '');
            $secretKeyLive = trim($_POST['payhub_secret_key_live'] ?? '');
            $mode = $_POST['mode'] ?? 'sandbox';

            $stmt = $db->query("SELECT id FROM payment_settings LIMIT 1");
            if ($stmt->fetch()) {
                $stmt = $db->prepare("UPDATE payment_settings SET payhub_public_key_sandbox = ?, payhub_secret_key_sandbox = ?, payhub_public_key_live = ?, payhub_secret_key_live = ?, mode = ?");
                $stmt->execute([$publicKeySandbox, $secretKeySandbox, $publicKeyLive, $secretKeyLive, $mode]);
            } else {
                $stmt = $db->prepare("INSERT INTO payment_settings (payhub_public_key_sandbox, payhub_secret_key_sandbox, payhub_public_key_live, payhub_secret_key_live, mode) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$publicKeySandbox, $secretKeySandbox, $publicKeyLive, $secretKeyLive, $mode]);
            }

            \App\Core\Session::setFlash('success', 'Payment Settings updated successfully.');
            header("Location: /admin/settings/payments");
            die();
        }

        $stmt = $db->query("SELECT * FROM payment_settings LIMIT 1");
        $settings = $stmt->fetch() ?: [
            'payhub_public_key_sandbox' => '', 'payhub_secret_key_sandbox' => '',
            'payhub_public_key_live' => '', 'payhub_secret_key_live' => '',
            'mode' => 'sandbox'
        ];

        require __DIR__ . '/../templates/payment-settings.php';
    }
}
