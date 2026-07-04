<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Dynamic SEO and Header Code -->
    <?php
        $db = \App\Core\Database::getInstance();
        $stmt = $db->query("SELECT setting_key, setting_value FROM site_config WHERE setting_key IN ('meta_title', 'meta_description', 'meta_keywords', 'custom_header', 'ga_id')");
        $config = [];
        while ($row = $stmt->fetch()) { $config[$row['setting_key']] = $row['setting_value']; }
    ?>
    <title><?= htmlspecialchars($config['meta_title'] ?? 'SOLOREEL') ?></title>
    <meta name="description" content="<?= htmlspecialchars($config['meta_description'] ?? '') ?>">
    <meta name="keywords" content="<?= htmlspecialchars($config['meta_keywords'] ?? '') ?>">
    <?php if(!empty($config['ga_id'])): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?= $config['ga_id'] ?>"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '<?= $config['ga_id'] ?>');
    </script>
    <?php endif; ?>
    <?= $config['custom_header'] ?? '' ?>
    <link rel="icon" type="image/png" href="/favicon.ico">
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
                    <a href="/"><?= \App\Helpers\Site::getLogoHtml() ?></a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/search" class="text-gray-300 hover:text-white">Search</a>
                    <a href="/blog" class="text-gray-300 hover:text-white">Blog</a>
                    <?php if(\App\Core\Session::isLoggedIn()): ?>
                        <a href="/profile" class="text-gray-300 hover:text-white">Profile</a>
                    <?php else: ?>
                        <a href="/login" class="text-gray-300 hover:text-white">Sign In</a>
                        <a href="/register" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded font-medium">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Banners / Featured Slider -->
    <div x-data="{ activeSlide: 0, slides: <?= count($featuredSeries) ?> }" class="relative pt-16 h-[60vh] overflow-hidden">
        <?php foreach($featuredSeries as $index => $banner): ?>
            <div x-show="activeSlide === <?= $index ?>"
                 x-transition:enter="transition ease-out duration-500"
                 x-transition:enter-start="opacity-0 transform scale-105"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 x-transition:leave="transition ease-in duration-500"
                 x-transition:leave-start="opacity-100 transform scale-100"
                 x-transition:leave-end="opacity-0 transform scale-105"
                 class="absolute inset-0 z-0">
                <img src="<?= htmlspecialchars($banner['hero_image']) ?>" alt="<?= htmlspecialchars($banner['title']) ?>" class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black via-black/50 to-transparent"></div>
                <div class="absolute bottom-10 left-10 z-10 max-w-2xl">
                    <h2 class="text-4xl font-bold mb-2"><?= htmlspecialchars($banner['title']) ?></h2>
                    <p class="text-xl text-gray-300 mb-4 line-clamp-2"><?= htmlspecialchars($banner['synopsis']) ?></p>
                    <a href="/movie/<?= htmlspecialchars($banner['slug']) ?>" class="inline-flex items-center bg-red-600 text-white font-bold px-6 py-3 rounded-full hover:bg-red-700 transition">
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                        Play Now
                    </a>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if(count($featuredSeries) > 1): ?>
        <script>
            setInterval(() => {
                let alpineData = document.querySelector('[x-data]').__x.$data;
                alpineData.activeSlide = (alpineData.activeSlide + 1) % alpineData.slides;
            }, 5000);
        </script>
        <?php endif; ?>
    </div>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Latest Releases -->
        <?php if(!empty($latestSeries)): ?>
        <div class="mb-12">
            <div class="flex items-center mb-6">
                <span class="text-2xl mr-2">🆕</span>
                <h3 class="text-2xl font-bold">Latest Releases</h3>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php foreach($latestSeries as $series): ?>
                    <a href="/movie/<?= $series['slug'] ?>" class="movie-card group cursor-pointer">
                        <div class="relative aspect-[2/3] overflow-hidden rounded-lg">
                            <img src="<?= htmlspecialchars($series['cover_image'] ?? '/assets/img/default-cover.jpg') ?>" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4l12 6-12 6z"></path></svg>
                            </div>
                            <div class="absolute bottom-2 left-2 right-2 flex justify-between items-end">
                                <span class="bg-red-600 text-xs font-bold px-2 py-1 rounded text-white shadow">EP.1 / EP.<?= $series['episode_count'] ?></span>
                            </div>
                        </div>
                        <h4 class="mt-2 text-sm font-medium text-gray-300 group-hover:text-white truncate"><?= htmlspecialchars($series['title']) ?></h4>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Shelves -->
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
                                    <div class="absolute bottom-2 left-2 right-2 flex justify-between items-end">
                                        <span class="bg-red-600 text-xs font-bold px-2 py-1 rounded text-white shadow">EP.1 / EP.<?= $series['episode_count'] ?></span>
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
            <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($config['meta_title'] ?? 'SOLOREEL') ?>. All rights reserved.</p>
        </div>
    </footer>
    <script src="/assets/js/protection.js"></script>
    <!-- Custom Footer Code -->
    <?php
        $stmt = $db->prepare("SELECT setting_value FROM site_config WHERE setting_key = 'custom_footer'");
        $stmt->execute();
        echo $stmt->fetchColumn();
    ?>
</body>
</html>
