<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Settings - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <?php require __DIR__ . "/partials/sidebar.php"; ?>

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
                                <div class="mb-4 bg-gray-800 p-4 rounded text-sm text-gray-300">
                                    <h3 class="font-bold text-white mb-2">How to Setup Google Auth:</h3>
                                    <ol class="list-decimal ml-4 space-y-1">
                                        <li>Go to <a href="https://console.cloud.google.com/" target="_blank" class="text-blue-400 hover:underline">Google Cloud Console</a>.</li>
                                        <li>Create a new Project.</li>
                                        <li>Navigate to "APIs & Services" > "Credentials".</li>
                                        <li>Click "Create Credentials" > "OAuth client ID".</li>
                                        <li>Select "Web application".</li>
                                        <li>Under "Authorized redirect URIs", add: <br><code class="text-xs bg-black p-1 rounded"><?= (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]/auth/google/callback" ?></code></li>
                                        <li>Copy the Client ID and Secret into the fields below.</li>
                                    </ol>
                                </div>
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

                        <!-- Cron Jobs Section -->
                        <div class="mt-10 border-t pt-8">
                            <h2 class="text-2xl font-bold mb-4">Cron Jobs Setup</h2>
                            <p class="text-gray-600 mb-6">Add these entries to your server's crontab to enable scheduled tasks.</p>

                            <div class="space-y-4">
                                <div class="bg-gray-800 rounded-lg p-5 border border-gray-700">
                                    <h3 class="font-bold text-white mb-2">Video Queue Processing</h3>
                                    <p class="text-gray-400 text-sm mb-2">Converts uploaded MP4 videos to HLS streaming format. Runs every minute.</p>
                                    <code class="block bg-black text-green-400 p-3 rounded text-sm font-mono">
* * * * * /usr/bin/php <?= dirname($_SERVER['SCRIPT_FILENAME']) ?>/cron/process-video-queue.php >> <?= dirname(dirname($_SERVER['SCRIPT_FILENAME'])) ?>/logs/video-queue.log 2>&1
                                    </code>
                                </div>

                                <div class="bg-gray-800 rounded-lg p-5 border border-gray-700">
                                    <h3 class="font-bold text-white mb-2">Email Queue Processing</h3>
                                    <p class="text-gray-400 text-sm mb-2">Sends pending emails from the queue. Runs every minute.</p>
                                    <code class="block bg-black text-green-400 p-3 rounded text-sm font-mono">
* * * * * /usr/bin/php <?= dirname($_SERVER['SCRIPT_FILENAME']) ?>/cron/process-email-queue.php >> <?= dirname(dirname($_SERVER['SCRIPT_FILENAME'])) ?>/logs/email-queue.log 2>&1
                                    </code>
                                </div>

                                <div class="bg-gray-800 p-4 rounded text-sm text-gray-400">
                                    <p class="font-bold text-amber-400 mb-1">To add cron jobs via cPanel:</p>
                                    <ol class="list-decimal ml-4 space-y-1">
                                        <li>Log into cPanel.</li>
                                        <li>Click <strong>"Cron Jobs"</strong> under the Advanced section.</li>
                                        <li>Select <strong>"Once Per Minute"</strong> in Common Settings.</li>
                                        <li>Paste the command from above into the command field.</li>
                                        <li>Click <strong>"Add New Cron Job"</strong>.</li>
                                    </ol>
                                    <p class="font-bold text-amber-400 mt-3 mb-1">To add via SSH:</p>
                                    <code class="block bg-black text-green-400 p-3 rounded text-sm font-mono">crontab -e</code>
                                    <p class="text-gray-500 mt-2">Then paste the cron commands above, save, and exit (Ctrl+X, Y, Enter in nano).</p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
