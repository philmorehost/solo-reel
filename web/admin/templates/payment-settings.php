<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Settings - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <aside class="w-64 bg-gray-900 text-white flex-shrink-0 hidden md:flex flex-col">
            <div class="h-16 flex items-center justify-center border-b border-gray-800">
                <span class="text-xl font-bold text-red-500 tracking-tighter">SOLOREEL Admin</span>
            </div>
            <nav class="flex-1 overflow-y-auto py-4">
                <a href="/admin" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Dashboard</a>
                <a href="/admin/series" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Series</a>
                <a href="/admin/episodes" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Episodes</a>
                <a href="/admin/users" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Users</a>
                <a href="/admin/coins" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Coins Mgmt</a>
                <a href="/admin/settings" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Settings</a>
                <a href="/admin/settings/payments" class="block px-6 py-3 bg-gray-800 border-l-4 border-red-500">Payment Gateway</a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6">
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
