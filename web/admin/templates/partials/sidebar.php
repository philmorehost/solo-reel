<script>
function toggleAdminSidebar() {
    var panel = document.getElementById('admin-sidebar');
    var overlay = document.getElementById('admin-overlay');
    if (!panel) return;
    var open = panel.classList.toggle('open');
    if (overlay) overlay.classList.toggle('hidden', !open);
}
</script>
<div id="admin-overlay" class="admin-sidebar-overlay hidden md:hidden" onclick="toggleAdminSidebar()"></div>
<aside id="admin-sidebar" class="admin-sidebar-panel w-64 bg-gray-900 text-white flex flex-col md:static md:translate-x-0 md:z-auto overflow-y-auto">
    <div class="h-16 flex items-center justify-between px-4 border-b border-gray-800">
        <a href="/admin" class="text-xl font-bold text-red-500 tracking-tighter">SOLOREEL</a>
        <button onclick="toggleAdminSidebar()" class="md:hidden text-white/60 hover:text-white">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <nav class="flex-1 py-4 space-y-1">
        <?php
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $items = [
                '/admin' => ['label' => 'Dashboard', 'icon' => '🏠'],
                '/admin/series' => ['label' => 'Series', 'icon' => '🎬'],
                '/admin/episodes' => ['label' => 'Episodes', 'icon' => '▶️'],
                '/admin/genres' => ['label' => 'Genres', 'icon' => '🎭'],
                '/admin/shelves' => ['label' => 'Shelves', 'icon' => '📚'],
                '/admin/banners' => ['label' => 'Banners', 'icon' => '🖼️'],
                '/admin/custom-ads' => ['label' => 'Custom Ads', 'icon' => '📢'],
                '/admin/blog' => ['label' => 'Blog Posts', 'icon' => '📝'],
                '/admin/blog-categories' => ['label' => 'Blog Categories', 'icon' => '🏷️'],
                '/admin/users' => ['label' => 'Users', 'icon' => '👥'],
                '/admin/coins' => ['label' => 'Coins Mgmt', 'icon' => '🪙'],
                '/admin/series-requests' => ['label' => 'Series Requests', 'icon' => '🙋'],
                '/admin/reports' => ['label' => 'Reports', 'icon' => '📊'],
                '/admin/security' => ['label' => 'Security', 'icon' => '🛡️'],
                '/admin/settings' => ['label' => 'Settings', 'icon' => '⚙️'],
                '/admin/settings/payments' => ['label' => 'Payment Gateway', 'icon' => '💳'],
            ];
            foreach ($items as $path => $item) {
                $isActive = ($path === '/admin') ? ($uri === '/admin' || $uri === '/admin/') : str_starts_with($uri, $path);
                $classes = $isActive ? 'block px-6 py-3 bg-gray-800 border-l-4 border-red-500 text-white' : 'block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white transition';
                echo '<a href="' . $path . '" onclick="if(window.innerWidth<768)toggleAdminSidebar()" class="' . $classes . '"><span class="ml-2">' . $item['icon'] . ' ' . htmlspecialchars($item['label']) . '</span></a>';
            }
        ?>
    </nav>
    <div class="p-4 border-t border-gray-800 space-y-2">
        <a href="/" class="block text-center px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded transition text-sm">View Site</a>
        <a href="/logout" class="block text-center px-4 py-2 bg-red-600 hover:bg-red-700 rounded transition text-sm">Logout</a>
    </div>
</aside>
