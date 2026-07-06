<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Ads - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script><link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body class="bg-black text-white antialiased font-sans">

    <?php require __DIR__ . '/../partials/header.php'; ?>

    <main class="max-w-3xl mx-auto px-4 py-8 pt-20">
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold">My Ads</h1>
            <a href="/advertise" class="bg-red-600 hover:bg-red-700 text-white font-bold px-4 py-2 rounded">New Ad</a>
        </div>

        <?php $error = \App\Core\Session::getFlash('error'); if($error): ?>
            <div class="bg-red-500/10 border border-red-500 text-red-500 p-3 rounded mb-6 text-center text-sm"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <?php $success = \App\Core\Session::getFlash('success'); if($success): ?>
            <div class="bg-green-500/10 border border-green-500 text-green-500 p-3 rounded mb-6 text-center text-sm"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if (empty($ads)): ?>
            <p class="text-gray-400 text-center py-12">You haven't created any ads yet.</p>
        <?php endif; ?>

        <div class="space-y-4">
            <?php foreach ($ads as $ad): ?>
            <?php
                $expired = !empty($ad['expires_at']) && strtotime($ad['expires_at']) <= time();
                $statusLabel = $ad['payment_status'] === 'pending' ? 'Awaiting Payment' : ($expired ? 'Expired' : ($ad['is_active'] ? 'Active' : 'Inactive'));
                $statusColor = $ad['payment_status'] === 'pending' ? 'bg-yellow-500/20 text-yellow-400' : ($expired ? 'bg-gray-700 text-gray-300' : ($ad['is_active'] ? 'bg-green-500/20 text-green-400' : 'bg-gray-700 text-gray-300'));
            ?>
            <div class="bg-gray-900 border border-gray-800 rounded-lg p-4 flex items-center gap-4">
                <?php if ($ad['media_type'] === 'video'): ?>
                    <video src="<?= htmlspecialchars($ad['media_url']) ?>" class="w-24 h-14 object-cover rounded" muted></video>
                <?php else: ?>
                    <img src="<?= htmlspecialchars($ad['media_url']) ?>" class="w-24 h-14 object-cover rounded">
                <?php endif; ?>
                <div class="flex-1">
                    <p class="font-bold"><?= htmlspecialchars($ad['title']) ?></p>
                    <p class="text-gray-400 text-sm"><?= (int)$ad['duration_seconds'] ?>s &bull; <?= ucfirst($ad['platform_placement']) ?></p>
                    <p class="text-gray-500 text-xs"><?= $ad['expires_at'] ? 'Expires ' . htmlspecialchars($ad['expires_at']) : 'No expiry set' ?></p>
                </div>
                <span class="text-xs font-bold px-2 py-1 rounded-full <?= $statusColor ?>"><?= $statusLabel ?></span>
                <?php if ($ad['payment_status'] !== 'pending'): ?>
                <form method="POST" action="/advertise/renew/<?= (int)$ad['id'] ?>">
                    <?= \App\Core\Security::csrfField() ?>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold px-3 py-2 rounded">Renew</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </main>

    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
