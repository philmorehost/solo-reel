<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <aside class="w-64 bg-gray-900 text-white flex-shrink-0 hidden md:flex flex-col">
            <div class="h-16 flex items-center justify-center border-b border-gray-800">
                <span class="text-xl font-bold text-red-500 tracking-tighter">SOLOREEL Admin</span>
            </div>
            <nav class="flex-1 overflow-y-auto py-4">
                <a href="/admin" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Dashboard</a>
                <a href="/admin/series" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Series</a>
                <a href="/admin/episodes" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Episodes</a>
                <a href="/admin/users" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Users</a>
                <a href="/admin/coins" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Coins Mgmt</a>
                <a href="/admin/settings" class="block px-6 py-3 bg-gray-800 border-l-4 border-red-500">Settings</a>
                <a href="/admin/settings/payments" class="block px-6 py-3 text-gray-400 hover:bg-gray-800 hover:text-white">Payment Gateway</a>
            </nav>
        </aside>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6">
                <h1 class="text-2xl font-semibold text-gray-800">Site Settings</h1>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">

                <?php $msg = \App\Core\Session::getFlash('success'); if($msg): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4"><?= htmlspecialchars($msg) ?></div>
                <?php endif; ?>

                <div class="bg-white rounded-lg shadow overflow-hidden p-6 w-full max-w-4xl">
                    <form method="POST" enctype="multipart/form-data">
                        <?= \App\Core\Security::csrfField() ?>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <!-- Left Column -->
                            <div>
                                <h2 class="text-xl font-bold mb-4 border-b pb-2">Branding</h2>
                                <div class="mb-4">
                                    <label class="block text-gray-700 font-bold mb-2">Site Logo (png, jpg, webp)</label>
                                    <input type="file" name="site_logo" accept="image/png, image/jpeg, image/webp" class="w-full border rounded px-3 py-2">
                                    <?php if(!empty($settings['site_logo'])): ?>
                                        <img src="<?= htmlspecialchars($settings['site_logo']) ?>" class="mt-2 h-12 object-contain bg-gray-900 rounded">
                                    <?php endif; ?>
                                </div>
                                <div class="mb-4">
                                    <label class="block text-gray-700 font-bold mb-2">Favicon (png, jpg, webp)</label>
                                    <input type="file" name="site_favicon" accept="image/png, image/jpeg, image/webp" class="w-full border rounded px-3 py-2">
                                    <?php if(!empty($settings['site_favicon'])): ?>
                                        <img src="<?= htmlspecialchars($settings['site_favicon']) ?>" class="mt-2 h-8 w-8 object-contain bg-gray-900 rounded">
                                    <?php endif; ?>
                                </div>

                                <h2 class="text-xl font-bold mt-8 mb-4 border-b pb-2">General / SEO</h2>

                                <div class="mb-4">
                                    <label class="block text-gray-700 font-bold mb-2">Site / Meta Title</label>
                                    <input type="text" name="meta_title" value="<?= htmlspecialchars($settings['meta_title'] ?? '') ?>" required class="w-full border rounded px-3 py-2">
                                </div>

                                <div class="mb-4">
                                    <label class="block text-gray-700 font-bold mb-2">Meta Description</label>
                                    <textarea name="meta_description" rows="3" class="w-full border rounded px-3 py-2"><?= htmlspecialchars($settings['meta_description'] ?? '') ?></textarea>
                                </div>

                                                                <div class="mb-4">
                                    <label class="block text-gray-700 font-bold mb-2">Meta Keywords</label>
                                    <input type="text" name="meta_keywords" value="<?= htmlspecialchars($settings['meta_keywords'] ?? '') ?>" placeholder="keyword1, keyword2, keyword3" class="w-full border rounded px-3 py-2">
                                </div>

                                <div class="mb-4 flex gap-4">
                                    <label class="flex items-center">
                                        <input type="hidden" name="seo_llms_enabled" value="0">
                                        <input type="checkbox" name="seo_llms_enabled" value="1" <?= (isset($settings['seo_llms_enabled']) && $settings['seo_llms_enabled'] == '1') ? 'checked' : '' ?> class="mr-2">
                                        Enable AI/LLMs.txt
                                    </label>
                                    <label class="flex items-center">
                                        <input type="hidden" name="seo_sitemap_enabled" value="0">
                                        <input type="checkbox" name="seo_sitemap_enabled" value="1" <?= (isset($settings['seo_sitemap_enabled']) && $settings['seo_sitemap_enabled'] == '1') ? 'checked' : '' ?> class="mr-2">
                                        Enable Auto-Sitemap
                                    </label>
                                </div>

                                <h2 class="text-xl font-bold mt-8 mb-4 border-b pb-2">Social Sharing</h2>
                                <div class="mb-2"><input type="text" name="social_facebook" value="<?= htmlspecialchars($settings['social_facebook'] ?? '') ?>" placeholder="Facebook URL" class="w-full border rounded px-3 py-2"></div>
                                <div class="mb-2"><input type="text" name="social_twitter" value="<?= htmlspecialchars($settings['social_twitter'] ?? '') ?>" placeholder="Twitter/X URL" class="w-full border rounded px-3 py-2"></div>
                                <div class="mb-2"><input type="text" name="social_instagram" value="<?= htmlspecialchars($settings['social_instagram'] ?? '') ?>" placeholder="Instagram URL" class="w-full border rounded px-3 py-2"></div>
                                <div class="mb-4"><input type="text" name="social_tiktok" value="<?= htmlspecialchars($settings['social_tiktok'] ?? '') ?>" placeholder="TikTok URL" class="w-full border rounded px-3 py-2"></div>
                            </div>

                            <!-- Right Column -->
                            <div>
                                <h2 class="text-xl font-bold mb-4 border-b pb-2">Analytics & Custom Code</h2>

                                <div class="mb-4">
                                    <label class="block text-gray-700 font-bold mb-2">Google Analytics ID (GA4)</label>
                                    <input type="text" name="ga_id" value="<?= htmlspecialchars($settings['ga_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX" class="w-full border rounded px-3 py-2">
                                </div>

                                <div class="mb-4">
                                    <label class="block text-gray-700 font-bold mb-2">Custom Header Code (&lt;head&gt;)</label>
                                    <textarea name="custom_header" rows="4" class="w-full border rounded px-3 py-2 font-mono text-sm" placeholder="<script>...</script>"><?= htmlspecialchars($settings['custom_header'] ?? '') ?></textarea>
                                </div>

                                <div class="mb-4">
                                    <label class="block text-gray-700 font-bold mb-2">Custom Footer Code (before &lt;/body&gt;)</label>
                                    <textarea name="custom_footer" rows="4" class="w-full border rounded px-3 py-2 font-mono text-sm" placeholder="<script>...</script>"><?= htmlspecialchars($settings['custom_footer'] ?? '') ?></textarea>
                                </div>

                                <h2 class="text-xl font-bold mt-8 mb-4 border-b pb-2">Google Login Integration</h2>
                                <div class="mb-2 flex items-center">
                                    <input type="hidden" name="google_auth_enabled" value="0">
                                    <input type="checkbox" name="google_auth_enabled" value="1" <?= (isset($settings['google_auth_enabled']) && $settings['google_auth_enabled'] == '1') ? 'checked' : '' ?> class="mr-2">
                                    <label class="font-bold">Enable Google Auth</label>
                                </div>
                                <div class="mb-2"><input type="text" name="google_client_id" value="<?= htmlspecialchars($settings['google_client_id'] ?? '') ?>" placeholder="Google Client ID" class="w-full border rounded px-3 py-2"></div>
                                <div class="mb-4"><input type="password" name="google_client_secret" value="<?= htmlspecialchars($settings['google_client_secret'] ?? '') ?>" placeholder="Google Client Secret" class="w-full border rounded px-3 py-2"></div>
                            </div>
                        </div>

                        <div class="mt-8 border-t pt-6">
                            <button type="submit" class="bg-indigo-600 text-white font-bold py-3 px-8 rounded hover:bg-indigo-700 transition">
                                Save All Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
