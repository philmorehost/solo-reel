<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <aside class="w-64 bg-gray-900 text-white flex-shrink-0 hidden md:flex flex-col">
            <div class="h-16 flex items-center justify-center border-b border-gray-800">
                <span class="text-xl font-bold text-red-500 tracking-tighter">SOLOREEL Admin</span>
            </div>
            <nav class="flex-1 overflow-y-auto py-4">
                <a href="/admin" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Dashboard</a>
                <a href="/admin/series" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Series</a>
                <a href="/admin/episodes" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Episodes</a>
                <a href="/admin/users" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Users</a>
                <a href="/admin/settings" class="block px-6 py-3 bg-gray-800 border-l-4 border-red-500">Settings</a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6">
                <h1 class="text-2xl font-semibold text-gray-800">Site Settings</h1>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">

                <?php $msg = \App\Core\Session::getFlash('success'); if($msg): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>

                <div class="bg-white rounded-lg shadow overflow-hidden p-6 max-w-2xl">
                    <form method="POST">
                        <?= \App\Core\Security::csrfField() ?>

                        <h2 class="text-xl font-bold mb-4 border-b pb-2">General / SEO</h2>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Site / Meta Title</label>
                            <input type="text" name="meta_title" value="<?= htmlspecialchars($settings['meta_title'] ?? '') ?>" required class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 font-bold mb-2">Meta Description</label>
                            <textarea name="meta_description" rows="3" class="w-full border rounded px-3 py-2"><?= htmlspecialchars($settings['meta_description'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="bg-gray-900 text-white font-bold py-2 px-6 rounded hover:bg-gray-800">
                            Save Settings
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
