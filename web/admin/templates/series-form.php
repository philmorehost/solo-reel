<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= isset($series) ? 'Edit' : 'Create' ?> Series - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel='stylesheet' href='/assets/css/admin-responsive.css'>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">

        <?php require __DIR__ . '/partials/sidebar.php'; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6 justify-between">
            <button onclick="toggleAdminSidebar()" class="admin-hamburger mr-3 p-2 text-gray-600 hover:text-gray-900"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                <h1 class="text-2xl font-semibold text-gray-800"><?= isset($series) ? 'Edit' : 'Create' ?> Series</h1>
                <a href="/admin/series" class="text-gray-500 hover:text-gray-700">Back to List</a>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="bg-white rounded-lg shadow overflow-hidden p-6 max-w-2xl mx-auto">
                    <form method="POST" enctype="multipart/form-data">
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
                            <select name="genre" class="w-full border rounded px-3 py-2">
                                <option value="">Select Genre</option>
                                <?php foreach($genres as $g): ?>
                                    <option value="<?= htmlspecialchars($g['name']) ?>" <?= (isset($series) && $series['genre'] === $g['name']) ? 'selected' : '' ?>><?= htmlspecialchars($g['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Upload Cover Image</label>
                            <input type="file" name="cover_image" accept="image/*" class="w-full border rounded px-3 py-2 bg-gray-50">
                            <?php if(isset($series) && !empty($series['cover_image'])): ?>
                                <img src="<?= htmlspecialchars($series['cover_image']) ?>" class="mt-2 h-32 object-cover rounded shadow">
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Upload Featured Hero Image (Recommended for Slider)</label>
                            <input type="file" name="hero_image" accept="image/*" class="w-full border rounded px-3 py-2 bg-gray-50">
                            <?php if(isset($series) && !empty($series['hero_image'])): ?>
                                <img src="<?= htmlspecialchars($series['hero_image']) ?>" class="mt-2 h-24 object-cover rounded shadow">
                            <?php endif; ?>
                        </div>

                        <div class="mb-4 flex items-center bg-blue-50 p-3 rounded border border-blue-100">
                            <input type="checkbox" name="is_featured" id="is_featured" class="mr-2" <?= (!empty($series['is_featured'])) ? 'checked' : '' ?>>
                            <label for="is_featured" class="text-blue-900 font-bold cursor-pointer">Feature in Landing Page Slider?</label>
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

                        <button type="submit" class="bg-indigo-600 text-white font-bold py-2 px-6 rounded shadow hover:bg-indigo-700 transition">
                            Save Series
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
