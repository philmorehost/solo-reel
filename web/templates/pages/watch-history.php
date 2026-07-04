<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Watch History - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-white antialiased font-sans">

    <nav class="fixed w-full z-50 bg-black/80 backdrop-blur border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-red-600 font-bold text-2xl tracking-tighter">SOLOREEL</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/profile" class="text-gray-300 hover:text-white">Profile</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="pt-24 max-w-5xl mx-auto px-4 py-12">
        <h1 class="text-3xl font-bold mb-8">Watch History</h1>

        <?php if(empty($history)): ?>
            <p class="text-gray-500">You haven't watched anything yet.</p>
        <?php else: ?>
            <div class="space-y-4">
                <?php foreach($history as $item): ?>
                    <a href="/movie/<?= htmlspecialchars($item['series_slug']) ?>" class="flex flex-col sm:flex-row items-center p-4 bg-gray-900 rounded-lg hover:bg-gray-800 transition group border border-gray-800 hover:border-gray-700">
                        <div class="relative w-full sm:w-48 aspect-video bg-black rounded overflow-hidden sm:mr-6 mb-4 sm:mb-0 flex-shrink-0">
                            <img src="<?= htmlspecialchars($item['thumbnail_url'] ?? '/assets/img/default-thumb.jpg') ?>" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-grow text-center sm:text-left">
                            <h3 class="font-bold text-xl group-hover:text-red-500 transition"><?= htmlspecialchars($item['series_title']) ?></h3>
                            <p class="text-gray-400 mt-1"><?= htmlspecialchars($item['episode_title']) ?></p>
                            <p class="text-sm text-gray-500 mt-2">Watched on <?= date('M d, Y', strtotime($item['last_watched_at'])) ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
