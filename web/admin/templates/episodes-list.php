<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Episodes Management - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel='stylesheet' href='/assets/css/admin-responsive.css'>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <?php require __DIR__ . '/partials/sidebar.php'; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6 justify-between">
            <button onclick="toggleAdminSidebar()" class="admin-hamburger mr-3 p-2 text-gray-600 hover:text-gray-900"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                <h1 class="text-2xl font-semibold text-gray-800">Episodes Management</h1>
                <a href="/admin/episodes/create" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">Upload Episode</a>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <?php $msg = \App\Core\Session::getFlash('success'); if($msg): ?>
                    <div class="bg-green-100 text-green-700 p-2 rounded mb-4 text-sm"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="p-4 border-b border-gray-200">
                        <input type="text" id="episodes-search" placeholder="Search by series, episode #, or title..." class="w-full max-w-md border rounded px-3 py-2 text-sm">
                    </div>
                    <div class="table-responsive">
                    <table class="min-w-full divide-y divide-gray-200" id="episodes-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Series</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Episode #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Video Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($episodes as $e): ?>
                            <tr data-search="<?= htmlspecialchars(strtolower($e['series_title'] . ' ' . $e['episode_number'] . ' ' . $e['title'])) ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($e['series_title']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?= htmlspecialchars($e['episode_number']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($e['title']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <?php if (!empty($e['video_url'])): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-bold bg-green-100 text-green-700">Ready</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-bold bg-gray-200 text-gray-700">No video</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="/admin/episodes/edit/<?= $e['id'] ?>" class="text-indigo-600 hover:text-indigo-900 mr-2">Edit</a>
                                    <?php if(\App\Core\Session::get('user_role') === 'super_admin'): ?>
                                    <a href="/admin/episodes/delete/<?= $e['id'] ?>" onclick="return confirm('Delete episode?')" class="text-red-600 hover:text-red-900">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr id="episodes-no-results" style="display:none;">
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No episodes match your search.</td>
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
        initAdminTableFilter('episodes-search', '#episodes-table tbody tr[data-search]', { emptyMessageId: 'episodes-no-results' });
    </script>
</body>
</html>
