<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Ads - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel='stylesheet' href='/assets/css/admin-responsive.css'>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <?php require __DIR__ . "/partials/sidebar.php"; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6 justify-between">
            <button onclick="toggleAdminSidebar()" class="admin-hamburger mr-3 p-2 text-gray-600 hover:text-gray-900"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                <h1 class="text-2xl font-semibold text-gray-800">Custom Ads</h1>
                <a href="/admin/custom-ads/create" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded shadow">Add New Ad</a>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <?php $msg = \App\Core\Session::getFlash('success'); if($msg): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>
                <?php $err = \App\Core\Session::getFlash('error'); if($err): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4"><?= htmlspecialchars($err) ?></div>
                <?php endif; ?>

                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="table-responsive">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Media</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Placement</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach($ads as $ad): ?>
                            <?php
                                $expired = !empty($ad['expires_at']) && strtotime($ad['expires_at']) <= time();
                            ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($ad['media_type'] === 'video'): ?>
                                        <video src="<?= htmlspecialchars($ad['media_url']) ?>" class="h-12 w-24 object-cover rounded" muted></video>
                                    <?php else: ?>
                                        <img src="<?= htmlspecialchars($ad['media_url']) ?>" class="h-12 w-24 object-cover rounded">
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($ad['title']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 capitalize"><?= htmlspecialchars($ad['placement']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?= $ad['expires_at'] ? htmlspecialchars($ad['expires_at']) : 'Never' ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($expired): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-700">Expired</span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $ad['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                            <?= $ad['is_active'] ? 'Active' : 'Inactive' ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="/admin/custom-ads/delete/<?= $ad['id'] ?>" onclick="return confirm('Delete this ad?')" class="text-red-600 hover:text-red-900">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($ads)): ?>
                            <tr><td colspan="6" class="px-6 py-8 text-center text-gray-400">No custom ads yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
