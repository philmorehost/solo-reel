<?php
require_once "app/core/Database.php";
require_once "app/core/Config.php";
$db = \App\Core\Database::getInstance();
$stmt = $db->query("SELECT * FROM payment_settings LIMIT 1");
$settings = $stmt->fetch();
echo "Public Key: " . substr($settings['payhub_public_key'], 0, 12) . "...\n";
echo "Secret Key: " . substr($settings['payhub_secret_key'], 0, 12) . "...\n";
echo "Mode: " . $settings['mode'] . "\n";
echo "Base URL: " . $settings['payhub_base_url'] . "\n";
