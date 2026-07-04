<?php
require_once __DIR__ . "/app/core/Autoload.php";
require_once __DIR__ . '/app/core/Router.php';
require_once __DIR__ . '/app/core/Database.php';
require_once __DIR__ . "/app/core/Env.php";
\App\Core\Env::load(__DIR__ . "/.env");
require_once __DIR__ . '/app/core/Session.php';
require_once __DIR__ . '/app/core/Auth.php';

// Bootstrap
// Check if installed
if (!file_exists(__DIR__ . "/storage/install.lock")) {
    header("Location: /install/");
    die();
}

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

$router->dispatch($uri, $method);
