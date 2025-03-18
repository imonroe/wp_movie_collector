<?php
/**
 * Fired during plugin activation.
 *
 * @since      1.0.0
 * @package    WP_Movie_Collector
 */

class WP_Movie_Collector_Activator {

    /**
     * Set up the plugin during activation.
     *
     * Creates necessary database tables and initializes default options.
     *
     * @since    1.0.0
     */
    public static function activate() {
        // Create database tables
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/db/class-wp-movie-collector-db.php';
        $db = new WP_Movie_Collector_DB();
        $db->create_tables();
        
        // Add default plugin options
        add_option('wp_movie_collector_version', WP_MOVIE_COLLECTOR_VERSION);
    }
}
