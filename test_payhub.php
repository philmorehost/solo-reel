<?php
$keys = [
    'secret' => 'sk_live_1234567890'
];

// Try fetching virtual accounts
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://merchant.payhub.com.ng/api/virtual-accounts");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$headers = [
    "Authorization: Bearer " . $keys['secret']
];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
echo "GET /api/virtual-accounts: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . " - " . $response . "\n\n";

// Try POST to virtual accounts
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://merchant.payhub.com.ng/api/virtual-accounts");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['email' => 'test@test.com']));
$headers[] = "Content-Type: application/json";
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
echo "POST /api/virtual-accounts: " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . " - " . $response . "\n\n";
