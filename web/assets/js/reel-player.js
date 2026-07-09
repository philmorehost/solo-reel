(function () {
    'use strict';

    var episodes = window.__REEL_EPISODES || [];
    var csrfToken = window.__REEL_CSRF_TOKEN || '';
    var unlockUrlBase = window.__REEL_UNLOCK_URL_BASE || '/unlock/';
    var progressUrlBase = window.__REEL_PROGRESS_URL_BASE || '/progress/';
    var likeUrlBase = window.__REEL_LIKE_URL_BASE || '/episodes/';
    var saveUrlBase = window.__REEL_SAVE_URL_BASE || '/episodes/';
    var shareUrlBase = window.__REEL_SHARE_URL_BASE || '/episodes/';
    var commentsUrlBase = window.__REEL_COMMENTS_URL_BASE || '/episodes/';
    var seriesSlug = window.__REEL_SERIES_SLUG || '';
    var feed = document.getElementById('reel-feed');
    if (!feed || episodes.length === 0) return;

    var episodesById = {};
    episodes.forEach(function (e) { episodesById[e.id] = e; });

    function formatCount(n) {
        n = parseInt(n, 10) || 0;
        if (n < 1000) return String(n);
        var trim = function (s) { return s.replace(/\.0$/, ''); };
        if (n < 1000000) return trim((n / 1000).toFixed(1)) + 'K';
        return trim((n / 1000000).toFixed(1)) + 'M';
    }

    function escapeHtml(s) {
        var div = document.createElement('div');
        div.textContent = s == null ? '' : String(s);
        return div.innerHTML;
    }

    function timeAgo(dateStr) {
        if (!dateStr) return '';
        var then = new Date(String(dateStr).replace(' ', 'T')).getTime();
        if (isNaN(then)) return '';
        var seconds = Math.max(0, Math.floor((Date.now() - then) / 1000));
        if (seconds < 60) return 'now';
        var minutes = Math.floor(seconds / 60);
        if (minutes < 60) return minutes + 'm';
        var hours = Math.floor(minutes / 60);
        if (hours < 24) return hours + 'h';
        var days = Math.floor(hours / 24);
        if (days < 7) return days + 'd';
        return Math.floor(days / 7) + 'w';
    }

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
            recordProgress(ep);
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
        syncSidebarForActive(index);

        var ep = episodes[index];
        if (ep && ep.slug) {
            history.replaceState(null, '', '/episodes/' + ep.slug);
            document.title = ep.title + ' - SOLOREEL';
            recordProgress(ep);
        }
    }

    function scrollToIndex(index, smooth) {
        var card = cardAt(index);
        if (!card) return;
        card.scrollIntoView({ behavior: smooth === false ? 'auto' : 'smooth', block: 'start' });
    }

    // Fire-and-forget: powers "resume last-watched episode" and the Continue
    // Watching shelf. Best-effort, no CSRF needed (matches ProgressController).
    function recordProgress(ep) {
        if (!ep || !ep.id || !ep.has_access) return;
        fetch(progressUrlBase + ep.id, { method: 'POST', credentials: 'same-origin' }).catch(function () {});
    }

    // --- Desktop episode-picker sidebar (reelshort-style) — hidden on mobile
    // via CSS; the vertical swipe feed is unaffected either way, this is purely
    // an additional way to jump between already-loaded episodes. ---
    var CHUNK_SIZE = 50;
    var sidebarGrid = document.getElementById('reel-sidebar-grid');
    var sidebarTabs = document.getElementById('reel-sidebar-range-tabs');
    var sidebarLikeBtn = document.getElementById('reel-sidebar-like-btn');
    var sidebarLikeIcon = document.getElementById('reel-sidebar-like-icon');
    var sidebarLikeTotalEl = document.getElementById('reel-sidebar-like-total');
    var sidebarSaveBtn = document.getElementById('reel-sidebar-save-btn');
    var sidebarSaveIcon = document.getElementById('reel-sidebar-save-icon');
    var sidebarSaveTotalEl = document.getElementById('reel-sidebar-save-total');
    var sidebarShareBtn = document.getElementById('reel-sidebar-share-btn');
    var sidebarCommentBtn = document.getElementById('reel-sidebar-comment-btn');
    var sidebarInfoBtn = document.getElementById('reel-sidebar-info-btn');
    var sidebarTitleEl = document.getElementById('reel-sidebar-title');
    var sidebarBreadcrumbEpEl = document.getElementById('reel-sidebar-breadcrumb-ep');
    var sidebarPlotHeadingEl = document.getElementById('reel-sidebar-plot-heading');
    var sidebarSynopsisToggle = document.querySelector('.reel-sidebar-synopsis-toggle');
    var sidebarSynopsisText = document.querySelector('.reel-sidebar-synopsis');
    var seriesTitleText = window.__REEL_SERIES_TITLE || '';
    var seriesTotalsInit = window.__REEL_SERIES_TOTALS || {};
    var seriesLikeTotal = parseInt(seriesTotalsInit.like_count, 10) || 0;
    var seriesSaveTotal = parseInt(seriesTotalsInit.save_count, 10) || 0;
    var currentChunk = 0;

    if (sidebarSynopsisToggle && sidebarSynopsisText) {
        sidebarSynopsisToggle.addEventListener('click', function () {
            var isNowClamped = sidebarSynopsisText.classList.toggle('line-clamp-3');
            sidebarSynopsisToggle.textContent = isNowClamped ? 'More' : 'Less';
        });
    }

    function syncSidebarEpisodeDetails(index) {
        var ep = episodes[index];
        if (!ep) return;
        if (sidebarBreadcrumbEpEl) sidebarBreadcrumbEpEl.textContent = 'Episode ' + ep.episode_number;
        if (sidebarTitleEl) sidebarTitleEl.textContent = 'Episode ' + ep.episode_number + ' - ' + seriesTitleText;
        if (sidebarPlotHeadingEl) sidebarPlotHeadingEl.textContent = 'Plot of Episode ' + ep.episode_number;
    }

    function updateActiveTabUI(chunk) {
        if (!sidebarTabs) return;
        Array.prototype.forEach.call(sidebarTabs.querySelectorAll('.reel-sidebar-tab'), function (tab) {
            var isActive = parseInt(tab.getAttribute('data-chunk'), 10) === chunk;
            tab.classList.toggle('text-white', isActive);
            tab.classList.toggle('border-red-600', isActive);
            tab.classList.toggle('text-gray-400', !isActive);
        });
    }

    function showChunk(chunk) {
        currentChunk = chunk;
        Array.prototype.forEach.call(sidebarGrid.querySelectorAll('.reel-ep-grid-btn'), function (btn) {
            btn.style.display = (parseInt(btn.getAttribute('data-chunk'), 10) === chunk) ? '' : 'none';
        });
        updateActiveTabUI(chunk);
    }

    function updateSidebarGridActive(index) {
        if (!sidebarGrid) return;
        var chunk = Math.floor(index / CHUNK_SIZE);
        if (chunk !== currentChunk) showChunk(chunk);
        Array.prototype.forEach.call(sidebarGrid.querySelectorAll('.reel-ep-grid-btn'), function (btn) {
            btn.classList.toggle('is-active', parseInt(btn.getAttribute('data-index'), 10) === index);
        });
    }

    function renderSidebarLikeState() {
        if (!sidebarLikeIcon) return;
        var ep = episodes[activeIndex];
        var liked = !!(ep && ep.is_liked_by_viewer);
        sidebarLikeIcon.setAttribute('fill', liked ? 'currentColor' : 'none');
        sidebarLikeIcon.classList.toggle('text-red-500', liked);
        sidebarLikeIcon.classList.toggle('text-white', !liked);
        if (sidebarLikeTotalEl) sidebarLikeTotalEl.textContent = formatCount(seriesLikeTotal);
    }

    function renderSidebarSaveState() {
        if (!sidebarSaveIcon) return;
        var ep = episodes[activeIndex];
        var saved = !!(ep && ep.is_saved_by_viewer);
        sidebarSaveIcon.setAttribute('fill', saved ? 'currentColor' : 'none');
        sidebarSaveIcon.classList.toggle('text-yellow-400', saved);
        sidebarSaveIcon.classList.toggle('text-white', !saved);
        if (sidebarSaveTotalEl) sidebarSaveTotalEl.textContent = formatCount(seriesSaveTotal);
    }

    // Mirrors the per-card rail's like button for the same episode, so both
    // controls stay in sync no matter which one the viewer used.
    function sidebarToggleLike() {
        var ep = episodes[activeIndex];
        if (!ep || !ep.id) return;
        var wasLiked = !!ep.is_liked_by_viewer;
        var newLiked = !wasLiked;
        ep.is_liked_by_viewer = newLiked;
        ep.like_count = (ep.like_count || 0) + (newLiked ? 1 : -1);
        seriesLikeTotal += newLiked ? 1 : -1;
        renderSidebarLikeState();
        var railBtn = cards[activeIndex] && cards[activeIndex].querySelector('.reel-like-btn');
        if (railBtn) setLikeUI(railBtn, newLiked, ep.like_count);

        fetch(likeUrlBase + ep.id + '/like', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'csrf_token=' + encodeURIComponent(csrfToken),
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (!data.status) throw new Error('like failed');
            ep.is_liked_by_viewer = data.data.liked;
            ep.like_count = data.data.count;
            renderSidebarLikeState();
            if (railBtn) setLikeUI(railBtn, data.data.liked, data.data.count);
        }).catch(function () {
            ep.is_liked_by_viewer = wasLiked;
            ep.like_count = (ep.like_count || 0) + (wasLiked ? 1 : -1);
            seriesLikeTotal += wasLiked ? 1 : -1;
            renderSidebarLikeState();
            if (railBtn) setLikeUI(railBtn, wasLiked, ep.like_count);
        });
    }

    function sidebarToggleSave() {
        var ep = episodes[activeIndex];
        if (!ep || !ep.id) return;
        var wasSaved = !!ep.is_saved_by_viewer;
        var newSaved = !wasSaved;
        ep.is_saved_by_viewer = newSaved;
        ep.save_count = (ep.save_count || 0) + (newSaved ? 1 : -1);
        seriesSaveTotal += newSaved ? 1 : -1;
        renderSidebarSaveState();
        var railBtn = cards[activeIndex] && cards[activeIndex].querySelector('.reel-save-btn');
        if (railBtn) setSaveUI(railBtn, newSaved, ep.save_count);

        fetch(saveUrlBase + ep.id + '/save', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'csrf_token=' + encodeURIComponent(csrfToken),
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (!data.status) throw new Error('save failed');
            ep.is_saved_by_viewer = data.data.saved;
            ep.save_count = data.data.count;
            renderSidebarSaveState();
            if (railBtn) setSaveUI(railBtn, data.data.saved, data.data.count);
        }).catch(function () {
            ep.is_saved_by_viewer = wasSaved;
            ep.save_count = (ep.save_count || 0) + (wasSaved ? 1 : -1);
            seriesSaveTotal += wasSaved ? 1 : -1;
            renderSidebarSaveState();
            if (railBtn) setSaveUI(railBtn, wasSaved, ep.save_count);
        });
    }

    function syncSidebarForActive(index) {
        if (!sidebarGrid) return;
        renderSidebarLikeState();
        renderSidebarSaveState();
        syncSidebarEpisodeDetails(index);
        if (sidebarShareBtn) sidebarShareBtn.style.display = (episodes[index] && episodes[index].can_share) ? '' : 'none';
        updateSidebarGridActive(index);
    }

    function initDesktopSidebar() {
        if (!sidebarGrid) return; // sidebar not rendered (shouldn't happen, but stay defensive)

        var chunkCount = Math.ceil(episodes.length / CHUNK_SIZE);
        if (chunkCount > 1 && sidebarTabs) {
            var tabsHtml = '';
            for (var c = 0; c < chunkCount; c++) {
                var start = c * CHUNK_SIZE;
                var end = Math.min(start + CHUNK_SIZE, episodes.length) - 1;
                tabsHtml += '<button type="button" class="reel-sidebar-tab pb-2 border-b-2 border-transparent" data-chunk="' + c + '">' + start + '-' + end + '</button>';
            }
            sidebarTabs.innerHTML = tabsHtml;
            sidebarTabs.addEventListener('click', function (e) {
                var btn = e.target.closest && e.target.closest('.reel-sidebar-tab');
                if (!btn) return;
                showChunk(parseInt(btn.getAttribute('data-chunk'), 10));
            });
        }

        var gridHtml = '';
        episodes.forEach(function (ep, idx) {
            var chunk = Math.floor(idx / CHUNK_SIZE);
            gridHtml += '<button type="button" class="reel-ep-grid-btn bg-gray-800 hover:bg-gray-700 rounded-lg text-white text-sm font-semibold flex items-center justify-center" data-index="' + idx + '" data-chunk="' + chunk + '"' + (chunk === 0 ? '' : ' style="display:none;"') + '>' +
                escapeHtml(ep.episode_number) +
                (!ep.has_access ? '<span class="reel-ep-lock-badge"><svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg></span>' : '') +
                '</button>';
        });
        sidebarGrid.innerHTML = gridHtml;
        sidebarGrid.addEventListener('click', function (e) {
            var btn = e.target.closest && e.target.closest('.reel-ep-grid-btn');
            if (!btn) return;
            scrollToIndex(parseInt(btn.getAttribute('data-index'), 10));
        });

        updateActiveTabUI(0);

        if (sidebarLikeBtn) sidebarLikeBtn.addEventListener('click', sidebarToggleLike);
        if (sidebarSaveBtn) sidebarSaveBtn.addEventListener('click', sidebarToggleSave);
        if (sidebarShareBtn) sidebarShareBtn.addEventListener('click', function () { shareEpisode(sidebarShareBtn, episodes[activeIndex]); });
        if (sidebarCommentBtn) sidebarCommentBtn.addEventListener('click', function () {
            var ep = episodes[activeIndex];
            if (ep) openCommentsSheet(ep.id);
        });
        if (sidebarInfoBtn) sidebarInfoBtn.addEventListener('click', function () { openInfoSheet(); });
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
    // setActive() won't run for this one on its own — its "already active"
    // guard skips the IntersectionObserver's first callback for this card —
    // so record progress for it directly here.
    reconcileWindow(activeIndex);
    scrollToIndex(activeIndex, false);
    playActive();
    recordProgress(episodes[activeIndex]);
    initDesktopSidebar();
    syncSidebarForActive(activeIndex);

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

    // --- Like / Save: shared toggle POST + optimistic UI, used by both the
    // action rail buttons and double-tap-to-like. ---
    function setLikeUI(btn, liked, count) {
        var icon = btn.querySelector('.reel-like-icon');
        var countEl = btn.querySelector('.reel-like-count');
        btn.setAttribute('aria-pressed', liked ? 'true' : 'false');
        icon.setAttribute('fill', liked ? 'currentColor' : 'none');
        icon.classList.toggle('text-red-500', liked);
        icon.classList.toggle('text-white', !liked);
        if (typeof count === 'number') countEl.textContent = formatCount(count);
    }

    function setSaveUI(btn, saved, count) {
        var icon = btn.querySelector('.reel-save-icon');
        var countEl = btn.querySelector('.reel-save-count');
        btn.setAttribute('aria-pressed', saved ? 'true' : 'false');
        icon.setAttribute('fill', saved ? 'currentColor' : 'none');
        icon.classList.toggle('text-yellow-400', saved);
        icon.classList.toggle('text-white', !saved);
        if (typeof count === 'number') countEl.textContent = formatCount(count);
    }

    function toggleLike(btn, ep) {
        if (!ep || !ep.id) return;
        var wasPressed = btn.getAttribute('aria-pressed') === 'true';
        setLikeUI(btn, !wasPressed);
        fetch(likeUrlBase + ep.id + '/like', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'csrf_token=' + encodeURIComponent(csrfToken),
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (!data.status) { setLikeUI(btn, wasPressed); return; }
            setLikeUI(btn, data.data.liked, data.data.count);
        }).catch(function () { setLikeUI(btn, wasPressed); });
    }

    function toggleSave(btn, ep) {
        if (!ep || !ep.id) return;
        var wasPressed = btn.getAttribute('aria-pressed') === 'true';
        setSaveUI(btn, !wasPressed);
        fetch(saveUrlBase + ep.id + '/save', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'csrf_token=' + encodeURIComponent(csrfToken),
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(function (data) {
            if (!data.status) { setSaveUI(btn, wasPressed); return; }
            setSaveUI(btn, data.data.saved, data.data.count);
        }).catch(function () { setSaveUI(btn, wasPressed); });
    }

    function recordShare(ep) {
        if (!ep || !ep.id) return;
        fetch(shareUrlBase + ep.id + '/share', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'csrf_token=' + encodeURIComponent(csrfToken) + '&platform=web',
            credentials: 'same-origin'
        }).catch(function () {});
    }

    // Native OS share-sheet handoff: download the actual video, then hand it
    // to navigator.share() as a file so Instagram/TikTok/Facebook appear as
    // share targets. HLS streams can't be packaged as one file, so those are
    // opened in a new tab instead (mirrors the isHls check in ensureMounted).
    function shareEpisode(btn, ep) {
        if (!ep || !ep.id) return;
        recordShare(ep);
        var countEl = btn.querySelector('.reel-share-count');
        var bumpedCount = formatCount((parseInt((ep.share_count || 0), 10) || 0) + 1);
        ep.share_count = (ep.share_count || 0) + 1;
        if (countEl) countEl.textContent = bumpedCount;

        if (!ep.video_url || /\.m3u8(\?|$)/i.test(ep.video_url)) {
            window.open(ep.video_url, '_blank');
            return;
        }

        btn.disabled = true;
        btn.classList.add('opacity-50');
        fetch(ep.video_url)
            .then(function (r) { return r.blob(); })
            .then(function (blob) {
                var file = new File([blob], 'soloreel-episode-' + ep.id + '.mp4', { type: blob.type || 'video/mp4' });
                if (navigator.canShare && navigator.canShare({ files: [file] })) {
                    return navigator.share({ files: [file], title: ep.title || 'SOLOREEL' }).catch(function () {});
                }
                var url = URL.createObjectURL(blob);
                var a = document.createElement('a');
                a.href = url;
                a.download = 'soloreel-episode-' + ep.id + '.mp4';
                document.body.appendChild(a);
                a.click();
                a.remove();
                setTimeout(function () { URL.revokeObjectURL(url); }, 30000);
            })
            .catch(function () {})
            .then(function () {
                btn.disabled = false;
                btn.classList.remove('opacity-50');
            });
    }

    // Double-tap-to-like: idempotent like-only gesture (Instagram convention
    // — repeated double-taps always show the heart burst, but only actually
    // like once; never toggles a like back off). Keeps the action rail's own
    // like button in sync since both act on the same underlying toggle.
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
        var likeBtn = card.querySelector('.reel-like-btn');
        if (likeBtn && likeBtn.getAttribute('aria-pressed') !== 'true') {
            toggleLike(likeBtn, ep);
        }
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
                    var index = parseInt(card.getAttribute('data-index'), 10);
                    if (/insufficient/i.test(data.error || '')) {
                        openOffersSheet(episodes[index]);
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
                if (index === activeIndex) {
                    playActive();
                    recordProgress(episodes[index]);
                }
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

    // --- Action rail: like / save / share / comment / info, delegated off
    // the feed container (mirrors the unlock-button delegation above). ---
    feed.addEventListener('click', function (e) {
        var likeBtn = e.target.closest && e.target.closest('.reel-like-btn');
        if (likeBtn) { e.stopPropagation(); toggleLike(likeBtn, episodesById[likeBtn.getAttribute('data-episode-id')]); return; }

        var saveBtn = e.target.closest && e.target.closest('.reel-save-btn');
        if (saveBtn) { e.stopPropagation(); toggleSave(saveBtn, episodesById[saveBtn.getAttribute('data-episode-id')]); return; }

        var shareBtn = e.target.closest && e.target.closest('.reel-share-btn');
        if (shareBtn) { e.stopPropagation(); shareEpisode(shareBtn, episodesById[shareBtn.getAttribute('data-episode-id')]); return; }

        var commentBtn = e.target.closest && e.target.closest('.reel-comment-btn');
        if (commentBtn) { e.stopPropagation(); openCommentsSheet(commentBtn.getAttribute('data-episode-id')); return; }

        var infoBtn = e.target.closest && e.target.closest('.reel-info-btn');
        if (infoBtn) { e.stopPropagation(); openInfoSheet(); return; }
    });

    // --- Bottom sheets: series info + comments, shared across cards ---
    var infoSheet = document.getElementById('reel-info-sheet');
    var commentsSheet = document.getElementById('reel-comments-sheet');
    var offersSheet = document.getElementById('reel-offers-sheet');
    var infoBody = infoSheet ? infoSheet.querySelector('.reel-info-body') : null;
    var commentsList = commentsSheet ? commentsSheet.querySelector('.reel-comments-list') : null;
    var commentsTotal = commentsSheet ? commentsSheet.querySelector('.reel-comments-total') : null;
    var commentForm = commentsSheet ? commentsSheet.querySelector('.reel-comment-form') : null;
    var commentInput = commentsSheet ? commentsSheet.querySelector('.reel-comment-input') : null;
    var infoCache = null;
    var activeCommentEpisodeId = null;

    function openSheet(sheet) {
        if (!sheet) return;
        sheet.classList.remove('hidden');
        var panel = sheet.querySelector('.reel-sheet-panel');
        requestAnimationFrame(function () { panel.style.transform = 'translateY(0)'; });
    }

    function closeSheet(sheet) {
        if (!sheet) return;
        var panel = sheet.querySelector('.reel-sheet-panel');
        panel.style.transform = '';
        setTimeout(function () { sheet.classList.add('hidden'); }, 300);
    }

    [infoSheet, commentsSheet, offersSheet].forEach(function (sheet) {
        if (!sheet) return;
        var backdrop = sheet.querySelector('.reel-sheet-backdrop');
        var closeBtn = sheet.querySelector('.reel-sheet-close');
        if (backdrop) backdrop.addEventListener('click', function () { closeSheet(sheet); });
        if (closeBtn) closeBtn.addEventListener('click', function () { closeSheet(sheet); });
    });

    // Unlock-offers sheet (VIP plans + coin top-up): every offer card is its
    // own <form> posting straight to the existing checkout flow, so this just
    // needs to fill in which episode to return to and the price/balance shown
    // in the header before revealing it.
    function openOffersSheet(ep) {
        if (!offersSheet || !ep) return;
        var priceEl = offersSheet.querySelector('.reel-offers-price');
        if (priceEl) priceEl.textContent = formatCount(ep.coin_cost || 0);
        var returnPath = '/episodes/' + ep.slug;
        Array.prototype.forEach.call(offersSheet.querySelectorAll('.reel-offer-return-to'), function (input) {
            input.value = returnPath;
        });
        openSheet(offersSheet);
    }

    var getCoinsLinks = Array.prototype.slice.call(document.querySelectorAll('.reel-get-coins-link'));
    getCoinsLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            var slug = link.getAttribute('data-episode-slug');
            var ep = null;
            for (var i = 0; i < episodes.length; i++) {
                if (episodes[i].slug === slug) { ep = episodes[i]; break; }
            }
            openOffersSheet(ep || episodes[activeIndex]);
        });
    });

    function renderInfo(series) {
        if (!infoBody) return;
        var html = '';
        html += '<h4 class="text-xl font-bold mb-1">' + escapeHtml(series.title) + '</h4>';
        if (series.genre || series.status) {
            html += '<p class="text-xs text-gray-500 mb-3">' + escapeHtml(series.genre || '') + (series.status ? ' · ' + escapeHtml(series.status) : '') + '</p>';
        }
        if (series.synopsis) html += '<p class="text-sm text-gray-300 mb-5 leading-relaxed">' + escapeHtml(series.synopsis) + '</p>';
        var eps = series.episodes || [];
        html += '<h5 class="text-sm font-bold text-gray-400 mb-2">Episodes (' + eps.length + ')</h5>';
        html += '<div class="space-y-2">';
        eps.forEach(function (ep) {
            var locked = !ep.is_unlocked;
            html += '<button type="button" class="reel-info-ep-row w-full flex items-center gap-3 p-2 rounded-lg hover:bg-gray-800/60 text-left" data-slug="' + escapeHtml(ep.slug) + '">' +
                '<img src="' + escapeHtml(ep.thumbnail_url || '/assets/img/default-thumb.jpg') + '" class="w-16 h-10 object-cover rounded flex-shrink-0' + (locked ? ' opacity-50' : '') + '">' +
                '<span class="flex-1 text-sm truncate">EP ' + parseInt(ep.episode_number, 10) + ' · ' + escapeHtml(ep.title) + '</span>' +
                (locked ? '<svg class="w-4 h-4 text-gray-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"></path></svg>' : '') +
                '</button>';
        });
        html += '</div>';
        infoBody.innerHTML = html;
    }

    function openInfoSheet() {
        if (!infoSheet) return;
        openSheet(infoSheet);
        if (infoCache) { renderInfo(infoCache); return; }
        infoBody.innerHTML = '<div class="text-center text-gray-500 py-8">Loading...</div>';
        fetch('/api/v1/series/' + encodeURIComponent(seriesSlug) + '/by-slug')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.status) { infoBody.innerHTML = '<div class="text-center text-gray-500 py-8">Unable to load.</div>'; return; }
                infoCache = data.data;
                renderInfo(infoCache);
            })
            .catch(function () { infoBody.innerHTML = '<div class="text-center text-gray-500 py-8">Network error.</div>'; });
    }

    if (infoBody) {
        infoBody.addEventListener('click', function (e) {
            var row = e.target.closest && e.target.closest('.reel-info-ep-row');
            if (!row) return;
            var slug = row.getAttribute('data-slug');
            var targetIndex = -1;
            for (var i = 0; i < episodes.length; i++) {
                if (episodes[i].slug === slug) { targetIndex = i; break; }
            }
            closeSheet(infoSheet);
            if (targetIndex >= 0) {
                scrollToIndex(targetIndex);
            } else {
                window.location.href = '/episodes/' + slug;
            }
        });
    }

    function renderComments(items) {
        if (!commentsList) return;
        if (!items.length) {
            commentsList.innerHTML = '<div class="text-center text-gray-500 py-8">No comments yet. Be the first!</div>';
            return;
        }
        commentsList.innerHTML = items.map(function (c) {
            var initial = escapeHtml(((c.author || 'G').trim()[0] || 'G').toUpperCase());
            return '<div class="flex gap-3">' +
                '<div class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center text-xs font-bold flex-shrink-0">' + initial + '</div>' +
                '<div class="flex-1 min-w-0">' +
                '<p class="text-sm"><span class="font-semibold">' + escapeHtml(c.author || 'Guest') + '</span> <span class="text-gray-200">' + escapeHtml(c.body) + '</span></p>' +
                '<p class="text-xs text-gray-500 mt-0.5">' + timeAgo(c.created_at) + '</p>' +
                '</div>' +
                '</div>';
        }).join('');
    }

    function openCommentsSheet(episodeId) {
        if (!commentsSheet) return;
        activeCommentEpisodeId = episodeId;
        openSheet(commentsSheet);
        commentsList.innerHTML = '<div class="text-center text-gray-500 py-8">Loading...</div>';
        fetch(commentsUrlBase + episodeId + '/comments?limit=50')
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.status) { commentsList.innerHTML = '<div class="text-center text-gray-500 py-8">Unable to load comments.</div>'; return; }
                if (commentsTotal) commentsTotal.textContent = data.data.total;
                renderComments(data.data.items);
            })
            .catch(function () { commentsList.innerHTML = '<div class="text-center text-gray-500 py-8">Network error.</div>'; });
    }

    if (commentForm) {
        commentForm.addEventListener('submit', function (e) {
            e.preventDefault();
            var body = commentInput.value.trim();
            if (!body || !activeCommentEpisodeId) return;
            var submitBtn = commentForm.querySelector('.reel-comment-submit');
            submitBtn.disabled = true;
            fetch(commentsUrlBase + activeCommentEpisodeId + '/comments', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'csrf_token=' + encodeURIComponent(csrfToken) + '&body=' + encodeURIComponent(body),
                credentials: 'same-origin'
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    submitBtn.disabled = false;
                    if (!data.status) return;
                    commentInput.value = '';
                    var epObj = episodesById[activeCommentEpisodeId];
                    if (epObj) {
                        epObj.comment_count = (epObj.comment_count || 0) + 1;
                        var railBtn = feed.querySelector('.reel-comment-btn[data-episode-id="' + activeCommentEpisodeId + '"]');
                        if (railBtn) railBtn.querySelector('.reel-comment-count').textContent = formatCount(epObj.comment_count);
                    }
                    openCommentsSheet(activeCommentEpisodeId);
                })
                .catch(function () { submitBtn.disabled = false; });
        });
    }
})();
