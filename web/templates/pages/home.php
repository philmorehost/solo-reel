<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Dynamic SEO and Header Code -->
    <?php
        $db = \App\Core\Database::getInstance();
        $stmt = $db->query("SELECT setting_key, setting_value FROM site_config WHERE setting_key IN ('meta_title', 'meta_description', 'meta_keywords', 'custom_header', 'ga_id', 'google_adsense_client_id')");
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
    <?php if(!empty($config['google_adsense_client_id'])): ?>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= htmlspecialchars($config['google_adsense_client_id']) ?>" crossorigin="anonymous"></script>
    <?php endif; ?>
    <?= $config['custom_header'] ?? '' ?>
    <link rel="icon" type="image/png" href="/favicon.ico">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Anton&display=swap');
        body {
            background-color: #12081b;
            color: #fff;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }
        .font-anton {
            font-family: 'Anton', sans-serif;
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

    <?php require __DIR__ . '/../partials/header.php'; ?>

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
                <?php if (($banner['media_type'] ?? 'image') === 'video'): ?>
                <video src="<?= htmlspecialchars($banner['hero_image']) ?>" class="w-full h-full object-cover opacity-80" autoplay muted loop playsinline></video>
                <?php else: ?>
                <img src="<?= htmlspecialchars($banner['hero_image']) ?>" alt="<?= htmlspecialchars($banner['title']) ?>" class="w-full h-full object-cover opacity-80">
                <?php endif; ?>
                <div class="absolute inset-0 hero-gradient"></div>
                <div class="absolute inset-0 bg-gradient-to-r from-[#0f0f11] via-[#0f0f11]/60 to-transparent w-2/3"></div>

                <div class="absolute bottom-[15%] left-[5%] md:left-[8%] z-10 max-w-3xl pr-4">
                    <span class="inline-block px-3 py-1 <?= !empty($banner['is_ad']) ? 'bg-gray-700' : 'bg-red-600' ?> text-white text-xs font-bold tracking-widest uppercase rounded-full mb-4 shadow-lg"><?= !empty($banner['is_ad']) ? 'Sponsored' : 'New Episode' ?></span>
                    <h2 class="text-5xl md:text-7xl font-anton tracking-wider mb-4 leading-tight drop-shadow-2xl uppercase"><?= htmlspecialchars($banner['title']) ?></h2>
                    <?php if (!empty($banner['synopsis'])): ?>
                    <p class="text-lg md:text-xl text-white/90 mb-8 line-clamp-3 font-light leading-relaxed max-w-2xl text-shadow"><?= htmlspecialchars($banner['synopsis']) ?></p>
                    <?php endif; ?>
                    <div class="flex gap-4">
                        <?php if (!empty($banner['is_ad'])): ?>
                        <a href="<?= htmlspecialchars($banner['slug']) ?>" target="_blank" rel="noopener sponsored" class="inline-flex items-center justify-center bg-white text-black font-bold px-8 py-3.5 rounded-md hover:bg-gray-200 transition-colors text-lg">
                            Learn More
                        </a>
                        <?php else: ?>
                        <a href="/movie/<?= htmlspecialchars($banner['slug']) ?>" class="inline-flex items-center justify-center bg-white text-black font-bold px-8 py-3.5 rounded-md hover:bg-gray-200 transition-colors text-lg">
                            <svg class="w-6 h-6 mr-2" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4l12 6-12 6z"></path></svg>
                            Play
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if(count($featuredSeries) > 1): ?>
        <script>
            // Each slide (ad or regular banner) can set its own on-screen duration
            // (ads: 5/10/15s per what the advertiser picked; regular banners: 5s).
            const bannerDurationsMs = <?= json_encode(array_map(function ($b) { return (int)($b['duration_seconds'] ?? 5) * 1000; }, $featuredSeries)) ?>;
            (function advance() {
                let alpineData = document.querySelector('[x-data]').__x.$data;
                const wait = bannerDurationsMs[alpineData.activeSlide] || 5000;
                setTimeout(() => {
                    alpineData.activeSlide = (alpineData.activeSlide + 1) % alpineData.slides;
                    advance();
                }, wait);
            })();
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

    <?php require __DIR__ . '/../partials/footer.php'; ?>
    <script src="/assets/js/protection.js"></script>
    <!-- Custom Footer Code -->
    <?php
        $stmt = $db->prepare("SELECT setting_value FROM site_config WHERE setting_key = 'custom_footer'");
        $stmt->execute();
        echo $stmt->fetchColumn();
    ?>
</body>
</html>
