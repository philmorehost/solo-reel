<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">

        <!-- Sidebar -->
        <?php require __DIR__ . "/partials/sidebar.php"; ?>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6">
                <h1 class="text-2xl font-semibold text-gray-800">Dashboard</h1>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                    <!-- Stat Card -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-gray-500 text-sm font-medium mb-1">Total Users</h3>
                        <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['users']) ?></p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-gray-500 text-sm font-medium mb-1">Total Series</h3>
                        <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['series']) ?></p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-gray-500 text-sm font-medium mb-1">Total Episodes</h3>
                        <p class="text-3xl font-bold text-gray-900"><?= number_format($stats['episodes']) ?></p>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-gray-500 text-sm font-medium mb-1">Total Revenue</h3>
                        <p class="text-3xl font-bold text-green-600">NGN <?= number_format($stats['revenue'], 2) ?></p>
                    </div>
                </div>

                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold mb-4">Quick Actions</h2>
                    <div class="flex gap-4">
                        <a href="/admin/series/create" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded shadow">Add New Series</a>
                        <a href="/admin/episodes/create" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded shadow">Upload Episode</a>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
