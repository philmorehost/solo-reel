<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($post['title']) ?> - SOLOREEL</title>
    <meta name="description" content="<?= htmlspecialchars($post['excerpt']) ?>">
    <script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="/assets/css/responsive.css">
    <style>
        body { background-color: #000; color: #fff; }
        .blog-content p { margin-bottom: 1.5rem; color: #d1d5db; line-height: 1.8; }
        .blog-content h2 { font-size: 1.875rem; font-weight: 700; margin-top: 2.5rem; margin-bottom: 1rem; color: #fff; }
        .blog-content a { color: #ef4444; text-decoration: underline; }
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
                    <a href="/blog" class="text-gray-300 hover:text-white">Back to Blog</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="pt-24 max-w-3xl mx-auto px-4 py-12 min-h-screen">
        <div class="mb-8">
            <span class="text-red-500 font-bold uppercase tracking-wider text-sm"><?= htmlspecialchars($post['category_name'] ?? 'News') ?></span>
            <h1 class="text-4xl sm:text-5xl font-bold mt-2 mb-4 leading-tight"><?= htmlspecialchars($post['title']) ?></h1>
            <div class="flex items-center text-gray-500 text-sm border-b border-gray-800 pb-6 mb-8">
                <span>By <?= htmlspecialchars($post['author_name'] ?? 'Admin') ?></span>
                <span class="mx-3">•</span>
                <span><?= date('F d, Y', strtotime($post['created_at'])) ?></span>
            </div>
        </div>

        <?php if(!empty($post['cover_image'])): ?>
            <div class="w-full aspect-video rounded-xl overflow-hidden mb-10 bg-gray-900 border border-gray-800">
                <img src="<?= htmlspecialchars($post['cover_image']) ?>" class="w-full h-full object-cover">
            </div>
        <?php endif; ?>

        <div class="blog-content text-lg">
            <?= $post['body'] ?> <!-- Intentionally unescaped because it's HTML content from admin -->
        </div>
    </main>
    <script src="/assets/js/protection.js"></script>
</body>
</html>
