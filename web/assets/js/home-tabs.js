// Home page content-hub tabs: HOT / NEW / RANKING / CATEGORIES / TV SERIES / MOVIES.
// Each tab fetches from the read-only /api/v1 endpoints (already public, no
// JWT required — see Api\SeriesController::hot/newReleases/categories and
// Api\ListController::ranking) and renders its own distinct layout.
// Card links resolve resume_slug client-side via resume-batch, matching the
// server-rendered shelves elsewhere on this page.
(function () {
    function getGuestId() {
        const match = document.cookie.match(/(?:^|; )guest_id=([^;]+)/);
        return match ? decodeURIComponent(match[1]) : '';
    }

    async function resolveLinks(seriesList) {
        const ids = [...new Set(seriesList.map(s => s.id))];
        if (!ids.length) return {};
        try {
            const res = await fetch('/api/v1/series/resume-batch?ids=' + ids.join(',') + '&guest_id=' + encodeURIComponent(getGuestId()));
            const data = await res.json();
            return data.data || {};
        } catch (e) {
            return {};
        }
    }

    function linkFor(series, resumeSlugs) {
        const slug = resumeSlugs[series.id];
        return slug ? '/episodes/' + slug : '/movie/' + series.slug;
    }

    function badgesHtml(series) {
        let html = '';
        if (series.is_hot) html += '<span class="bg-red-600 text-[10px] font-bold px-2 py-0.5 rounded text-white shadow">🔥 HOT</span>';
        if (series.is_new) html += '<span class="bg-emerald-600 text-[10px] font-bold px-2 py-0.5 rounded text-white shadow">NEW</span>';
        return html;
    }

    function cardHtml(series, resumeSlugs) {
        const cover = series.cover_image_url || series.cover_image || '/assets/img/default-cover.jpg';
        return `
        <a href="${linkFor(series, resumeSlugs)}" class="movie-card flex-none w-[140px] sm:w-[180px] md:w-[200px] snap-start relative group rounded-xl bg-[#1a1a1f] shadow-2xl border border-gray-800/50 block">
            <div class="relative aspect-[2/3] overflow-hidden rounded-t-xl">
                <img src="${cover}" class="w-full h-full object-cover" loading="lazy">
                <div class="play-overlay absolute inset-0 bg-black/50 opacity-0 transform scale-90 transition-all flex items-center justify-center backdrop-blur-sm group-hover:opacity-100 group-hover:scale-100">
                    <div class="bg-red-600/90 rounded-full p-4 shadow-[0_0_20px_rgba(220,38,38,0.6)]">
                        <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4l12 6-12 6z"></path></svg>
                    </div>
                </div>
                <div class="absolute top-2 left-2 flex flex-col gap-1 items-start">${badgesHtml(series)}</div>
                <div class="absolute bottom-2 left-2 right-2 bg-black/80 backdrop-blur px-2 py-1 rounded text-[10px] font-bold text-white border border-gray-700 w-fit">
                    EP.1 / EP.${series.episode_count}
                </div>
            </div>
            <div class="p-3">
                <h4 class="text-sm font-semibold text-gray-200 group-hover:text-white truncate">${escapeHtml(series.title)}</h4>
            </div>
        </a>`;
    }

    function rankingRowHtml(series, rank, resumeSlugs) {
        const cover = series.cover_image_url || series.cover_image || '/assets/img/default-cover.jpg';
        const rankColor = rank <= 3 ? 'text-yellow-400' : 'text-gray-500';
        return `
        <a href="${linkFor(series, resumeSlugs)}" class="flex items-center gap-4 bg-[#1a1a1f] border border-gray-800/50 rounded-xl p-3 hover:bg-white/5 transition">
            <span class="text-2xl sm:text-3xl font-extrabold w-8 sm:w-10 text-center flex-shrink-0 ${rankColor}">${rank}</span>
            <img src="${cover}" class="w-14 h-20 object-cover rounded-lg flex-shrink-0 bg-gray-800" loading="lazy">
            <div class="flex-1 min-w-0">
                <h4 class="font-semibold text-gray-100 truncate">${escapeHtml(series.title)}</h4>
                <p class="text-xs text-gray-500">EP.${series.episode_count}</p>
            </div>
            <div class="flex items-center gap-1 text-red-500 font-bold flex-shrink-0">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.653 16.915l-.005-.003-.019-.01a20.759 20.759 0 01-1.162-.682 22.045 22.045 0 01-2.582-1.9C4.045 12.733 2 10.352 2 7.5 2 5.015 4.015 3 6.5 3c1.298 0 2.417.523 3.5 1.653C11.083 3.523 12.202 3 13.5 3 15.985 3 18 5.015 18 7.5c0 2.852-2.045 5.233-3.885 6.82a22.045 22.045 0 01-2.582 1.9 20.759 20.759 0 01-1.161.682l-.019.01-.005.003-.002.001a.75.75 0 01-.69 0l-.002-.001z"></path></svg>
                ${series.like_count}
            </div>
        </a>`;
    }

    function rowSection(title, cardsHtml) {
        if (!cardsHtml) return '';
        return `<div class="mb-10">
            <h3 class="text-xl font-bold mb-4">${title}</h3>
            <div class="flex overflow-x-auto gap-4 pb-6 pt-2 hide-scrollbar snap-x">${cardsHtml}</div>
        </div>`;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str || '';
        return div.innerHTML;
    }

    const tabs = {
        async hot(container) {
            const res = await fetch('/api/v1/series/hot?size=24');
            const data = await res.json();
            const series = data.data || [];
            const resumeSlugs = await resolveLinks(series);
            container.innerHTML = `<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 sm:gap-5">
                ${series.map(s => cardHtml(s, resumeSlugs)).join('')}
            </div>` || '<p class="text-gray-500 text-center py-12">Nothing trending yet.</p>';
        },

        async new(container) {
            const res = await fetch('/api/v1/series/new');
            const data = await res.json();
            const comingSoon = data.data?.coming_soon || [];
            const allNew = data.data?.all_new || [];
            const resumeSlugs = await resolveLinks([...comingSoon, ...allNew]);
            const comingHtml = comingSoon.map(s => cardHtml(s, resumeSlugs)).join('');
            const newHtml = allNew.map(s => cardHtml(s, resumeSlugs)).join('');
            container.innerHTML =
                rowSection('🔜 Coming Soon', comingHtml) +
                rowSection('✨ New Releases', newHtml) +
                (!comingHtml && !newHtml ? '<p class="text-gray-500 text-center py-12">No new titles yet.</p>' : '');
        },

        async ranking(container) {
            const res = await fetch('/api/v1/ranking?limit=30');
            const data = await res.json();
            const series = data.data || [];
            const resumeSlugs = await resolveLinks(series);
            container.innerHTML = series.length
                ? `<div class="max-w-2xl mx-auto space-y-3">${series.map((s, i) => rankingRowHtml(s, i + 1, resumeSlugs)).join('')}</div>`
                : '<p class="text-gray-500 text-center py-12">No rankings yet — be the first to like a series!</p>';
        },

        async categories(container) {
            const res = await fetch('/api/v1/series/categories');
            const data = await res.json();
            const groups = data.data || [];
            const allSeries = groups.flatMap(g => g.series);
            const resumeSlugs = await resolveLinks(allSeries);
            container.innerHTML = groups.length
                ? groups.map(g => rowSection(g.genre, g.series.map(s => cardHtml(s, resumeSlugs)).join(''))).join('')
                : '<p class="text-gray-500 text-center py-12">No categories yet.</p>';
        },

        async tv_series(container) {
            const res = await fetch('/api/v1/search?category=tv_series&size=60');
            const data = await res.json();
            const series = data.data || [];
            const resumeSlugs = await resolveLinks(series);
            container.innerHTML = series.length
                ? `<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 sm:gap-5">${series.map(s => cardHtml(s, resumeSlugs)).join('')}</div>`
                : '<p class="text-gray-500 text-center py-12">No TV Series yet.</p>';
        },

        async movies(container) {
            const res = await fetch('/api/v1/search?category=movies&size=60');
            const data = await res.json();
            const series = data.data || [];
            const resumeSlugs = await resolveLinks(series);
            container.innerHTML = series.length
                ? `<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 sm:gap-5">${series.map(s => cardHtml(s, resumeSlugs)).join('')}</div>`
                : '<p class="text-gray-500 text-center py-12">No movies yet.</p>';
        }
    };

    function initHomeTabs() {
        const nav = document.getElementById('home-tabs');
        const container = document.getElementById('home-tab-content');
        if (!nav || !container) return;

        const buttons = nav.querySelectorAll('.home-tab-btn');
        const cache = {};

        async function activate(tabKey) {
            buttons.forEach(b => {
                const active = b.dataset.tab === tabKey;
                b.classList.toggle('bg-red-600', active);
                b.classList.toggle('text-white', active);
                b.classList.toggle('bg-gray-900/80', !active);
                b.classList.toggle('text-gray-300', !active);
            });

            if (cache[tabKey]) {
                container.innerHTML = cache[tabKey];
                return;
            }

            container.innerHTML = '<div class="flex justify-center py-16"><svg class="w-8 h-8 text-red-500 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg></div>';
            await tabs[tabKey](container);
            cache[tabKey] = container.innerHTML;
        }

        buttons.forEach(btn => {
            btn.addEventListener('click', () => activate(btn.dataset.tab));
        });

        activate(buttons[0]?.dataset.tab || 'hot');
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initHomeTabs);
    } else {
        initHomeTabs();
    }
})();
