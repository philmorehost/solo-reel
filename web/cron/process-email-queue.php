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
    // Determine the host for sending
    $domain = getenv('HTTP_HOST') ?: 'soloreel.tv';

    // Simple built-in PHP mailer integration
    $to = $email['to_email'];
    $subject = $email['subject'];
    $message = $email['body_html'];

    // To send HTML mail, the Content-type header must be set
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
    $headers .= 'From: SOLOREEL <noreply@' . $domain . '>' . "\r\n";

    // Send email using PHP's native mail function
    // For cPanel environments, this usually hooks straight into Exim
    $sent = mail($to, $subject, $message, $headers);

    if ($sent) {
        $update = $db->prepare("UPDATE email_queue SET status = 'sent', sent_at = NOW() WHERE id = ?");
        $update->execute([$email['id']]);
    } else {
        $update = $db->prepare("UPDATE email_queue SET status = 'failed', error_message = 'Native mail() function failed' WHERE id = ?");
        $update->execute([$email['id']]);
    }
}

echo "Processed " . count($emails) . " emails.\n";
