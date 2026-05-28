/**
 * Public-facing JavaScript for WP Movie Collector
 */
(function($) {
    'use strict';

    /**
     * Initialize filters and search functionality
     */
    function initFilters() {
        // Auto-submit filters when changed (including sort dropdown)
        $('.wp-movie-collector-filters select').on('change', function() {
            $(this).closest('form').submit();
        });

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
