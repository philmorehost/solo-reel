<aside class="w-64 bg-gray-900 text-white flex-shrink-0 hidden md:flex flex-col">
    <div class="h-16 flex items-center justify-center border-b border-gray-800">
        <a href="/admin" class="text-xl font-bold text-red-500 tracking-tighter">SOLOREEL Admin</a>
    </div>
    <nav class="flex-1 overflow-y-auto py-4 space-y-1">
        <?php
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $items = [
                '/admin' => ['label' => 'Dashboard', 'icon' => '🏠'],
                '/admin/series' => ['label' => 'Series', 'icon' => '🎬'],
                '/admin/episodes' => ['label' => 'Episodes', 'icon' => '▶️'],
                '/admin/genres' => ['label' => 'Genres', 'icon' => '🎭'],
                '/admin/shelves' => ['label' => 'Shelves', 'icon' => '📚'],
                '/admin/banners' => ['label' => 'Banners', 'icon' => '🖼️'],
                '/admin/blog' => ['label' => 'Blog Posts', 'icon' => '📝'],
                '/admin/blog-categories' => ['label' => 'Blog Categories', 'icon' => '🏷️'],
                '/admin/users' => ['label' => 'Users', 'icon' => '👥'],
                '/admin/coins' => ['label' => 'Coins Mgmt', 'icon' => '🪙'],
                '/admin/reports' => ['label' => 'Reports', 'icon' => '📊'],
                '/admin/security' => ['label' => 'Security', 'icon' => '🛡️'],
                '/admin/settings' => ['label' => 'Settings', 'icon' => '⚙️'],
                '/admin/settings/payments' => ['label' => 'Payment Gateway', 'icon' => '💳'],
            ];

            foreach ($items as $path => $item) {
                // Exact match for dashboard, startswith for others
                $isActive = ($path === '/admin') ? ($uri === '/admin' || $uri === '/admin/') : str_starts_with($uri, $path);
                $classes = $isActive
                    ? 'block px-6 py-3 bg-gray-800 border-l-4 border-red-500 text-white'
                    : 'block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white transition';

                echo '<a href="' . $path . '" class="' . $classes . '">' . $item['icon'] . '  <span class="ml-2">' . htmlspecialchars($item['label']) . '</span></a>';
            }
        ?>
    </nav>
    <div class="p-4 border-t border-gray-800">
        <a href="/" class="block w-full text-center px-4 py-2 bg-gray-800 hover:bg-gray-700 rounded transition mb-2">View Site</a>
        <a href="/logout" class="block w-full text-center px-4 py-2 bg-red-600 hover:bg-red-700 rounded transition">Logout</a>
    </div>
</aside>
