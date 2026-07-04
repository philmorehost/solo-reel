<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-white antialiased font-sans flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-gray-900 p-8 rounded-lg shadow-xl border border-gray-800">
        <h2 class="text-3xl font-bold mb-6 text-center text-red-600 tracking-tighter">SOLOREEL</h2>
        <h3 class="text-xl mb-6 text-center text-gray-300">Sign in to your account</h3>

        <?php $error = \App\Core\Session::getFlash('error'); if($error): ?>
            <div class="bg-red-500/10 border border-red-500 text-red-500 p-3 rounded mb-4 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="/login" method="POST" class="space-y-4">
            <?= \App\Core\Security::csrfField() ?>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Email</label>
                <input type="email" name="email" required class="w-full bg-black border border-gray-700 rounded px-4 py-2 text-white focus:outline-none focus:border-red-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Password</label>
                <input type="password" name="password" required class="w-full bg-black border border-gray-700 rounded px-4 py-2 text-white focus:outline-none focus:border-red-500">
            </div>
            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition-colors">
                Sign In
            </button>
        </form>
        <div class="my-6 border-t border-gray-800"></div>
        <a href="/auth/google" class="w-full flex items-center justify-center bg-white text-gray-900 font-bold py-2 px-4 rounded transition-colors mb-4">
            <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24"><path fill="currentColor" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
            Sign in with Google
        </a>
        <p class="mt-4 text-center text-sm text-gray-400">
            <div class="flex justify-between w-full">
                <a href="/forgot-password" class="text-red-500 hover:text-red-400">Forgot Password?</a>
                <span>Don't have an account? <a href="/register" class="text-red-500 hover:text-red-400">Sign up</a></span>
            </div>
        </p>
    </div>
</body>
</html>
