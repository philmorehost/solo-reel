<?php

require_once __DIR__ . "/../app/core/Env.php";
\App\Core\Env::load(__DIR__ . "/../.env");

require_once __DIR__ . '/../app/core/Database.php';

use App\Core\Database;

if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.");
}

$db = Database::getInstance();
$stmt = $db->query("SELECT * FROM email_queue WHERE status = 'pending' LIMIT 50");
$emails = $stmt->fetchAll();

foreach ($emails as $email) {
    // In a real scenario we'd use PHPMailer here and actually send.
    // This is mocked for implementation structure.
    $sent = true;

    if ($sent) {
        $update = $db->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?");
        $update->execute([$email['id']]);
    } else {
        $update = $db->prepare("UPDATE email_queue SET status = 'failed', error_message = 'Failed to send' WHERE id = ?");
        $update->execute([$email['id']]);
    }
}

echo "Processed " . count($emails) . " emails.\n";
