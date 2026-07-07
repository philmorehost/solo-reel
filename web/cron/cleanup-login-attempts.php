<?php

require_once __DIR__ . "/../app/core/Env.php";
\App\Core\Env::load(__DIR__ . "/../.env");
require_once __DIR__ . '/../app/core/Database.php';

use App\Core\Database;

// This doesn't just check php_sapi_name() === 'cli': this host's cron runs a
// CGI-flavored php binary, so a strict 'cli' check silently kills every cron
// run. REQUEST_METHOD is only ever set for real HTTP requests, never for
// cron, regardless of SAPI.
if (php_sapi_name() !== 'cli' && isset($_SERVER['REQUEST_METHOD'])) {
    die("This script can only be run from the command line.");
}

// Retention only — the lockout WINDOW (site_config.lockout_duration_minutes)
// is checked live by App\Core\BruteForce on every login attempt; this just
// keeps the audit table from growing forever.
$retentionDays = 30;

$db = Database::getInstance();
$stmt = $db->prepare("DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL ? DAY)");
$stmt->execute([$retentionDays]);

echo "Deleted {$stmt->rowCount()} login attempt record(s) older than {$retentionDays} days.\n";
