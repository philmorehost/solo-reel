<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Series Management - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel='stylesheet' href='/assets/css/admin-responsive.css'>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">

        <?php require __DIR__ . '/partials/sidebar.php'; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6 justify-between">
            <button onclick="toggleAdminSidebar()" class="admin-hamburger mr-3 p-2 text-gray-600 hover:text-gray-900"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                <h1 class="text-2xl font-semibold text-gray-800">Series Management</h1>
                <a href="/admin/series/create" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded shadow">Add New Series</a>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <?php $msg = \App\Core\Session::getFlash('success'); if($msg): ?>
                    <div class="bg-green-100 text-green-700 p-2 rounded mb-4 text-sm"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-4 border-b border-gray-200">
                        <input type="text" id="series-search" placeholder="Search by title, status, or genre..." class="w-full max-w-md border rounded px-3 py-2 text-sm">
                    </div>
                    <div class="table-responsive">
                    <table class="min-w-full divide-y divide-gray-200" id="series-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Genre</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($series as $s): ?>
                            <tr data-search="<?= htmlspecialchars(strtolower($s['title'] . ' ' . $s['status'] . ' ' . ($s['genre'] ?? ''))) ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($s['title']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        <?= htmlspecialchars($s['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($s['genre'] ?? 'N/A') ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="/admin/series/edit/<?= $s['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</a>
                                    <?php if(\App\Core\Session::get('user_role') === 'super_admin'): ?>
                                    <a href="/admin/series/delete/<?= $s['id'] ?>" onclick="return confirm('Delete series?')" class="text-red-600 hover:text-red-900">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr id="series-no-results" style="display:none;">
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No series match your search.</td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script src="/assets/js/admin-table-filter.js"></script>
    <script>
        initAdminTableFilter('series-search', '#series-table tbody tr[data-search]', { emptyMessageId: 'series-no-results' });
    </script>
</body>
</html>
