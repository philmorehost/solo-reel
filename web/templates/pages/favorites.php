<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Favorites - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .movie-card:hover { transform: scale(1.05); transition: 0.3s; }
    </style>
</head>
<body class="bg-black text-white antialiased font-sans">

    <nav class="fixed w-full z-50 bg-black/80 backdrop-blur border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="text-red-600 font-bold text-2xl tracking-tighter">SOLOREEL</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/profile" class="text-gray-300 hover:text-white">Profile</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="pt-24 max-w-7xl mx-auto px-4 py-12">
        <h1 class="text-3xl font-bold mb-8">My Favorites</h1>

        <?php $msg = \App\Core\Session::getFlash('success'); if($msg): ?>
            <div class="bg-green-500/10 border border-green-500 text-green-500 p-3 rounded mb-8 text-center text-sm">
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <?php if(empty($series)): ?>
            <p class="text-gray-500">You haven't added any series to your favorites yet.</p>
        <?php else: ?>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <?php foreach($series as $s): ?>
                    <div class="relative group">
                        <a href="/movie/<?= htmlspecialchars($s['slug']) ?>" class="movie-card block cursor-pointer">
                            <div class="relative aspect-[2/3] overflow-hidden rounded-lg">
                                <img src="<?= htmlspecialchars($s['cover_image'] ?? '/assets/img/default-cover.jpg') ?>" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                                    <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4l12 6-12 6z"></path></svg>
                                </div>
                            </div>
                            <h4 class="mt-2 text-sm font-medium text-gray-300 group-hover:text-white truncate"><?= htmlspecialchars($s['title']) ?></h4>
                        </a>
                        <form action="/favorites/<?= $s['id'] ?>" method="POST" class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition">
                            <input type="hidden" name="_method" value="DELETE">
                            <?= \App\Core\Security::csrfField() ?>
                            <button type="submit" class="bg-red-600/80 hover:bg-red-600 text-white rounded-full p-2" title="Remove from Favorites">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
