<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'wp-movie-collector' ), '', array( 'response' => 403 ) );
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php
    // Show error message if there is one
    if (isset($_GET['error'])) {
        $error_type = sanitize_text_field(wp_unslash($_GET['error']));
        
        if ($error_type === 'validation') {
            // Get validation errors from transient
            $errors = get_transient('wp_movie_collector_form_errors_' . get_current_user_id());
            if ($errors && is_array($errors)) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>' . esc_html__('Please fix the following errors:', 'wp-movie-collector') . '</strong></p>';
                echo '<ul>';
                foreach ($errors as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
                
                // Delete the transient
                delete_transient('wp_movie_collector_form_errors_' . get_current_user_id());
            }
        } else {
            $error_message = '';
            
            switch ($error_type) {
                case 'db_error':
                    $error_message = __('There was an error saving the movie to the database. Please try again.', 'wp-movie-collector');
                    break;
                default:
                    $error_message = __('An unknown error occurred. Please try again.', 'wp-movie-collector');
                    break;
            }
            
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
        }
    }
    ?>
    
    <div class="wp-movie-collector-form">
        <div class="wp-movie-collector-barcode-scanner">
            <h3><?php esc_html_e('Scan Barcode', 'wp-movie-collector'); ?></h3>
            <p><?php esc_html_e('Use a barcode scanner or enter a barcode manually to quickly add a movie.', 'wp-movie-collector'); ?></p>
            <div class="wp-movie-collector-barcode-input">
                <input type="text" id="wp-movie-collector-barcode" class="regular-text" placeholder="<?php esc_attr_e('Scan or enter barcode...', 'wp-movie-collector'); ?>">
                <button type="button" id="wp-movie-collector-lookup-barcode" class="button"><?php esc_html_e('Lookup', 'wp-movie-collector'); ?></button>
            </div>
            <div id="wp-movie-collector-barcode-result"></div>
        </div>
        
        <div class="wp-movie-collector-api-search">
            <h3><?php esc_html_e('Search Movie Database', 'wp-movie-collector'); ?></h3>
            <p><?php esc_html_e('Search for a movie title to retrieve metadata.', 'wp-movie-collector'); ?></p>
            <div class="wp-movie-collector-search-input">
                <input type="text" id="wp-movie-collector-movie-search" class="regular-text" placeholder="<?php esc_attr_e('Search movie title...', 'wp-movie-collector'); ?>">
                <button type="button" id="wp-movie-collector-search-movie" class="button"><?php esc_html_e('Search', 'wp-movie-collector'); ?></button>
            </div>
            <div id="wp-movie-collector-search-results"></div>
        </div>
        
        <h3><?php esc_html_e('Movie Details', 'wp-movie-collector'); ?></h3>
        <form method="post" id="wp-movie-collector-add-movie-form">
            <?php wp_nonce_field('wp_movie_collector_add_movie', 'wp_movie_collector_nonce'); ?>

            <?php
            // Movie detail fields, shared with the edit-movie screen. No $movie
            // in scope here, so every field renders empty for a new movie.
            include WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'admin/partials/movie-fields.php';
            ?>

            <div id="wp-movie-collector-duplicate-warning" class="notice notice-warning inline" style="display:none;">
                <p id="wp-movie-collector-duplicate-message"></p>
                <p><a id="wp-movie-collector-duplicate-edit-link" href="#" class="button button-small"><?php esc_html_e( 'Edit Existing Movie', 'wp-movie-collector' ); ?></a></p>
            </div>

            <div class="form-group">
                <label for="movie-allow-duplicate">
                    <input type="checkbox" id="movie-allow-duplicate" name="movie[allow_duplicate]" value="1">
                    <?php esc_html_e( 'Allow duplicate (I own multiple copies)', 'wp-movie-collector' ); ?>
                </label>
            </div>

            <p class="submit">
                <button type="submit" class="button button-primary" name="wp_movie_collector_add_movie_submit">
                    <?php esc_html_e('Add Movie', 'wp-movie-collector'); ?>
                </button>
            </p>
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Barcode lookup
    $('#wp-movie-collector-lookup-barcode').on('click', function() {
        var barcode = $('#wp-movie-collector-barcode').val();
        if (!barcode) {
            return;
        }
        
        $('#wp-movie-collector-barcode-result').html(<?php echo wp_json_encode('<p>' . esc_html__('Looking up barcode...', 'wp-movie-collector') . '</p>'); ?>);
        
        $.ajax({
            url: wp_movie_collector_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'wp_movie_collector_barcode_lookup',
                barcode: barcode,
                context: 'movie',
                nonce: wp_movie_collector_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.existing_in_db) {
                        var existingType = response.data.existing_type || 'movie';
                        var editLabel = existingType === 'box_set'
                            ? <?php echo wp_json_encode( esc_html__( 'Edit Existing Box Set', 'wp-movie-collector' ) ); ?>
                            : <?php echo wp_json_encode( esc_html__( 'Edit Existing Movie', 'wp-movie-collector' ) ); ?>;
                        var msg = existingType === 'box_set'
                            ? <?php echo wp_json_encode( esc_html__( 'This barcode belongs to an existing box set in your collection.', 'wp-movie-collector' ) ); ?>
                            : <?php echo wp_json_encode( esc_html__( 'This barcode already exists in your collection.', 'wp-movie-collector' ) ); ?>;
                        $('#wp-movie-collector-barcode-result').html(
                            '<div class="notice notice-warning"><p>' + wpMovieCollectorEscHtml(msg) +
                            ' <a href="' + wpMovieCollectorEscHtml(response.data.edit_url) + '" class="button button-small">' +
                            editLabel + '</a></p></div>'
                        );
                    } else {
                        $('#wp-movie-collector-barcode-result').html(<?php echo wp_json_encode('<p class="success">' . esc_html__('Movie found! Filling in details...', 'wp-movie-collector') . '</p>'); ?>);
                        fillMovieForm(response.data);

                        if (response.data.api_source === 'BarcodeLookup' && response.data.title &&
                            (!response.data.director || !response.data.actors || !response.data.description ||
                             (response.data.description && response.data.description.length < 50))) {
                            searchTMDBForMoreDetails(response.data.title, response.data.release_year);
                        }
                    }
                } else {
                    $('#wp-movie-collector-barcode-result').html('<p class="error"></p>').find('p').text(response.data);
                }
            },
            error: function() {
                $('#wp-movie-collector-barcode-result').html(<?php echo wp_json_encode('<p class="error">' . esc_html__('Error looking up barcode. Please try again.', 'wp-movie-collector') . '</p>'); ?>);
            }
        });
    });
    
    // Movie search
    $('#wp-movie-collector-search-movie').on('click', function() {
        var title = $('#wp-movie-collector-movie-search').val();
        if (!title) {
            return;
        }
        
        $('#wp-movie-collector-search-results').html(<?php echo wp_json_encode('<p>' . esc_html__('Searching...', 'wp-movie-collector') . '</p>'); ?>);
        
        $.ajax({
            url: wp_movie_collector_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'wp_movie_collector_movie_search',
                title: title,
                nonce: wp_movie_collector_admin.nonce
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    var resultsHtml = '<div class="wp-movie-collector-search-results">';
                    resultsHtml += '<h4>' + <?php echo wp_json_encode(esc_html__('Search Results', 'wp-movie-collector')); ?> + '</h4>';
                    resultsHtml += '<ul>';
                    
                    $.each(response.data, function(index, movie) {
                        resultsHtml += '<li>';
                        resultsHtml += '<a href="#" class="wp-movie-collector-select-movie" data-movie-id="' + parseInt(movie.id, 10) + '">';
                        resultsHtml += wpMovieCollectorEscHtml(movie.title) + ' (' + wpMovieCollectorEscHtml(movie.release_year) + ')';
                        resultsHtml += '</a>';
                        resultsHtml += '</li>';
                    });
                    
                    resultsHtml += '</ul></div>';
                    $('#wp-movie-collector-search-results').html(resultsHtml);
                    
                    // Handle movie selection
                    $('.wp-movie-collector-select-movie').on('click', function(e) {
                        e.preventDefault();
                        var movieId = $(this).data('movie-id');
                        
                        $.ajax({
                            url: wp_movie_collector_admin.ajax_url,
                            type: 'POST',
                            data: {
                                action: 'wp_movie_collector_get_movie_details',
                                movie_id: movieId,
                                nonce: wp_movie_collector_admin.nonce
                            },
                            success: function(response) {
                                if (response.success) {
                                    fillMovieForm(response.data);
                                }
                            }
                        });
                    });
                } else {
                    $('#wp-movie-collector-search-results').html(<?php echo wp_json_encode('<p class="error">' . esc_html__('No movies found matching that title.', 'wp-movie-collector') . '</p>'); ?>);
                }
            },
            error: function() {
                $('#wp-movie-collector-search-results').html(<?php echo wp_json_encode('<p class="error">' . esc_html__('Error searching for movie. Please try again.', 'wp-movie-collector') . '</p>'); ?>);
            }
        });
    });
    
    // Check for duplicates via AJAX when title or year changes.
    var duplicateCheckTimer;
    function checkForDuplicateMovie() {
        clearTimeout(duplicateCheckTimer);
        duplicateCheckTimer = setTimeout(function() {
            if ($('#movie-allow-duplicate').is(':checked')) {
                $('#wp-movie-collector-duplicate-warning').hide();
                return;
            }

            var title = $('#movie-title').val();
            var year = $('#movie-release-year').val();
            var barcode = $('#movie-barcode').val();

            if (!title && !barcode) {
                $('#wp-movie-collector-duplicate-warning').hide();
                return;
            }

            $.ajax({
                url: wp_movie_collector_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'wp_movie_collector_check_duplicate_movie',
                    title: title,
                    year: year,
                    barcode: barcode,
                    nonce: wp_movie_collector_admin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.has_duplicates) {
                        var match, msg, editUrl;
                        if (response.data.cross_type_match) {
                            match = response.data.cross_type_match;
                            msg = <?php echo wp_json_encode( esc_html__( 'A box set with this barcode already exists in your collection:', 'wp-movie-collector' ) ); ?>;
                            msg += ' ' + match.title + ' (' + (match.release_year || '') + ')';
                            editUrl = <?php echo wp_json_encode( admin_url( 'admin.php?page=wp-movie-collector-edit-box-set&id=' ) ); ?> + parseInt(match.id, 10);
                            $('#wp-movie-collector-duplicate-edit-link').text(<?php echo wp_json_encode( esc_html__( 'Edit Existing Box Set', 'wp-movie-collector' ) ); ?>);
                        } else if (response.data.barcode_match) {
                            match = response.data.barcode_match;
                            msg = <?php echo wp_json_encode( esc_html__( 'A movie with this barcode already exists in your collection:', 'wp-movie-collector' ) ); ?>;
                            msg += ' ' + match.title + ' (' + (match.release_year || '') + ')';
                            editUrl = <?php echo wp_json_encode( admin_url( 'admin.php?page=wp-movie-collector-edit-movie&id=' ) ); ?> + parseInt(match.id, 10);
                            $('#wp-movie-collector-duplicate-edit-link').text(<?php echo wp_json_encode( esc_html__( 'Edit Existing Movie', 'wp-movie-collector' ) ); ?>);
                        } else {
                            match = response.data.title_matches[0];
                            msg = <?php echo wp_json_encode( esc_html__( 'A movie with this title and year already exists:', 'wp-movie-collector' ) ); ?>;
                            msg += ' ' + match.title + ' (' + (match.release_year || '') + ')';
                            editUrl = <?php echo wp_json_encode( admin_url( 'admin.php?page=wp-movie-collector-edit-movie&id=' ) ); ?> + parseInt(match.id, 10);
                            $('#wp-movie-collector-duplicate-edit-link').text(<?php echo wp_json_encode( esc_html__( 'Edit Existing Movie', 'wp-movie-collector' ) ); ?>);
                        }
                        $('#wp-movie-collector-duplicate-message').text(msg);
                        $('#wp-movie-collector-duplicate-edit-link').attr('href', editUrl);
                        $('#wp-movie-collector-duplicate-warning').show();
                    } else {
                        $('#wp-movie-collector-duplicate-warning').hide();
                    }
                }
            });
        }, 500);
    }

    $('#movie-title, #movie-release-year, #movie-barcode').on('change blur', checkForDuplicateMovie);
    $('#movie-allow-duplicate').on('change', function() {
        if ($(this).is(':checked')) {
            $('#wp-movie-collector-duplicate-warning').hide();
        } else {
            checkForDuplicateMovie();
        }
    });

    // Fill movie form with data
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
            wpMovieCollectorSetImagePreview($('#movie-cover-image-url').siblings('.image-preview'), movie.cover_image_url);
            $('#movie-cover-image-url').siblings('.wp-movie-collector-remove-image-button').show();
        }
        
        $('#movie-api-source').val(movie.api_source || '');
    }
    
    /**
     * Search TMDB for more details about the movie
     */
    function searchTMDBForMoreDetails(title, year) {
        $('#wp-movie-collector-search-results').html(<?php echo wp_json_encode('<p>' . esc_html__('Searching for additional movie details...', 'wp-movie-collector') . '</p>'); ?>);
        
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
                    
                    $('#wp-movie-collector-search-results').html(<?php echo wp_json_encode('<p>' . esc_html__('Found match, retrieving full details...', 'wp-movie-collector') . '</p>'); ?>);
                    
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
                                    <?php echo wp_json_encode('<p class="success">' . esc_html__('Successfully retrieved additional movie details from TMDB!', 'wp-movie-collector') . '</p>'); ?>
                                );
                            } else {
                                $('#wp-movie-collector-search-results').html(
                                    <?php echo wp_json_encode('<p class="error">' . esc_html__('Error retrieving full movie details.', 'wp-movie-collector') . '</p>'); ?>
                                );
                            }
                        },
                        error: function() {
                            $('#wp-movie-collector-search-results').html(
                                <?php echo wp_json_encode('<p class="error">' . esc_html__('Error connecting to server.', 'wp-movie-collector') . '</p>'); ?>
                            );
                        }
                    });
                } else {
                    $('#wp-movie-collector-search-results').html(
                        <?php echo wp_json_encode('<p class="error">' . esc_html__('No additional movie details found.', 'wp-movie-collector') . '</p>'); ?>
                    );
                }
            },
            error: function() {
                $('#wp-movie-collector-search-results').html(
                    <?php echo wp_json_encode('<p class="error">' . esc_html__('Error searching for additional movie details.', 'wp-movie-collector') . '</p>'); ?>
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
            wpMovieCollectorSetImagePreview($('#movie-cover-image-url').siblings('.image-preview'), tmdbMovie.cover_image_url);
            $('#movie-cover-image-url').siblings('.wp-movie-collector-remove-image-button').show();
        }
        
        // Update API source to indicate we now have TMDB data
        $('#movie-api-source').val('TMDb (auto-enhanced)');
    }
});
</script>