<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('wp_movie_collector_settings');
        do_settings_sections('wp_movie_collector_settings');
        ?>
        
        <table class="form-table">
            <tr>
                <th scope="row"><label for="wp_movie_collector_tmdb_api_key"><?php _e('TMDb API Key', 'wp-movie-collector'); ?></label></th>
                <td>
                    <input type="text" id="wp_movie_collector_tmdb_api_key" name="wp_movie_collector_tmdb_api_key" 
                           value="<?php echo esc_attr(get_option('wp_movie_collector_tmdb_api_key')); ?>" class="regular-text">
                    <p class="description">
                        <?php _e('Enter your TMDb API key. You can get one from <a href="https://www.themoviedb.org/settings/api" target="_blank">TMDb</a>.', 'wp-movie-collector'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wp_movie_collector_omdb_api_key"><?php _e('OMDb API Key', 'wp-movie-collector'); ?></label></th>
                <td>
                    <input type="text" id="wp_movie_collector_omdb_api_key" name="wp_movie_collector_omdb_api_key" 
                           value="<?php echo esc_attr(get_option('wp_movie_collector_omdb_api_key')); ?>" class="regular-text">
                    <p class="description">
                        <?php _e('Enter your OMDb API key. You can get one from <a href="https://www.omdbapi.com/apikey.aspx" target="_blank">OMDb</a>.', 'wp-movie-collector'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wp_movie_collector_barcode_api_key"><?php _e('BarcodeLookup API Key', 'wp-movie-collector'); ?></label></th>
                <td>
                    <input type="text" id="wp_movie_collector_barcode_api_key" name="wp_movie_collector_barcode_api_key" 
                           value="<?php echo esc_attr(get_option('wp_movie_collector_barcode_api_key')); ?>" class="regular-text">
                    <p class="description">
                        <?php _e('Enter your BarcodeLookup API key. You can get one from <a href="https://barcodelookup.com/api" target="_blank">BarcodeLookup</a>.', 'wp-movie-collector'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Database Information', 'wp-movie-collector'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Database Tables', 'wp-movie-collector'); ?></th>
                <td>
                    <?php
                    global $wpdb;
                    $db = new WP_Movie_Collector_DB();
                    
                    $tables = array(
                        $db->get_movies_table(),
                        $db->get_box_sets_table(),
                        $db->get_relationships_table()
                    );
                    
                    foreach ($tables as $table) {
                        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
                        echo '<p>';
                        echo '<strong>' . esc_html($table) . '</strong>: ';
                        if ($exists) {
                            echo '<span style="color: green;">' . __('Exists', 'wp-movie-collector') . '</span>';
                        } else {
                            echo '<span style="color: red;">' . __('Does not exist', 'wp-movie-collector') . '</span>';
                        }
                        echo '</p>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Database Statistics', 'wp-movie-collector'); ?></th>
                <td>
                    <?php
                    $movies_count = $wpdb->get_var("SELECT COUNT(*) FROM {$db->get_movies_table()}");
                    $box_sets_count = $wpdb->get_var("SELECT COUNT(*) FROM {$db->get_box_sets_table()}");
                    $relationships_count = $wpdb->get_var("SELECT COUNT(*) FROM {$db->get_relationships_table()}");
                    
                    echo '<p>' . __('Movies:', 'wp-movie-collector') . ' <strong>' . (int)$movies_count . '</strong></p>';
                    echo '<p>' . __('Box Sets:', 'wp-movie-collector') . ' <strong>' . (int)$box_sets_count . '</strong></p>';
                    echo '<p>' . __('Box Set Relationships:', 'wp-movie-collector') . ' <strong>' . (int)$relationships_count . '</strong></p>';
                    ?>
                </td>
            </tr>
        </table>
        
        <h2><?php _e('Tools', 'wp-movie-collector'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Repair Database', 'wp-movie-collector'); ?></th>
                <td>
                    <p>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=wp-movie-collector-settings&action=repair_db'), 'wp_movie_collector_repair_db', 'wp_movie_collector_nonce'); ?>" class="button">
                            <?php _e('Repair Database Tables', 'wp-movie-collector'); ?>
                        </a>
                    </p>
                    <p class="description">
                        <?php _e('This will attempt to recreate any missing database tables.', 'wp-movie-collector'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>