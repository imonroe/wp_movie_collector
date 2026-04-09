<?php
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have permission to access this page.', 'wp-movie-collector' ) );
}
?>
<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php if (isset($_GET['cache_cleared'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php echo esc_html__('API cache invalidated successfully. Cached responses will be refreshed on next request.', 'wp-movie-collector'); ?></p>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['cache_error'])) : ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html__('Failed to clear API cache. Please try again.', 'wp-movie-collector'); ?></p>
        </div>
    <?php endif; ?>

    <form method="post" action="options.php">
        <?php
        settings_fields('wp_movie_collector_settings');
        do_settings_sections('wp_movie_collector_settings');
        ?>
        
        <table class="form-table">
            <tr>
                <th scope="row"><label for="wp_movie_collector_tmdb_api_key"><?php esc_html_e('TMDb API Key', 'wp-movie-collector'); ?></label></th>
                <td>
                    <input type="text" id="wp_movie_collector_tmdb_api_key" name="wp_movie_collector_tmdb_api_key" 
                           value="<?php echo esc_attr(get_option('wp_movie_collector_tmdb_api_key')); ?>" class="regular-text">
                    <p class="description">
                        <?php echo wp_kses(__('Enter your TMDb API key. You can get one from <a href="https://www.themoviedb.org/settings/api" target="_blank">TMDb</a>.', 'wp-movie-collector'), array('a' => array('href' => array(), 'target' => array()))); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wp_movie_collector_omdb_api_key"><?php esc_html_e('OMDb API Key', 'wp-movie-collector'); ?></label></th>
                <td>
                    <input type="text" id="wp_movie_collector_omdb_api_key" name="wp_movie_collector_omdb_api_key" 
                           value="<?php echo esc_attr(get_option('wp_movie_collector_omdb_api_key')); ?>" class="regular-text">
                    <p class="description">
                        <?php echo wp_kses(__('Enter your OMDb API key. You can get one from <a href="https://www.omdbapi.com/apikey.aspx" target="_blank">OMDb</a>.', 'wp-movie-collector'), array('a' => array('href' => array(), 'target' => array()))); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="wp_movie_collector_barcode_api_key"><?php esc_html_e('BarcodeLookup API Key', 'wp-movie-collector'); ?></label></th>
                <td>
                    <input type="text" id="wp_movie_collector_barcode_api_key" name="wp_movie_collector_barcode_api_key" 
                           value="<?php echo esc_attr(get_option('wp_movie_collector_barcode_api_key')); ?>" class="regular-text">
                    <p class="description">
                        <?php echo wp_kses(__('Enter your BarcodeLookup API key. You can get one from <a href="https://barcodelookup.com/api" target="_blank">BarcodeLookup</a>.', 'wp-movie-collector'), array('a' => array('href' => array(), 'target' => array()))); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <h2><?php esc_html_e('Database Information', 'wp-movie-collector'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Database Tables', 'wp-movie-collector'); ?></th>
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
                        $exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table)) === $table;
                        echo '<p>';
                        echo '<strong>' . esc_html($table) . '</strong>: ';
                        if ($exists) {
                            echo '<span style="color: green;">' . esc_html__('Exists', 'wp-movie-collector') . '</span>';
                        } else {
                            echo '<span style="color: red;">' . esc_html__('Does not exist', 'wp-movie-collector') . '</span>';
                        }
                        echo '</p>';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Database Statistics', 'wp-movie-collector'); ?></th>
                <td>
                    <?php
                    $movies_count = $wpdb->get_var("SELECT COUNT(*) FROM {$db->get_movies_table()}");
                    $box_sets_count = $wpdb->get_var("SELECT COUNT(*) FROM {$db->get_box_sets_table()}");
                    $relationships_count = $wpdb->get_var("SELECT COUNT(*) FROM {$db->get_relationships_table()}");
                    
                    echo '<p>' . esc_html__('Movies:', 'wp-movie-collector') . ' <strong>' . intval($movies_count) . '</strong></p>';
                    echo '<p>' . esc_html__('Box Sets:', 'wp-movie-collector') . ' <strong>' . intval($box_sets_count) . '</strong></p>';
                    echo '<p>' . esc_html__('Box Set Relationships:', 'wp-movie-collector') . ' <strong>' . intval($relationships_count) . '</strong></p>';
                    ?>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>

    <h2><?php esc_html_e('Tools', 'wp-movie-collector'); ?></h2>
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Repair Database', 'wp-movie-collector'); ?></th>
            <td>
                <p>
                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=wp-movie-collector-settings&action=repair_db'), 'wp_movie_collector_repair_db', 'wp_movie_collector_nonce')); ?>" class="button">
                        <?php esc_html_e('Repair Database Tables', 'wp-movie-collector'); ?>
                    </a>
                </p>
                <p class="description">
                    <?php esc_html_e('This will attempt to recreate any missing database tables.', 'wp-movie-collector'); ?>
                </p>
            </td>
        </tr>
        <tr>
            <th scope="row"><?php esc_html_e('Clear API Cache', 'wp-movie-collector'); ?></th>
            <td>
                <form method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
                    <?php wp_nonce_field('wp_movie_collector_clear_cache', 'wp_movie_collector_nonce'); ?>
                    <input type="hidden" name="action" value="wp_movie_collector_clear_api_cache">
                    <p>
                        <button type="submit" class="button" id="wp-movie-collector-clear-cache">
                            <?php esc_html_e('Clear API Cache', 'wp-movie-collector'); ?>
                        </button>
                    </p>
                </form>
                <p class="description">
                    <?php esc_html_e('This will clear all cached API responses (movie searches, details, and barcode lookups).', 'wp-movie-collector'); ?>
                </p>
            </td>
        </tr>
    </table>
</div>