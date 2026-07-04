<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #000; color: #fff; }
    </style>
</head>
<body class="antialiased font-sans">

    <nav class="fixed w-full z-50 bg-black/80 backdrop-blur border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/"><?= \App\Helpers\Site::getLogoHtml() ?></a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/search" class="text-gray-300 hover:text-white">Search</a>
                    <a href="/blog" class="text-white font-bold">Blog</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="pt-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 min-h-screen">
        <h1 class="text-4xl font-bold mb-12 border-b border-gray-800 pb-4">Latest News & Updates</h1>

        <?php if(empty($posts)): ?>
            <p class="text-gray-500">No blog posts available yet.</p>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <?php foreach($posts as $p): ?>
                    <a href="/blog/<?= htmlspecialchars($p['slug']) ?>" class="block group">
                        <div class="relative aspect-video overflow-hidden rounded-lg mb-4 bg-gray-900 border border-gray-800">
                            <?php if(!empty($p['cover_image'])): ?>
                                <img src="<?= htmlspecialchars($p['cover_image']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            <?php else: ?>
                                <div class="w-full h-full flex items-center justify-center text-gray-700">No Image</div>
                            <?php endif; ?>
                        </div>
                        <div class="text-xs text-red-500 font-bold uppercase tracking-wider mb-2">
                            <?= htmlspecialchars($p['category_name'] ?? 'News') ?>
                        </div>
                        <h3 class="text-xl font-bold mb-2 group-hover:text-red-500 transition line-clamp-2"><?= htmlspecialchars($p['title']) ?></h3>
                        <p class="text-gray-400 text-sm line-clamp-3 mb-3"><?= htmlspecialchars($p['excerpt']) ?></p>
                        <div class="text-xs text-gray-500 flex items-center gap-2">
                            <span><?= date('M d, Y', strtotime($p['created_at'])) ?></span>
                            <span>•</span>
                            <span><?= htmlspecialchars($p['author_name'] ?? 'Admin') ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    <script src="/assets/js/protection.js"></script>
</body>
</html>
