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

            <div class="relative flex items-center my-4">
                <div class="flex-grow border-t border-gray-800"></div>
                <span class="flex-shrink mx-3 text-xs text-gray-500 uppercase tracking-widest">or</span>
                <div class="flex-grow border-t border-gray-800"></div>
            </div>
            <a href="/auth/google"
               class="flex items-center justify-center gap-3 w-full bg-white hover:bg-gray-100 text-gray-800 font-semibold py-3 px-4 rounded-lg transition-colors border border-gray-200 shadow-sm">
                <svg class="w-5 h-5 flex-shrink-0" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                <span class="text-sm sm:text-base">Continue with Google</span>
            </a>

            <p class="text-center text-sm text-gray-400 mt-4">
                Already have an account? <a href="/login" class="text-red-400 hover:text-red-300 font-medium">Sign in</a>
            </p>
        </div>
    </div>
    <?php require __DIR__ . '/../partials/footer.php'; ?>
</body>
</html>
