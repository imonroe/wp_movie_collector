/**
 * Admin JavaScript for WP Movie Collector
 */
(function($) {
    'use strict';

    /**
     * Handle barcode input events
     */
    function setupBarcodeScanner() {
        // Focus the barcode input field when the page loads
        $('#wp-movie-collector-barcode').focus();

        // Handle barcode scanner input - scanners typically append Enter key
        $('#wp-movie-collector-barcode').on('keypress', function(e) {
            if (e.which === 13 && $(this).val().length > 0) {
                e.preventDefault();
                $('#wp-movie-collector-lookup-barcode').trigger('click');
            }
        });
    }

    /**
     * Initialize the add movie form
     */
    function initAddMovieForm() {
        setupBarcodeScanner();

        // Movie title search auto-complete
        $('#wp-movie-collector-movie-search').on('keypress', function(e) {
            if (e.which === 13) {
                e.preventDefault();
                $('#wp-movie-collector-search-movie').trigger('click');
            }
        });

        // Form submission handler
        $('#wp-movie-collector-add-movie-form').on('submit', function(e) {
            // Basic client-side validation
            const requiredFields = ['movie-title', 'movie-release-year', 'movie-format', 'movie-region-code'];
            let isValid = true;

            requiredFields.forEach(function(field) {
                if (!$('#' + field).val()) {
                    isValid = false;
                    $('#' + field).addClass('error');
                } else {
                    $('#' + field).removeClass('error');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert(wp_movie_collector_admin.messages.required_fields);
                return false;
            }

            // Could add more validation here
        });
    }

    /**
     * Initialize the add box set form
     */
    function initAddBoxSetForm() {
        setupBarcodeScanner();

        // Form submission handler
        $('#wp-movie-collector-add-box-set-form').on('submit', function(e) {
            // Basic client-side validation
            const requiredFields = ['box-set-title', 'box-set-release-year', 'box-set-format', 'box-set-region-code'];
            let isValid = true;

            requiredFields.forEach(function(field) {
                if (!$('#' + field).val()) {
                    isValid = false;
                    $('#' + field).addClass('error');
                } else {
                    $('#' + field).removeClass('error');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert(wp_movie_collector_admin.messages.required_fields);
                return false;
            }
        });
    }

    /**
     * Add event listeners for movie management
     */
    function setupMovieManagement() {
        // Delete movie confirmation
        $('.wp-movie-collector-delete-movie').on('click', function(e) {
            if (!confirm(wp_movie_collector_admin.messages.confirm_delete_movie)) {
                e.preventDefault();
                return false;
            }
        });

        // Delete box set confirmation
        $('.wp-movie-collector-delete-box-set').on('click', function(e) {
            if (!confirm(wp_movie_collector_admin.messages.confirm_delete_box_set)) {
                e.preventDefault();
                return false;
            }
        });

        // Movie filter changes
        $('#wp-movie-collector-filter-format, #wp-movie-collector-filter-genre, #wp-movie-collector-filter-year').on('change', function() {
            $('#wp-movie-collector-filter-form').submit();
        });
    }

    /**
     * Initialize the import/export page
     */
    function initImportExport() {
        $('#wp-movie-collector-import-form').on('submit', function(e) {
            const fileInput = $('#wp-movie-collector-import-file');
            if (fileInput.val() === '') {
                e.preventDefault();
                alert(wp_movie_collector_admin.messages.no_file_selected);
                return false;
            }

            const fileExt = fileInput.val().split('.').pop().toLowerCase();
            if (fileExt !== 'csv') {
                e.preventDefault();
                alert(wp_movie_collector_admin.messages.invalid_file_type);
                return false;
            }
        });
    }

    /**
     * Document ready handler
     */
    $(function() {
        // Initialize the appropriate functionality based on the current page
        const currentPage = $('#wp-movie-collector-current-page').val();

        // Common functionality
        setupMovieManagement();

        // Page-specific functionality
        if (currentPage === 'add-movie') {
            initAddMovieForm();
        } else if (currentPage === 'add-box-set') {
            initAddBoxSetForm();
        } else if (currentPage === 'import-export') {
            initImportExport();
        }

        // Handle any AJAX errors
        $(document).ajaxError(function(event, jqxhr, settings, thrownError) {
            console.error('AJAX Error:', thrownError);
        });
    });

})(jQuery);