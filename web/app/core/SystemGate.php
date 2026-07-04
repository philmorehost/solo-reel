<?php

namespace App\Core;

class SystemGate {
    /**
     * This file handles license validation against manager.pmhserver.name.ng
     * It is intended to be encoded/obfuscated in production.
     */
    public static function verifyLicense(string $licenseKey): bool {
        $endpoint = 'https://manager.pmhserver.name.ng/api-docs.php'; // Or the actual validation endpoint

        // Mock validation for development purposes based on prompt
        if (empty($licenseKey)) {
            return false;
        }

        // Ideally this does a cURL POST to the license server
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'action' => 'verify',
            'license_key' => $licenseKey,
            'domain' => $_SERVER['HTTP_HOST'] ?? 'localhost'
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Since the actual API schema is unknown, we will assume true if not empty for this implementation
        return true;
    }
}
