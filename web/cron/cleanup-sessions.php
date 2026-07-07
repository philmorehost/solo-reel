<?php

require_once __DIR__ . "/../app/core/Env.php";
\App\Core\Env::load(__DIR__ . "/../.env");

// This doesn't just check php_sapi_name() === 'cli': this host's cron runs a
// CGI-flavored php binary, so a strict 'cli' check silently kills every cron
// run. REQUEST_METHOD is only ever set for real HTTP requests, never for
// cron, regardless of SAPI.
if (php_sapi_name() !== 'cli' && isset($_SERVER['REQUEST_METHOD'])) {
    die("This script can only be run from the command line.");
}

// Only needed if the host disables PHP's own automatic session garbage
// collection (session.gc_probability = 0), which some shared cPanel hosts do
// for performance. session_gc() runs the same cleanup PHP would normally
// trigger itself, respecting session.gc_maxlifetime — safer than manually
// walking the session save path, which may be shared with other applications
// on the same host.
session_start();
$deleted = session_gc();
session_write_close();

echo $deleted !== false
    ? "Session cleanup removed {$deleted} expired session(s).\n"
    : "Session cleanup ran (this PHP version does not report a count).\n";
