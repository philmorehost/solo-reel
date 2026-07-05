<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Write Blog Post - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel='stylesheet' href='/assets/css/admin-responsive.css'>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <?php require __DIR__ . "/partials/sidebar.php"; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6 justify-between">
            <button onclick="toggleAdminSidebar()" class="admin-hamburger mr-3 p-2 text-gray-600 hover:text-gray-900"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                <h1 class="text-2xl font-semibold text-gray-800">Write New Post</h1>
                <a href="/admin/blog" class="text-gray-500 hover:text-gray-700">Back</a>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="bg-white rounded-lg shadow p-6 max-w-4xl mx-auto">
                    <form method="POST" enctype="multipart/form-data">
                        <?= \App\Core\Security::csrfField() ?>

                        <div class="mb-4">
                            <label class="block font-bold mb-2">Title</label>
                            <input type="text" name="title" required class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mb-4">
                            <label class="block font-bold mb-2">Category</label>
                            <select name="category_id" class="w-full border rounded px-3 py-2">
                                <option value="">No Category</option>
                                <?php foreach($categories as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block font-bold mb-2">Cover Image</label>
                            <input type="file" name="cover_image" accept="image/*" class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mb-4">
                            <label class="block font-bold mb-2">Excerpt (SEO Description)</label>
                            <textarea name="excerpt" rows="3" class="w-full border rounded px-3 py-2"></textarea>
                        </div>

                        <div class="mb-6">
                            <label class="block font-bold mb-2">Body Content (HTML allowed)</label>
                            <textarea name="body" rows="10" required class="w-full border rounded px-3 py-2"></textarea>
                        </div>

                        <button type="submit" class="bg-indigo-600 text-white font-bold py-2 px-6 rounded hover:bg-indigo-700">Publish Post</button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
