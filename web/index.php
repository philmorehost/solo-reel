<?php
require_once __DIR__ . "/app/core/Autoload.php";
require_once __DIR__ . '/app/core/Router.php';
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . "/app/core/Env.php";
\App\Core\Env::load(__DIR__ . "/.env");
require_once __DIR__ . "/app/middleware/SecurityMiddleware.php";
// Ensure DB connection does not break installer
if (file_exists(__DIR__ . "/storage/install.lock")) {
    \App\Middleware\SecurityMiddleware::checkBlacklist();
    require_once __DIR__ . "/app/middleware/AuthMiddleware.php";
    \App\Middleware\AuthMiddleware::checkVerified();
}
require_once __DIR__ . '/app/core/Session.php';
require_once __DIR__ . '/app/core/Auth.php';

// Bootstrap
// Check if installed
if (!file_exists(__DIR__ . "/storage/install.lock")) {
    header("Location: /install/");
    die();
}

\App\Core\Migrator::autoHeal();
\App\Core\Session::start();

// Handle API preflight CORS if needed
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    die();
}

$router = new \App\Core\Router();
require_once __DIR__ . '/app/config/routes.php';

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Safety net: an uncaught error/exception on an API route must never leak raw
// PHP error HTML (or an empty body) to the mobile apps — that breaks their
// JSON parser with errors like "malformed JSON at line 1 column 1". Any
// \Throwable not already caught by a controller gets turned into clean JSON.
$isApiRequest = str_starts_with($uri, '/api/');

if ($isApiRequest) {
    $emitJsonError = function (string $message, int $code = 500) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code($code);
        }
        echo json_encode(['status' => false, 'error' => $message]);
    };

    set_exception_handler(function (\Throwable $e) use ($emitJsonError) {
        error_log('Uncaught API exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
        $emitJsonError('Internal server error');
        exit;
    });

    set_error_handler(function ($severity, $message, $file, $line) {
        // Let PHP's own error_reporting filter decide relevance; log and continue
        // (don't throw for notices/warnings — only true fatals need the shutdown hook below).
        error_log("PHP error [$severity]: $message in $file:$line");
        return true;
    });

    register_shutdown_function(function () use ($emitJsonError) {
        $err = error_get_last();
        if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
            if (ob_get_level() > 0) {
                ob_clean();
            }
            error_log('Fatal API error: ' . $err['message'] . ' in ' . $err['file'] . ':' . $err['line']);
            $emitJsonError('Internal server error');
        }
    });
}

$router->dispatch($uri, $method);
