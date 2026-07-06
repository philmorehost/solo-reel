<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ad Pricing - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel='stylesheet' href='/assets/css/admin-responsive.css'>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <?php require __DIR__ . "/partials/sidebar.php"; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6">
            <button onclick="toggleAdminSidebar()" class="admin-hamburger mr-3 p-2 text-gray-600 hover:text-gray-900"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                <h1 class="text-2xl font-semibold text-gray-800">Ad Marketplace Pricing</h1>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <?php $msg = \App\Core\Session::getFlash('success'); if($msg): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>

                <p class="text-gray-600 mb-4 max-w-2xl">Set the price (NGN) users pay to run a self-serve banner ad, per on-screen duration and per placement. These prices are shown on the public <code>/advertise</code> page and via the app's Advertise screen.</p>

                <div class="bg-white rounded-lg shadow overflow-hidden p-6 max-w-3xl">
                    <form method="POST">
                        <?= \App\Core\Security::csrfField() ?>
                        <table class="min-w-full">
                            <thead>
                                <tr>
                                    <th class="text-left py-2 px-3 text-xs font-medium text-gray-500 uppercase">Duration</th>
                                    <th class="text-left py-2 px-3 text-xs font-medium text-gray-500 uppercase">Website Only</th>
                                    <th class="text-left py-2 px-3 text-xs font-medium text-gray-500 uppercase">App Only</th>
                                    <th class="text-left py-2 px-3 text-xs font-medium text-gray-500 uppercase">Website + App</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach (['5', '10', '15'] as $duration): ?>
                                <tr class="border-t">
                                    <td class="py-3 px-3 font-bold text-gray-700"><?= $duration ?> seconds</td>
                                    <?php foreach (['website', 'app', 'both'] as $placement): ?>
                                    <td class="py-3 px-3">
                                        <input type="number" step="0.01" min="0" name="price_<?= $duration ?>_<?= $placement ?>"
                                               value="<?= htmlspecialchars($prices[$duration][$placement] ?? '0.00') ?>"
                                               class="w-32 border rounded px-3 py-2">
                                    </td>
                                    <?php endforeach; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <button type="submit" class="mt-6 bg-indigo-600 text-white font-bold py-2 px-6 rounded hover:bg-indigo-700">
                            Save Pricing
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
