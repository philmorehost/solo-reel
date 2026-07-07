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

$db = Database::getInstance();
$stmt = $db->query("SELECT * FROM email_queue WHERE status = 'pending' LIMIT 50");
$emails = $stmt->fetchAll();

// Determine the sending domain once, matching BaseApiController::baseUrl()'s
// convention. getenv('HTTP_HOST') is always false under cron (no web server
// in the loop), so this used to silently fall back to a hardcoded
// 'soloreel.tv' — a domain this account's mail (Exim) isn't configured for,
// which is a likely cause of these emails bouncing or landing in spam.
$baseUrlEnv = getenv('BASE_URL') ?: getenv('APP_URL');
$domain = $baseUrlEnv ? parse_url($baseUrlEnv, PHP_URL_HOST) : null;
$domain = $domain ?: 'soloshort.pmhserver.name.ng';

foreach ($emails as $email) {
    // Simple built-in PHP mailer integration
    $to = $email['to_email'];
    $subject = $email['subject'];
    $message = $email['body_html'];

    // To send HTML mail, the Content-type header must be set
    $headers  = 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
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
