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
        <div class="flex items-center space-x-2 sm:space-x-4">
            <?php if($androidLink): ?>
                <a href="<?= htmlspecialchars($androidLink) ?>" target="_blank" class="bg-white text-[#FF0055] font-black py-1.5 px-3 sm:px-5 rounded-full text-xs sm:text-sm hover:scale-105 transition-transform shadow-lg uppercase tracking-wider">Android</a>
            <?php endif; ?>
            <?php if($iosLink): ?>
                <a href="<?= htmlspecialchars($iosLink) ?>" target="_blank" class="bg-white text-[#FF8800] font-black py-1.5 px-3 sm:px-5 rounded-full text-xs sm:text-sm hover:scale-105 transition-transform shadow-lg uppercase tracking-wider">iOS</a>
            <?php endif; ?>
            <button id="app-download-close" class="text-white hover:text-gray-200 focus:outline-none ml-2 sm:ml-4 p-1">
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