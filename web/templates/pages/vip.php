<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIP Membership - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body class="bg-black text-white antialiased font-sans">

    <?php require __DIR__ . '/../partials/header.php'; ?>

    <main class="max-w-4xl mx-auto px-4 py-8 pt-24">
        <div class="text-center mb-10">
            <p class="text-5xl mb-3">👑</p>
            <h1 class="text-3xl font-bold mb-2">SOLOREEL VIP</h1>
            <p class="text-gray-400">Unlock every episode for free, no ads, no per-episode coins — for as long as you're subscribed.</p>
        </div>

        <?php $error = \App\Core\Session::getFlash('error'); if($error): ?>
            <div class="bg-red-500/10 border border-red-500 text-red-500 p-3 rounded mb-8 text-center text-sm max-w-lg mx-auto">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php $success = \App\Core\Session::getFlash('success'); if($success): ?>
            <div class="bg-green-500/10 border border-green-500 text-green-500 p-3 rounded mb-8 text-center text-sm max-w-lg mx-auto">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <?php if ($activeSubscription): ?>
            <div class="bg-gradient-to-r from-yellow-600/20 to-amber-500/10 border border-yellow-500/50 rounded-xl p-6 mb-10 text-center">
                <p class="text-yellow-400 font-bold text-lg mb-1">You're a VIP member 👑</p>
                <p class="text-gray-300 text-sm">Plan: <?= htmlspecialchars($activeSubscription['plan_name']) ?> · Renews/expires <?= htmlspecialchars(date('M j, Y', strtotime($activeSubscription['expires_at']))) ?></p>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-5">
            <?php foreach($plans as $plan): ?>
                <div class="relative bg-gradient-to-b from-yellow-500/10 to-gray-900 border border-yellow-600/40 rounded-2xl p-6 flex flex-col shadow-xl hover:scale-105 transition">
                    <div class="absolute top-0 left-0 right-0 h-1.5 bg-gradient-to-r from-yellow-400 to-amber-600 rounded-t-2xl"></div>
                    <h3 class="text-xl font-bold mt-2 mb-1 text-white"><?= htmlspecialchars($plan['name']) ?></h3>
                    <p class="text-3xl font-extrabold text-yellow-400 mb-1"><?= htmlspecialchars($plan['currency']) ?> <?= number_format($plan['price'], 2) ?></p>
                    <p class="text-gray-400 text-sm mb-5"><?= (int)$plan['duration_days'] ?> days</p>

                    <ul class="text-sm text-gray-300 space-y-2 mb-6 flex-grow">
                        <?php if ($plan['perk_free_unlocks']): ?>
                            <li class="flex items-center gap-2"><span class="text-yellow-400">✔</span> Unlock all episodes free</li>
                        <?php endif; ?>
                        <?php if ($plan['perk_ad_free']): ?>
                            <li class="flex items-center gap-2"><span class="text-yellow-400">✔</span> No ads required to unlock</li>
                        <?php endif; ?>
                        <li class="flex items-center gap-2"><span class="text-yellow-400">✔</span> Cancel anytime</li>
                    </ul>

                    <form action="/vip/subscribe" method="POST" class="mt-auto">
                        <?= \App\Core\Security::csrfField() ?>
                        <input type="hidden" name="plan_id" value="<?= (int)$plan['id'] ?>">
                        <input type="hidden" name="return_to" value="/vip">
                        <button type="submit" class="w-full bg-gradient-to-r from-yellow-500 to-amber-600 hover:brightness-110 text-black font-bold py-3 px-4 rounded-lg transition shadow-lg">
                            Subscribe
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-12">
            <p class="text-gray-500 text-sm">Prefer to pay as you go? <a href="/coin-shop" class="text-red-500 hover:text-red-400 font-semibold">Buy coins instead</a></p>
        </div>
    </main>

    <?php require __DIR__ . '/../partials/footer.php'; ?>
    <script src="/assets/js/protection.js"></script>
</body>
</html>
