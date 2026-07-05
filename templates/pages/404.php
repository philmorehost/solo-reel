<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>404 - SOLOREEL</title>
<script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-white antialiased font-sans flex items-center justify-center min-h-screen">
<div class="text-center px-4">
    <div class="text-8xl font-black text-white/5 mb-6">404</div>
    <h1 class="text-3xl font-bold mb-3">Page Not Found</h1>
    <p class="text-white/40 mb-2">The page you were looking for does not exist or has been moved.</p>
    <?php if (isset($debugUri)): ?>
        <p class="text-red-400/50 text-xs font-mono mb-6"><?= htmlspecialchars($debugMethod ?? 'GET') ?> <?= htmlspecialchars($debugUri) ?></p>
    <?php endif; ?>
    <a href="/" class="inline-flex items-center gap-2 bg-gradient-to-r from-red-600 to-red-800 text-white font-bold px-6 py-3 rounded-xl hover:from-red-500 hover:to-red-700 transition-all shadow-lg shadow-red-600/20">
        &larr; Go Home
    </a>
</div>
</body></html>
