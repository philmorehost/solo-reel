<?php
// Responsive site header with hamburger menu
$logoHtml = \App\Helpers\Site::getLogoHtml();
$isLoggedIn = \App\Core\Session::isLoggedIn();
$userName = \App\Core\Session::get('user_name');
$coinBalance = \App\Core\Session::get('user_coin_balance');
?><script src="/assets/js/header-search.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>
    /* Prevent text selection and drag */
    body {
        -webkit-user-select: none;
        -moz-user-select: none;
        -ms-user-select: none;
        user-select: none;
        -webkit-touch-callout: none;
    }
    img {
        -webkit-user-drag: none;
        -khtml-user-drag: none;
        -moz-user-drag: none;
        -o-user-drag: none;
    }
</style>
<script>
    // Prevent right click
    document.addEventListener('contextmenu', event => event.preventDefault());

    // Prevent Print Screen and shortcuts for screenshots/recording
    document.addEventListener('keyup', (e) => {
        if (e.key === 'PrintScreen' || e.code === 'PrintScreen') {
            navigator.clipboard.writeText(''); // Clear clipboard
            alert("Screenshots are disabled on this website for security reasons.");
        }
    });

    document.addEventListener('keydown', (e) => {
        // Prevent common shortcuts (Ctrl+S, Ctrl+P, Mac equivalents)
        if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'p')) {
            e.preventDefault();
        }
    });
</script>
<nav x-data="{ scrolled: false, mobileOpen: false }"
     @scroll.window="scrolled = (window.pageYOffset > 50)"
     :class="{ 'bg-black/90 backdrop-blur-md shadow-lg': scrolled, 'bg-transparent': !scrolled }"
     class="fixed w-full z-50 transition-all duration-300">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 relative z-50">
        <div class="flex items-center justify-between h-16 sm:h-20 gap-3">
            <div class="flex items-center min-w-0">
                <a href="/" class="transition-transform hover:scale-105 mr-4 sm:mr-6 flex-shrink-0"><?= $logoHtml ?></a>
                <div class="hidden lg:flex items-center space-x-6 flex-shrink-0">
                    <a href="/" class="text-white font-semibold hover:text-red-500 transition text-sm lg:text-base">Home</a>
                    <a href="/for-you" class="text-gray-300 font-medium hover:text-white transition text-sm lg:text-base">For You</a>
                    <a href="/my-list" class="text-gray-300 font-medium hover:text-white transition text-sm lg:text-base">My List</a>
                    <a href="/blog" class="text-gray-300 font-medium hover:text-white transition text-sm lg:text-base">Blog</a>
                    <?php if($isLoggedIn): ?><a href="/vip" class="text-yellow-400 font-semibold hover:text-yellow-300 transition text-sm lg:text-base">👑 VIP</a><?php endif; ?>
                </div>
            </div>

            <!-- Live search with typeahead dropdown -->
            <div class="flex-1 max-w-md relative" x-data="headerSearch()" @click.outside="showResults = false">
                <div class="relative">
                    <input type="text" x-model="query" @input="onInput()" @focus="if (results.length) showResults = true"
                           placeholder="Search titles..."
                           class="w-full bg-gray-900/80 border border-gray-700 focus:border-red-500 rounded-full pl-10 pr-4 py-2 sm:py-2.5 text-sm text-white placeholder-gray-500 focus:outline-none transition">
                    <svg class="w-4 h-4 text-gray-500 absolute left-3.5 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <svg x-show="isLoading" class="w-4 h-4 text-red-500 animate-spin absolute right-3.5 top-1/2 -translate-y-1/2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                </div>
                <div x-show="showResults" x-transition x-cloak
                     class="absolute top-full mt-2 left-0 right-0 bg-gray-900 border border-gray-700 rounded-xl shadow-2xl overflow-hidden z-50 max-h-[70vh] overflow-y-auto">
                    <template x-if="!isLoading && results.length === 0">
                        <p class="text-gray-500 text-sm text-center py-6">No titles found.</p>
                    </template>
                    <template x-for="item in results" :key="item.id">
                        <a :href="item.resume_slug ? '/episodes/' + item.resume_slug : '/movie/' + item.slug"
                           class="flex items-center gap-3 px-4 py-2.5 hover:bg-white/5 transition border-b border-white/5 last:border-0">
                            <img :src="item.cover_image_url || '/assets/img/default-cover.jpg'" class="w-10 h-14 object-cover rounded flex-shrink-0 bg-gray-800">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-white truncate" x-text="item.title"></p>
                                <p class="text-xs text-gray-500" x-text="(item.genre || 'Drama') + ' · ' + item.episode_count + ' episodes'"></p>
                            </div>
                        </a>
                    </template>
                </div>
            </div>

            <div class="flex items-center space-x-3 sm:space-x-4 flex-shrink-0">
                <?php if($isLoggedIn): ?>
                    <a href="/coin-shop" class="flex items-center gap-1.5 text-xs sm:text-sm font-bold bg-gray-900/80 border border-gray-700 hover:border-yellow-500 px-2.5 sm:px-3 py-1.5 rounded-full transition">
                        <svg class="w-3.5 h-3.5 sm:w-4 sm:h-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"></path></svg>
                        <span class="hidden xs:inline"><?= (int)$coinBalance ?></span>
                    </a>
                    <a href="/profile" class="text-gray-300 hover:text-white">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 bg-red-600 rounded-full flex items-center justify-center text-white font-bold text-xs sm:text-sm">
                            <?= strtoupper(substr($userName, 0, 1)) ?>
                        </div>
                    </a>
                <?php else: ?>
                    <a href="/login" class="hidden sm:inline text-white font-medium hover:text-red-500 transition text-sm">Sign In</a>
                    <a href="/register" class="bg-red-600 hover:bg-red-700 shadow-[0_0_15px_rgba(220,38,38,0.5)] text-white px-3 sm:px-5 py-2 sm:py-2.5 rounded-md font-semibold transition-all text-sm whitespace-nowrap">Sign Up</a>
                <?php endif; ?>
                <button @click="mobileOpen = !mobileOpen" class="lg:hidden p-2 text-white/60 hover:text-white focus:outline-none">
                    <svg x-show="!mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    <svg x-show="mobileOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        </div>
    </div>
    <!-- Mobile backdrop -->
    <div x-show="mobileOpen" x-transition.opacity @click="mobileOpen = false" class="fixed inset-0 z-40 bg-black/50 md:hidden" x-cloak></div>
    <!-- Mobile Menu -->
    <div x-show="mobileOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 -translate-y-4"
         class="fixed top-16 sm:top-20 left-0 right-0 z-50 lg:hidden bg-black/95 backdrop-blur-xl border-t border-white/10" x-cloak>
        <div class="px-4 py-4 space-y-1">
            <a href="/" @click="mobileOpen = false" class="block px-4 py-3 rounded-lg text-white font-semibold bg-white/5">Home</a>
            <a href="/for-you" @click="mobileOpen = false" class="block px-4 py-3 rounded-lg text-gray-300 hover:bg-white/5 transition">For You</a>
            <a href="/my-list" @click="mobileOpen = false" class="block px-4 py-3 rounded-lg text-gray-300 hover:bg-white/5 transition">My List</a>
            <a href="/blog" @click="mobileOpen = false" class="block px-4 py-3 rounded-lg text-gray-300 hover:bg-white/5 transition">Blog</a>
            <a href="/shelf/top" @click="mobileOpen = false" class="block px-4 py-3 rounded-lg text-gray-300 hover:bg-white/5 transition">Top Series</a>
            <?php if($isLoggedIn): ?><a href="/vip" @click="mobileOpen = false" class="block px-4 py-3 rounded-lg text-yellow-400 font-semibold hover:bg-white/5 transition">👑 VIP</a><?php endif; ?>
            <hr class="border-white/10 my-2">
            <?php if($isLoggedIn): ?>
                <a href="/profile" @click="mobileOpen = false" class="block px-4 py-3 rounded-lg text-gray-300 hover:bg-white/5 transition">Profile</a>
                <a href="/coin-shop" @click="mobileOpen = false" class="block px-4 py-3 rounded-lg text-gray-300 hover:bg-white/5 transition">Coin Shop</a>
                <a href="/favorites" @click="mobileOpen = false" class="block px-4 py-3 rounded-lg text-gray-300 hover:bg-white/5 transition">Favorites</a>
                <a href="/watch-history" @click="mobileOpen = false" class="block px-4 py-3 rounded-lg text-gray-300 hover:bg-white/5 transition">Watch History</a>
                <a href="/advertise" @click="mobileOpen = false" class="block px-4 py-3 rounded-lg text-gray-300 hover:bg-white/5 transition">Advertise With Us</a>
                <a href="/logout" @click="mobileOpen = false" class="block px-4 py-3 rounded-lg text-red-400 hover:bg-red-500/10 transition">Logout</a>
            <?php else: ?>
                <a href="/login" @click="mobileOpen = false" class="block px-4 py-3 rounded-lg text-red-400 font-semibold hover:bg-red-500/10 transition">Sign In</a>
                <a href="/register" @click="mobileOpen = false" class="block px-4 py-3 rounded-lg bg-red-600 text-white font-semibold hover:bg-red-700 transition text-center">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Website Anti-Screenshot & Anti-Screen Recording Protection -->
<style>
    /* Disable print stylesheet to prevent saving page as PDF / print screenshots */
    @media print {
        html, body {
            display: none !important;
        }
    }
    /* Disable text selection and dragging of media */
    html, body {
        -webkit-user-select: none !important;
        -moz-user-select: none !important;
        -ms-user-select: none !important;
        user-select: none !important;
        -webkit-user-drag: none !important;
    }
</style>
<script>
    // 1. Disable Right Click (Context Menu)
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
    });

    // 2. Disable Copy, Cut, and Paste
    document.addEventListener('copy', function(e) {
        e.preventDefault();
    });
    document.addEventListener('cut', function(e) {
        e.preventDefault();
    });

    // 3. Detect and block common screenshot/recording keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Prevent F12 (Developer Tools)
        if (e.key === 'F12') {
            e.preventDefault();
            return false;
        }
        
        // Prevent Ctrl+Shift+I / Cmd+Opt+I (Developer Tools)
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'I' || e.key === 'i')) {
            e.preventDefault();
            return false;
        }

        // Prevent Ctrl+Shift+J / Cmd+Opt+J (Console)
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'J' || e.key === 'j')) {
            e.preventDefault();
            return false;
        }

        // Prevent Ctrl+U / Cmd+Opt+U (View Source)
        if ((e.ctrlKey || e.metaKey) && (e.key === 'U' || e.key === 'u')) {
            e.preventDefault();
            return false;
        }

        // Prevent Ctrl+S / Cmd+S (Save Page)
        if ((e.ctrlKey || e.metaKey) && (e.key === 'S' || e.key === 's')) {
            e.preventDefault();
            return false;
        }

        // Attempt to detect and empty clipboard on PrintScreen keypress
        if (e.key === 'PrintScreen' || e.keyCode === 44) {
            e.preventDefault();
            navigator.clipboard.writeText('').catch(function() {});
            alert('Screenshots are disabled on this platform.');
            return false;
        }
    });

    // 4. Continuously empty clipboard if the window is in focus to prevent screenshot pasting
    setInterval(function() {
        if (document.hasFocus()) {
            navigator.clipboard.writeText('').catch(function() {});
        }
    }, 1000);
</script>