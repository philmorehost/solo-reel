<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: radial-gradient(circle at top right, rgba(220, 38, 38, 0.08), transparent 45%), #030303;
        }
    </style>
</head>
<body class="text-white antialiased min-h-screen flex flex-col">

    <?php require __DIR__ . '/../partials/header.php'; ?>

    <main class="pt-24 pb-16 flex-1 max-w-6xl mx-auto px-4 w-full">
        <!-- Dashboard Header / Welcome Section -->
        <div class="relative overflow-hidden bg-gradient-to-r from-zinc-900 via-zinc-900/90 to-red-950/20 border border-zinc-800/80 rounded-2xl p-6 sm:p-8 mb-8 shadow-2xl">
            <div class="relative z-10 flex flex-col sm:flex-row items-center gap-6">
                <!-- User Avatar (using SVG fallback with premium gradient) -->
                <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-2xl bg-gradient-to-tr from-red-600 to-amber-500 flex items-center justify-center text-white font-extrabold text-3xl sm:text-4xl shadow-lg border-2 border-zinc-800">
                    <?= strtoupper(substr(htmlspecialchars($user['username']), 0, 1)) ?>
                </div>
                <div class="text-center sm:text-left flex-1">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-red-500/10 text-red-400 border border-red-500/20 mb-2">Member</span>
                    <h1 class="text-2xl sm:text-4xl font-black tracking-tight text-white"><?= htmlspecialchars($user['username']) ?></h1>
                    <p class="text-zinc-400 text-sm mt-1"><?= htmlspecialchars($user['email']) ?></p>
                </div>
            </div>
            <!-- Background Glow Decorator -->
            <div class="absolute right-0 bottom-0 w-64 h-64 bg-red-600/10 rounded-full blur-3xl -mr-16 -mb-16 pointer-events-none"></div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Side Panel: Wallet & Account Settings -->
            <div class="space-y-6 lg:col-span-1">
                <!-- Wallet & Coins Card -->
                <div class="bg-zinc-900/40 backdrop-blur-md border border-zinc-800/80 rounded-2xl p-6 shadow-xl relative overflow-hidden group">
                    <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:scale-110 transition duration-300">
                        <svg class="w-24 h-24 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z" clip-rule="evenodd"></path></svg>
                    </div>

                    <h3 class="text-zinc-400 text-xs font-bold uppercase tracking-widest mb-1">Coin Balance</h3>
                    <div class="flex items-baseline gap-2 mb-6">
                        <span class="text-4xl sm:text-5xl font-black text-transparent bg-clip-text bg-gradient-to-r from-amber-400 via-yellow-300 to-amber-500 tracking-tight"><?= (float)$user['coin_balance'] ?></span>
                        <span class="text-sm font-semibold text-amber-500 uppercase">Coins</span>
                    </div>

                    <a href="/coin-shop" class="flex items-center justify-center gap-2 w-full bg-gradient-to-r from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 text-white font-bold py-3 px-4 rounded-xl transition duration-200 shadow-lg shadow-red-950/40 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                        Buy Coin Packages
                    </a>
                </div>

                <!-- Account Information / Quick Details Card -->
                <div class="bg-zinc-900/40 backdrop-blur-md border border-zinc-800/80 rounded-2xl p-6 shadow-xl">
                    <h3 class="text-white font-bold text-lg mb-4 flex items-center gap-2">
                        <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Account Info
                    </h3>
                    <div class="space-y-4 text-sm">
                        <div>
                            <span class="text-zinc-500 block mb-1">Username</span>
                            <span class="text-zinc-200 font-medium block bg-zinc-950/50 border border-zinc-800/60 rounded-lg p-2.5 font-mono"><?= htmlspecialchars($user['username']) ?></span>
                        </div>
                        <div>
                            <span class="text-zinc-500 block mb-1">Email Address</span>
                            <span class="text-zinc-200 font-medium block bg-zinc-950/50 border border-zinc-800/60 rounded-lg p-2.5 font-mono"><?= htmlspecialchars($user['email']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side Panel: Watch History & Transactions -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Watch History / Continue Watching Section -->
                <div>
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight text-white flex items-center gap-2">
                            <span class="w-1.5 h-6 bg-red-600 rounded-full"></span>
                            Continue Watching
                        </h2>
                        <a href="/watch-history" class="text-xs font-semibold text-red-500 hover:text-red-400 transition flex items-center gap-1">
                            View All
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>

                    <?php if(empty($watchHistory)): ?>
                        <div class="bg-zinc-900/20 border border-dashed border-zinc-800 rounded-2xl p-8 text-center">
                            <p class="text-zinc-500 text-sm">No watch history available. Start exploring series!</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <?php foreach($watchHistory as $item): ?>
                                <a href="/episodes/<?= htmlspecialchars($item['series_slug']) ?>" class="flex items-center gap-4 bg-zinc-900/30 border border-zinc-800/70 hover:border-red-500/50 rounded-xl overflow-hidden hover:bg-zinc-900/50 transition duration-200 group">
                                    <div class="relative w-28 h-18 sm:w-32 sm:h-20 flex-shrink-0 bg-zinc-950">
                                        <img src="<?= htmlspecialchars($item['thumbnail_url'] ?? '/assets/img/default-thumb.jpg') ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                        <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-200">
                                            <svg class="w-8 h-8 text-white drop-shadow-md" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                                        </div>
                                    </div>
                                    <div class="p-3 min-w-0 pr-4">
                                        <h4 class="font-bold text-sm text-zinc-100 truncate group-hover:text-red-400 transition"><?= htmlspecialchars($item['series_title']) ?></h4>
                                        <p class="text-xs text-zinc-500 truncate mt-0.5"><?= htmlspecialchars($item['episode_title']) ?></p>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Transactions Section -->
                <div>
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl sm:text-2xl font-extrabold tracking-tight text-white flex items-center gap-2">
                            <span class="w-1.5 h-6 bg-red-600 rounded-full"></span>
                            Recent Transactions
                        </h2>
                    </div>

                    <?php if(empty($coinHistory)): ?>
                        <div class="bg-zinc-900/20 border border-dashed border-zinc-800 rounded-2xl p-8 text-center">
                            <p class="text-zinc-500 text-sm">No transaction records found.</p>
                        </div>
                    <?php else: ?>
                        <div class="bg-zinc-900/30 border border-zinc-800/70 rounded-2xl overflow-hidden shadow-lg">
                            <div class="divide-y divide-zinc-800/80">
                                <?php foreach($coinHistory as $txn): ?>
                                    <div class="flex items-center justify-between p-4 hover:bg-zinc-900/20 transition">
                                        <div class="min-w-0 pr-4">
                                            <p class="text-sm font-semibold text-zinc-200 truncate"><?= htmlspecialchars($txn['description']) ?></p>
                                            <p class="text-xs text-zinc-500 mt-0.5"><?= date('M d, Y', strtotime($txn['created_at'])) ?></p>
                                        </div>
                                        <div class="font-extrabold text-sm flex-shrink-0 <?= (float)$txn['amount'] > 0 ? 'text-emerald-500' : 'text-rose-500' ?>">
                                            <?= (float)$txn['amount'] > 0 ? '+' : '' ?><?= (float)$txn['amount'] ?> Coins
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <?php require __DIR__ . '/../partials/footer.php'; ?>

    <script src="/assets/js/protection.js"></script>
</body>
</html>
