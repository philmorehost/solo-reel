<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Management - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel='stylesheet' href='/assets/css/admin-responsive.css'>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <?php require __DIR__ . "/partials/sidebar.php"; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6">
            <button onclick="toggleAdminSidebar()" class="admin-hamburger mr-3 p-2 text-gray-600 hover:text-gray-900"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                <h1 class="text-2xl font-semibold text-gray-800">Security Access Rules</h1>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <?php $msg = \App\Core\Session::getFlash('success'); if($msg): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>
                <?php $err = \App\Core\Session::getFlash('error'); if($err): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?= htmlspecialchars($err) ?></div>
                <?php endif; ?>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Whitelist -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-xl font-bold mb-4 text-green-600">IP Whitelist (Admin Access)</h2>
                        <form action="/admin/security/add-ip" method="POST" class="flex flex-wrap gap-2 mb-6">
                            <?= \App\Core\Security::csrfField() ?>
                            <input type="hidden" name="type" value="whitelist">
                            <input type="text" name="ip_address" placeholder="e.g. 192.168.1.1" required class="border rounded px-3 py-2 flex-1 text-sm">
                            <input type="text" name="description" placeholder="Description" class="border rounded px-3 py-2 flex-1 text-sm">
                            <button type="submit" class="bg-green-600 text-white font-bold px-4 py-2 rounded hover:bg-green-700">Add</button>
                        </form>
                        <ul class="divide-y divide-gray-200">
                            <?php foreach($whitelist as $ip): ?>
                                <li class="py-3 flex justify-between items-center">
                                    <div><span class="font-mono"><?= htmlspecialchars($ip['ip_address']) ?></span> <span class="text-sm text-gray-500 ml-2"><?= htmlspecialchars($ip['description']) ?></span></div>
                                    <form action="/admin/security/remove-ip" method="POST" onsubmit="return confirm('Remove this IP?');">
                                        <?= \App\Core\Security::csrfField() ?>
                                        <input type="hidden" name="type" value="whitelist">
                                        <input type="hidden" name="id" value="<?= $ip['id'] ?>">
                                        <button type="submit" class="text-red-500 hover:text-red-700 text-sm">Remove</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Blacklist -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-xl font-bold mb-4 text-red-600">IP Blacklist (Global Block)</h2>
                        <form action="/admin/security/add-ip" method="POST" class="flex flex-wrap gap-2 mb-6">
                            <?= \App\Core\Security::csrfField() ?>
                            <input type="hidden" name="type" value="blacklist">
                            <input type="text" name="ip_address" placeholder="e.g. 192.168.1.1" required class="border rounded px-3 py-2 flex-1 text-sm">
                            <input type="text" name="description" placeholder="Description" class="border rounded px-3 py-2 flex-1 text-sm">
                            <button type="submit" class="bg-red-600 text-white font-bold px-4 py-2 rounded hover:bg-red-700">Add</button>
                        </form>
                        <ul class="divide-y divide-gray-200">
                            <?php foreach($blacklist as $ip): ?>
                                <li class="py-3 flex justify-between items-center">
                                    <div><span class="font-mono"><?= htmlspecialchars($ip['ip_address']) ?></span> <span class="text-sm text-gray-500 ml-2"><?= htmlspecialchars($ip['description']) ?></span></div>
                                    <form action="/admin/security/remove-ip" method="POST" onsubmit="return confirm('Remove this IP?');">
                                        <?= \App\Core\Security::csrfField() ?>
                                        <input type="hidden" name="type" value="blacklist">
                                        <input type="hidden" name="id" value="<?= $ip['id'] ?>">
                                        <button type="submit" class="text-red-500 hover:text-red-700 text-sm">Remove</button>
                                    </form>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
