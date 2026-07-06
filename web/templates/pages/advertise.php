<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advertise With Us - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body class="bg-black text-white antialiased font-sans">

    <?php require __DIR__ . '/../partials/header.php'; ?>

    <main class="max-w-3xl mx-auto px-4 py-8 pt-20">
        <h1 class="text-3xl font-bold mb-2 text-center">Advertise With Us</h1>
        <p class="text-gray-400 text-center mb-8">Run your own banner ad on the home screen — pick how long it stays on screen, where it shows, and pay once for a <?= (int)\App\Helpers\Site::getConfig('ad_campaign_days', 30) ?>-day campaign.</p>

        <?php $error = \App\Core\Session::getFlash('error'); if($error): ?>
            <div class="bg-red-500/10 border border-red-500 text-red-500 p-3 rounded mb-6 text-center text-sm"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="bg-gray-900 border border-gray-800 rounded-lg p-4 mb-8 overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-gray-400 text-left">
                        <th class="py-2 pr-4">Duration</th>
                        <th class="py-2 pr-4">Website Only</th>
                        <th class="py-2 pr-4">App Only</th>
                        <th class="py-2">Website + App</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (['5', '10', '15'] as $d): ?>
                    <tr class="border-t border-gray-800">
                        <td class="py-2 pr-4 font-bold"><?= $d ?>s</td>
                        <td class="py-2 pr-4">&#8358;<?= number_format($prices[$d]['website'] ?? 0, 2) ?></td>
                        <td class="py-2 pr-4">&#8358;<?= number_format($prices[$d]['app'] ?? 0, 2) ?></td>
                        <td class="py-2">&#8358;<?= number_format($prices[$d]['both'] ?? 0, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <form method="POST" action="/advertise/subscribe" enctype="multipart/form-data" class="bg-gray-900 border border-gray-800 rounded-lg p-6">
            <?= \App\Core\Security::csrfField() ?>

            <div class="mb-4">
                <label class="block font-bold mb-2">Ad Title</label>
                <input type="text" name="title" required class="w-full bg-black border border-gray-700 rounded px-3 py-2 text-white">
            </div>

            <div class="mb-4">
                <label class="block font-bold mb-2">Target Link (opened when tapped, optional)</label>
                <input type="url" name="target_url" placeholder="https://example.com" class="w-full bg-black border border-gray-700 rounded px-3 py-2 text-white">
            </div>

            <div class="mb-4 p-4 border border-dashed border-gray-700 rounded">
                <label class="block font-bold mb-2">Banner Image or Video</label>
                <input type="file" name="media_file" accept="image/png,image/jpeg,image/webp,video/mp4,video/webm" required class="w-full text-sm">
                <p class="text-xs text-gray-500 mt-2">Recommended size: 1200 &times; 600px (2:1). Images: png/jpg/webp. Video: mp4/webm, keep it short and muted-friendly.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <div>
                    <label class="block font-bold mb-2">On-Screen Duration</label>
                    <select name="duration_seconds" class="w-full bg-black border border-gray-700 rounded px-3 py-2 text-white">
                        <option value="5">5 seconds</option>
                        <option value="10">10 seconds</option>
                        <option value="15">15 seconds</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold mb-2">Where should it show?</label>
                    <select name="platform_placement" class="w-full bg-black border border-gray-700 rounded px-3 py-2 text-white">
                        <option value="both">Website + App</option>
                        <option value="website">Website Only</option>
                        <option value="app">App Only</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded transition">
                Continue to Payment
            </button>
        </form>

        <p class="text-center text-gray-500 text-sm mt-6">
            Already running an ad? <a href="/my-ads" class="text-red-500 underline">Manage your ads</a>
        </p>
    </main>

    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
