<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($shelf['name']) ?> - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="/assets/css/responsive.css">
    <style>
        body { background-color: #0f0f11; color: #fff; }
        .movie-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .movie-card:hover { transform: scale(1.05) translateY(-5px); z-index: 10; }
        .movie-card:hover .play-overlay { opacity: 1; transform: scale(1); }
    </style>
    <link rel="icon" type="image/png" href="/favicon.ico">
    <?php $adsenseId = \App\Helpers\Site::getConfig('google_adsense_client_id'); if (!empty($adsenseId)): ?>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= htmlspecialchars($adsenseId) ?>" crossorigin="anonymous"></script>
    <?php endif; ?>
</head>
<body class="antialiased font-sans">

    <?php require __DIR__ . '/../partials/header.php'; ?>

    <main class="pt-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <a href="/" class="inline-flex items-center gap-1 text-sm text-gray-400 hover:text-white transition mb-6">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            Home
        </a>

        <div class="flex items-center mb-2">
            <span class="text-3xl mr-3"><?= htmlspecialchars($shelf['emoji'] ?? '📚') ?></span>
            <h1 class="text-3xl font-bold"><?= htmlspecialchars($shelf['name']) ?></h1>
        </div>
        <p class="text-gray-400 mb-8">
            <?php if (!empty($shelf['description'])): ?>
                <?= htmlspecialchars($shelf['description']) ?> &middot;
            <?php endif; ?>
            <?= count($series) ?> Series
        </p>

        <?php if(empty($series)): ?>
            <div class="flex flex-col items-center justify-center py-24 text-center">
                <svg class="w-16 h-16 text-gray-700 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                <p class="text-gray-500">No series in this shelf yet. Check back soon.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 sm:gap-6">
                <?php foreach($series as $s): ?>
                    <a href="<?= !empty($s['resume_slug']) ? '/episodes/' . htmlspecialchars($s['resume_slug']) : '/movie/' . htmlspecialchars($s['slug']) ?>" class="movie-card group relative rounded-xl bg-[#1a1a1f] shadow-2xl border border-gray-800/50 block overflow-hidden">
                        <div class="relative aspect-[2/3] overflow-hidden rounded-t-xl">
                            <img src="<?= htmlspecialchars($s['cover_image'] ?? '/assets/img/default-cover.jpg') ?>" class="w-full h-full object-cover">

                            <!-- Play Overlay -->
                            <div class="play-overlay absolute inset-0 bg-black/50 opacity-0 transform scale-90 transition-all flex items-center justify-center backdrop-blur-sm">
                                <div class="bg-red-600/90 rounded-full p-4 shadow-[0_0_20px_rgba(220,38,38,0.6)]">
                                    <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4l12 6-12 6z"></path></svg>
                                </div>
                            </div>

                            <!-- Episode Badge -->
                            <?php if (isset($s['episode_count'])): ?>
                            <div class="absolute top-2 left-2 bg-black/80 backdrop-blur px-2 py-1 rounded text-[10px] font-bold text-white border border-gray-700">
                                EP.1 / EP.<?= (int) $s['episode_count'] ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="p-3">
                            <h4 class="text-sm font-semibold text-gray-200 group-hover:text-white truncate"><?= htmlspecialchars($s['title']) ?></h4>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    <?php require __DIR__ . '/../partials/footer.php'; ?>
    <script src="/assets/js/protection.js"></script>
</body>
</html>
