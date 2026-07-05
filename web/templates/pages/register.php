<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">
    <title>Create Account - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body class="bg-black text-white antialiased font-sans min-h-screen flex flex-col">
    <div class="flex-1 flex items-center justify-center px-4 py-8">
        <div class="w-full max-w-md mx-auto">
            <div class="text-center mb-6">
                <a href="/"><?= \App\Helpers\Site::getLogoHtml() ?></a>
                <h1 class="text-xl sm:text-2xl font-bold mt-4 text-white">Create Account</h1>
                <p class="text-gray-400 text-sm mt-1">Join SOLOREEL and start watching</p>
            </div>

            <?php $error = \App\Core\Session::getFlash('error'); if($error): ?>
                <div class="bg-red-500/10 border border-red-500/30 text-red-400 p-3 rounded mb-4 text-sm"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="/register" method="POST" class="space-y-4 bg-gray-900/50 border border-gray-800 rounded-xl p-5 sm:p-6">
                <?= \App\Core\Security::csrfField() ?>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">Username</label>
                    <input type="text" name="username" required class="w-full bg-black border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">Email</label>
                    <input type="email" name="email" required autocomplete="email" class="w-full bg-black border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-red-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-400 mb-1.5">Password</label>
                    <input type="password" name="password" required autocomplete="new-password" class="w-full bg-black border border-gray-700 rounded-lg px-4 py-3 text-white focus:outline-none focus:border-red-500">
                </div>
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 rounded-lg transition-colors text-sm sm:text-base">
                    Create Account
                </button>
            </form>

            <p class="text-center text-sm text-gray-400 mt-4">
                Already have an account? <a href="/login" class="text-red-400 hover:text-red-300 font-medium">Sign in</a>
            </p>
        </div>
    </div>
    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
