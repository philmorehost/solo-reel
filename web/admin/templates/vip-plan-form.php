<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($plan) ? 'Edit' : 'Create' ?> VIP Plan - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel='stylesheet' href='/assets/css/admin-responsive.css'>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">

        <?php require __DIR__ . '/partials/sidebar.php'; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6 justify-between">
            <button onclick="toggleAdminSidebar()" class="admin-hamburger mr-3 p-2 text-gray-600 hover:text-gray-900"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                <h1 class="text-2xl font-semibold text-gray-800"><?= isset($plan) ? 'Edit' : 'Create' ?> VIP Plan</h1>
                <a href="/admin/vip-plans" class="text-gray-500 hover:text-gray-700">Back to List</a>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="bg-white rounded-lg shadow overflow-hidden p-6 max-w-lg mx-auto">
                    <form method="POST">
                        <?= \App\Core\Security::csrfField() ?>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Plan Name</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($plan['name'] ?? '') ?>" required class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-gray-700 font-bold mb-2">Price</label>
                                <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($plan['price'] ?? '') ?>" required class="w-full border rounded px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-gray-700 font-bold mb-2">Currency</label>
                                <input type="text" name="currency" value="<?= htmlspecialchars($plan['currency'] ?? 'NGN') ?>" required class="w-full border rounded px-3 py-2">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Duration (days)</label>
                            <input type="number" name="duration_days" value="<?= htmlspecialchars($plan['duration_days'] ?? '30') ?>" required class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mb-4 flex items-center bg-yellow-50 p-3 rounded border border-yellow-100">
                            <input type="checkbox" name="perk_free_unlocks" id="perk_free_unlocks" class="mr-2" <?= (!isset($plan) || $plan['perk_free_unlocks']) ? 'checked' : '' ?>>
                            <label for="perk_free_unlocks" class="text-yellow-900 font-bold cursor-pointer">Free episode unlocks (bypasses coin cost)</label>
                        </div>

                        <div class="mb-4 flex items-center bg-blue-50 p-3 rounded border border-blue-100">
                            <input type="checkbox" name="perk_ad_free" id="perk_ad_free" class="mr-2" <?= (!isset($plan) || $plan['perk_ad_free']) ? 'checked' : '' ?>>
                            <label for="perk_ad_free" class="text-blue-900 font-bold cursor-pointer">Ad-free unlocks (skips the ad requirement)</label>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Sort Order</label>
                            <input type="number" name="sort_order" value="<?= htmlspecialchars($plan['sort_order'] ?? '0') ?>" class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mb-6 flex items-center">
                            <input type="checkbox" name="is_active" id="is_active" class="mr-2" <?= (!isset($plan) || $plan['is_active']) ? 'checked' : '' ?>>
                            <label for="is_active" class="text-gray-700 font-bold">Active and visible to users?</label>
                        </div>

                        <button type="submit" class="bg-indigo-600 text-white font-bold py-2 px-4 rounded hover:bg-indigo-700">
                            Save Plan
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
