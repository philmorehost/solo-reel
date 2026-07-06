<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Custom Ad - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel='stylesheet' href='/assets/css/admin-responsive.css'>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">

        <?php require __DIR__ . "/partials/sidebar.php"; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6 justify-between">
            <button onclick="toggleAdminSidebar()" class="admin-hamburger mr-3 p-2 text-gray-600 hover:text-gray-900"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                <h1 class="text-2xl font-semibold text-gray-800">Create Custom Ad</h1>
                <a href="/admin/custom-ads" class="text-gray-500 hover:text-gray-700">Back to List</a>
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
                            <label class="block text-gray-700 font-bold mb-2">Target Link (opened when tapped)</label>
                            <input type="url" name="target_url" class="w-full border rounded px-3 py-2" placeholder="https://example.com">
                        </div>

                        <div class="mb-4 p-4 border border-dashed border-gray-400 rounded bg-gray-50">
                            <label class="block text-gray-700 font-bold mb-2">Upload Media (image: png/jpg/webp, or video: mp4/webm)</label>
                            <input type="file" name="media_file" accept="image/png,image/jpeg,image/webp,video/mp4,video/webm" required class="w-full">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Placement</label>
                            <select name="placement" class="w-full border rounded px-3 py-2">
                                <option value="both">Home Banner + Episode Interstitial</option>
                                <option value="banner">Home Banner Only</option>
                                <option value="interstitial">Episode Interstitial Only</option>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 font-bold mb-2">Starts At (optional)</label>
                                <input type="datetime-local" name="starts_at" class="w-full border rounded px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-bold mb-2">Expires At (optional)</label>
                                <input type="datetime-local" name="expires_at" class="w-full border rounded px-3 py-2">
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mb-4">Leave either blank for no start/end limit. Expired ads automatically stop appearing everywhere.</p>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Sort Order</label>
                            <input type="number" name="sort_order" value="0" class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mb-6 flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" class="mr-2" checked>
                            <label for="is_active" class="text-gray-700 font-bold">Active and visible to users?</label>
                        </div>

                        <button type="submit" class="bg-indigo-600 text-white font-bold py-2 px-4 rounded hover:bg-indigo-700">
                            Save Ad
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
