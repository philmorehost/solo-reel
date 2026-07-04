<?php
session_start();

if (file_exists(__DIR__ . '/../storage/install.lock')) {
    die("Installation already completed. Please remove /install/ directory.");
}

$step = $_GET['step'] ?? 1;

require_once __DIR__ . '/../app/core/SystemGate.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($step == 1) {
        $licenseKey = $_POST['license_key'] ?? '';
        if (\App\Core\SystemGate::verifyLicense($licenseKey)) {
            $_SESSION['install_license'] = $licenseKey;
            header("Location: ?step=2");
            die();
        } else {
            $error = "Invalid license key.";
        }
    } elseif ($step == 2) {
        $dbHost = $_POST['db_host'] ?? '127.0.0.1';
        $dbName = $_POST['db_name'] ?? '';
        $dbUser = $_POST['db_user'] ?? '';
        $dbPass = $_POST['db_pass'] ?? '';

        try {
            $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

            // Create .env file
            $env = "DB_HOST=$dbHost\nDB_NAME=$dbName\nDB_USER=$dbUser\nDB_PASS=$dbPass\nJWT_SECRET=" . bin2hex(random_bytes(32)) . "\n";
            file_put_contents(__DIR__ . '/../.env', $env);

            $_SESSION['install_db'] = [
                'host' => $dbHost,
                'name' => $dbName,
                'user' => $dbUser,
                'pass' => $dbPass
            ];

            header("Location: ?step=3");
            die();
        } catch (PDOException $e) {
            $error = "Database connection failed: " . $e->getMessage();
        }
    } elseif ($step == 3) {
        $adminEmail = $_POST['admin_email'];
        $adminUser = $_POST['admin_user'];
        $adminPass = $_POST['admin_pass'];
        $siteTitle = $_POST['site_title'];

        try {
            $dbConfig = $_SESSION['install_db'];
            $pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['name']}", $dbConfig['user'], $dbConfig['pass']);

            // Run schema
            $schemas = ['001_initial_schema_mysql.sql', '002_payment_gateway.sql', '003_add_seo_columns.sql', '004_video_queue.sql'];
            foreach ($schemas as $schema) {
                $sql = file_get_contents(__DIR__ . '/../schema/' . $schema);
                $pdo->exec($sql);
            }

            // Insert admin
            $hash = password_hash($adminPass, PASSWORD_ARGON2ID);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, role) VALUES (?, ?, ?, 'super_admin')");
            $stmt->execute([$adminUser, $adminEmail, $hash]);

            // Create lock file
            file_put_contents(__DIR__ . '/../storage/install.lock', date('Y-m-d H:i:s'));

            header("Location: ?step=4");
            die();

        } catch (Exception $e) {
            $error = "Installation failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SOLOREEL Installer - Step <?= htmlspecialchars($step) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center font-sans">
    <div class="max-w-xl w-full bg-white rounded-lg shadow-xl p-8 border-t-4 border-red-600">
        <h1 class="text-3xl font-bold text-center mb-6 text-gray-800">SOLOREEL Setup</h1>

        <?php if(isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if($step == 1): ?>
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Step 1: License Verification</h2>
            <div class="mb-4 text-sm text-gray-600">
                <p>PHP Version: <?= phpversion() ?> <?= version_compare(phpversion(), '8.0.0', '>=') ? '✅' : '❌' ?></p>
                <p>PDO Extension: <?= extension_loaded('pdo_mysql') ? '✅' : '❌' ?></p>
                <p>GD Extension: <?= extension_loaded('gd') ? '✅' : '❌' ?></p>
                <p>cURL Extension: <?= extension_loaded('curl') ? '✅' : '❌' ?></p>
            </div>
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-gray-700 font-bold mb-2">License Key</label>
                    <input type="text" name="license_key" required class="w-full border rounded px-3 py-2" placeholder="Get this from manager.pmhserver.name.ng">
                </div>
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">Verify & Continue</button>
            </form>

        <?php elseif($step == 2): ?>
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Step 2: Database Setup</h2>
            <form method="POST">
                <div class="mb-4"><label class="block font-bold mb-1">Database Host</label><input type="text" name="db_host" value="127.0.0.1" required class="w-full border rounded px-3 py-2"></div>
                <div class="mb-4"><label class="block font-bold mb-1">Database Name</label><input type="text" name="db_name" required class="w-full border rounded px-3 py-2"></div>
                <div class="mb-4"><label class="block font-bold mb-1">Database User</label><input type="text" name="db_user" required class="w-full border rounded px-3 py-2"></div>
                <div class="mb-4"><label class="block font-bold mb-1">Database Password</label><input type="password" name="db_pass" class="w-full border rounded px-3 py-2"></div>
                <button type="submit" class="w-full bg-red-600 text-white font-bold py-2 px-4 rounded">Connect Database</button>
            </form>

        <?php elseif($step == 3): ?>
            <h2 class="text-xl font-semibold mb-4 text-gray-700">Step 3: Admin Details</h2>
            <form method="POST">
                <div class="mb-4"><label class="block font-bold mb-1">Site Title</label><input type="text" name="site_title" value="SOLOREEL" required class="w-full border rounded px-3 py-2"></div>
                <div class="mb-4"><label class="block font-bold mb-1">Admin Username</label><input type="text" name="admin_user" required class="w-full border rounded px-3 py-2"></div>
                <div class="mb-4"><label class="block font-bold mb-1">Admin Email</label><input type="email" name="admin_email" required class="w-full border rounded px-3 py-2"></div>
                <div class="mb-4"><label class="block font-bold mb-1">Admin Password</label><input type="password" name="admin_pass" required class="w-full border rounded px-3 py-2"></div>
                <button type="submit" class="w-full bg-red-600 text-white font-bold py-2 px-4 rounded">Install System</button>
            </form>

        <?php elseif($step == 4): ?>
            <div class="text-center">
                <div class="text-green-500 mb-4">
                    <svg class="w-20 h-20 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <h2 class="text-2xl font-bold mb-4">Congratulations!</h2>
                <p class="text-gray-600 mb-6">SOLOREEL has been successfully installed.</p>
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 text-left">
                    <p class="text-sm text-yellow-700"><strong>Important Security Notice:</strong> Please delete the <code>/install</code> directory from your server immediately to prevent unauthorized access.</p>
                </div>
                <div class="flex gap-4 justify-center">
                    <a href="/" class="bg-gray-800 text-white font-bold py-2 px-6 rounded hover:bg-gray-700">Visit Site</a>
                    <a href="/login" class="bg-red-600 text-white font-bold py-2 px-6 rounded hover:bg-red-700">Login to Admin</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
