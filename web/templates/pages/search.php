<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search - SOLOREEL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <style>
        body { background-color: #000; color: #fff; }
        .movie-card:hover { transform: scale(1.05); transition: 0.3s; }
    </style>
    <?php $adsenseId = \App\Helpers\Site::getConfig('google_adsense_client_id'); if (!empty($adsenseId)): ?>
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=<?= htmlspecialchars($adsenseId) ?>" crossorigin="anonymous"></script>
    <?php endif; ?>
</head>
<body class="antialiased font-sans">

    <?php require __DIR__ . '/../partials/header.php'; ?>

    <main class="pt-24 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 min-h-screen"
          x-data="searchComponent()">

        <!-- Search & Filter Controls -->
        <div class="mb-12 bg-gray-900 border border-gray-800 p-6 rounded-xl shadow-xl">
            <div class="flex flex-col md:flex-row gap-4 relative max-w-4xl mx-auto">
                <div class="flex-grow relative">
                    <input type="text" x-model.debounce.500ms="query" placeholder="Type to search series..." class="w-full bg-black border border-gray-700 rounded-lg px-6 py-4 text-white focus:outline-none focus:border-red-500 text-lg">
                    <div class="absolute right-4 top-4 text-gray-500">
                        <svg x-show="!isLoading" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <svg x-show="isLoading" class="w-6 h-6 animate-spin text-red-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    </div>
                </div>

                <select x-model="selectedGenre" class="bg-black border border-gray-700 rounded-lg px-4 py-4 text-white focus:outline-none focus:border-red-500 md:w-48">
                    <option value="">All Genres</option>
                    <?php foreach($genres as $g): ?>
                        <option value="<?= htmlspecialchars($g['name']) ?>"><?= htmlspecialchars($g['name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <select x-model="selectedStatus" class="bg-black border border-gray-700 rounded-lg px-4 py-4 text-white focus:outline-none focus:border-red-500 md:w-48">
                    <option value="">Any Status</option>
                    <option value="ongoing">Ongoing</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
        </div>

        <!-- Results -->
        <div>
            <template x-if="results.length === 0 && !isLoading && hasSearched">
                <p class="text-gray-500 text-center mt-12 text-lg">No results found matching your criteria.</p>
            </template>

            <template x-if="!hasSearched">
                <div class="text-center mt-20 text-gray-500">
                    <svg class="w-20 h-20 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <p class="text-xl">Discover your next favorite series.</p>
                </div>
            </template>

            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-6">
                <template x-for="item in results" :key="item.id">
                    <a :href="'/movie/' + item.slug" class="movie-card group cursor-pointer block relative">
                        <div class="relative aspect-[2/3] overflow-hidden rounded-lg bg-gray-900 border border-gray-800">
                            <img :src="item.cover_image ? item.cover_image : '/assets/img/default-cover.jpg'" class="w-full h-full object-cover">
                            <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center backdrop-blur-sm">
                                <div class="bg-red-600 rounded-full p-3 shadow-lg transform scale-90 group-hover:scale-100 transition">
                                    <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4l12 6-12 6z"></path></svg>
                                </div>
                            </div>
                            <div class="absolute bottom-2 left-2 right-2 flex flex-col items-start gap-1">
                                <span class="bg-black/70 text-[10px] text-gray-300 px-2 py-0.5 rounded shadow backdrop-blur" x-text="item.genre || 'Drama'"></span>
                                <span class="bg-red-600/90 text-xs font-bold px-2 py-1 rounded text-white shadow backdrop-blur" x-text="'EP.1 / EP.' + item.episode_count"></span>
                            </div>
                        </div>
                        <h4 class="mt-3 text-sm font-medium text-gray-300 group-hover:text-white line-clamp-2" x-text="item.title"></h4>
                    </a>
                </template>
            </div>
        </div>
    </main>

    <script>
        function searchComponent() {
            return {
                query: '',
                selectedGenre: '',
                selectedStatus: '',
                results: [],
                isLoading: false,
                hasSearched: false,

                init() {
                    this.$watch('query', () => this.fetchResults());
                    this.$watch('selectedGenre', () => this.fetchResults());
                    this.$watch('selectedStatus', () => this.fetchResults());
                    // Initial fetch so something shows if desired, or wait for input.
                    this.fetchResults();
                },

                fetchResults() {
                    this.isLoading = true;
                    this.hasSearched = true;

                    const params = new URLSearchParams();
                    if(this.query) params.append('q', this.query);
                    if(this.selectedGenre) params.append('genre', this.selectedGenre);
                    if(this.selectedStatus) params.append('status', this.selectedStatus);

                    fetch('/search?' + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        this.results = data.data || [];
                    })
                    .catch(err => console.error(err))
                    .finally(() => {
                        this.isLoading = false;
                    });
                }
            }
        }
    </script>
    <script src="/assets/js/protection.js"></script>
</body>
</html>
