<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= isset($user) ? 'Edit' : 'Create' ?> User - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">

        <?php require __DIR__ . "/partials/sidebar.php"; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6 justify-between">
                <h1 class="text-2xl font-semibold text-gray-800"><?= isset($user) ? 'Edit' : 'Create' ?> User</h1>
                <a href="/admin/users" class="text-gray-500 hover:text-gray-700">Back to List</a>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="bg-white rounded-lg shadow overflow-hidden p-6 max-w-2xl mx-auto">
                    <form method="POST">
                        <?= \App\Core\Security::csrfField() ?>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Username</label>
                            <input type="text" name="username" value="<?= htmlspecialchars($user['username'] ?? '') ?>" required class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Email</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Password <?= isset($user) ? '(leave blank to keep current)' : '' ?></label>
                            <input type="password" name="password" <?= !isset($user) ? 'required' : '' ?> class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Role</label>
                            <select name="role" class="w-full border rounded px-3 py-2">
                                <option value="user" <?= (isset($user) && $user['role'] === 'user') ? 'selected' : '' ?>>User</option>
                                <option value="admin" <?= (isset($user) && $user['role'] === 'admin') ? 'selected' : '' ?>>Admin</option>
                                <option value="super_admin" <?= (isset($user) && $user['role'] === 'super_admin') ? 'selected' : '' ?>>Super Admin</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Status</label>
                            <select name="status" class="w-full border rounded px-3 py-2">
                                <option value="active" <?= (isset($user) && $user['status'] === 'active') ? 'selected' : '' ?>>Active</option>
                                <option value="blocked" <?= (isset($user) && $user['status'] === 'blocked') ? 'selected' : '' ?>>Blocked</option>
                            </select>
                        </div>

                        <?php if(isset($user)): ?>
                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Coin Balance</label>
                            <input type="number" step="0.01" name="coin_balance" value="<?= htmlspecialchars($user['coin_balance'] ?? 0) ?>" class="w-full border rounded px-3 py-2">
                        </div>
                        <?php endif; ?>

                        <button type="submit" class="bg-indigo-600 text-white font-bold py-2 px-4 rounded hover:bg-indigo-700">
                            Save User
                        </button>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
