<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php
    // Show success message if there is one
    if (isset($_GET['message'])) {
        $message_type = sanitize_text_field($_GET['message']);
        $message = '';
        
        switch ($message_type) {
            case 'movie_added':
                $message = __('Movie added to box set successfully!', 'wp-movie-collector');
                break;
            case 'movie_removed':
                $message = __('Movie removed from box set successfully!', 'wp-movie-collector');
                break;
            case 'movies_reordered':
                $message = __('Movies reordered successfully!', 'wp-movie-collector');
                break;
            default:
                $message = __('Operation completed successfully.', 'wp-movie-collector');
                break;
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }
    
    // Show error message if there is one
    if (isset($_GET['error'])) {
        $error_type = sanitize_text_field($_GET['error']);
        $error_message = '';
        
        switch ($error_type) {
            case 'invalid_box_set':
                $error_message = __('Invalid box set ID.', 'wp-movie-collector');
                break;
            case 'invalid_movie':
                $error_message = __('Invalid movie ID.', 'wp-movie-collector');
                break;
            default:
                $error_message = __('An unknown error occurred. Please try again.', 'wp-movie-collector');
                break;
        }
        
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
    }
    
    // Get box set ID from URL
    $box_set_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    // Check if box set exists
    global $wpdb;
    $db = new WP_Movie_Collector_DB();
    $box_set = $db->get_box_set($box_set_id);
    
    if (!$box_set) {
        echo '<div class="notice notice-error"><p>' . __('Box set not found.', 'wp-movie-collector') . '</p></div>';
        echo '<p><a href="' . admin_url('admin.php?page=wp-movie-collector-box-sets') . '" class="button">' . __('Back to Box Sets', 'wp-movie-collector') . '</a></p>';
        return;
    }
    ?>
    
    <div class="wp-movie-collector-box-set-details">
        <h2><?php echo esc_html($box_set['title']); ?> (<?php echo esc_html($box_set['release_year']); ?>)</h2>
        <p>
            <strong><?php _e('Format:', 'wp-movie-collector'); ?></strong> <?php echo esc_html($box_set['format']); ?> |
            <strong><?php _e('Region:', 'wp-movie-collector'); ?></strong> <?php echo esc_html($box_set['region_code']); ?>
        </p>
        
        <?php if (!empty($box_set['description'])): ?>
        <div class="wp-movie-collector-description">
            <h3><?php _e('Description', 'wp-movie-collector'); ?></h3>
            <p><?php echo esc_html($box_set['description']); ?></p>
        </div>
        <?php endif; ?>
    </div>
    
    <div class="wp-movie-collector-tabs">
        <ul class="wp-movie-collector-tabs-nav">
            <li class="active"><a href="#current-movies"><?php _e('Current Movies', 'wp-movie-collector'); ?></a></li>
            <li><a href="#add-movies"><?php _e('Add Movies', 'wp-movie-collector'); ?></a></li>
        </ul>
        
        <div class="wp-movie-collector-tab-content">
            <!-- Current Movies Tab -->
            <div id="current-movies" class="wp-movie-collector-tab-pane active">
                <h3><?php _e('Movies in this Box Set', 'wp-movie-collector'); ?></h3>
                
                <?php
                // Get movies in this box set
                $movies = $db->get_movies_in_box_set($box_set_id);
                
                if (empty($movies)) {
                    echo '<p>' . __('No movies in this box set yet.', 'wp-movie-collector') . '</p>';
                } else {
                ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="wp-movie-collector-reorder-movies-form">
                    <input type="hidden" name="action" value="wp_movie_collector_reorder_movies">
                    <input type="hidden" name="box_set_id" value="<?php echo esc_attr($box_set_id); ?>">
                    <?php wp_nonce_field('wp_movie_collector_reorder_movies', 'wp_movie_collector_nonce'); ?>
                    
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th width="20"><?php _e('Order', 'wp-movie-collector'); ?></th>
                                <th><?php _e('Title', 'wp-movie-collector'); ?></th>
                                <th><?php _e('Release Year', 'wp-movie-collector'); ?></th>
                                <th><?php _e('Format', 'wp-movie-collector'); ?></th>
                                <th><?php _e('Actions', 'wp-movie-collector'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="sortable-movies">
                            <?php foreach ($movies as $index => $movie): ?>
                            <tr class="movie-item" data-movie-id="<?php echo esc_attr($movie['id']); ?>">
                                <td>
                                    <span class="dashicons dashicons-move"></span>
                                    <input type="hidden" name="movie_order[]" value="<?php echo esc_attr($movie['id']); ?>">
                                </td>
                                <td><?php echo esc_html($movie['title']); ?></td>
                                <td><?php echo esc_html($movie['release_year']); ?></td>
                                <td><?php echo esc_html($movie['format']); ?></td>
                                <td>
                                    <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=wp_movie_collector_remove_movie&box_set_id=' . $box_set_id . '&movie_id=' . $movie['id']), 'wp_movie_collector_remove_movie_' . $movie['id'], 'wp_movie_collector_nonce'); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e('Are you sure you want to remove this movie from the box set?', 'wp-movie-collector'); ?>')">
                                        <?php _e('Remove', 'wp-movie-collector'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <p class="submit">
                        <button type="submit" class="button button-primary">
                            <?php _e('Save Order', 'wp-movie-collector'); ?>
                        </button>
                    </p>
                </form>
                <?php
                }
                ?>
            </div>
            
            <!-- Add Movies Tab -->
            <div id="add-movies" class="wp-movie-collector-tab-pane">
                <h3><?php _e('Add Movies to Box Set', 'wp-movie-collector'); ?></h3>
                
                <div class="wp-movie-collector-search">
                    <input type="text" id="wp-movie-collector-movie-search" class="regular-text" placeholder="<?php esc_attr_e('Search movies by title...', 'wp-movie-collector'); ?>">
                    <button type="button" id="wp-movie-collector-search-movies" class="button"><?php _e('Search', 'wp-movie-collector'); ?></button>
                </div>
                
                <div id="wp-movie-collector-search-results">
                    <!-- Search results will be displayed here -->
                </div>
                
                <div class="wp-movie-collector-movies-list">
                    <h4><?php _e('Available Movies', 'wp-movie-collector'); ?></h4>
                    
                    <?php
                    // Get movies not in this box set
                    $existing_movie_ids = array_map(function($movie) {
                        return $movie['id'];
                    }, $movies);
                    
                    $available_movies = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$db->get_movies_table()} 
                            WHERE id NOT IN (
                                SELECT movie_id FROM {$db->get_relationships_table()} 
                                WHERE box_set_id = %d
                            )
                            ORDER BY title ASC
                            LIMIT 50",
                            $box_set_id
                        ),
                        ARRAY_A
                    );
                    
                    if (empty($available_movies)) {
                        echo '<p>' . __('No available movies found. Add some movies first!', 'wp-movie-collector') . '</p>';
                    } else {
                    ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                        <input type="hidden" name="action" value="wp_movie_collector_add_movies_to_box_set">
                        <input type="hidden" name="box_set_id" value="<?php echo esc_attr($box_set_id); ?>">
                        <?php wp_nonce_field('wp_movie_collector_add_movies', 'wp_movie_collector_nonce'); ?>
                        
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th width="20"><input type="checkbox" id="select-all-movies"></th>
                                    <th><?php _e('Title', 'wp-movie-collector'); ?></th>
                                    <th><?php _e('Release Year', 'wp-movie-collector'); ?></th>
                                    <th><?php _e('Format', 'wp-movie-collector'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($available_movies as $movie): ?>
                                <tr>
                                    <td><input type="checkbox" name="movie_ids[]" value="<?php echo esc_attr($movie['id']); ?>"></td>
                                    <td><?php echo esc_html($movie['title']); ?></td>
                                    <td><?php echo esc_html($movie['release_year']); ?></td>
                                    <td><?php echo esc_html($movie['format']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <p class="submit">
                            <button type="submit" class="button button-primary">
                                <?php _e('Add Selected Movies to Box Set', 'wp-movie-collector'); ?>
                            </button>
                        </p>
                    </form>
                    <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
    
    <p>
        <a href="<?php echo admin_url('admin.php?page=wp-movie-collector-box-sets'); ?>" class="button"><?php _e('Back to Box Sets', 'wp-movie-collector'); ?></a>
    </p>
</div>

<style>
.wp-movie-collector-tabs-nav {
    display: flex;
    margin: 20px 0 0 0;
    padding: 0;
    border-bottom: 1px solid #ccc;
}

.wp-movie-collector-tabs-nav li {
    margin: 0;
    padding: 0;
    list-style: none;
}

.wp-movie-collector-tabs-nav a {
    display: block;
    padding: 10px 15px;
    text-decoration: none;
    background: #f1f1f1;
    color: #444;
    margin-right: 5px;
    border: 1px solid #ccc;
    border-bottom: none;
}

.wp-movie-collector-tabs-nav li.active a {
    background: #fff;
    position: relative;
    border-bottom: 1px solid #fff;
    margin-bottom: -1px;
    font-weight: bold;
}

.wp-movie-collector-tab-content {
    background: #fff;
    border: 1px solid #ccc;
    border-top: none;
    padding: 20px;
    margin-bottom: 20px;
}

.wp-movie-collector-tab-pane {
    display: none;
}

.wp-movie-collector-tab-pane.active {
    display: block;
}

#sortable-movies .dashicons-move {
    cursor: move;
    color: #999;
}

.wp-movie-collector-search {
    margin-bottom: 20px;
}

#wp-movie-collector-search-results {
    margin-bottom: 20px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab functionality
    $('.wp-movie-collector-tabs-nav a').on('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all tabs
        $('.wp-movie-collector-tabs-nav li').removeClass('active');
        $('.wp-movie-collector-tab-pane').removeClass('active');
        
        // Add active class to clicked tab
        $(this).parent().addClass('active');
        $($(this).attr('href')).addClass('active');
    });
    
    // Sortable functionality for reordering movies
    if ($('#sortable-movies').length) {
        $('#sortable-movies').sortable({
            handle: '.dashicons-move',
            update: function(event, ui) {
                // Update the hidden input values after sorting
                $('#sortable-movies tr').each(function(index) {
                    $(this).find('input[name="movie_order[]"]').val($(this).data('movie-id'));
                });
            }
        });
    }
    
    // Select all movies checkbox
    $('#select-all-movies').on('change', function() {
        $('input[name="movie_ids[]"]').prop('checked', $(this).prop('checked'));
    });
    
    // Movie search functionality
    $('#wp-movie-collector-search-movies').on('click', function() {
        var searchQuery = $('#wp-movie-collector-movie-search').val();
        if (!searchQuery) {
            return;
        }
        
        var boxSetId = <?php echo esc_js($box_set_id); ?>;
        
        $('#wp-movie-collector-search-results').html('<p><?php _e('Searching...', 'wp-movie-collector'); ?></p>');
        
        $.ajax({
            url: wp_movie_collector_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'wp_movie_collector_search_available_movies',
                box_set_id: boxSetId,
                query: searchQuery,
                nonce: wp_movie_collector_admin.nonce
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    var resultsHtml = '<h4><?php _e('Search Results', 'wp-movie-collector'); ?></h4>';
                    resultsHtml += '<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">';
                    resultsHtml += '<input type="hidden" name="action" value="wp_movie_collector_add_movies_to_box_set">';
                    resultsHtml += '<input type="hidden" name="box_set_id" value="' + boxSetId + '">';
                    resultsHtml += '<?php wp_nonce_field('wp_movie_collector_add_movies', 'wp_movie_collector_nonce'); ?>';
                    
                    resultsHtml += '<table class="wp-list-table widefat fixed striped">';
                    resultsHtml += '<thead><tr>';
                    resultsHtml += '<th width="20"><input type="checkbox" id="select-all-search-results"></th>';
                    resultsHtml += '<th><?php _e('Title', 'wp-movie-collector'); ?></th>';
                    resultsHtml += '<th><?php _e('Release Year', 'wp-movie-collector'); ?></th>';
                    resultsHtml += '<th><?php _e('Format', 'wp-movie-collector'); ?></th>';
                    resultsHtml += '</tr></thead><tbody>';
                    
                    $.each(response.data, function(index, movie) {
                        resultsHtml += '<tr>';
                        resultsHtml += '<td><input type="checkbox" name="movie_ids[]" value="' + movie.id + '"></td>';
                        resultsHtml += '<td>' + movie.title + '</td>';
                        resultsHtml += '<td>' + movie.release_year + '</td>';
                        resultsHtml += '<td>' + movie.format + '</td>';
                        resultsHtml += '</tr>';
                    });
                    
                    resultsHtml += '</tbody></table>';
                    resultsHtml += '<p class="submit"><button type="submit" class="button button-primary"><?php _e('Add Selected Movies to Box Set', 'wp-movie-collector'); ?></button></p>';
                    resultsHtml += '</form>';
                    
                    $('#wp-movie-collector-search-results').html(resultsHtml);
                    
                    // Select all search results checkbox
                    $('#select-all-search-results').on('change', function() {
                        $(this).closest('form').find('input[name="movie_ids[]"]').prop('checked', $(this).prop('checked'));
                    });
                } else {
                    $('#wp-movie-collector-search-results').html('<p><?php _e('No movies found matching your search.', 'wp-movie-collector'); ?></p>');
                }
            },
            error: function() {
                $('#wp-movie-collector-search-results').html('<p class="error"><?php _e('Error searching for movies. Please try again.', 'wp-movie-collector'); ?></p>');
            }
        });
    });
});
</script>