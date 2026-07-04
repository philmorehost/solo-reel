<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= isset($package) ? 'Edit' : 'Create' ?> Package - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">

        <?php require __DIR__ . '/partials/sidebar.php'; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6 justify-between">
                <h1 class="text-2xl font-semibold text-gray-800"><?= isset($package) ? 'Edit' : 'Create' ?> Coin Package</h1>
                <a href="/admin/coins" class="text-gray-500 hover:text-gray-700">Back to List</a>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="bg-white rounded-lg shadow overflow-hidden p-6 max-w-lg mx-auto">
                    <form method="POST">
                        <?= \App\Core\Security::csrfField() ?>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Package Name</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($package['name'] ?? '') ?>" required class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Theme Color (Hex Code)</label>
                            <div class="flex items-center gap-2">
                                <input type="color" name="color_code" value="<?= htmlspecialchars($package['color_code'] ?? '#000000') ?>" class="h-10 w-10 border rounded cursor-pointer">
                                <input type="text" name="color_code_text" value="<?= htmlspecialchars($package['color_code'] ?? '#000000') ?>" class="w-full border rounded px-3 py-2" oninput="this.previousElementSibling.value = this.value">
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Used to colorize the package shield on the frontend.</p>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Amount of Coins</label>
                            <input type="number" step="0.01" name="coins" value="<?= htmlspecialchars($package['coins'] ?? '') ?>" required class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 font-bold mb-2">Price</label>
                                <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($package['price'] ?? '') ?>" required class="w-full border rounded px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-bold mb-2">Currency</label>
                                <input type="text" name="currency" value="<?= htmlspecialchars($package['currency'] ?? 'NGN') ?>" required class="w-full border rounded px-3 py-2">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Sort Order</label>
                            <input type="number" name="sort_order" value="<?= htmlspecialchars($package['sort_order'] ?? '0') ?>" class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mb-6 flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" class="mr-2" <?= (!isset($package) || $package['is_active']) ? 'checked' : '' ?>>
                            <label for="is_active" class="text-gray-700 font-bold">Active and visible to users?</label>
                        </div>

                        <button type="submit" class="bg-indigo-600 text-white font-bold py-2 px-4 rounded hover:bg-indigo-700">
                            Save Package
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
