/**
 * Public-facing JavaScript for WP Movie Collector
 */
(function($) {
    'use strict';

    /**
     * Initialize filters and search functionality
     */
    function initFilters() {
        // Auto-submit filters when changed
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
     * Initialize the movie grid layout
     */
    function initGrid() {
        // If using a masonry layout or similar, initialize it here
    }

    /**
     * Setup AJAX for loading more movies
     */
    function initLoadMore() {
        $('.wp-movie-collector-load-more').on('click', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const page = $button.data('page');
            const args = $button.data('args');
            
            $button.text(wp_movie_collector_public.loading_text).prop('disabled', true);
            
            $.ajax({
                url: wp_movie_collector_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'wp_movie_collector_load_more',
                    page: page,
                    args: args,
                    nonce: wp_movie_collector_public.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Append new items to the grid
                        $('.wp-movie-collector-grid').append(response.data.html);
                        
                        // Update button or hide if no more pages
                        if (response.data.has_more) {
                            $button.data('page', page + 1).text(wp_movie_collector_public.load_more_text).prop('disabled', false);
                        } else {
                            $button.remove();
                        }
                    } else {
                        $button.text(wp_movie_collector_public.error_text).prop('disabled', false);
                    }
                },
                error: function() {
                    $button.text(wp_movie_collector_public.error_text).prop('disabled', false);
                }
            });
        });
    }

    /**
     * Document ready handler
     */
    $(function() {
        initFilters();
        initGrid();
        initLoadMore();
    });

})(jQuery);
