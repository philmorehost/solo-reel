<footer class="bg-black border-t border-white/10 mt-16">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-12">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <div class="col-span-2 md:col-span-1">
                <a href="/" class="inline-block mb-4"><?= \App\Helpers\Site::getLogoHtml() ?></a>
                <p class="text-gray-400 text-sm leading-relaxed">Stream the best vertical short dramas. Binge-worthy episodes in 9:16 format.</p>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-3 text-sm uppercase tracking-wider">Browse</h4>
                <ul class="space-y-2">
                    <li><a href="/" class="text-gray-400 hover:text-white text-sm transition">Home</a></li>
                    <li><a href="/search" class="text-gray-400 hover:text-white text-sm transition">Search</a></li>
                    <li><a href="/shelf/top" class="text-gray-400 hover:text-white text-sm transition">Top Series</a></li>
                    <li><a href="/blog" class="text-gray-400 hover:text-white text-sm transition">Blog</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-white font-semibold mb-3 text-sm uppercase tracking-wider">Account</h4>
                <ul class="space-y-2">
                    <?php if(\App\Core\Session::isLoggedIn()): ?>
                        <li><a href="/profile" class="text-gray-400 hover:text-white text-sm transition">Profile</a></li>
                        <li><a href="/coin-shop" class="text-gray-400 hover:text-white text-sm transition">Buy Coins</a></li>
                    <?php else: ?>
                        <li><a href="/login" class="text-gray-400 hover:text-white text-sm transition">Sign In</a></li>
                        <li><a href="/register" class="text-gray-400 hover:text-white text-sm transition">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <div class="border-t border-white/10 mt-8 pt-6 text-center">
            <p class="text-gray-500 text-xs">&copy; <?= date('Y') ?> SOLOREEL. All rights reserved.</p>
        </div>
    </div>
</footer>

<?php
$androidLink = \App\Helpers\Site::getConfig('android_app_link');
$iosLink = \App\Helpers\Site::getConfig('ios_app_link');
if ($androidLink || $iosLink):
?>
<!-- Sticky App Download Footer -->
<div id="app-download-sticky" class="fixed bottom-0 left-0 w-full z-50 transform translate-y-full transition-transform duration-300 hidden shadow-2xl" style="background: linear-gradient(90deg, #FF0055 0%, #FF8800 100%);">
    <div class="max-w-[1400px] mx-auto px-4 sm:px-6 lg:px-8 py-3 flex items-center justify-between">
        <div class="flex items-center text-white">
            <svg class="w-8 h-8 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            <div class="font-bold text-sm sm:text-base tracking-wide leading-tight">
                Experience SOLOREEL <br class="sm:hidden">in our Mobile App!
            </div>
        </div>
        <div class="flex items-center space-x-2 sm:space-x-3">
            <?php if($androidLink): ?>
                <a href="<?= htmlspecialchars($androidLink) ?>" target="_blank" aria-label="Download on Android" title="Download on Android" class="bg-white text-[#3DDC84] w-9 h-9 sm:w-11 sm:h-11 rounded-full flex items-center justify-center hover:scale-110 transition-transform shadow-lg flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M17.6 9.48l1.84-3.18c.16-.31.04-.69-.26-.85a.637.637 0 00-.83.22l-1.88 3.24a11.463 11.463 0 00-8.94 0L5.65 5.67a.643.643 0 00-.87-.2c-.28.18-.37.54-.22.83L6.4 9.48C3.3 11.25 1.28 14.44 1 18h22c-.28-3.56-2.3-6.75-5.4-8.52zM7 15.25a1.25 1.25 0 110-2.5 1.25 1.25 0 010 2.5zm10 0a1.25 1.25 0 110-2.5 1.25 1.25 0 010 2.5z"/></svg>
                </a>
            <?php endif; ?>
            <?php if($iosLink): ?>
                <a href="<?= htmlspecialchars($iosLink) ?>" target="_blank" aria-label="Download on iOS" title="Download on iOS" class="bg-white text-black w-9 h-9 sm:w-11 sm:h-11 rounded-full flex items-center justify-center hover:scale-110 transition-transform shadow-lg flex-shrink-0">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" viewBox="0 0 24 24" fill="currentColor"><path d="M12.152 6.896c-.948 0-2.415-1.078-3.96-1.04-2.04.027-3.91 1.183-4.961 3.014-2.117 3.675-.546 9.103 1.519 12.09 1.013 1.454 2.208 3.09 3.792 3.039 1.52-.065 2.09-.987 3.935-.987 1.831 0 2.35.987 3.96.948 1.637-.026 2.676-1.48 3.676-2.948 1.156-1.688 1.636-3.325 1.662-3.415-.039-.013-3.182-1.221-3.22-4.857-.026-3.04 2.48-4.494 2.597-4.559-1.429-2.09-3.623-2.324-4.39-2.376-2-.156-3.675 1.09-4.61 1.09zm3.415-3.09c.844-1.026 1.417-2.454 1.26-3.883-1.222.052-2.7.858-3.57 1.883-.78.91-1.466 2.376-1.284 3.767 1.36.104 2.75-.688 3.594-1.767z"/></svg>
                </a>
            <?php endif; ?>
            <button id="app-download-close" class="text-white hover:text-gray-200 focus:outline-none ml-2 sm:ml-3 p-1 flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (!localStorage.getItem('appDownloadClosed')) {
        const banner = document.getElementById('app-download-sticky');
        if (banner) {
            banner.classList.remove('hidden');
            // small delay to allow transition
            setTimeout(() => {
                banner.classList.remove('translate-y-full');
            }, 100);
        }
    }
    
    const closeBtn = document.getElementById('app-download-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            const banner = document.getElementById('app-download-sticky');
            banner.classList.add('translate-y-full');
            setTimeout(() => {
                banner.classList.add('hidden');
            }, 300);
            localStorage.setItem('appDownloadClosed', 'true');
        });
    }
});
</script>
<?php endif; ?>