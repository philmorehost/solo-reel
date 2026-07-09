<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>For You - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <style>
        body { background: #000; overflow: hidden; }
        #foryou-feed {
            height: 100vh;
            overflow-y: scroll;
            scroll-snap-type: y mandatory;
        }
        .foryou-card {
            height: 100vh;
            scroll-snap-align: start;
            position: relative;
        }
        .foryou-card video { width: 100%; height: 100%; object-fit: cover; }
        ::-webkit-scrollbar { display: none; }
    </style>
</head>
<body class="antialiased font-sans">

    <?php require __DIR__ . '/../partials/header.php'; ?>

    <?php if (empty($trailers)): ?>
        <main class="min-h-screen flex items-center justify-center text-center px-6">
            <div>
                <p class="text-6xl mb-4">🎬</p>
                <h1 class="text-2xl font-bold text-white mb-2">No trailers yet</h1>
                <p class="text-gray-500">Check back soon — new trailers show up here as they're added.</p>
            </div>
        </main>
    <?php else: ?>
        <div id="foryou-feed">
            <?php foreach($trailers as $i => $t): ?>
                <div class="foryou-card" data-index="<?= $i ?>">
                    <video src="<?= htmlspecialchars($t['trailer_url']) ?>" muted loop playsinline
                           poster="<?= htmlspecialchars($t['cover_image'] ?? '/assets/img/default-cover.jpg') ?>"></video>
                    <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-black/40 pointer-events-none"></div>
                    <div class="absolute bottom-24 sm:bottom-16 left-4 right-4 sm:left-8 sm:right-8 z-10">
                        <h2 class="text-white text-xl sm:text-2xl font-extrabold mb-3 drop-shadow-lg"><?= htmlspecialchars($t['series_title']) ?></h2>
                        <a href="<?= !empty($t['resume_slug']) ? '/episodes/' . htmlspecialchars($t['resume_slug']) : '/movie/' . htmlspecialchars($t['series_slug']) ?>"
                           class="inline-flex items-center gap-2 bg-white text-black font-bold px-6 py-3 rounded-full hover:bg-gray-200 transition shadow-lg">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4l12 6-12 6z"></path></svg>
                            Watch Now
                        </a>
                    </div>
                    <button class="foryou-mute-toggle absolute top-4 right-4 z-10 bg-black/50 text-white rounded-full p-2.5">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.617.79L5.03 14H3a1 1 0 01-1-1V7a1 1 0 011-1h2.03l3.353-2.79a1 1 0 011-.134zM14.657 2.929a1 1 0 011.414 0A9.972 9.972 0 0119 10a9.972 9.972 0 01-2.929 7.071 1 1 0 11-1.414-1.414A7.971 7.971 0 0017 10c0-2.21-.894-4.208-2.343-5.657a1 1 0 010-1.414zm-2.829 2.828a1 1 0 011.415 0A5.983 5.983 0 0115 10a5.983 5.983 0 01-1.757 4.243 1 1 0 11-1.415-1.415A3.987 3.987 0 0013 10a3.987 3.987 0 00-1.172-2.828 1 1 0 010-1.415z"></path></svg>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>

        <script>
            (function () {
                const cards = document.querySelectorAll('.foryou-card');
                let muted = true;

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        const video = entry.target.querySelector('video');
                        if (!video) return;
                        if (entry.isIntersecting) {
                            video.muted = muted;
                            video.play().catch(() => {});
                        } else {
                            video.pause();
                        }
                    });
                }, { threshold: 0.6 });

                cards.forEach(card => observer.observe(card));

                document.querySelectorAll('.foryou-mute-toggle').forEach(btn => {
                    btn.addEventListener('click', () => {
                        muted = !muted;
                        cards.forEach(card => {
                            const video = card.querySelector('video');
                            if (video) video.muted = muted;
                        });
                    });
                });
            })();
        </script>
    <?php endif; ?>

    <script src="/assets/js/protection.js"></script>
</body>
</html>
