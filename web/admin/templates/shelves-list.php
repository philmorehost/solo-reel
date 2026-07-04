<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shelves - SOLOREEL Admin</title>
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
                <a href="/admin/shelves" class="block px-6 py-3 bg-gray-800 border-l-4 border-red-500">Shelves</a>
                <a href="/admin/users" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Users</a>
                <a href="/admin/settings" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Settings</a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6 justify-between">
                <h1 class="text-2xl font-semibold text-gray-800">Shelves Management</h1>
                <a href="/admin/shelves/create" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded shadow">Add New Shelf</a>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Icon</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($shelves as $s): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-2xl"><?= htmlspecialchars($s['emoji']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($s['name']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($s['slug']) ?>
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
