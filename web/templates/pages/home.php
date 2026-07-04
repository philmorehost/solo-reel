<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOLOREEL - Vertical Short Dramas</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
                <div class="flex items-center space-x-4">
                    <a href="/search" class="text-gray-300 hover:text-white">Search</a>
                    <a href="/login" class="text-gray-300 hover:text-white">Sign In</a>
                    <a href="/register" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded font-medium">Sign Up</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Banners -->
    <div x-data="{ activeSlide: 0, slides: <?= count($banners) ?> }" class="relative pt-16 h-[60vh] overflow-hidden">
        <?php foreach($banners as $index => $banner): ?>
            <div x-show="activeSlide === <?= $index ?>"
                 x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="opacity-0 transform scale-105"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-500"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-105"
                 class="absolute inset-0 z-0">
                <img src="<?= htmlspecialchars($banner['image_url']) ?>" alt="<?= htmlspecialchars($banner['title']) ?>" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent"></div>
                <div class="absolute bottom-10 left-10 z-10">
                    <h2 class="text-4xl font-bold mb-2"><?= htmlspecialchars($banner['title']) ?></h2>
                    <p class="text-xl text-gray-300"><?= htmlspecialchars($banner['subtitle']) ?></p>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if(count($banners) > 1): ?>
        <script>
            setInterval(() => {
                let alpineData = document.querySelector('[x-data]').__x.$data;
                alpineData.activeSlide = (alpineData.activeSlide + 1) % alpineData.slides;
            }, 5000);
        </script>
        <?php endif; ?>
    </div>

    <!-- Shelves -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <?php foreach($shelves as $shelf): ?>
            <?php if(!empty($shelf['series'])): ?>
                <div class="mb-12">
                    <div class="flex items-center mb-6">
                        <span class="text-2xl mr-2"><?= htmlspecialchars($shelf['emoji'] ?? '') ?></span>
                        <h3 class="text-2xl font-bold"><?= htmlspecialchars($shelf['name']) ?></h3>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        <?php foreach($shelf['series'] as $series): ?>
                            <a href="/movie/<?= $series['slug'] ?>" class="movie-card group cursor-pointer">
                                <div class="relative aspect-[2/3] overflow-hidden rounded-lg">
                                    <img src="<?= htmlspecialchars($series['cover_image'] ?? '/assets/img/default-cover.jpg') ?>" class="w-full h-full object-cover">
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                        <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4l12 6-12 6z"></path></svg>
                                    </div>
                                </div>
                                <h4 class="mt-2 text-sm font-medium text-gray-300 group-hover:text-white truncate"><?= htmlspecialchars($series['title']) ?></h4>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 border-t border-gray-800 py-12">
        <div class="max-w-7xl mx-auto px-4 text-center text-gray-400">
            <p>&copy; <?= date('Y') ?> SOLOREEL. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
