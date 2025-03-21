<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php
    // Show error message if there is one
    if (isset($_GET['error'])) {
        $error_type = sanitize_text_field($_GET['error']);
        
        if ($error_type === 'validation') {
            // Get validation errors from transient
            $errors = get_transient('wp_movie_collector_form_errors');
            if ($errors && is_array($errors)) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>' . __('Please fix the following errors:', 'wp-movie-collector') . '</strong></p>';
                echo '<ul>';
                foreach ($errors as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
                
                // Delete the transient
                delete_transient('wp_movie_collector_form_errors');
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
            <h3><?php _e('Scan Barcode', 'wp-movie-collector'); ?></h3>
            <p><?php _e('Use a barcode scanner or enter a barcode manually to quickly add a movie.', 'wp-movie-collector'); ?></p>
            <div class="wp-movie-collector-barcode-input">
                <input type="text" id="wp-movie-collector-barcode" class="regular-text" placeholder="<?php _e('Scan or enter barcode...', 'wp-movie-collector'); ?>">
                <button type="button" id="wp-movie-collector-lookup-barcode" class="button"><?php _e('Lookup', 'wp-movie-collector'); ?></button>
            </div>
            <div id="wp-movie-collector-barcode-result"></div>
        </div>
        
        <div class="wp-movie-collector-api-search">
            <h3><?php _e('Search Movie Database', 'wp-movie-collector'); ?></h3>
            <p><?php _e('Search for a movie title to retrieve metadata.', 'wp-movie-collector'); ?></p>
            <div class="wp-movie-collector-search-input">
                <input type="text" id="wp-movie-collector-movie-search" class="regular-text" placeholder="<?php _e('Search movie title...', 'wp-movie-collector'); ?>">
                <button type="button" id="wp-movie-collector-search-movie" class="button"><?php _e('Search', 'wp-movie-collector'); ?></button>
            </div>
            <div id="wp-movie-collector-search-results"></div>
        </div>
        
        <h3><?php _e('Movie Details', 'wp-movie-collector'); ?></h3>
        <form method="post" id="wp-movie-collector-add-movie-form">
            <?php wp_nonce_field('wp_movie_collector_add_movie', 'wp_movie_collector_nonce'); ?>
            
            <div class="form-group">
                <label for="movie-title"><?php _e('Title', 'wp-movie-collector'); ?></label>
                <input type="text" id="movie-title" name="movie[title]" class="regular-text" required>
            </div>
            
            <div class="form-group">
                <label for="movie-release-year"><?php _e('Release Year', 'wp-movie-collector'); ?></label>
                <input type="number" id="movie-release-year" name="movie[release_year]" min="1900" max="<?php echo date('Y'); ?>" class="small-text" required>
            </div>
            
            <div class="form-group">
                <label for="movie-format"><?php _e('Format', 'wp-movie-collector'); ?></label>
                <select id="movie-format" name="movie[format]" required>
                    <option value=""><?php _e('Select Format', 'wp-movie-collector'); ?></option>
                    <option value="DVD"><?php _e('DVD', 'wp-movie-collector'); ?></option>
                    <option value="Blu-ray"><?php _e('Blu-ray', 'wp-movie-collector'); ?></option>
                    <option value="4K UHD"><?php _e('4K Ultra HD', 'wp-movie-collector'); ?></option>
                    <option value="VHS"><?php _e('VHS', 'wp-movie-collector'); ?></option>
                    <option value="LaserDisc"><?php _e('LaserDisc', 'wp-movie-collector'); ?></option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="movie-region-code"><?php _e('Region Code', 'wp-movie-collector'); ?></label>
                <select id="movie-region-code" name="movie[region_code]" required>
                    <option value=""><?php _e('Select Region', 'wp-movie-collector'); ?></option>
                    <option value="R1">Region 1 (USA, Canada)</option>
                    <option value="R2">Region 2 (Europe, Japan, Middle East)</option>
                    <option value="R3">Region 3 (East Asia)</option>
                    <option value="R4">Region 4 (Australia, New Zealand, Latin America)</option>
                    <option value="R5">Region 5 (Africa, Asia, Russia)</option>
                    <option value="R6">Region 6 (China)</option>
                    <option value="R0">Region Free</option>
                    <option value="A">Region A (Blu-ray: Americas, East Asia)</option>
                    <option value="B">Region B (Blu-ray: Europe, Africa, Australia)</option>
                    <option value="C">Region C (Blu-ray: Central/South Asia, Russia, China)</option>
                    <option value="ABC">Region Free (Blu-ray)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="movie-barcode"><?php _e('Barcode', 'wp-movie-collector'); ?></label>
                <input type="text" id="movie-barcode" name="movie[barcode]" class="regular-text">
            </div>
            
            <div class="form-group">
                <label for="movie-director"><?php _e('Director', 'wp-movie-collector'); ?></label>
                <input type="text" id="movie-director" name="movie[director]" class="regular-text">
            </div>
            
            <div class="form-group">
                <label for="movie-studio"><?php _e('Studio', 'wp-movie-collector'); ?></label>
                <input type="text" id="movie-studio" name="movie[studio]" class="regular-text">
            </div>
            
            <div class="form-group">
                <label for="movie-actors"><?php _e('Actors', 'wp-movie-collector'); ?></label>
                <textarea id="movie-actors" name="movie[actors]"></textarea>
                <p class="description"><?php _e('Enter actors separated by commas.', 'wp-movie-collector'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="movie-genre"><?php _e('Genre', 'wp-movie-collector'); ?></label>
                <input type="text" id="movie-genre" name="movie[genre]" class="regular-text">
                <p class="description"><?php _e('Enter genres separated by commas.', 'wp-movie-collector'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="movie-special-features"><?php _e('Special Features', 'wp-movie-collector'); ?></label>
                <textarea id="movie-special-features" name="movie[special_features]"></textarea>
                <p class="description"><?php _e('Enter special features like director\'s commentary, deleted scenes, etc.', 'wp-movie-collector'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="movie-cover-image"><?php _e('Cover Image', 'wp-movie-collector'); ?></label>
                <div class="wp-movie-collector-image-upload-container">
                    <div class="image-preview"></div>
                    <input type="hidden" id="movie-cover-image-id" name="movie[cover_image_id]" class="image-id-field">
                    <input type="url" id="movie-cover-image-url" name="movie[cover_image_url]" class="regular-text image-url-field" placeholder="<?php _e('Image URL or upload', 'wp-movie-collector'); ?>">
                    <button type="button" class="button wp-movie-collector-upload-image-button"><?php _e('Upload Image', 'wp-movie-collector'); ?></button>
                    <button type="button" class="button wp-movie-collector-remove-image-button" style="display:none;"><?php _e('Remove Image', 'wp-movie-collector'); ?></button>
                    <p class="description"><?php _e('Upload an image or enter a URL for the movie cover.', 'wp-movie-collector'); ?></p>
                </div>
            </div>
            
            <div class="form-group">
                <label for="movie-description"><?php _e('Description', 'wp-movie-collector'); ?></label>
                <textarea id="movie-description" name="movie[description]"></textarea>
            </div>
            
            <div class="form-group">
                <label for="movie-acquisition-date"><?php _e('Acquisition Date', 'wp-movie-collector'); ?></label>
                <input type="date" id="movie-acquisition-date" name="movie[acquisition_date]" class="regular-text">
                <p class="description"><?php _e('When did you acquire this movie?', 'wp-movie-collector'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="movie-box-set"><?php _e('Part of Box Set?', 'wp-movie-collector'); ?></label>
                <select id="movie-box-set" name="movie[box_set_id]">
                    <option value=""><?php _e('Not part of a box set', 'wp-movie-collector'); ?></option>
                    <?php
                    global $wpdb;
                    $db = new WP_Movie_Collector_DB();
                    $box_sets = $wpdb->get_results("SELECT id, title FROM {$db->get_box_sets_table()} ORDER BY title ASC", ARRAY_A);
                    
                    if ($box_sets) {
                        foreach ($box_sets as $box_set) {
                            echo '<option value="' . esc_attr($box_set['id']) . '">' . esc_html($box_set['title']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="movie-custom-notes"><?php _e('Custom Notes', 'wp-movie-collector'); ?></label>
                <textarea id="movie-custom-notes" name="movie[custom_notes]"></textarea>
                <p class="description"><?php _e('Any personal notes about this movie.', 'wp-movie-collector'); ?></p>
            </div>
            
            <input type="hidden" id="movie-api-source" name="movie[api_source]" value="">
            
            <p class="submit">
                <button type="submit" class="button button-primary" name="wp_movie_collector_add_movie_submit">
                    <?php _e('Add Movie', 'wp-movie-collector'); ?>
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
        
        $('#wp-movie-collector-barcode-result').html('<p><?php _e('Looking up barcode...', 'wp-movie-collector'); ?></p>');
        
        $.ajax({
            url: wp_movie_collector_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'wp_movie_collector_barcode_lookup',
                barcode: barcode,
                nonce: wp_movie_collector_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#wp-movie-collector-barcode-result').html('<p class="success"><?php _e('Movie found! Filling in details...', 'wp-movie-collector'); ?></p>');
                    
                    // Fill in form with movie details
                    fillMovieForm(response.data);
                    
                    // If we got a title from barcode lookup but have limited metadata,
                    // automatically search for more details from TMDB
                    if (response.data.api_source === 'BarcodeLookup' && response.data.title && 
                        (!response.data.director || !response.data.actors || !response.data.description || 
                         (response.data.description && response.data.description.length < 50))) {
                        searchTMDBForMoreDetails(response.data.title, response.data.release_year);
                    }
                } else {
                    $('#wp-movie-collector-barcode-result').html('<p class="error">' + response.data + '</p>');
                }
            },
            error: function() {
                $('#wp-movie-collector-barcode-result').html('<p class="error"><?php _e('Error looking up barcode. Please try again.', 'wp-movie-collector'); ?></p>');
            }
        });
    });
    
    // Movie search
    $('#wp-movie-collector-search-movie').on('click', function() {
        var title = $('#wp-movie-collector-movie-search').val();
        if (!title) {
            return;
        }
        
        $('#wp-movie-collector-search-results').html('<p><?php _e('Searching...', 'wp-movie-collector'); ?></p>');
        
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
                    resultsHtml += '<h4><?php _e('Search Results', 'wp-movie-collector'); ?></h4>';
                    resultsHtml += '<ul>';
                    
                    $.each(response.data, function(index, movie) {
                        resultsHtml += '<li>';
                        resultsHtml += '<a href="#" class="wp-movie-collector-select-movie" data-movie-id="' + movie.id + '">';
                        resultsHtml += movie.title + ' (' + movie.release_year + ')';
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
                    $('#wp-movie-collector-search-results').html('<p class="error"><?php _e('No movies found matching that title.', 'wp-movie-collector'); ?></p>');
                }
            },
            error: function() {
                $('#wp-movie-collector-search-results').html('<p class="error"><?php _e('Error searching for movie. Please try again.', 'wp-movie-collector'); ?></p>');
            }
        });
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
            $('#movie-cover-image-url').siblings('.image-preview').html('<img src="' + movie.cover_image_url + '" alt="" style="max-width:150px;max-height:150px;" />');
            $('#movie-cover-image-url').siblings('.wp-movie-collector-remove-image-button').show();
        }
        
        $('#movie-api-source').val(movie.api_source || '');
    }
    
    /**
     * Search TMDB for more details about the movie
     */
    function searchTMDBForMoreDetails(title, year) {
        $('#wp-movie-collector-search-results').html('<p><?php _e('Searching for additional movie details...', 'wp-movie-collector'); ?></p>');
        
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
                    
                    $('#wp-movie-collector-search-results').html('<p><?php _e('Found match, retrieving full details...', 'wp-movie-collector'); ?></p>');
                    
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
                                    '<p class="success"><?php _e('Successfully retrieved additional movie details from TMDB!', 'wp-movie-collector'); ?></p>'
                                );
                            } else {
                                $('#wp-movie-collector-search-results').html(
                                    '<p class="error"><?php _e('Error retrieving full movie details.', 'wp-movie-collector'); ?></p>'
                                );
                            }
                        },
                        error: function() {
                            $('#wp-movie-collector-search-results').html(
                                '<p class="error"><?php _e('Error connecting to server.', 'wp-movie-collector'); ?></p>'
                            );
                        }
                    });
                } else {
                    $('#wp-movie-collector-search-results').html(
                        '<p class="error"><?php _e('No additional movie details found.', 'wp-movie-collector'); ?></p>'
                    );
                }
            },
            error: function() {
                $('#wp-movie-collector-search-results').html(
                    '<p class="error"><?php _e('Error searching for additional movie details.', 'wp-movie-collector'); ?></p>'
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
});
</script>