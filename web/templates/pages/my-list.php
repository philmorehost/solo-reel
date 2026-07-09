<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My List - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        body { background-color: #000; color: #fff; }
        .movie-card:hover { transform: scale(1.05); transition: 0.3s; }
    </style>
</head>
<body class="antialiased font-sans">

    <?php require __DIR__ . '/../partials/header.php'; ?>

    <main class="pt-24 max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-12 min-h-screen" x-data="{ tab: 'history' }">
        <h1 class="text-3xl font-bold mb-8">My List</h1>

        <div class="flex gap-2 mb-10 border-b border-white/10">
            <button @click="tab = 'history'" :class="tab === 'history' ? 'border-red-600 text-white' : 'border-transparent text-gray-500'" class="px-4 py-3 font-semibold border-b-2 transition">History</button>
            <button @click="tab = 'liked'" :class="tab === 'liked' ? 'border-red-600 text-white' : 'border-transparent text-gray-500'" class="px-4 py-3 font-semibold border-b-2 transition">Liked</button>
            <button @click="tab = 'saved'" :class="tab === 'saved' ? 'border-red-600 text-white' : 'border-transparent text-gray-500'" class="px-4 py-3 font-semibold border-b-2 transition">Saved</button>
        </div>

        <!-- History -->
        <div x-show="tab === 'history'" x-cloak>
            <?php if (empty($history)): ?>
                <p class="text-gray-500 text-center py-16">You haven't watched anything yet.</p>
            <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 sm:gap-5">
                    <?php foreach($history as $item): ?>
                        <a href="/episodes/<?= htmlspecialchars($item['episode_slug']) ?>" class="movie-card group cursor-pointer block relative rounded-xl bg-[#1a1a1f] shadow-2xl border border-gray-800/50">
                            <div class="relative aspect-[2/3] overflow-hidden rounded-t-xl">
                                <img src="<?= htmlspecialchars($item['cover_image'] ?? '/assets/img/default-cover.jpg') ?>" class="w-full h-full object-cover">
                                <div class="absolute top-2 left-2 bg-black/80 backdrop-blur px-2 py-1 rounded text-[10px] font-bold text-white border border-gray-700">
                                    EP.<?= (int)$item['episode_number'] ?> / EP.<?= (int)$item['episode_count'] ?>
                                </div>
                            </div>
                            <div class="p-3"><h4 class="text-sm font-semibold text-gray-200 group-hover:text-white truncate"><?= htmlspecialchars($item['title']) ?></h4></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Liked -->
        <div x-show="tab === 'liked'" x-cloak>
            <?php if (empty($liked)): ?>
                <p class="text-gray-500 text-center py-16">You haven't liked any series yet.</p>
            <?php else: ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 sm:gap-5">
                    <?php foreach($liked as $item): ?>
                        <a href="<?= !empty($item['resume_slug']) ? '/episodes/' . htmlspecialchars($item['resume_slug']) : '/movie/' . htmlspecialchars($item['slug']) ?>" class="movie-card group cursor-pointer block relative rounded-xl bg-[#1a1a1f] shadow-2xl border border-gray-800/50">
                            <div class="relative aspect-[2/3] overflow-hidden rounded-t-xl">
                                <img src="<?= htmlspecialchars($item['cover_image'] ?? '/assets/img/default-cover.jpg') ?>" class="w-full h-full object-cover">
                                <div class="absolute top-2 left-2 bg-black/80 backdrop-blur px-2 py-1 rounded text-[10px] font-bold text-white border border-gray-700">
                                    EP.<?= (int)$item['episode_count'] ?>
                                </div>
                            </div>
                            <div class="p-3"><h4 class="text-sm font-semibold text-gray-200 group-hover:text-white truncate"><?= htmlspecialchars($item['title']) ?></h4></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Saved (= My List proper) -->
        <div x-show="tab === 'saved'" x-cloak>
            <?php if (empty($saved)): ?>
                <p class="text-gray-500 text-center py-16">Save series while watching to see them here.</p>
            <?php else: ?>
                <div id="saved-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 sm:gap-5">
                    <?php foreach($saved as $item): ?>
                        <div class="movie-card group relative rounded-xl bg-[#1a1a1f] shadow-2xl border border-gray-800/50" data-series-id="<?= (int)$item['id'] ?>">
                            <a href="<?= !empty($item['resume_slug']) ? '/episodes/' . htmlspecialchars($item['resume_slug']) : '/movie/' . htmlspecialchars($item['slug']) ?>" class="block">
                                <div class="relative aspect-[2/3] overflow-hidden rounded-t-xl">
                                    <img src="<?= htmlspecialchars($item['cover_image'] ?? '/assets/img/default-cover.jpg') ?>" class="w-full h-full object-cover">
                                    <div class="absolute top-2 left-2 bg-black/80 backdrop-blur px-2 py-1 rounded text-[10px] font-bold text-white border border-gray-700">
                                        EP.<?= (int)$item['episode_count'] ?>
                                    </div>
                                </div>
                                <div class="p-3"><h4 class="text-sm font-semibold text-gray-200 group-hover:text-white truncate"><?= htmlspecialchars($item['title']) ?></h4></div>
                            </a>
                            <button class="remove-saved-btn absolute top-2 right-2 bg-black/70 hover:bg-red-600 text-white rounded-full p-1.5 transition" title="Remove from My List">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php require __DIR__ . '/../partials/footer.php'; ?>
    <script>
        (function () {
            const csrfToken = '<?= addslashes(\App\Core\Security::csrfToken()) ?>';
            document.querySelectorAll('.remove-saved-btn').forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    const card = btn.closest('[data-series-id]');
                    const seriesId = card.dataset.seriesId;
                    fetch('/my-list/saved/' + seriesId + '?csrf_token=' + encodeURIComponent(csrfToken), {
                        method: 'DELETE'
                    })
                    .then(res => res.json())
                    .then(data => { if (data.status) card.remove(); })
                    .catch(() => {});
                });
            });
        })();
    </script>
    <script src="/assets/js/protection.js"></script>
</body>
</html>
