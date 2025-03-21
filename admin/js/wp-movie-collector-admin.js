/**
 * Admin JavaScript for WP Movie Collector
 */
(function($) {
    'use strict';
    
    /**
     * Initialize media uploader
     */
    function setupMediaUploader() {
        // Movie cover image upload
        let movieImageFrame;
        $('.wp-movie-collector-upload-image-button').on('click', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const imageIdField = button.siblings('.image-id-field');
            const imageUrlField = button.siblings('.image-url-field');
            const imagePreview = button.siblings('.image-preview');
            
            // If the frame already exists, reopen it
            if (movieImageFrame) {
                movieImageFrame.open();
                return;
            }
            
            // Create the media frame
            movieImageFrame = wp.media({
                title: wp_movie_collector_admin.messages.select_image || 'Select or Upload Image',
                button: {
                    text: wp_movie_collector_admin.messages.use_image || 'Use this image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            // When an image is selected, run a callback
            movieImageFrame.on('select', function() {
                const attachment = movieImageFrame.state().get('selection').first().toJSON();
                
                // Set the field values
                imageIdField.val(attachment.id);
                imageUrlField.val(attachment.url);
                
                // Update preview
                imagePreview.html('<img src="' + attachment.url + '" alt="" style="max-width:150px;max-height:150px;" />');
                
                // Show the remove button
                button.siblings('.wp-movie-collector-remove-image-button').show();
            });
            
            // Finally, open the modal
            movieImageFrame.open();
        });
        
        // Remove image button
        $('.wp-movie-collector-remove-image-button').on('click', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const imageIdField = button.siblings('.image-id-field');
            const imageUrlField = button.siblings('.image-url-field');
            const imagePreview = button.siblings('.image-preview');
            
            // Clear the fields
            imageIdField.val('');
            imageUrlField.val('');
            
            // Clear preview
            imagePreview.html('');
            
            // Hide remove button
            button.hide();
        });
    }

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
     * Fill movie form with data
     */
    function fillMovieForm(movie) {
        $('#movie-title').val(movie.title || '');
        $('#movie-release-year').val(movie.release_year || '');
        $('#movie-barcode').val(movie.barcode || '');
        $('#movie-director').val(movie.director || '');
        $('#movie-studio').val(movie.studio || '');
        $('#movie-actors').val(movie.actors || '');
        $('#movie-genre').val(movie.genre || '');
        $('#movie-description').val(movie.description || '');
        
        // Handle cover image
        $('#movie-cover-image-url').val(movie.cover_image_url || '');
        if (movie.cover_image_id) {
            $('#movie-cover-image-id').val(movie.cover_image_id);
        }
        
        // Display cover image preview if URL exists
        if (movie.cover_image_url) {
            $('#movie-cover-image-url').siblings('.image-preview').html('<img src="' + movie.cover_image_url + '" alt="" style="max-width:150px;max-height:150px;" />');
            $('#movie-cover-image-url').siblings('.wp-movie-collector-remove-image-button').show();
        }
        
        $('#movie-api-source').val(movie.api_source || '');
        
        // If we got a title from the barcode lookup but have limited metadata, 
        // automatically search for more details from TMDB
        if (movie.api_source === 'BarcodeLookup' && movie.title && 
            (!movie.director || !movie.actors || !movie.description || movie.description.length < 50)) {
            searchTMDBForMoreDetails(movie.title, movie.release_year);
        }
    }
    
    /**
     * Search TMDB for more details about the movie
     */
    function searchTMDBForMoreDetails(title, year) {
        $('#wp-movie-collector-search-results').html('<p>Searching for additional movie details...</p>');
        
        $.ajax({
            url: wp_movie_collector_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'wp_movie_collector_movie_search',
                title: title,
                year: year,
                nonce: wp_movie_collector_admin.nonce
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    // Get details for the first match
                    var movieId = response.data[0].id;
                    
                    $('#wp-movie-collector-search-results').html('<p>Found match, retrieving full details...</p>');
                    
                    $.ajax({
                        url: wp_movie_collector_admin.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'wp_movie_collector_get_movie_details',
                            movie_id: movieId,
                            nonce: wp_movie_collector_admin.nonce
                        },
                        success: function(detailsResponse) {
                            if (detailsResponse.success) {
                                var tmdbMovie = detailsResponse.data;
                                
                                // Preserve the barcode from the original data
                                var barcode = $('#movie-barcode').val();
                                
                                // Fill form with detailed movie info from TMDB
                                fillMovieFormWithTMDBData(tmdbMovie, barcode);
                                
                                $('#wp-movie-collector-search-results').html(
                                    '<p class="success">Successfully retrieved additional movie details from TMDB!</p>'
                                );
                            } else {
                                $('#wp-movie-collector-search-results').html(
                                    '<p class="error">Error retrieving full movie details.</p>'
                                );
                            }
                        },
                        error: function() {
                            $('#wp-movie-collector-search-results').html(
                                '<p class="error">Error connecting to server.</p>'
                            );
                        }
                    });
                } else {
                    $('#wp-movie-collector-search-results').html(
                        '<p class="error">No additional movie details found.</p>'
                    );
                }
            },
            error: function() {
                $('#wp-movie-collector-search-results').html(
                    '<p class="error">Error searching for additional movie details.</p>'
                );
            }
        });
    }
    
    /**
     * Fill the form with TMDB data while preserving some original values
     */
    function fillMovieFormWithTMDBData(tmdbMovie, barcode) {
        // Don't override title if we already have one
        if (!$('#movie-title').val() && tmdbMovie.title) {
            $('#movie-title').val(tmdbMovie.title);
        }
        
        // Don't override release year if we already have one
        if (!$('#movie-release-year').val() && tmdbMovie.release_year) {
            $('#movie-release-year').val(tmdbMovie.release_year);
        }
        
        // Always preserve the barcode
        $('#movie-barcode').val(barcode);
        
        // Fill in the remaining data, favoring TMDB's more detailed information
        if (tmdbMovie.director) {
            $('#movie-director').val(tmdbMovie.director);
        }
        
        if (tmdbMovie.studio) {
            $('#movie-studio').val(tmdbMovie.studio);
        }
        
        if (tmdbMovie.actors) {
            $('#movie-actors').val(tmdbMovie.actors);
        }
        
        if (tmdbMovie.genre) {
            $('#movie-genre').val(tmdbMovie.genre);
        }
        
        if (tmdbMovie.description) {
            $('#movie-description').val(tmdbMovie.description);
        }
        
        // Handle cover image - prefer TMDB's higher quality images
        if (tmdbMovie.cover_image_url) {
            $('#movie-cover-image-url').val(tmdbMovie.cover_image_url);
            $('#movie-cover-image-url').siblings('.image-preview')
                .html('<img src="' + tmdbMovie.cover_image_url + '" alt="" style="max-width:150px;max-height:150px;" />');
            $('#movie-cover-image-url').siblings('.wp-movie-collector-remove-image-button').show();
        }
        
        // Update API source to indicate we now have TMDB data
        $('#movie-api-source').val('TMDb (auto-enhanced)');
    }

    /**
     * Document ready handler
     */
    $(function() {
        // Initialize the appropriate functionality based on the current page
        const currentPage = $('#wp-movie-collector-current-page').val();

        // Common functionality
        setupMovieManagement();
        setupMediaUploader(); // Always set up the media uploader

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