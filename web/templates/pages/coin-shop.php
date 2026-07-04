<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coin Shop - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-white antialiased font-sans">

    <nav class="w-full bg-gray-900 border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="/"><?= \App\Helpers\Site::getLogoHtml() ?></a>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                    <span class="font-bold"><?= \App\Core\Session::get('user_coin_balance') ?> Coins</span>
                </div>
                <a href="/profile" class="text-gray-400 hover:text-white text-sm">Dashboard</a>
            </div>
        </div>
    </nav>

    <main class="max-w-4xl mx-auto px-4 py-12">
        <h1 class="text-3xl font-bold mb-8 text-center">Top Up Your Coins</h1>

        <?php $error = \App\Core\Session::getFlash('error'); if($error): ?>
            <div class="bg-red-500/10 border border-red-500 text-red-500 p-3 rounded mb-8 text-center text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php $success = \App\Core\Session::getFlash('success'); if($success): ?>
            <div class="bg-green-500/10 border border-green-500 text-green-500 p-3 rounded mb-8 text-center text-sm">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- Virtual Account Info -->
        <div class="bg-gray-800 rounded-lg p-6 mb-10 text-center border border-gray-700 shadow-xl">
            <h2 class="text-lg text-gray-300 mb-2">Your Dedicated Virtual Account</h2>
            <p class="text-sm text-gray-400 mb-4"><?= htmlspecialchars($instruction) ?></p>
            <?php if($virtualAccount && $virtualAccount['account_number'] !== 'Setup Required'): ?>
                <div class="bg-black border border-gray-700 p-6 rounded-lg inline-block mx-auto min-w-[300px]">
                    <p class="text-gray-500 text-sm mb-1 uppercase tracking-wider">Account Number</p>
                    <p class="text-4xl font-mono tracking-widest text-white mb-2"><?= htmlspecialchars($virtualAccount['account_number']) ?></p>
                    <p class="text-gray-400 font-bold"><?= htmlspecialchars($virtualAccount['bank_name']) ?></p>
                    <p class="text-xs text-gray-600 mt-4">Ref: <?= htmlspecialchars($virtualAccount['reference']) ?></p>
                </div>
            <?php else: ?>
                <div class="bg-red-900/50 text-red-200 border border-red-800 p-6 rounded-lg inline-block mx-auto">
                    <p class="font-bold mb-2">Account setup pending or Payhub keys not configured.</p>
                    <p class="text-sm">Please contact support.</p>
                </div>
            <?php endif; ?>
        </div>

        <h2 class="text-2xl font-bold mb-6 text-center">Or Buy Instantly via Card</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <?php foreach($packages as $pkg): ?>
                <div class="bg-gray-900 border border-gray-800 rounded-lg p-6 text-center hover:scale-105 transition cursor-pointer relative overflow-hidden flex flex-col h-full shadow-lg">
                    <!-- Package Color Shield / Banner -->
                    <div class="absolute top-0 left-0 right-0 h-2" style="background-color: <?= htmlspecialchars($pkg['color_code'] ?? '#dc2626') ?>;"></div>

                    <h3 class="text-xl font-bold mb-4 mt-2 text-white"><?= htmlspecialchars($pkg['name']) ?></h3>

                    <div class="text-yellow-500 mb-2 flex justify-center items-center gap-1">
                        <svg class="w-8 h-8" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                        <span class="text-3xl font-bold text-white"><?= (int)$pkg['coins'] ?></span>
                    </div>

                    <p class="text-gray-400 mb-6 font-medium text-lg flex-grow flex items-center justify-center"><?= htmlspecialchars($pkg['currency']) ?> <?= number_format($pkg['price'], 2) ?></p>

                    <form action="/coins/purchase" method="POST" class="mt-auto">
                        <?= \App\Core\Security::csrfField() ?>
                        <input type="hidden" name="package_id" value="<?= $pkg['id'] ?>">
                        <button type="submit" style="background-color: <?= htmlspecialchars($pkg['color_code'] ?? '#dc2626') ?>;" class="w-full text-white font-bold py-3 px-4 rounded transition-colors shadow-lg hover:brightness-110">
                            Buy <?= htmlspecialchars($pkg['name']) ?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
    <?php if (defined('APP_DEBUG') && APP_DEBUG && isset($debugLog) && $vbaAttempted): ?>
    <div class="max-w-4xl mx-auto px-4 pb-12">
        <details class="bg-gray-800 border border-gray-700 rounded-lg p-4 text-xs">
            <summary class="text-gray-400 font-bold cursor-pointer">VBA Debug Log (click to expand)</summary>
            <pre class="mt-3 text-gray-300 whitespace-pre-wrap"><?php foreach($debugLog as $line): ?><?= htmlspecialchars($line) ?>

<?php endforeach; ?></pre>
        </details>
    </div>
    <?php endif; ?>
    <script src="/assets/js/protection.js"></script>
</body>
</html>
