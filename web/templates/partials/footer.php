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