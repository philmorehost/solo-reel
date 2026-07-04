<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($series['title']) ?> - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="icon" type="image/png" href="/favicon.ico">
</head>
<body class="bg-black text-white antialiased font-sans">

    <!-- Navbar -->
    <nav class="fixed w-full z-50 bg-black/80 backdrop-blur border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/"><?= \App\Helpers\Site::getLogoHtml() ?></a>
                </div>
            </div>
        </div>
    </nav>

    <main class="pt-24 max-w-5xl mx-auto px-4 pb-12">
        <div class="flex flex-col md:flex-row gap-8">
            <div class="w-full md:w-1/3">
                <img src="<?= htmlspecialchars($series['cover_image'] ?? '/assets/img/default-cover.jpg') ?>" class="w-full rounded-lg shadow-xl aspect-[2/3] object-cover">
            </div>
            <div class="w-full md:w-2/3">
                <h1 class="text-4xl font-bold mb-4"><?= htmlspecialchars($series['title']) ?></h1>
                <div class="flex items-center gap-4 text-sm text-gray-400 mb-6">
                    <span class="bg-gray-800 px-2 py-1 rounded"><?= htmlspecialchars($series['status']) ?></span>
                    <span><?= htmlspecialchars($series['genre'] ?? 'Drama') ?></span>
                    <span><?= count($episodes) ?> Episodes</span>
                </div>
                <p class="text-gray-300 leading-relaxed mb-8"><?= nl2br(htmlspecialchars($series['synopsis'] ?? '')) ?></p>

                <?php require __DIR__ . '/social-share.php'; ?>
                <?php if(!empty($episodes)): ?>
                    <a href="/episodes/<?= $episodes[0]['slug'] ?>" class="inline-block bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-8 rounded-full transition-colors text-lg">
                        Play Episode 1
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-16">
            <h2 class="text-2xl font-bold mb-6">Episodes</h2>
            <div class="space-y-4">
                <?php foreach($episodes as $ep): ?>
                    <a href="/episodes/<?= $ep['slug'] ?>" class="flex items-center p-4 bg-gray-900 rounded-lg hover:bg-gray-800 transition group cursor-pointer border border-gray-800 hover:border-gray-700">
                        <div class="relative w-32 aspect-video bg-black rounded overflow-hidden mr-4 flex-shrink-0">
                            <img src="<?= htmlspecialchars($ep['thumbnail_url'] ?? '/assets/img/default-thumb.jpg') ?>" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition">
                                <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4l12 6-12 6z"></path></svg>
                            </div>
                        </div>
                        <div class="flex-grow">
                            <h3 class="font-bold text-lg group-hover:text-red-500 transition"><?= htmlspecialchars($ep['episode_number'] . '. ' . $ep['title']) ?></h3>
                            <p class="text-sm text-gray-400 mt-1"><?= gmdate("i:s", $ep['duration_seconds']) ?></p>
                        </div>
                        <div>
                            <?php if($ep['is_free']): ?>
                                <span class="bg-green-600/20 text-green-500 px-3 py-1 rounded text-xs font-bold uppercase">Free</span>
                            <?php else: ?>
                                <span class="bg-red-600/20 text-red-500 px-3 py-1 rounded text-xs font-bold flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
                                    <?= (float)$ep['coin_cost'] ?> Coins
                                </span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
    <script src="/assets/js/protection.js"></script>
</body>
</html>
