<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-white antialiased font-sans flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-gray-900 p-8 rounded-lg shadow-xl border border-gray-800">
        <h2 class="text-3xl font-bold mb-6 text-center text-red-600 tracking-tighter">SOLOREEL</h2>
        <h3 class="text-xl mb-6 text-center text-gray-300">Create an account</h3>

        <?php $error = \App\Core\Session::getFlash('error'); if($error): ?>
            <div class="bg-red-500/10 border border-red-500 text-red-500 p-3 rounded mb-4 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="/register" method="POST" class="space-y-4">
            <?= \App\Core\Security::csrfField() ?>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Username</label>
                <input type="text" name="username" required class="w-full bg-black border border-gray-700 rounded px-4 py-2 text-white focus:outline-none focus:border-red-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Email</label>
                <input type="email" name="email" required class="w-full bg-black border border-gray-700 rounded px-4 py-2 text-white focus:outline-none focus:border-red-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-400 mb-1">Password</label>
                <input type="password" name="password" required class="w-full bg-black border border-gray-700 rounded px-4 py-2 text-white focus:outline-none focus:border-red-500">
            </div>
            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition-colors">
                Sign Up
            </button>
        </form>
        <p class="mt-4 text-center text-sm text-gray-400">
            Already have an account? <a href="/login" class="text-red-500 hover:text-red-400">Sign in</a>
        </p>
    </div>
</body>
</html>
