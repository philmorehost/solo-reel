<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Genres - SOLOREEL Admin</title>
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
                <a href="/admin/series" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Series</a>
                <a href="/admin/episodes" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Episodes</a>
                <a href="/admin/genres" class="block px-6 py-3 bg-gray-800 border-l-4 border-red-500">Genres</a>
                <a href="/admin/shelves" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Shelves</a>
                <a href="/admin/users" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Users</a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6">
                <h1 class="text-2xl font-semibold text-gray-800">Genres Management</h1>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="md:col-span-1">
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-lg font-bold mb-4">Add New Genre</h2>
                        <?php $msg = \App\Core\Session::getFlash('success'); if($msg): ?>
                            <div class="bg-green-100 text-green-700 p-2 rounded mb-4 text-sm"><?= htmlspecialchars($msg) ?></div>
                        <?php endif; ?>
                        <?php $err = \App\Core\Session::getFlash('error'); if($err): ?>
                            <div class="bg-red-100 text-red-700 p-2 rounded mb-4 text-sm"><?= htmlspecialchars($err) ?></div>
                        <?php endif; ?>
                        <form action="/admin/genres/create" method="POST">
                            <?= \App\Core\Security::csrfField() ?>
                            <div class="mb-4">
                                <label class="block text-gray-700 text-sm font-bold mb-2">Genre Name</label>
                                <input type="text" name="name" required class="w-full border rounded px-3 py-2">
                            </div>
                            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded transition">Add Genre</button>
                        </form>
                    </div>
                </div>

                <div class="md:col-span-2 bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($genres as $g): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?= htmlspecialchars($g['name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($g['slug']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <?php if(\App\Core\Session::get('user_role') === 'super_admin'): ?>
                                    <a href="/admin/genres/delete/<?= $g['id'] ?>" onclick="return confirm('Delete genre?')" class="text-red-600 hover:text-red-900">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
