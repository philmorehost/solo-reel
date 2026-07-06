<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($episode['title']) ?> - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <style>
        /* Mobile vertical optimization */
        .video-container { height: 100vh; width: 100vw; background: #000; position: relative; }
        @media (min-width: 768px) {
            .video-container { height: calc(100vh - 64px); max-width: 450px; margin: 0 auto; aspect-ratio: 9/16; }
        }
        video { width: 100%; height: 100%; object-fit: cover; }
    </style>
    <?php $adsenseId = \App\Helpers\Site::getConfig('google_adsense_client_id'); if (!empty($adsenseId)): ?>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= htmlspecialchars($adsenseId) ?>" crossorigin="anonymous"></script>
    <?php endif; ?>
</head>
<body class="bg-black text-white antialiased font-sans overflow-hidden">

    <!-- Navbar (Desktop only) -->
    <nav class="hidden md:block fixed w-full z-50 bg-black/80 backdrop-blur border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center gap-4">
                    <a href="/movie/<?= $episode['series_slug'] ?>" class="text-gray-400 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>
                    <span class="font-bold text-lg"><?= htmlspecialchars($episode['series_title']) ?></span>
                </div>
            </div>
        </div>
    </nav>

    <div class="video-container md:pt-16 relative">
        <!-- Mobile back button overlay -->
        <a href="/movie/<?= $episode['series_slug'] ?>" class="md:hidden absolute top-4 left-4 z-50 p-2 bg-black/50 rounded-full text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        </a>

        <?php if($hasAccess): ?>
            <video id="video" controls autoplay playsinline controlsList="nodownload" oncontextmenu="return false;"></video>

            <script>
                var video = document.getElementById('video');
                var videoSrc = '<?= htmlspecialchars($episode['video_url'] ?? '') ?>';

                if (Hls.isSupported()) {
                    var hls = new Hls();
                    hls.loadSource(videoSrc);
                    hls.attachMedia(video);
                } else if (video.canPlayType('application/vnd.apple.mpegurl')) {
                    video.src = videoSrc;
                }

                <?php if($nextEpisode): ?>
                video.addEventListener('ended', function() {
                    window.location.href = '/episodes/<?= $nextEpisode['slug'] ?>';
                });
                <?php endif; ?>
            </script>
        <?php else: ?>
            <div class="absolute inset-0 flex flex-col items-center justify-center bg-gray-900/90 z-40 p-6 text-center">
                <svg class="w-16 h-16 text-gray-500 mb-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
                <h2 class="text-2xl font-bold mb-2">Episode Locked</h2>
                <p class="text-gray-400 mb-6">Unlock this episode for <span class="text-white font-bold"><?= (float)$episode['coin_cost'] ?> Coins</span></p>

                    <form action="/unlock/<?= $episode['id'] ?>" method="POST" class="w-full max-w-xs">
                        <?= \App\Core\Security::csrfField() ?>
                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-full transition-colors flex items-center justify-center gap-2">
                            Unlock Now
                        </button>
                    </form>
                    <p class="mt-4 text-sm text-gray-500">Your Balance: <?= \App\Core\Session::get('user_coin_balance', 0) ?> Coins</p>
                    <a href="/coin-shop" class="mt-2 text-sm text-red-500 hover:underline">Get more coins</a>
                    <?php if(!\App\Core\Session::isLoggedIn()): ?>
                        <p class="mt-4 text-xs text-gray-400">Guest mode. <a href="/login" class="text-white hover:underline">Login or Register</a> to save your coins.</p>
                    <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <script src="/assets/js/protection.js"></script>
</body>
</html>
