<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($episode) ? 'Edit' : 'Create' ?> Episode - SOLOREEL Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel='stylesheet' href='/assets/css/admin-responsive.css'>
</head>
<body class="bg-gray-100 font-sans antialiased text-gray-900">
    <div class="flex h-screen overflow-hidden">
        <?php require __DIR__ . "/partials/sidebar.php"; ?>

        <main class="flex-1 flex flex-col overflow-hidden">
            <header class="h-16 bg-white shadow flex items-center px-6 justify-between">
            <button onclick="toggleAdminSidebar()" class="admin-hamburger mr-3 p-2 text-gray-600 hover:text-gray-900"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg></button>
                <h1 class="text-2xl font-semibold text-gray-800"><?= isset($episode) ? 'Edit' : 'Create' ?> Episode</h1>
                <a href="/admin/episodes" class="text-gray-500 hover:text-gray-700">Back to List</a>
            </header>

            <div class="flex-1 overflow-y-auto p-6 bg-gray-50">
                <div class="bg-white rounded-lg shadow overflow-hidden p-6 max-w-2xl mx-auto">
                    <!-- Note the enctype="multipart/form-data" added for file uploads -->
                    <form id="episode-form" method="POST" enctype="multipart/form-data">
                        <?= \App\Core\Security::csrfField() ?>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Series</label>
                            <select name="series_id" class="w-full border rounded px-3 py-2" required>
                                <option value="">Select a Series</option>
                                <?php foreach($seriesList as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= (isset($episode) && $episode['series_id'] == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Episode Title</label>
                            <input type="text" name="title" value="<?= htmlspecialchars($episode['title'] ?? '') ?>" required class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 font-bold mb-2">Episode Number</label>
                            <input type="number" name="episode_number" value="<?= htmlspecialchars($episode['episode_number'] ?? 1) ?>" required class="w-full border rounded px-3 py-2">
                        </div>

                        <!-- Video Upload Field -->
                        <div class="mb-4 p-4 border border-dashed border-gray-400 rounded bg-gray-50">
                            <label class="block text-gray-700 font-bold mb-2">Upload Video (MP4)</label>
                            <input type="file" id="video_file" name="video_file" accept="video/mp4" <?= !isset($episode) ? 'required' : '' ?> class="w-full">
                            <p class="text-sm text-gray-500 mt-2">Video is published immediately upon upload<?= isset($episode) ? ' (leave blank to keep the current video)' : '' ?>.</p>

                            <!-- Progress Bar Container -->
                            <div id="upload-progress-container" class="hidden mt-4">
                                <div class="flex justify-between mb-1">
                                    <span class="text-sm font-medium text-gray-700">Uploading...</span>
                                    <span id="upload-percent" class="text-sm font-medium text-gray-700">0%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div id="upload-progress-bar" class="bg-red-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Trailer Upload Field (optional) -->
                        <div class="mb-4 p-4 border border-dashed border-gray-400 rounded bg-gray-50">
                            <label class="block text-gray-700 font-bold mb-2">Upload Trailer (MP4, optional)</label>
                            <input type="file" name="trailer_file" accept="video/mp4" class="w-full">
                            <p class="text-sm text-gray-500 mt-2">
                                Shown in the "For You" feed as an auto-playing trailer<?= isset($episode) && !empty($episode['trailer_url']) ? ' (leave blank to keep the current trailer)' : '. Leave blank if this episode has no trailer' ?>.
                            </p>
                        </div>

                        <!-- Cover Image / Thumbnail Field -->
                        <div class="mb-4 p-4 border border-dashed border-gray-400 rounded bg-gray-50">
                            <label class="block text-gray-700 font-bold mb-2">Cover Image (Thumbnail)</label>
                            <?php if (isset($episode) && !empty($episode['thumbnail_url'])): ?>
                                <img id="thumbnail-preview" src="<?= htmlspecialchars($episode['thumbnail_url']) ?>" class="w-24 aspect-[2/3] object-cover rounded mb-2 border">
                            <?php else: ?>
                                <img id="thumbnail-preview" class="w-24 aspect-[2/3] object-cover rounded mb-2 border hidden">
                            <?php endif; ?>
                            <input type="file" id="thumbnail_file" name="thumbnail_file" accept="image/jpeg,image/png,image/webp" class="w-full">
                            <p id="thumbnail-help" class="text-sm text-gray-500 mt-2">
                                Optional — a frame is automatically captured from the video you upload above<?= isset($episode) ? ' when you choose a new one' : '' ?>. Pick a file here to use your own cover instead.
                            </p>
                        </div>

                        <script>
                            var thumbnailInput = document.getElementById('thumbnail_file');
                            var thumbnailPreview = document.getElementById('thumbnail-preview');
                            var thumbnailHelp = document.getElementById('thumbnail-help');

                            thumbnailInput.addEventListener('change', function (e) {
                                var file = e.target.files[0];
                                if (!file) return;
                                thumbnailPreview.src = URL.createObjectURL(file);
                                thumbnailPreview.classList.remove('hidden');
                                thumbnailHelp.textContent = 'Using the cover image you selected.';
                            });

                            // Auto-capture a cover image from the uploaded video, entirely in the
                            // browser (HTML5 <video> + <canvas>) — no server-side ffmpeg involved,
                            // since this host doesn't have it. Only runs if the admin hasn't already
                            // picked their own cover image.
                            document.getElementById('video_file').addEventListener('change', function (e) {
                                var file = e.target.files[0];
                                if (!file || (thumbnailInput.files && thumbnailInput.files.length > 0)) return;

                                var video = document.createElement('video');
                                video.preload = 'metadata';
                                video.muted = true;
                                video.playsInline = true;
                                video.style.cssText = 'position:absolute;width:1px;height:1px;opacity:0;pointer-events:none;';
                                document.body.appendChild(video);
                                video.src = URL.createObjectURL(file);

                                function cleanup() {
                                    URL.revokeObjectURL(video.src);
                                    if (video.parentNode) video.parentNode.removeChild(video);
                                }

                                video.addEventListener('loadedmetadata', function () {
                                    video.currentTime = Math.min(1, (video.duration || 0) / 2);
                                });

                                video.addEventListener('seeked', function () {
                                    var canvas = document.createElement('canvas');
                                    canvas.width = video.videoWidth;
                                    canvas.height = video.videoHeight;
                                    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);

                                    canvas.toBlob(function (blob) {
                                        if (blob && (!thumbnailInput.files || thumbnailInput.files.length === 0)) {
                                            var dataTransfer = new DataTransfer();
                                            dataTransfer.items.add(new File([blob], 'auto-thumbnail.jpg', { type: 'image/jpeg' }));
                                            thumbnailInput.files = dataTransfer.files;

                                            thumbnailPreview.src = URL.createObjectURL(blob);
                                            thumbnailPreview.classList.remove('hidden');
                                            thumbnailHelp.textContent = 'Cover image auto-captured from your video — pick a file above to use your own instead.';
                                        }
                                        cleanup();
                                    }, 'image/jpeg', 0.85);
                                });

                                video.addEventListener('error', cleanup);
                            });
                        </script>

                        <div class="mb-4 flex items-center">
                            <input type="checkbox" name="is_free" id="is_free" class="mr-2" <?= (!isset($episode) || $episode['is_free']) ? 'checked' : '' ?>>
                            <label for="is_free" class="text-gray-700 font-bold">Is Free?</label>
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 font-bold mb-2">Coin Cost (if not free)</label>
                            <input type="number" step="0.01" name="coin_cost" value="<?= htmlspecialchars($episode['coin_cost'] ?? 0.00) ?>" class="w-full border rounded px-3 py-2">
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 font-bold mb-2">Unlock Method (if not free)</label>
                            <select name="unlock_method" class="w-full border rounded px-3 py-2">
                                <option value="coins" <?= (isset($episode) && ($episode['unlock_method'] ?? 'coins') === 'coins') ? 'selected' : '' ?>>Coins Only</option>
                                <option value="ads" <?= (isset($episode) && ($episode['unlock_method'] ?? '') === 'ads') ? 'selected' : '' ?>>Ads Only</option>
                                <option value="both" <?= (isset($episode) && ($episode['unlock_method'] ?? '') === 'both') ? 'selected' : '' ?>>Coins or Ads</option>
                            </select>
                        </div>

                        <button id="submit-btn" type="submit" class="bg-blue-600 text-white font-bold py-2 px-4 rounded hover:bg-blue-700">
                            Save Episode
                        </button>
                    </form>
                    
                    <script>
                        document.getElementById('episode-form').addEventListener('submit', function(e) {
                            e.preventDefault();
                            
                            var form = this;
                            var submitBtn = document.getElementById('submit-btn');
                            var formData = new FormData(form);
                            
                            var progressContainer = document.getElementById('upload-progress-container');
                            var progressBar = document.getElementById('upload-progress-bar');
                            var progressPercent = document.getElementById('upload-percent');
                            
                            progressContainer.classList.remove('hidden');
                            progressBar.classList.remove('bg-green-600');
                            progressBar.classList.add('bg-red-600');
                            progressBar.style.width = '0%';
                            progressPercent.innerText = '0%';
                            submitBtn.disabled = true;
                            submitBtn.innerText = 'Uploading...';
                            
                            var xhr = new XMLHttpRequest();
                            xhr.open('POST', form.action, true);
                            
                            xhr.upload.onprogress = function(event) {
                                if (event.lengthComputable) {
                                    var percentComplete = Math.round((event.loaded / event.total) * 100);
                                    progressBar.style.width = percentComplete + '%';
                                    progressPercent.innerText = percentComplete + '%';
                                    
                                    if (percentComplete === 100) {
                                        progressBar.classList.remove('bg-red-600');
                                        progressBar.classList.add('bg-green-600');
                                        progressPercent.innerText = 'Saving...';
                                    }
                                }
                            };
                            
                            xhr.onload = function() {
                                if (xhr.status >= 200 && xhr.status < 300) {
                                    window.location.href = '/admin/episodes';
                                } else {
                                    alert('Error uploading episode. Check console for details.');
                                    console.error(xhr.responseText);
                                    submitBtn.disabled = false;
                                    submitBtn.innerText = 'Save Episode';
                                }
                            };
                            
                            xhr.onerror = function() {
                                alert('Network error occurred during upload.');
                                submitBtn.disabled = false;
                                submitBtn.innerText = 'Save Episode';
                            };
                            
                            xhr.send(formData);
                        });
                    </script>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
