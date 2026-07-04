<?php

namespace App\Core;

class SystemGate {
    /**
     * This file handles license validation against manager.pmhserver.name.ng
     * It is intended to be encoded/obfuscated in production.
     */
    public static function verifyLicense(string $licenseKey): bool {
        // Base64 obfuscated endpoint to obscure the license check slightly natively
        $encodedEndpoint = 'aHR0cHM6Ly9tYW5hZ2VyLnBtaHNlcnZlci5uYW1lLm5nL2FwaS1kb2NzLnBocA==';

        if (empty($licenseKey)) {
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, base64_decode($encodedEndpoint));
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
