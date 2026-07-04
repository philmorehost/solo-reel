<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coin Shop - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="/favicon.ico">
</head>
<body class="bg-black text-white antialiased font-sans">

    <nav class="w-full bg-gray-900 border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <a href="/" class="text-red-600 font-bold text-xl tracking-tighter">SOLOREEL</a>
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                <span class="font-bold"><?= \App\Core\Session::get('user_coin_balance') ?> Coins</span>
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
        <div class="bg-gray-800 rounded-lg p-6 mb-10 text-center border border-gray-700">
            <h2 class="text-lg text-gray-300 mb-2">Your Dedicated Virtual Account</h2>
            <p class="text-sm text-gray-400 mb-4">Transfer NGN to this account to auto-fund your wallet.</p>
            <div class="bg-black p-4 rounded inline-block">
                <div class="text-3xl font-mono tracking-widest text-white"><?= htmlspecialchars($virtualAccount['account_number']) ?></div>
                <div class="text-gray-400 mt-1"><?= htmlspecialchars($virtualAccount['bank_name']) ?></div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            <?php foreach($packages as $pkg): ?>
                <div class="bg-gray-900 border border-gray-800 rounded-lg p-6 text-center hover:border-red-500 transition cursor-pointer">
                    <div class="text-yellow-500 mb-2">
                        <svg class="w-12 h-12 mx-auto" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                    </div>
                    <h3 class="text-2xl font-bold mb-1"><?= (int)$pkg['coins'] ?> Coins</h3>
                    <p class="text-gray-400 mb-6 font-medium"><?= htmlspecialchars($pkg['currency']) ?> <?= number_format($pkg['price'], 2) ?></p>
                    <form action="/coins/purchase" method="POST">
                        <?= \App\Core\Security::csrfField() ?>
                        <input type="hidden" name="package_id" value="<?= $pkg['id'] ?>">
                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition-colors">
                            Buy Now
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
    <script src="/assets/js/protection.js"></script>
</body>
</html>
