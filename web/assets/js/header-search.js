// Header live-search typeahead — reuses the existing /api/v1/search endpoint
// (same one the /search page's Alpine component calls), just with a small
// result size and a dropdown instead of a full results grid.
function headerSearch() {
    return {
        query: '',
        results: [],
        isLoading: false,
        showResults: false,
        debounceTimer: null,

        onInput() {
            clearTimeout(this.debounceTimer);
            if (this.query.trim() === '') {
                this.results = [];
                this.showResults = false;
                return;
            }
            this.debounceTimer = setTimeout(() => this.fetchResults(), 400);
        },

        fetchResults() {
            this.isLoading = true;
            fetch('/api/v1/search?q=' + encodeURIComponent(this.query) + '&size=8')
                .then(res => res.json())
                .then(data => {
                    this.results = data.data || [];
                    this.showResults = true;
                })
                .catch(() => {})
                .finally(() => { this.isLoading = false; });
        }
    };
}
