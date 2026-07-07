<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Series Requests - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel='stylesheet' href='/assets/css/admin-responsive.css'>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <?php require __DIR__ . "/partials/sidebar.php"; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6 justify-between">
                <button onclick="toggleAdminSidebar()" class="admin-hamburger mr-3 p-2 text-gray-600 hover:text-gray-900"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                <h1 class="text-2xl font-semibold text-gray-800">Series Requests</h1>
                <div class="space-x-2 text-sm">
                    <a href="/admin/series-requests" class="px-3 py-2 rounded <?= empty($_GET['status']) ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700' ?>">All</a>
                    <a href="/admin/series-requests?status=pending" class="px-3 py-2 rounded <?= ($_GET['status'] ?? '') === 'pending' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700' ?>">Pending</a>
                    <a href="/admin/series-requests?status=available" class="px-3 py-2 rounded <?= ($_GET['status'] ?? '') === 'available' ? 'bg-indigo-600 text-white' : 'bg-gray-200 text-gray-700' ?>">Available</a>
                </div>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <?php if ($msg = \App\Core\Session::getFlash('success')): ?>
                    <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>
                <?php if ($msg = \App\Core\Session::getFlash('error')): ?>
                    <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <?php if (!empty($requests)): ?>
                    <div class="p-4 border-b border-gray-200">
                        <input type="text" id="series-requests-search" placeholder="Search by title, requester, or status..." class="w-full max-w-md border rounded px-3 py-2 text-sm">
                    </div>
                    <?php endif; ?>
                    <div class="table-responsive">
                    <table class="min-w-full divide-y divide-gray-200" id="series-requests-table">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requests</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requester</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($requests)): ?>
                            <tr><td colspan="6" class="px-6 py-10 text-center text-sm text-gray-500">No series requests yet.</td></tr>
                            <?php endif; ?>
                            <?php foreach($requests as $r): ?>
                            <tr data-search="<?= htmlspecialchars(strtolower($r['title'] . ' ' . ($r['requester_email'] ?? '') . ' ' . $r['requester_type'] . ' ' . $r['status'] . ' ' . ($r['linked_series_title'] ?? ''))) ?>">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($r['title']) ?>
                                    <?php if ($r['is_hot']): ?>
                                        <span class="ml-2 px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">🔥 HOT</span>
                                    <?php endif; ?>
                                    <?php if (!empty($r['description'])): ?>
                                        <p class="text-xs text-gray-500 mt-1 max-w-md truncate"><?= htmlspecialchars($r['description']) ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900"><?= (int)$r['request_count'] ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($r['requester_email'] ?: '—') ?>
                                    <span class="block text-xs text-gray-400"><?= htmlspecialchars($r['requester_type']) ?></span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($r['status'] === 'available'): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Available</span>
                                        <?php if (!empty($r['linked_series_title'])): ?>
                                            <span class="block text-xs text-gray-400 mt-1">→ <?= htmlspecialchars($r['linked_series_title']) ?></span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars(date('M j, Y', strtotime($r['created_at']))) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <?php if ($r['status'] !== 'available'): ?>
                                    <form method="POST" action="/admin/series-requests/mark-available/<?= (int)$r['id'] ?>" class="flex items-center justify-end gap-2">
                                        <?= \App\Core\Security::csrfField() ?>
                                        <select name="series_id" class="border border-gray-300 rounded px-2 py-1 text-xs max-w-[180px]">
                                            <option value="">Link a series (optional)</option>
                                            <?php foreach($allSeries as $s): ?>
                                                <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['title']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" onclick="return confirm('Mark this request as available and notify all requesters?')" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-xs">Mark Available</button>
                                    </form>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400">Notified<?= $r['notified'] ? ' ✓' : '…' ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <tr id="series-requests-no-results" style="display:none;">
                                <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">No requests match your search.</td>
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
        initAdminTableFilter('series-requests-search', '#series-requests-table tbody tr[data-search]', { emptyMessageId: 'series-requests-no-results' });
    </script>
</body>
</html>
