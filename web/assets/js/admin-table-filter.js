/**
 * Generic instant client-side filter for admin list tables. Rows carry a
 * lowercased data-search attribute (whatever fields matter for that table);
 * matching is a plain substring test against the current input value, so the
 * same script works unmodified across every admin list page regardless of
 * column layout.
 */
(function () {
    'use strict';

    window.initAdminTableFilter = function (inputId, rowSelector, options) {
        var input = document.getElementById(inputId);
        if (!input) return;

        var opts = options || {};
        var rows = Array.prototype.slice.call(document.querySelectorAll(rowSelector));
        var emptyRow = opts.emptyMessageId ? document.getElementById(opts.emptyMessageId) : null;

        function applyFilter() {
            var query = input.value.trim().toLowerCase();
            var visibleCount = 0;

            rows.forEach(function (row) {
                var haystack = row.getAttribute('data-search') || '';
                var matches = query === '' || haystack.indexOf(query) !== -1;
                row.style.display = matches ? '' : 'none';
                if (matches) visibleCount++;
            });

            if (emptyRow) {
                emptyRow.style.display = visibleCount === 0 ? '' : 'none';
            }
        }

        input.addEventListener('input', applyFilter);
        applyFilter();
    };
})();
