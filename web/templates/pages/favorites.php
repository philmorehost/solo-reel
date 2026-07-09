<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>My Favorites - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body class="bg-black text-white antialiased font-sans min-h-screen flex flex-col">
    <?php require __DIR__ . '/../partials/header.php'; ?>
    <main class="flex-1 pt-20">
        <div class="max-w-6xl mx-auto px-4 py-8">
            <h1 class="text-2xl font-bold mb-6">My Favorites</h1>
            <?php if (empty($favorites)): ?>
            <div class="bg-gray-900 border border-gray-800 rounded-xl p-8 text-center">
                <p class="text-gray-400">No favorites yet. Browse series and add them to your list.</p>
                <a href="/search" class="inline-block mt-4 bg-red-600 hover:bg-red-700 text-white px-5 py-2.5 rounded-lg font-semibold transition">Browse Series</a>
            </div>
            <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3 sm:gap-4">
                <?php foreach ($favorites as $fav): ?>
                <a href="<?= !empty($fav['resume_slug']) ? '/episodes/' . htmlspecialchars($fav['resume_slug']) : '/movie/' . htmlspecialchars($fav['slug'] ?? '') ?>" class="group">
                    <div class="aspect-[2/3] rounded-xl overflow-hidden bg-gray-900">
                        <img src="<?= htmlspecialchars($fav['cover_image_url'] ?? '') ?>" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                    </div>
                    <p class="text-sm font-medium mt-2 text-gray-200 truncate"><?= htmlspecialchars($fav['title'] ?? '') ?></p>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
