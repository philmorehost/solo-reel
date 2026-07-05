<?php
require_once "web/app/core/Database.php";
require_once "web/app/core/Config.php";
$db = \App\Core\Database::getInstance();
$stmt = $db->query("SELECT * FROM payment_settings LIMIT 1");
$settings = $stmt->fetch();
print_r($settings);
