<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/cinematic.css">
    <link rel="stylesheet" href="/assets/css/admin-responsive.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13.5/dist/cdn.min.js"></script>
</head>
<body class="bg-black text-white antialiased font-sans">
    <div class="flex h-screen overflow-hidden">
        <?php require __DIR__ . "/partials/sidebar.php"; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-zinc-950 border-b border-white/[0.06] flex items-center px-6">
                <button onclick="toggleAdminSidebar()" class="admin-hamburger mr-3 p-2 text-white/40 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="text-2xl font-bold">Dashboard</h1>
                <span class="ml-auto text-sm text-white/40"><?= date('l, F j, Y') ?></span>
            </header>

            <div class="flex-1 overflow-y-auto p-6 space-y-6">

                <?php $msg = \App\Core\Session::getFlash('success'); if($msg): ?>
                <div class="bg-emerald-500/10 border border-emerald-500/20 rounded-xl p-4 text-emerald-400 text-sm"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                    <div class="bg-zinc-900 border border-white/[0.06] rounded-xl p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-white/40 text-sm">Total Users</span>
                            <div class="w-10 h-10 rounded-lg bg-blue-500/10 flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                            </div>
                        </div>
                        <p class="text-3xl font-bold"><?= number_format($stats['users'] ?? 0) ?></p>
                        <p class="text-white/20 text-xs mt-1">+<?= $stats['new_users_this_month'] ?? 0 ?> this month</p>
                    </div>

                    <div class="bg-zinc-900 border border-white/[0.06] rounded-xl p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-white/40 text-sm">Total Series</span>
                            <div class="w-10 h-10 rounded-lg bg-red-500/10 flex items-center justify-center">
                                <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4v16M17 4v16M3 8h4m10 0h4M3 12h18M3 16h4m10 0h4M4 20h16a1 1 0 001-1V5a1 1 0 00-1-1H4a1 1 0 00-1 1v14a1 1 0 001 1z"/></svg>
                            </div>
                        </div>
                        <p class="text-3xl font-bold"><?= number_format($stats['series'] ?? 0) ?></p>
                        <p class="text-white/20 text-xs mt-1"><?= $stats['ongoing_series'] ?? 0 ?> ongoing</p>
                    </div>

                    <div class="bg-zinc-900 border border-white/[0.06] rounded-xl p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-white/40 text-sm">Total Episodes</span>
                            <div class="w-10 h-10 rounded-lg bg-purple-500/10 flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            </div>
                        </div>
                        <p class="text-3xl font-bold"><?= number_format($stats['episodes'] ?? 0) ?></p>
                        <p class="text-white/20 text-xs mt-1"><?= $stats['total_duration_formatted'] ?? '0h' ?> total</p>
                    </div>

                    <div class="bg-zinc-900 border border-white/[0.06] rounded-xl p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-white/40 text-sm">Revenue</span>
                            <div class="w-10 h-10 rounded-lg bg-amber-500/10 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                        <p class="text-3xl font-bold text-amber-400">&#8358;<?= number_format($stats['revenue'] ?? 0, 2) ?></p>
                        <p class="text-white/20 text-xs mt-1"><?= $stats['transactions_today'] ?? 0 ?> today</p>
                    </div>
                </div>

                <!-- Quick Actions + Recent Activity -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-zinc-900 border border-white/[0.06] rounded-xl p-6">
                        <h2 class="font-semibold text-lg mb-4">Quick Actions</h2>
                        <div class="grid grid-cols-2 gap-3">
                            <a href="/admin/series/create" class="flex items-center gap-3 p-3 bg-white/[0.03] border border-white/[0.06] rounded-lg text-sm hover:bg-white/[0.06] transition-colors">
                                <span class="text-red-400">+</span> Add Series
                            </a>
                            <a href="/admin/episodes/create" class="flex items-center gap-3 p-3 bg-white/[0.03] border border-white/[0.06] rounded-lg text-sm hover:bg-white/[0.06] transition-colors">
                                <span class="text-red-400">+</span> Add Episode
                            </a>
                            <a href="/admin/blog/create" class="flex items-center gap-3 p-3 bg-white/[0.03] border border-white/[0.06] rounded-lg text-sm hover:bg-white/[0.06] transition-colors">
                                <span class="text-red-400">+</span> New Post
                            </a>
                            <a href="/admin/settings" class="flex items-center gap-3 p-3 bg-white/[0.03] border border-white/[0.06] rounded-lg text-sm hover:bg-white/[0.06] transition-colors">
                                <span class="text-red-400">&rarr;</span> Settings
                            </a>
                            <a href="/admin/users" class="flex items-center gap-3 p-3 bg-white/[0.03] border border-white/[0.06] rounded-lg text-sm hover:bg-white/[0.06] transition-colors">
                                <span class="text-red-400">&rarr;</span> Users
                            </a>
                            <a href="/admin/settings/payments" class="flex items-center gap-3 p-3 bg-white/[0.03] border border-white/[0.06] rounded-lg text-sm hover:bg-white/[0.06] transition-colors">
                                <span class="text-red-400">&rarr;</span> Payments
                            </a>
                        </div>
                    </div>

                    <div class="bg-zinc-900 border border-white/[0.06] rounded-xl p-6">
                        <h2 class="font-semibold text-lg mb-4">System Status</h2>
                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between py-2 border-b border-white/[0.04]">
                                <span class="text-white/40">PHP Version</span>
                                <span><?= phpversion() ?></span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-white/[0.04]">
                                <span class="text-white/40">App Version</span>
                                <span>1.0.0</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-white/[0.04]">
                                <span class="text-white/40">Mode</span>
                                <span class="<?= defined('APP_DEBUG') && APP_DEBUG ? 'text-amber-400' : 'text-emerald-400' ?>"><?= defined('APP_DEBUG') && APP_DEBUG ? 'Debug' : 'Production' ?></span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-white/[0.04]">
                                <span class="text-white/40">Maintenance</span>
                                <span><?= ($maintenance ?? false) ? '<span class="text-amber-400">ON</span>' : '<span class="text-emerald-400">OFF</span>' ?></span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="text-white/40">Payment Gateway</span>
                                <span><?= ($paymentConfigured ?? false) ? '<span class="text-emerald-400">Configured</span>' : '<span class="text-red-400">Not Set</span>' ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
