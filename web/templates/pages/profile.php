<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="/favicon.ico">
</head>
<body class="bg-black text-white antialiased font-sans">

    <nav class="fixed w-full z-50 bg-black/80 backdrop-blur border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/"><?= \App\Helpers\Site::getLogoHtml() ?></a>
                </div>
            </div>
        </div>
    </nav>

    <main class="pt-24 max-w-3xl mx-auto px-4 py-12">
        <div class="bg-gray-900 border border-gray-800 rounded-lg p-8 shadow-xl">
            <div class="flex items-center justify-between mb-8 border-b border-gray-800 pb-6">
                <h1 class="text-3xl font-bold">My Profile</h1>
                <a href="/logout" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition-colors text-sm">
                    Logout
                </a>
            </div>

            <div class="space-y-6">
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Username</label>
                    <p class="text-lg font-medium"><?= htmlspecialchars($user['username']) ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Email</label>
                    <p class="text-lg font-medium"><?= htmlspecialchars($user['email']) ?></p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1">Coin Balance</label>
                    <div class="flex items-center gap-2">
                        <svg class="w-6 h-6 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                        <p class="text-2xl font-bold text-white"><?= (float)$user['coin_balance'] ?></p>
                    </div>
                    <a href="/coin-shop" class="inline-block mt-2 text-sm text-red-500 hover:underline">Top Up Coins</a>
                </div>
            </div>
        </div>
    </main>
    <script src="/assets/js/protection.js"></script>
</body>
</html>
