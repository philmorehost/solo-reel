<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= isset($series) ? 'Edit' : 'Create' ?> Series - SOLOREEL Admin</title>
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
                <a href="/admin/series" class="block px-6 py-3 bg-gray-800 border-l-4 border-red-500">Series</a>
                <a href="/admin/episodes" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Episodes</a>
                <a href="/admin/users" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Users</a>
                <a href="/admin/settings" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Settings</a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6 justify-between">
                <h1 class="text-2xl font-semibold text-gray-800"><?= isset($series) ? 'Edit' : 'Create' ?> Series</h1>
                <a href="/admin/series" class="text-gray-500 hover:text-gray-700">Back to List</a>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="bg-white rounded-lg shadow overflow-hidden p-6 max-w-2xl mx-auto">
                    <form method="POST">
                        <?= \App\Core\Security::csrfField() ?>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Title</label>
                            <input type="text" name="title" value="<?= htmlspecialchars($series['title'] ?? '') ?>" required class="w-full border rounded px-3 py-2">
                        </div>

                        <?php if(isset($series)): ?>
                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Slug</label>
                            <input type="text" name="slug" value="<?= htmlspecialchars($series['slug'] ?? '') ?>" required class="w-full border rounded px-3 py-2">
                        </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Genre</label>
                            <input type="text" name="genre" value="<?= htmlspecialchars($series['genre'] ?? '') ?>" class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Status</label>
                            <select name="status" class="w-full border rounded px-3 py-2">
                                <option value="ongoing" <?= (isset($series) && $series['status'] === 'ongoing') ? 'selected' : '' ?>>Ongoing</option>
                                <option value="completed" <?= (isset($series) && $series['status'] === 'completed') ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Synopsis</label>
                            <textarea name="synopsis" rows="5" class="w-full border rounded px-3 py-2"><?= htmlspecialchars($series['synopsis'] ?? '') ?></textarea>
                        </div>

                        <button type="submit" class="bg-indigo-600 text-white font-bold py-2 px-4 rounded hover:bg-indigo-700">
                            Save Series
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
