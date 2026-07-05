<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= isset($shelf) ? 'Edit' : 'Create' ?> Shelf - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel='stylesheet' href='/assets/css/admin-responsive.css'>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">

        <?php require __DIR__ . '/partials/sidebar.php'; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6 justify-between">
            <button onclick="toggleAdminSidebar()" class="admin-hamburger mr-3 p-2 text-gray-600 hover:text-gray-900"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                <h1 class="text-2xl font-semibold text-gray-800"><?= isset($shelf) ? 'Edit' : 'Create' ?> Shelf</h1>
                <a href="/admin/shelves" class="text-gray-500 hover:text-gray-700">Back to List</a>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="bg-white rounded-lg shadow overflow-hidden p-6 max-w-2xl mx-auto">
                    <form method="POST">
                        <?= \App\Core\Security::csrfField() ?>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Name</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($shelf['name'] ?? '') ?>" required class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Emoji Icon</label>
                            <input type="text" name="emoji" value="<?= htmlspecialchars($shelf['emoji'] ?? '') ?>" class="w-full border rounded px-3 py-2" placeholder="🔥">
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 font-bold mb-2">Sort Order</label>
                            <input type="number" name="sort_order" value="<?= htmlspecialchars($shelf['sort_order'] ?? '0') ?>" class="w-full border rounded px-3 py-2">
                        </div>

                        <button type="submit" class="bg-indigo-600 text-white font-bold py-2 px-4 rounded hover:bg-indigo-700">
                            Save Shelf
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
