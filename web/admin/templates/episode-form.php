<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= isset($episode) ? 'Edit' : 'Create' ?> Episode - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <?php require __DIR__ . "/partials/sidebar.php"; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6 justify-between">
                <h1 class="text-2xl font-semibold text-gray-800"><?= isset($episode) ? 'Edit' : 'Create' ?> Episode</h1>
                <a href="/admin/episodes" class="text-gray-500 hover:text-gray-700">Back to List</a>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="bg-white rounded-lg shadow overflow-hidden p-6 max-w-2xl mx-auto">
                    <!-- Note the enctype="multipart/form-data" added for file uploads -->
                    <form method="POST" enctype="multipart/form-data">
                        <?= \App\Core\Security::csrfField() ?>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Series</label>
                            <select name="series_id" class="w-full border rounded px-3 py-2" required>
                                <option value="">Select a Series</option>
                                <?php foreach($seriesList as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= (isset($episode) && $episode['series_id'] == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Episode Title</label>
                            <input type="text" name="title" value="<?= htmlspecialchars($episode['title'] ?? '') ?>" required class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Episode Number</label>
                            <input type="number" name="episode_number" value="<?= htmlspecialchars($episode['episode_number'] ?? 1) ?>" required class="w-full border rounded px-3 py-2">
                        </div>

                        <!-- Video Upload Field -->
                        <div class="mb-4 p-4 border border-dashed border-gray-400 rounded bg-gray-50">
                            <label class="block text-gray-700 font-bold mb-2">Upload Video (MP4)</label>
                            <input type="file" name="video_file" accept="video/mp4" <?= !isset($episode) ? 'required' : '' ?> class="w-full">
                            <p class="text-sm text-gray-500 mt-2">File will be queued for HLS conversion processing.</p>
                        </div>

                        <div class="mb-4 flex items-center">
                            <input type="checkbox" name="is_free" id="is_free" class="mr-2" <?= (!isset($episode) || $episode['is_free']) ? 'checked' : '' ?>>
                            <label for="is_free" class="text-gray-700 font-bold">Is Free?</label>
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 font-bold mb-2">Coin Cost (if not free)</label>
                            <input type="number" step="0.01" name="coin_cost" value="<?= htmlspecialchars($episode['coin_cost'] ?? 0.00) ?>" class="w-full border rounded px-3 py-2">
                        </div>

                        <button type="submit" class="bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700">
                            Save Episode
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
