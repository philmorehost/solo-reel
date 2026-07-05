<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Watch History - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body class="bg-black text-white antialiased font-sans min-h-screen flex flex-col">
    <?php require __DIR__ . '/../partials/header.php'; ?>

    <main class="flex-1 pt-20 max-w-5xl mx-auto px-4 py-8">
        <h1 class="text-2xl font-bold mb-6">Watch History</h1>

        <?php if(empty($history)): ?>
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-8 text-center">
                <p class="text-gray-400">You haven't watched anything yet.</p>
                <a href="/" class="inline-block mt-4 bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-lg font-semibold transition">Browse Series</a>
            </div>
        <?php else: ?>
            <div class="space-y-3">
                <?php foreach($history as $item): ?>
                    <a href="/movie/<?= htmlspecialchars($item['series_slug']) ?>" class="flex flex-col sm:flex-row items-center p-4 bg-gray-900 rounded-lg hover:bg-gray-800/80 transition border border-gray-800 hover:border-gray-700">
                        <div class="relative w-full sm:w-40 aspect-video bg-black rounded overflow-hidden sm:mr-4 mb-3 sm:mb-0 flex-shrink-0">
                            <img src="<?= htmlspecialchars($item['thumbnail_url'] ?? '') ?>" class="w-full h-full object-cover" loading="lazy">
                        </div>
                        <div class="flex-grow text-center sm:text-left">
                            <h3 class="font-bold text-lg group-hover:text-red-500 transition"><?= htmlspecialchars($item['series_title']) ?></h3>
                            <p class="text-gray-400 text-sm mt-1"><?= htmlspecialchars($item['episode_title']) ?></p>
                            <p class="text-xs text-gray-500 mt-1">Watched on <?= date('M d, Y', strtotime($item['last_watched_at'])) ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
