/**
 * Public-facing JavaScript for WP Movie Collector
 */
(function($) {
    'use strict';

    /**
     * Initialize filters and search functionality
     */
    function initFilters() {
        // Filters are applied via the visible submit button so keyboard and
        // screen-reader users get a predictable, explicit action rather than a
        // surprise navigation on every select change.

        // Clear filters button
        $('.wp-movie-collector-clear-filters').on('click', function(e) {
            e.preventDefault();
            window.location.href = $(this).data('base-url');
        });
    }


    /**
     * Document ready handler
     */
    $(function() {
        initFilters();
    });

})(jQuery);
