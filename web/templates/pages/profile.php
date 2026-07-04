<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-white antialiased font-sans">

    <nav class="fixed w-full z-50 bg-black/80 backdrop-blur border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/"><?= \App\Helpers\Site::getLogoHtml() ?></a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/" class="text-gray-300 hover:text-white">Home</a>
                    <a href="/logout" class="bg-red-600 hover:bg-red-700 text-white font-bold py-1 px-4 rounded text-sm transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="pt-24 max-w-7xl mx-auto px-4 py-12">
        <h1 class="text-3xl font-bold mb-8">My Dashboard</h1>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-12">
            <!-- Profile Info & Wallet -->
            <div class="bg-gray-900 border border-gray-800 rounded-lg p-6 shadow-xl">
                <h2 class="text-xl font-bold mb-4 border-b border-gray-800 pb-2">Profile</h2>
                <p class="text-gray-400 mb-1 text-sm">Username</p>
                <p class="font-bold mb-4"><?= htmlspecialchars($user['username']) ?></p>
                <p class="text-gray-400 mb-1 text-sm">Email</p>
                <p class="font-bold mb-6"><?= htmlspecialchars($user['email']) ?></p>

                <h2 class="text-xl font-bold mb-4 border-b border-gray-800 pb-2">Wallet</h2>
                <div class="flex items-center gap-2 mb-4">
                    <svg class="w-8 h-8 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                    <p class="text-3xl font-bold text-white"><?= (float)$user['coin_balance'] ?></p>
                </div>
                <a href="/coin-shop" class="block w-full text-center bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded transition">Top Up Coins</a>
            </div>

            <!-- Virtual Bank Account -->
            <div class="md:col-span-2 bg-gray-900 border border-gray-800 rounded-lg p-6 shadow-xl flex flex-col justify-center text-center">
                <h2 class="text-2xl font-bold mb-2">Dedicated Virtual Account</h2>
                <p class="text-gray-400 mb-6"><?= htmlspecialchars($instruction) ?></p>

                <?php if($virtualAccount): ?>
                    <div class="bg-black border border-gray-700 p-6 rounded-lg inline-block mx-auto min-w-[300px]">
                        <p class="text-gray-500 text-sm mb-1 uppercase tracking-wider">Account Number</p>
                        <p class="text-4xl font-mono tracking-widest text-white mb-2"><?= htmlspecialchars($virtualAccount['account_number']) ?></p>
                        <p class="text-gray-400 font-bold"><?= htmlspecialchars($virtualAccount['bank_name']) ?></p>
                        <p class="text-xs text-gray-600 mt-4">Ref: <?= htmlspecialchars($virtualAccount['reference']) ?></p>
                    </div>
                <?php else: ?>
                    <div class="bg-black/50 p-6 rounded-lg inline-block mx-auto">
                        <p class="text-yellow-500 mb-2">Account setup pending.</p>
                        <a href="/coin-shop" class="text-red-500 hover:underline">Click here to generate your account</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Recent Watch History -->
            <div>
                <div class="flex justify-between items-center mb-4 border-b border-gray-800 pb-2">
                    <h2 class="text-xl font-bold">Continue Watching</h2>
                    <a href="/watch-history" class="text-sm text-red-500 hover:underline">View All</a>
                </div>
                <?php if(empty($watchHistory)): ?>
                    <p class="text-gray-500">No watch history available.</p>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach($watchHistory as $item): ?>
                            <a href="/episodes/<?= htmlspecialchars($item['series_slug']) ?>" class="flex items-center bg-gray-900 rounded-lg overflow-hidden border border-gray-800 hover:border-red-500 transition group">
                                <img src="<?= htmlspecialchars($item['thumbnail_url'] ?? '/assets/img/default-thumb.jpg') ?>" class="w-24 h-16 object-cover">
                                <div class="p-3">
                                    <h4 class="font-bold text-sm text-gray-200 group-hover:text-white"><?= htmlspecialchars($item['series_title']) ?></h4>
                                    <p class="text-xs text-gray-500"><?= htmlspecialchars($item['episode_title']) ?></p>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Transaction History -->
            <div>
                <div class="flex justify-between items-center mb-4 border-b border-gray-800 pb-2">
                    <h2 class="text-xl font-bold">Recent Transactions</h2>
                </div>
                <?php if(empty($coinHistory)): ?>
                    <p class="text-gray-500">No recent transactions.</p>
                <?php else: ?>
                    <div class="space-y-3">
                        <?php foreach($coinHistory as $txn): ?>
                            <div class="flex items-center justify-between bg-gray-900 p-3 rounded-lg border border-gray-800">
                                <div>
                                    <p class="text-sm font-bold text-gray-200"><?= htmlspecialchars($txn['description']) ?></p>
                                    <p class="text-xs text-gray-500"><?= date('M d, Y', strtotime($txn['created_at'])) ?></p>
                                </div>
                                <div class="font-bold <?= (float)$txn['amount'] > 0 ? 'text-green-500' : 'text-red-500' ?>">
                                    <?= (float)$txn['amount'] > 0 ? '+' : '' ?><?= (float)$txn['amount'] ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </main>
    <script src="/assets/js/protection.js"></script>
</body>
</html>
