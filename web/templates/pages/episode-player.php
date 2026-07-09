<?php
function reel_format_count($n) {
    $n = (int) $n;
    if ($n < 1000) return (string) $n;
    if ($n < 1000000) return rtrim(rtrim(number_format($n / 1000, 1), '0'), '.') . 'K';
    return rtrim(rtrim(number_format($n / 1000000, 1), '0'), '.') . 'M';
}
?>
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

                    <button type="button" class="reel-unlock-btn w-full max-w-xs bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-full transition-colors" data-episode-id="<?= $ep['id'] ?>" data-episode-slug="<?= htmlspecialchars($ep['slug']) ?>" data-coin-cost="<?= (float) $ep['coin_cost'] ?>">
                        Unlock Now
                    </button>
                    <p class="reel-unlock-error mt-3 text-sm text-red-400 hidden"></p>
                    <p class="mt-4 text-sm text-gray-500">Your Balance: <span class="reel-coin-balance"><?= (float) \App\Core\Session::get('user_coin_balance', 0) ?></span> Coins</p>
                    <a href="/coin-shop" class="reel-get-coins-link mt-2 text-sm text-red-500 hover:underline" data-episode-slug="<?= htmlspecialchars($ep['slug']) ?>" data-coin-cost="<?= (float) $ep['coin_cost'] ?>">Get more coins</a>
                    <?php if (!\App\Core\Session::isLoggedIn()): ?>
                        <p class="mt-4 text-xs text-gray-400">Guest mode. <a href="/login" class="text-white hover:underline">Login or Register</a> to save your coins.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="reel-tap-layer absolute inset-0 z-20"></div>
            <div class="reel-heart-burst absolute inset-0 flex items-center justify-center pointer-events-none z-40" aria-hidden="true">
                <svg class="w-24 h-24 text-red-500 opacity-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3.172 5.172a4 4 0 015.656 0L10 6.343l1.172-1.171a4 4 0 115.656 5.656L10 17.657l-6.828-6.829a4 4 0 010-5.656z" clip-rule="evenodd"></path></svg>
            </div>

            <!-- Instagram-style action rail: like, comment, save, share (share
                 hidden past episode 2, computed server-side in can_share). -->
            <div class="reel-action-rail absolute right-3 bottom-28 z-30 flex flex-col items-center gap-5">
                <button type="button" class="reel-action-btn reel-like-btn flex flex-col items-center gap-1 text-white" data-episode-id="<?= $ep['id'] ?>" aria-pressed="<?= $ep['is_liked_by_viewer'] ? 'true' : 'false' ?>">
                    <svg class="reel-like-icon w-8 h-8 drop-shadow-lg <?= $ep['is_liked_by_viewer'] ? 'text-red-500' : 'text-white' ?>" fill="<?= $ep['is_liked_by_viewer'] ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                    </svg>
                    <span class="reel-like-count text-xs font-semibold drop-shadow"><?= reel_format_count($ep['like_count']) ?></span>
                </button>
                <button type="button" class="reel-action-btn reel-comment-btn flex flex-col items-center gap-1 text-white" data-episode-id="<?= $ep['id'] ?>">
                    <svg class="w-8 h-8 drop-shadow-lg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513-1.584.233-2.707 1.626-2.707 3.228v6.02z" />
                    </svg>
                    <span class="reel-comment-count text-xs font-semibold drop-shadow"><?= reel_format_count($ep['comment_count']) ?></span>
                </button>
                <button type="button" class="reel-action-btn reel-save-btn flex flex-col items-center gap-1 text-white" data-episode-id="<?= $ep['id'] ?>" aria-pressed="<?= $ep['is_saved_by_viewer'] ? 'true' : 'false' ?>">
                    <svg class="reel-save-icon w-8 h-8 drop-shadow-lg <?= $ep['is_saved_by_viewer'] ? 'text-yellow-400' : 'text-white' ?>" fill="<?= $ep['is_saved_by_viewer'] ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                    </svg>
                    <span class="reel-save-count text-xs font-semibold drop-shadow"><?= reel_format_count($ep['save_count']) ?></span>
                </button>
                <?php if (!empty($ep['can_share'])): ?>
                <button type="button" class="reel-action-btn reel-share-btn flex flex-col items-center gap-1 text-white" data-episode-id="<?= $ep['id'] ?>" data-video-url="<?= htmlspecialchars($ep['video_url'] ?? '') ?>">
                    <svg class="w-8 h-8 drop-shadow-lg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.876L5.999 12zm0 0h7.5" />
                    </svg>
                    <span class="reel-share-count text-xs font-semibold drop-shadow"><?= reel_format_count($ep['share_count']) ?></span>
                </button>
                <?php endif; ?>
                <button type="button" class="reel-action-btn reel-info-btn flex flex-col items-center gap-1 text-white" data-episode-id="<?= $ep['id'] ?>" aria-label="Series info">
                    <svg class="w-7 h-7 drop-shadow-lg" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                    </svg>
                </button>
            </div>

            <div class="absolute bottom-6 left-4 right-16 z-30 pointer-events-none">
                <p class="font-bold text-lg drop-shadow">EP <?= $ep['episode_number'] ?> · <?= htmlspecialchars($ep['title']) ?></p>
                <p class="text-sm text-gray-300 drop-shadow"><?= htmlspecialchars($episode['series_title']) ?></p>
            </div>
        </section>
        <?php endforeach; ?>
    </div>

    <!-- Desktop episode-picker sidebar (reelshort-style) — hidden on mobile,
         where the vertical swipe feed is the only navigation method. Series-
         wide like/save/comment totals here, per-episode state elsewhere. -->
    <aside id="reel-desktop-sidebar" class="hidden lg:flex flex-col fixed top-0 right-0 w-[380px] h-screen bg-[#0f0f11] border-l border-white/10 z-40 pt-20 pb-6 px-6 overflow-y-auto">
        <div class="flex-shrink-0 mb-4">
            <nav class="text-xs text-gray-400 mb-3 truncate">
                <a href="/" class="hover:text-white">Home</a>
                <span class="mx-1">/</span>
                <a href="/movie/<?= htmlspecialchars($episode['series_slug']) ?>" class="hover:text-white"><?= htmlspecialchars($episode['series_title']) ?></a>
                <span class="mx-1">/</span>
                <span id="reel-sidebar-breadcrumb-ep" class="text-gray-300">Episode <?= (int) $episode['episode_number'] ?></span>
            </nav>
            <h1 id="reel-sidebar-title" class="text-xl font-bold mb-3 leading-snug">Episode <?= (int) $episode['episode_number'] ?> - <?= htmlspecialchars($episode['series_title']) ?></h1>
            <?php if (!empty($episode['series_synopsis'])): ?>
            <div class="mb-3">
                <h2 id="reel-sidebar-plot-heading" class="text-sm font-semibold text-gray-300 mb-1">Plot of Episode <?= (int) $episode['episode_number'] ?></h2>
                <p class="reel-sidebar-synopsis text-sm text-gray-400 leading-relaxed line-clamp-3"><?= htmlspecialchars($episode['series_synopsis']) ?></p>
                <button type="button" class="reel-sidebar-synopsis-toggle text-red-500 text-sm font-semibold hover:underline mt-1">More</button>
            </div>
            <?php endif; ?>
            <?php if (!empty($episode['series_genre'])): ?>
            <div class="flex flex-wrap gap-2">
                <?php foreach (array_filter(array_map('trim', explode(',', $episode['series_genre']))) as $tag): ?>
                    <span class="bg-gray-800 text-gray-200 text-xs font-medium px-3 py-1.5 rounded"><?= htmlspecialchars($tag) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="flex items-center justify-around py-4 border-b border-white/10 mb-4 flex-shrink-0">
            <button type="button" id="reel-sidebar-like-btn" class="flex flex-col items-center gap-1 text-white hover:opacity-80 transition">
                <svg id="reel-sidebar-like-icon" class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                </svg>
                <span id="reel-sidebar-like-total" class="text-sm font-semibold"><?= reel_format_count($seriesTotals['like_count']) ?></span>
            </button>
            <button type="button" id="reel-sidebar-save-btn" class="flex flex-col items-center gap-1 text-white hover:opacity-80 transition">
                <svg id="reel-sidebar-save-icon" class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z" />
                </svg>
                <span id="reel-sidebar-save-total" class="text-sm font-semibold"><?= reel_format_count($seriesTotals['save_count']) ?></span>
            </button>
            <button type="button" id="reel-sidebar-comment-btn" class="flex flex-col items-center gap-1 text-white hover:opacity-80 transition">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.76c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513-1.584.233-2.707 1.626-2.707 3.228v6.02z" />
                </svg>
                <span class="text-sm font-semibold"><?= reel_format_count($seriesTotals['comment_count']) ?></span>
            </button>
            <button type="button" id="reel-sidebar-share-btn" class="flex flex-col items-center gap-1 text-white hover:opacity-80 transition">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.876L5.999 12zm0 0h7.5" />
                </svg>
                <span class="text-sm font-semibold">Share</span>
            </button>
            <button type="button" id="reel-sidebar-info-btn" class="flex flex-col items-center gap-1 text-white hover:opacity-80 transition" aria-label="Series info">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                </svg>
            </button>
        </div>

        <div id="reel-sidebar-range-tabs" class="flex gap-5 border-b border-white/10 mb-4 flex-shrink-0 text-sm font-semibold text-gray-400"></div>

        <div id="reel-sidebar-grid" class="grid grid-cols-6 gap-2 overflow-y-auto flex-1 content-start"></div>
    </aside>

    <!-- Series info bottom sheet: lazy-loaded on open, shared across cards -->
    <div id="reel-info-sheet" class="reel-sheet fixed inset-0 z-50 hidden">
        <div class="reel-sheet-backdrop absolute inset-0 bg-black/60"></div>
        <div class="reel-sheet-panel absolute bottom-0 left-0 right-0 md:max-w-md md:mx-auto bg-[#141416] rounded-t-2xl max-h-[75vh] flex flex-col translate-y-full transition-transform duration-300 ease-out">
            <div class="flex items-center justify-between p-4 border-b border-gray-800 flex-shrink-0">
                <h3 class="font-bold text-lg">Series Info</h3>
                <button type="button" class="reel-sheet-close text-gray-400 hover:text-white p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="reel-info-body overflow-y-auto p-4 flex-1">
                <div class="text-center text-gray-500 py-8">Loading...</div>
            </div>
        </div>
    </div>

    <!-- Comments bottom sheet: shared across cards, repopulated for whichever
         episode's comment button was tapped. -->
    <div id="reel-comments-sheet" class="reel-sheet fixed inset-0 z-50 hidden">
        <div class="reel-sheet-backdrop absolute inset-0 bg-black/60"></div>
        <div class="reel-sheet-panel absolute bottom-0 left-0 right-0 md:max-w-md md:mx-auto bg-[#141416] rounded-t-2xl max-h-[75vh] flex flex-col translate-y-full transition-transform duration-300 ease-out">
            <div class="flex items-center justify-between p-4 border-b border-gray-800 flex-shrink-0">
                <h3 class="font-bold text-lg">Comments (<span class="reel-comments-total">0</span>)</h3>
                <button type="button" class="reel-sheet-close text-gray-400 hover:text-white p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="reel-comments-list overflow-y-auto p-4 flex-1 space-y-4">
                <div class="text-center text-gray-500 py-8">Loading...</div>
            </div>
            <form class="reel-comment-form flex items-center gap-2 p-3 border-t border-gray-800 flex-shrink-0">
                <input type="text" class="reel-comment-input flex-1 bg-gray-900 border border-gray-700 rounded-full px-4 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-red-500" placeholder="Add a comment..." maxlength="500">
                <button type="submit" class="reel-comment-submit bg-red-600 hover:bg-red-700 text-white rounded-full p-2 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.769 59.769 0 0121.485 12 59.768 59.768 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                </button>
            </form>
        </div>
    </div>

    <!-- Unlock-offers sheet: VIP plans + coin top-up, shown when a locked
         episode can't be unlocked with the viewer's current balance. Every
         card is its own form that posts straight to the existing Payhub
         checkout flow, carrying return_to so payment lands back on this
         exact episode afterward (see CoinController::purchase()/subscribeVip()
         and PaymentController::verify()). -->
    <div id="reel-offers-sheet" class="reel-sheet fixed inset-0 z-50 hidden">
        <div class="reel-sheet-backdrop absolute inset-0 bg-black/60"></div>
        <div class="reel-sheet-panel absolute bottom-0 left-0 right-0 md:max-w-lg md:mx-auto bg-[#141416] rounded-t-2xl max-h-[85vh] flex flex-col translate-y-full transition-transform duration-300 ease-out">
            <div class="flex items-center justify-between p-4 border-b border-gray-800 flex-shrink-0">
                <div class="text-sm text-gray-300">
                    Price: <span class="reel-offers-price text-yellow-400 font-bold">0</span>
                    <span class="mx-2 text-gray-600">|</span>
                    Balance: <span class="reel-offers-balance text-yellow-400 font-bold"><?= (int) \App\Core\Session::get('user_coin_balance', 0) ?></span>
                </div>
                <button type="button" class="reel-sheet-close text-gray-400 hover:text-white p-1">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="overflow-y-auto p-4 flex-1">
                <?php if (!empty($vipPlans)): ?>
                <h3 class="font-bold text-lg mb-1">VIP Unlock all series for free</h3>
                <p class="text-xs text-gray-500 mb-3">Auto renew. Cancel anytime.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
                    <?php foreach ($vipPlans as $plan): ?>
                    <form method="POST" action="/vip/subscribe">
                        <?= \App\Core\Security::csrfField() ?>
                        <input type="hidden" name="plan_id" value="<?= (int) $plan['id'] ?>">
                        <input type="hidden" name="return_to" class="reel-offer-return-to" value="">
                        <button type="submit" class="w-full text-left rounded-xl p-4 bg-gradient-to-br from-amber-200 to-amber-400 text-black hover:brightness-105 transition">
                            <p class="font-bold"><?= htmlspecialchars($plan['name']) ?></p>
                            <p class="text-2xl font-extrabold mt-1"><?= htmlspecialchars($plan['currency']) ?> <?= number_format($plan['price'], 2) ?></p>
                            <p class="text-xs mt-1 opacity-80">Auto-renew. Cancel anytime.</p>
                            <div class="flex flex-wrap gap-x-3 gap-y-1 mt-3 text-xs font-semibold">
                                <?php if (!empty($plan['perk_free_unlocks'])): ?><span>Unlimited Viewing</span><?php endif; ?>
                                <?php if (!empty($plan['perk_ad_free'])): ?><span>Ad-Free</span><?php endif; ?>
                            </div>
                        </button>
                    </form>
                    <?php endforeach; ?>
                </div>
                <?php if (!\App\Core\Session::isLoggedIn()): ?>
                <p class="text-xs text-gray-400 mb-6 -mt-3">VIP requires an account. <a href="/login" class="text-white hover:underline">Login or Register</a> to subscribe.</p>
                <?php endif; ?>
                <?php endif; ?>

                <?php if (!empty($coinPackages)): ?>
                <h3 class="font-bold text-lg mb-3">Top up coins</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <?php foreach ($coinPackages as $pkg): ?>
                    <form method="POST" action="/coins/purchase">
                        <?= \App\Core\Security::csrfField() ?>
                        <input type="hidden" name="package_id" value="<?= (int) $pkg['id'] ?>">
                        <input type="hidden" name="return_to" class="reel-offer-return-to" value="">
                        <button type="submit" class="w-full text-left rounded-xl p-3 bg-gray-800 hover:bg-gray-700 transition relative overflow-hidden">
                            <div class="absolute top-0 left-0 right-0 h-1" style="background-color: <?= htmlspecialchars($pkg['color_code'] ?? '#dc2626') ?>;"></div>
                            <p class="text-lg font-bold text-white flex items-center gap-1">
                                <svg class="w-4 h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" clip-rule="evenodd"></path></svg>
                                <?= (int) $pkg['coins'] ?>
                            </p>
                            <p class="text-xs text-gray-400 mt-1"><?= htmlspecialchars($pkg['currency']) ?> <?= number_format($pkg['price'], 2) ?></p>
                        </button>
                    </form>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        window.__REEL_EPISODES = <?= json_encode($feedEpisodes) ?>;
        window.__REEL_START_INDEX = <?= (int) $currentIndex ?>;
        window.__REEL_CSRF_TOKEN = <?= json_encode(\App\Core\Security::csrfToken()) ?>;
        window.__REEL_UNLOCK_URL_BASE = '/unlock/';
        window.__REEL_PROGRESS_URL_BASE = '/progress/';
        window.__REEL_LIKE_URL_BASE = '/episodes/';
        window.__REEL_SAVE_URL_BASE = '/episodes/';
        window.__REEL_SHARE_URL_BASE = '/episodes/';
        window.__REEL_COMMENTS_URL_BASE = '/episodes/';
        window.__REEL_SERIES_SLUG = <?= json_encode($episode['series_slug']) ?>;
        window.__REEL_SERIES_TITLE = <?= json_encode($episode['series_title']) ?>;
        window.__REEL_SERIES_TOTALS = <?= json_encode($seriesTotals) ?>;
    </script>
    <script src="/assets/js/reel-player.js" defer></script>
    <script src="/assets/js/protection.js"></script>
</body>
</html>
