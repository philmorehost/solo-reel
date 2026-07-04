<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Email - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-white antialiased font-sans flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md bg-gray-900 p-8 rounded-lg shadow-xl border border-gray-800">
        <h2 class="text-3xl font-bold mb-6 text-center text-red-600 tracking-tighter">SOLOREEL</h2>
        <h3 class="text-xl mb-2 text-center text-gray-300">Verify Your Email</h3>
        <p class="text-center text-sm text-gray-500 mb-6">Enter the 6-digit OTP code sent to your email.</p>

        <?php $error = \App\Core\Session::getFlash('error'); if($error): ?>
            <div class="bg-red-500/10 border border-red-500 text-red-500 p-3 rounded mb-4 text-sm">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="/verify-otp" method="POST" class="space-y-4">
            <?= \App\Core\Security::csrfField() ?>
            <div>
                <input type="text" name="otp" required maxlength="6" class="w-full text-center tracking-[1em] text-2xl bg-black border border-gray-700 rounded px-4 py-3 text-white focus:outline-none focus:border-red-500" placeholder="000000">
            </div>
            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded transition-colors">
                Verify
            </button>
        </form>
    </div>
</body>
</html>
