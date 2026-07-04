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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        body {
            background-color: #0f0f11;
            color: #fff;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }
        .hero-gradient {
            background: linear-gradient(0deg, rgba(15,15,17,1) 0%, rgba(15,15,17,0.8) 20%, rgba(15,15,17,0) 60%);
        }
        .movie-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .movie-card:hover {
            transform: scale(1.05) translateY(-5px);
            z-index: 10;
        }
        .movie-card:hover .play-overlay {
            opacity: 1;
            transform: scale(1);
        }
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
        .hide-scrollbar {
            -ms-overflow-style: none;
            scrollbar-width: none;
        }
    </style>
</head>
<body class="antialiased font-sans">

    <!-- Navbar -->
    <nav x-data="{ scrolled: false }"
         @scroll.window="scrolled = (window.pageYOffset > 50)"
         :class="{ 'bg-black/90 backdrop-blur-md shadow-lg': scrolled, 'bg-transparent': !scrolled }"
         class="fixed w-full z-50 transition-all duration-300">
        <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-20">
                <div class="flex items-center">
                    <a href="/" class="transition-transform hover:scale-105"><?= \App\Helpers\Site::getLogoHtml() ?></a>
                    <div class="hidden md:flex ml-10 space-x-8">
                        <a href="/" class="text-white font-semibold hover:text-red-500 transition">Home</a>
                        <a href="/search" class="text-gray-300 font-medium hover:text-white transition">Search</a>
                        <a href="/blog" class="text-gray-300 font-medium hover:text-white transition">Blog</a>
                    </div>
                </div>
                <div class="flex items-center space-x-6">
                    <?php if(\App\Core\Session::isLoggedIn()): ?>
                        <a href="/coin-shop" class="flex items-center gap-2 text-sm font-bold bg-gray-900/80 border border-gray-700 hover:border-yellow-500 px-3 py-1.5 rounded-full transition">
                            <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"></path></svg>
                            <?= \App\Core\Session::get('user_coin_balance') ?>
                        </a>
                        <a href="/profile" class="text-gray-300 hover:text-white flex items-center">
                            <div class="w-8 h-8 bg-red-600 rounded-full flex items-center justify-center text-white font-bold">
                                <?= strtoupper(substr(\App\Core\Session::get('user_name'), 0, 1)) ?>
                            </div>
                        </a>
                    <?php else: ?>
                        <a href="/login" class="text-white font-medium hover:text-red-500 transition">Sign In</a>
                        <a href="/register" class="bg-red-600 hover:bg-red-700 shadow-[0_0_15px_rgba(220,38,38,0.5)] text-white px-5 py-2.5 rounded-md font-semibold transition-all">Sign Up</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Banners / Featured Slider -->
    <div x-data="{ activeSlide: 0, slides: <?= count($featuredSeries) ?> }" class="relative h-[85vh] overflow-hidden bg-black">
        <?php foreach($featuredSeries as $index => $banner): ?>
            <div x-show="activeSlide === <?= $index ?>"
                 x-transition:enter="transition ease-out duration-1000"
                 x-transition:enter-start="opacity-0 scale-105"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-1000"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-105"
                 class="absolute inset-0 z-0">
                <img src="<?= htmlspecialchars($banner['hero_image']) ?>" alt="<?= htmlspecialchars($banner['title']) ?>" class="w-full h-full object-cover opacity-80">
                <div class="absolute inset-0 hero-gradient"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-[#0f0f11] via-[#0f0f11]/60 to-transparent w-2/3"></div>

                <div class="absolute bottom-[15%] left-[5%] md:left-[8%] z-10 max-w-3xl pr-4">
                    <span class="inline-block px-3 py-1 bg-red-600 text-white text-xs font-bold tracking-widest uppercase rounded-full mb-4 shadow-lg">New Episode</span>
                    <h2 class="text-5xl md:text-7xl font-extrabold mb-4 leading-tight drop-shadow-2xl"><?= htmlspecialchars($banner['title']) ?></h2>
                    <p class="text-lg md:text-xl text-gray-300 mb-8 line-clamp-3 font-light leading-relaxed max-w-2xl text-shadow"><?= htmlspecialchars($banner['synopsis']) ?></p>
                    <div class="flex gap-4">
                        <a href="/movie/<?= htmlspecialchars($banner['slug']) ?>" class="inline-flex items-center justify-center bg-white text-black font-bold px-8 py-3.5 rounded hover:bg-gray-200 transition-colors text-lg">
                            <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4l12 6-12 6z"></path></svg>
                            Play Now
                        </a>
                        <button class="inline-flex items-center justify-center bg-gray-600/60 backdrop-blur text-white font-bold px-8 py-3.5 rounded hover:bg-gray-600/80 transition-colors text-lg border border-gray-500/30">
                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            My List
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if(count($featuredSeries) > 1): ?>
        <script>
            setInterval(() => {
                let alpineData = document.querySelector('[x-data]').__x.$data;
                alpineData.activeSlide = (alpineData.activeSlide + 1) % alpineData.slides;
            }, 6000);
        </script>
        <?php endif; ?>
    </div>

    <main class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-12 -mt-16 relative z-20">
        <!-- Latest Releases -->
        <?php if(!empty($latestSeries)): ?>
        <div class="mb-14">
            <h3 class="text-2xl font-bold mb-6 flex items-center">Latest Releases</h3>
            <div class="flex overflow-x-auto gap-4 pb-8 pt-4 hide-scrollbar snap-x">
                <?php foreach($latestSeries as $series): ?>
                    <a href="/movie/<?= $series['slug'] ?>" class="movie-card flex-none w-[140px] sm:w-[180px] md:w-[220px] snap-start relative group rounded-xl bg-[#1a1a1f] shadow-2xl border border-gray-800/50 block">
                        <div class="relative aspect-[2/3] overflow-hidden rounded-t-xl">
                            <img src="<?= htmlspecialchars($series['cover_image'] ?? '/assets/img/default-cover.jpg') ?>" class="w-full h-full object-cover">

                            <!-- Play Overlay -->
                            <div class="play-overlay absolute inset-0 bg-black/50 opacity-0 transform scale-90 transition-all flex items-center justify-center backdrop-blur-sm">
                                <div class="bg-red-600/90 rounded-full p-4 shadow-[0_0_20px_rgba(220,38,38,0.6)]">
                                    <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4l12 6-12 6z"></path></svg>
                                </div>
                            </div>

                            <!-- Episode Badge -->
                            <div class="absolute top-2 left-2 bg-black/80 backdrop-blur px-2 py-1 rounded text-[10px] font-bold text-white border border-gray-700">
                                EP.1 / EP.<?= $series['episode_count'] ?>
                            </div>
                        </div>
                        <div class="p-3">
                            <h4 class="text-sm font-semibold text-gray-200 group-hover:text-white truncate"><?= htmlspecialchars($series['title']) ?></h4>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Shelves -->
        <?php foreach($shelves as $shelf): ?>
            <?php if(!empty($shelf['series'])): ?>
                <div class="mb-14">
                    <h3 class="text-2xl font-bold mb-6 flex items-center">
                        <span class="mr-2"><?= htmlspecialchars($shelf['emoji'] ?? '') ?></span>
                        <?= htmlspecialchars($shelf['name']) ?>
                    </h3>
                    <div class="flex overflow-x-auto gap-4 pb-8 pt-4 hide-scrollbar snap-x">
                        <?php foreach($shelf['series'] as $series): ?>
                            <a href="/movie/<?= $series['slug'] ?>" class="movie-card flex-none w-[140px] sm:w-[180px] md:w-[220px] snap-start relative group rounded-xl bg-[#1a1a1f] shadow-2xl border border-gray-800/50 block">
                                <div class="relative aspect-[2/3] overflow-hidden rounded-t-xl">
                                    <img src="<?= htmlspecialchars($series['cover_image'] ?? '/assets/img/default-cover.jpg') ?>" class="w-full h-full object-cover">

                                    <!-- Play Overlay -->
                                    <div class="play-overlay absolute inset-0 bg-black/50 opacity-0 transform scale-90 transition-all flex items-center justify-center backdrop-blur-sm">
                                        <div class="bg-red-600/90 rounded-full p-4 shadow-[0_0_20px_rgba(220,38,38,0.6)]">
                                            <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4l12 6-12 6z"></path></svg>
                                        </div>
                                    </div>

                                    <!-- Episode Badge -->
                                    <div class="absolute top-2 left-2 bg-black/80 backdrop-blur px-2 py-1 rounded text-[10px] font-bold text-white border border-gray-700">
                                        EP.1 / EP.<?= $series['episode_count'] ?>
                                    </div>
                                </div>
                                <div class="p-3">
                                    <h4 class="text-sm font-semibold text-gray-200 group-hover:text-white truncate"><?= htmlspecialchars($series['title']) ?></h4>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-[#131315] pt-16 pb-8 border-t border-gray-800/50">
        <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-12">
                <div class="col-span-1 md:col-span-2">
                    <a href="/"><?= \App\Helpers\Site::getLogoHtml() ?></a>
                    <p class="mt-4 text-gray-400 max-w-sm">The best vertical short dramas and mini-series streaming platform. Watch anytime, anywhere.</p>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="/" class="hover:text-white transition">Home</a></li>
                        <li><a href="/search" class="hover:text-white transition">Search</a></li>
                        <li><a href="/blog" class="hover:text-white transition">Blog</a></li>
                        <li><a href="/coin-shop" class="hover:text-white transition">Buy Coins</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-white font-bold mb-4">Connect</h4>
                    <ul class="space-y-2 text-gray-400">
                        <?php if(!empty($config['social_facebook'])): ?><li><a href="<?= htmlspecialchars($config['social_facebook']) ?>" target="_blank" class="hover:text-white transition">Facebook</a></li><?php endif; ?>
                        <?php if(!empty($config['social_twitter'])): ?><li><a href="<?= htmlspecialchars($config['social_twitter']) ?>" target="_blank" class="hover:text-white transition">Twitter / X</a></li><?php endif; ?>
                        <?php if(!empty($config['social_instagram'])): ?><li><a href="<?= htmlspecialchars($config['social_instagram']) ?>" target="_blank" class="hover:text-white transition">Instagram</a></li><?php endif; ?>
                        <?php if(!empty($config['social_tiktok'])): ?><li><a href="<?= htmlspecialchars($config['social_tiktok']) ?>" target="_blank" class="hover:text-white transition">TikTok</a></li><?php endif; ?>
                    </ul>
                </div>
            </div>
            <div class="text-center text-gray-600 text-sm pt-8 border-t border-gray-800/50">
                <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($config['meta_title'] ?? 'SOLOREEL') ?>. All rights reserved.</p>
            </div>
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
