<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Banner - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">

        <?php require __DIR__ . "/partials/sidebar.php"; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6 justify-between">
                <h1 class="text-2xl font-semibold text-gray-800">Create Banner</h1>
                <a href="/admin/banners" class="text-gray-500 hover:text-gray-700">Back to List</a>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="bg-white rounded-lg shadow overflow-hidden p-6 max-w-2xl mx-auto">
                    <form method="POST" enctype="multipart/form-data">
                        <?= \App\Core\Security::csrfField() ?>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Title</label>
                            <input type="text" name="title" required class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Subtitle</label>
                            <input type="text" name="subtitle" class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Link URL</label>
                            <input type="text" name="link_url" class="w-full border rounded px-3 py-2" placeholder="/movie/my-series">
                        </div>

                        <div class="mb-4 p-4 border border-dashed border-gray-400 rounded bg-gray-50">
                            <label class="block text-gray-700 font-bold mb-2">Upload Image</label>
                            <input type="file" name="image_file" accept="image/*" required class="w-full">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Sort Order</label>
                            <input type="number" name="sort_order" value="0" class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mb-6 flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" class="mr-2" checked>
                            <label for="is_active" class="text-gray-700 font-bold">Active and visible to users?</label>
                        </div>

                        <button type="submit" class="bg-indigo-600 text-white font-bold py-2 px-4 rounded hover:bg-indigo-700">
                            Save Banner
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
