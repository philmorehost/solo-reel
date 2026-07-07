(function () {
    'use strict';

    var episodes = window.__REEL_EPISODES || [];
    var csrfToken = window.__REEL_CSRF_TOKEN || '';
    var unlockUrlBase = window.__REEL_UNLOCK_URL_BASE || '/unlock/';
    var feed = document.getElementById('reel-feed');
    if (!feed || episodes.length === 0) return;

    var cards = Array.prototype.slice.call(feed.querySelectorAll('.reel-card'));
    var MUTE_KEY = 'reel_muted';
    var muted = localStorage.getItem(MUTE_KEY) !== 'false'; // default muted until the user opts in (autoplay policy)
    var activeIndex = window.__REEL_START_INDEX || 0;
    var mounted = {}; // index -> { video, hls }
    var DOUBLE_TAP_MS = 300;
    var lastTapAt = {};

    var muteBtn = document.getElementById('reel-mute-btn');
    var muteIcon = document.getElementById('reel-mute-icon');
    var mutedIconPath = 'M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.617.788L4.65 13.65H2a1 1 0 01-1-1v-5.3a1 1 0 011-1h2.65l3.733-3.138a1 1 0 011-.136zM14.657 5.343a1 1 0 011.414 0A7.975 7.975 0 0118 10a7.975 7.975 0 01-1.929 4.657 1 1 0 11-1.414-1.414A5.975 5.975 0 0016 10a5.975 5.975 0 00-1.343-3.243 1 1 0 010-1.414z';
    var unmutedIconPath = 'M9.383 3.076A1 1 0 0110 4v12a1 1 0 01-1.617.788L4.65 13.65H2a1 1 0 01-1-1v-5.3a1 1 0 011-1h2.65l3.733-3.138a1 1 0 011-.136z';

    function updateMuteIcon() {
        if (muteIcon) muteIcon.querySelector('path').setAttribute('d', muted ? mutedIconPath : unmutedIconPath);
    }
    updateMuteIcon();

    function cardAt(index) { return cards[index]; }

    function ensureMounted(index) {
        if (index < 0 || index >= cards.length || mounted[index]) return;
        var card = cardAt(index);
        var ep = episodes[index];
        if (!ep || !ep.has_access || !ep.video_url) return;

        var video = document.createElement('video');
        video.className = 'reel-video absolute inset-0 w-full h-full object-cover';
        video.setAttribute('playsinline', '');
        video.setAttribute('webkit-playsinline', '');
        video.muted = muted;
        video.loop = false;
        video.preload = 'auto';
        card.insertBefore(video, card.firstChild);

        var poster = card.querySelector('.reel-poster');
        if (poster) poster.style.display = 'none';

        var hls = null;
        var isHls = /\.m3u8(\?|$)/i.test(ep.video_url);

        if (isHls && window.Hls && window.Hls.isSupported()) {
            hls = new window.Hls({ maxBufferLength: 15, maxMaxBufferLength: 30 });
            hls.loadSource(ep.video_url);
            hls.attachMedia(video);
        } else if (isHls && video.canPlayType('application/vnd.apple.mpegurl')) {
            video.src = ep.video_url;
        } else {
            // Plain progressive file (mp4, etc.) — native browser playback, no hls.js involved.
            video.src = ep.video_url;
        }

        video.addEventListener('ended', function () {
            var next = index + 1;
            if (next < cards.length) {
                scrollToIndex(next);
            } else {
                video.currentTime = 0;
                video.play().catch(function () {});
            }
        });

        mounted[index] = { video: video, hls: hls };
    }

    function unmount(index) {
        var m = mounted[index];
        if (!m) return;
        try { m.video.pause(); } catch (e) {}
        if (m.hls) { try { m.hls.destroy(); } catch (e) {} }
        m.video.removeAttribute('src');
        try { m.video.load(); } catch (e) {}
        if (m.video.parentNode) m.video.parentNode.removeChild(m.video);
        var card = cardAt(index);
        var poster = card && card.querySelector('.reel-poster');
        if (poster) poster.style.display = '';
        delete mounted[index];
    }

    function reconcileWindow(center) {
        ensureMounted(center - 1);
        ensureMounted(center);
        ensureMounted(center + 1);
        Object.keys(mounted).forEach(function (key) {
            var idx = parseInt(key, 10);
            if (idx < center - 1 || idx > center + 1) unmount(idx);
        });
    }

    function playActive() {
        var m = mounted[activeIndex];
        if (!m) return;
        m.video.muted = muted;
        var p = m.video.play();
        if (p && p.catch) p.catch(function () {});
    }

    function pauseAllExcept(exceptIndex) {
        Object.keys(mounted).forEach(function (key) {
            var idx = parseInt(key, 10);
            if (idx === exceptIndex) return;
            var m = mounted[idx];
            try {
                m.video.pause();
                m.video.currentTime = 0;
            } catch (e) {}
        });
    }

    function setActive(index) {
        if (index === activeIndex && mounted[index]) return;
        activeIndex = index;
        reconcileWindow(index);
        pauseAllExcept(index);
        playActive();

        var ep = episodes[index];
        if (ep && ep.slug) {
            history.replaceState(null, '', '/episodes/' + ep.slug);
            document.title = ep.title + ' - SOLOREEL';
        }
    }

    function scrollToIndex(index, smooth) {
        var card = cardAt(index);
        if (!card) return;
        card.scrollIntoView({ behavior: smooth === false ? 'auto' : 'smooth', block: 'start' });
    }

    // --- IntersectionObserver drives autoplay/pause as cards snap into view ---
    var io = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting && entry.intersectionRatio > 0.7) {
                var idx = parseInt(entry.target.getAttribute('data-index'), 10);
                setActive(idx);
            }
        });
    }, { threshold: [0, 0.7, 1] });
    cards.forEach(function (card) { io.observe(card); });

    // Jump straight to the requested episode without an animated scroll.
    reconcileWindow(activeIndex);
    scrollToIndex(activeIndex, false);
    playActive();

    // --- Mute toggle: persists as the user moves to the next/previous video ---
    if (muteBtn) {
        muteBtn.addEventListener('click', function () {
            muted = !muted;
            localStorage.setItem(MUTE_KEY, String(muted));
            updateMuteIcon();
            Object.keys(mounted).forEach(function (key) {
                mounted[parseInt(key, 10)].video.muted = muted;
            });
        });
    }

    // --- Tap-to-pause / double-tap-to-like, layered above the video but below
    // the scroll container so a vertical drag still scrolls (browsers only
    // fire "click" for a stationary tap, never for a scroll/drag gesture). ---
    cards.forEach(function (card) {
        var tapLayer = card.querySelector('.reel-tap-layer');
        if (!tapLayer) return;
        var index = parseInt(card.getAttribute('data-index'), 10);

        tapLayer.addEventListener('click', function () {
            var now = Date.now();
            var last = lastTapAt[index] || 0;
            lastTapAt[index] = now;

            if (now - last < DOUBLE_TAP_MS) {
                lastTapAt[index] = 0;
                likeEpisode(card, episodes[index]);
                return;
            }

            var m = mounted[index];
            if (!m) return;
            if (m.video.paused) {
                m.video.play().catch(function () {});
            } else {
                m.video.pause();
            }
        });
    });

    function likeEpisode(card, ep) {
        var burst = card.querySelector('.reel-heart-burst svg');
        if (burst) {
            burst.style.transition = 'none';
            burst.style.opacity = '1';
            burst.style.transform = 'scale(0.6)';
            requestAnimationFrame(function () {
                burst.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                burst.style.transform = 'scale(1.3)';
                setTimeout(function () { burst.style.opacity = '0'; }, 350);
            });
        }
        if (!ep || !ep.id) return;
        fetch('/favorites/' + ep.id, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'csrf_token=' + encodeURIComponent(csrfToken),
            credentials: 'same-origin'
        }).catch(function () {}); // best-effort; guests just get the visual heart
    }

    // --- Inline unlock (AJAX) so unlocking a card never leaves the feed ---
    feed.addEventListener('click', function (e) {
        var btn = e.target.closest && e.target.closest('.reel-unlock-btn');
        if (!btn) return;
        var episodeId = btn.getAttribute('data-episode-id');
        var card = btn.closest('.reel-card');
        var errorEl = card.querySelector('.reel-unlock-error');
        btn.disabled = true;
        btn.textContent = 'Unlocking...';

        fetch(unlockUrlBase + episodeId, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'csrf_token=' + encodeURIComponent(csrfToken),
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.status) {
                    btn.disabled = false;
                    btn.textContent = 'Unlock Now';
                    if (errorEl) {
                        errorEl.textContent = data.error || 'Unable to unlock this episode.';
                        errorEl.classList.remove('hidden');
                    }
                    return;
                }
                var index = parseInt(card.getAttribute('data-index'), 10);
                episodes[index].has_access = true;
                card.setAttribute('data-has-access', '1');
                var overlay = card.querySelector('.reel-locked-overlay');
                if (overlay) overlay.remove();
                var blurredPoster = card.querySelector('.reel-locked-poster');
                if (blurredPoster) {
                    blurredPoster.classList.remove('reel-locked-poster', 'blur-sm', 'scale-105', 'opacity-60');
                    blurredPoster.classList.add('reel-poster');
                }
                ensureMounted(index);
                if (index === activeIndex) playActive();
            })
            .catch(function () {
                btn.disabled = false;
                btn.textContent = 'Unlock Now';
                if (errorEl) {
                    errorEl.textContent = 'Network error. Please try again.';
                    errorEl.classList.remove('hidden');
                }
            });
    });
})();
