<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($series['title']) ?> - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="/assets/css/responsive.css">
    <link rel="icon" type="image/png" href="/favicon.ico">
</head>
<body class="bg-black text-white antialiased font-sans">

    <?php require __DIR__ . '/../partials/header.php'; ?>

    <main class="pt-20 max-w-5xl mx-auto px-4 pb-12">
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
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
                <?php foreach($episodes as $ep): ?>
                    <a href="/episodes/<?= $ep['slug'] ?>" class="block bg-gray-900 rounded-lg overflow-hidden hover:bg-gray-800 transition group cursor-pointer border border-gray-800 hover:border-gray-700 relative">
                        <div class="relative w-full aspect-[2/3] bg-black">
                            <img src="<?= htmlspecialchars($ep['thumbnail_url'] ?? '/assets/img/default-thumb.jpg') ?>" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/50 opacity-0 group-hover:opacity-100 flex items-center justify-center transition">
                                <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4l12 6-12 6z"></path></svg>
                            </div>
                            <div class="absolute top-2 left-2 bg-black/70 px-2 py-1 rounded text-xs text-white font-bold">
                                EP <?= htmlspecialchars($ep['episode_number']) ?>
                            </div>
                            <div class="absolute top-2 right-2">
                                <?php if($ep['is_free']): ?>
                                    <span class="bg-green-600 text-white px-2 py-1 rounded text-xs font-bold uppercase shadow">Free</span>
                                <?php else: ?>
                                    <span class="bg-red-600 text-white px-2 py-1 rounded text-xs font-bold shadow flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="p-3">
                            <h3 class="font-bold text-sm text-gray-200 group-hover:text-red-500 transition truncate" title="<?= htmlspecialchars($ep['title']) ?>"><?= htmlspecialchars($ep['title']) ?></h3>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
    <script src="/assets/js/protection.js"></script>
</body>
</html>
