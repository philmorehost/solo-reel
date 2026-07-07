<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?= htmlspecialchars($episode['title']) ?> - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/hls.js@latest"></script>
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <?php $adsenseId = \App\Helpers\Site::getConfig('google_adsense_client_id'); if (!empty($adsenseId)): ?>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= htmlspecialchars($adsenseId) ?>" crossorigin="anonymous"></script>
    <?php endif; ?>
</head>
<body class="reel-page bg-black text-white antialiased font-sans overflow-hidden">

    <!-- Navbar (Desktop only) -->
    <nav class="hidden md:flex fixed w-full z-50 bg-black/80 backdrop-blur border-b border-gray-800 items-center h-16">
        <div class="max-w-md mx-auto px-4 w-full flex items-center gap-4">
            <a href="/movie/<?= htmlspecialchars($episode['series_slug']) ?>" class="text-gray-400 hover:text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            </a>
            <span class="font-bold text-lg"><?= htmlspecialchars($episode['series_title']) ?></span>
        </div>
    </nav>

    <!-- Mobile back button overlay -->
    <a href="/movie/<?= htmlspecialchars($episode['series_slug']) ?>" class="md:hidden fixed top-4 left-4 z-50 p-2 bg-black/50 rounded-full text-white">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
    </a>

    <!-- Global mute toggle -->
    <button id="reel-mute-btn" type="button" class="fixed top-4 right-4 z-50 p-2 bg-black/50 rounded-full text-white" aria-label="Toggle mute">
        <svg id="reel-mute-icon" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.617.788L4.65 13.65H2a1 1 0 01-1-1v-5.3a1 1 0 011-1h2.65l3.733-3.138a1 1 0 011-.136zM14.657 5.343a1 1 0 011.414 0A7.975 7.975 0 0118 10a7.975 7.975 0 01-1.929 4.657 1 1 0 11-1.414-1.414A5.975 5.975 0 0016 10a5.975 5.975 0 00-1.343-3.243 1 1 0 010-1.414z"></path></svg>
    </button>

    <div id="reel-feed" class="reel-feed md:pt-0" role="list">
        <?php foreach ($feedEpisodes as $i => $ep): ?>
        <section
            class="reel-card md:max-w-md md:mx-auto"
            role="listitem"
            data-index="<?= $i ?>"
            data-id="<?= $ep['id'] ?>"
            data-slug="<?= htmlspecialchars($ep['slug']) ?>"
            data-video-url="<?= htmlspecialchars($ep['video_url'] ?? '') ?>"
            data-has-access="<?= $ep['has_access'] ? '1' : '0' ?>"
            data-episode-number="<?= $ep['episode_number'] ?>"
        >
            <?php if ($ep['has_access'] && !empty($ep['video_url'])): ?>
                <img src="<?= htmlspecialchars($ep['thumbnail_url'] ?? '/assets/img/default-thumb.jpg') ?>" class="reel-poster absolute inset-0 w-full h-full object-cover" alt="">
                <!-- <video> is only created by JS for the active card +/- 1 neighbor, to avoid mounting every episode's stream at once. -->
            <?php elseif ($ep['has_access']): ?>
                <!-- Videos are set the instant an admin uploads them (see EpisodeController::
                     handleVideoUpload()), so an unlocked episode with no video_url means the
                     upload never completed — show this instead of a silent black rectangle. -->
                <img src="<?= htmlspecialchars($ep['thumbnail_url'] ?? '/assets/img/default-thumb.jpg') ?>" class="absolute inset-0 w-full h-full object-cover opacity-50" alt="">
                <div class="absolute inset-0 flex flex-col items-center justify-center bg-black/60 z-30 p-6 text-center">
                    <svg class="w-10 h-10 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" stroke="currentColor" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"></path></svg>
                    <h2 class="text-xl font-bold mb-1">Video Unavailable</h2>
                    <p class="text-gray-400">This episode doesn't have a video yet.</p>
                </div>
            <?php else: ?>
                <img src="<?= htmlspecialchars($ep['thumbnail_url'] ?? '/assets/img/default-thumb.jpg') ?>" class="reel-locked-poster absolute inset-0 w-full h-full object-cover blur-sm scale-105 opacity-60" alt="">
                <div class="reel-locked-overlay absolute inset-0 flex flex-col items-center justify-center bg-black/70 z-30 p-6 text-center">
                    <svg class="w-16 h-16 text-gray-400 mb-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>
                    <h2 class="text-xl font-bold mb-1">Episode <?= $ep['episode_number'] ?> Locked</h2>
                    <p class="text-gray-400 mb-6">Unlock for <span class="text-white font-bold"><?= rtrim(rtrim(number_format($ep['coin_cost'], 2), '0'), '.') ?> Coins</span></p>

                    <button type="button" class="reel-unlock-btn w-full max-w-xs bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-full transition-colors" data-episode-id="<?= $ep['id'] ?>">
                        Unlock Now
                    </button>
                    <p class="reel-unlock-error mt-3 text-sm text-red-400 hidden"></p>
                    <p class="mt-4 text-sm text-gray-500">Your Balance: <span class="reel-coin-balance"><?= (float) \App\Core\Session::get('user_coin_balance', 0) ?></span> Coins</p>
                    <a href="/coin-shop" class="mt-2 text-sm text-red-500 hover:underline">Get more coins</a>
                    <?php if (!\App\Core\Session::isLoggedIn()): ?>
                        <p class="mt-4 text-xs text-gray-400">Guest mode. <a href="/login" class="text-white hover:underline">Login or Register</a> to save your coins.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="reel-tap-layer absolute inset-0 z-20"></div>
            <div class="reel-heart-burst absolute inset-0 flex items-center justify-center pointer-events-none z-40" aria-hidden="true">
                <svg class="w-24 h-24 text-red-500 opacity-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path></svg>
            </div>

            <div class="absolute bottom-6 left-4 right-4 z-30 pointer-events-none">
                <p class="font-bold text-lg drop-shadow">EP <?= $ep['episode_number'] ?> · <?= htmlspecialchars($ep['title']) ?></p>
                <p class="text-sm text-gray-300 drop-shadow"><?= htmlspecialchars($episode['series_title']) ?></p>
            </div>
        </section>
        <?php endforeach; ?>
    </div>

    <script>
        window.__REEL_EPISODES = <?= json_encode($feedEpisodes) ?>;
        window.__REEL_START_INDEX = <?= (int) $currentIndex ?>;
        window.__REEL_CSRF_TOKEN = <?= json_encode(\App\Core\Security::csrfToken()) ?>;
        window.__REEL_UNLOCK_URL_BASE = '/unlock/';
    </script>
    <script src="/assets/js/reel-player.js" defer></script>
    <script src="/assets/js/protection.js"></script>
</body>
</html>
