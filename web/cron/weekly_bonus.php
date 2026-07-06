<?php
/**
 * Weekly Bonus Cron Job
 * Run every Monday at 00:00: 0 0 * * 1 php /path/to/web/cron/weekly_bonus.php
 */
require_once __DIR__ . '/../app/core/Autoload.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Env.php';
\App\Core\Env::load(__DIR__ . '/../.env');

$db = \App\Core\Database::getInstance();

// Get weekly bonus amount from settings
$stmt = $db->prepare("SELECT setting_value FROM site_config WHERE setting_key = 'weekly_bonus_coins'");
$stmt->execute();
$cfg = $stmt->fetch();
$bonusAmount = (float)($cfg['setting_value'] ?? 50);

if ($bonusAmount <= 0) {
    echo "Weekly bonus is set to 0. Skipping.\n";
    exit;
}

$weekStart = date('Y-m-d', strtotime('monday this week'));
$expiresAt = date('Y-m-d H:i:s', strtotime('next monday'));

// Get all active non-admin users
$stmt = $db->query("SELECT id FROM users WHERE status = 'active' AND role = 'user'");
$users = $stmt->fetchAll();

$awarded = 0;
foreach ($users as $user) {
    try {
        // Skip if already awarded this week
        $checkStmt = $db->prepare("SELECT id FROM weekly_bonus_log WHERE user_id = ? AND week_start = ?");
        $checkStmt->execute([$user['id'], $weekStart]);
        if ($checkStmt->fetch()) continue;

        $db->beginTransaction();

        // Award bonus coins
        $db->prepare("UPDATE users SET bonus_coins = bonus_coins + ?, bonus_expires_at = ? WHERE id = ?")
           ->execute([$bonusAmount, $expiresAt, $user['id']]);

        // Log it
        $db->prepare("INSERT INTO weekly_bonus_log (user_id, coins_awarded, week_start, expires_at) VALUES (?, ?, ?, ?)")
           ->execute([$user['id'], $bonusAmount, $weekStart, $expiresAt]);

        // Coin transaction record
        $db->prepare("INSERT INTO coin_transactions (user_id, type, amount, description) VALUES (?, 'bonus', ?, ?)")
           ->execute([$user['id'], $bonusAmount, "Weekly bonus for week of $weekStart (expires $expiresAt)"]);

        $db->commit();
        $awarded++;
    } catch (Exception $e) {
        $db->rollBack();
        echo "Error for user {$user['id']}: {$e->getMessage()}\n";
    }
}

echo "Weekly bonus of {$bonusAmount} coins awarded to {$awarded} users.\n";
