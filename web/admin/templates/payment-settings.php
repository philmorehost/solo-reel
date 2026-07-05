<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Settings - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel='stylesheet' href='/assets/css/admin-responsive.css'>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <?php require __DIR__ . "/partials/sidebar.php"; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6">
            <button onclick="toggleAdminSidebar()" class="admin-hamburger mr-3 p-2 text-gray-600 hover:text-gray-900"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                <h1 class="text-2xl font-semibold text-gray-800">Payhub Gateway Settings</h1>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">

                <?php $msg = \App\Core\Session::getFlash('success'); if($msg): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>

                <div class="bg-white rounded-lg shadow overflow-hidden p-6 max-w-2xl">
                    <form method="POST">
                        <?= \App\Core\Security::csrfField() ?>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Payhub Public Key</label>
                            <input type="text" name="payhub_public_key" value="<?= htmlspecialchars($settings['payhub_public_key'] ?? '') ?>" class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Payhub Secret Key</label>
                            <input type="password" name="payhub_secret_key" value="<?= htmlspecialchars($settings['payhub_secret_key'] ?? '') ?>" class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 font-bold mb-2">Mode</label>
                            <select name="mode" class="w-full border rounded px-3 py-2">
                                <option value="sandbox" <?= (isset($settings['mode']) && $settings['mode'] === 'sandbox') ? 'selected' : '' ?>>Sandbox</option>
                                <option value="live" <?= (isset($settings['mode']) && $settings['mode'] === 'live') ? 'selected' : '' ?>>Live</option>
                            </select>
                        </div>

                        <div class="mb-6 bg-blue-50 border border-blue-200 p-4 rounded text-sm text-blue-800">
                            <strong>Webhook URL:</strong> Set your webhook URL in the Payhub dashboard to: <br>
                            <code><?= (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/payment/webhook" ?></code>
                        </div>

                        <button type="submit" class="bg-indigo-600 text-white font-bold py-2 px-6 rounded hover:bg-indigo-700">
                            Save Payment Settings
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
