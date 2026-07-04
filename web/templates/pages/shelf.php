<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($shelf['name']) ?> - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #000; color: #fff; }
        .movie-card:hover { transform: scale(1.05); transition: 0.3s; }
    </style>
</head>
<body class="antialiased font-sans">

    <!-- Navbar -->
    <nav class="fixed w-full z-50 bg-black/80 backdrop-blur border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-red-600 font-bold text-2xl tracking-tighter">SOLOREEL</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="pt-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex items-center mb-8">
            <span class="text-3xl mr-3"><?= htmlspecialchars($shelf['emoji'] ?? '') ?></span>
            <h1 class="text-3xl font-bold"><?= htmlspecialchars($shelf['name']) ?></h1>
        </div>

        <?php if(empty($series)): ?>
            <p class="text-gray-400">No series found in this shelf.</p>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php foreach($series as $s): ?>
                    <a href="/movie/<?= $s['slug'] ?>" class="movie-card group cursor-pointer">
                        <div class="relative aspect-[2/3] overflow-hidden rounded-lg">
                            <img src="<?= htmlspecialchars($s['cover_image'] ?? '/assets/img/default-cover.jpg') ?>" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4l12 6-12 6z"></path></svg>
                            </div>
                        </div>
                        <h4 class="mt-2 text-sm font-medium text-gray-300 group-hover:text-white truncate"><?= htmlspecialchars($s['title']) ?></h4>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
