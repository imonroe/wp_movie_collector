<?php
/**
 * The plugin bootstrap file
 *
 * @link              https://example.com
 * @since             1.0.0
 * @package           WP_Movie_Collector
 *
 * @wordpress-plugin
 * Plugin Name:       WP Movie Collector
 * Plugin URI:        https://example.com/wp-movie-collector
 * Description:       A WordPress plugin for collecting and managing your movie collection.
 * Version:           1.0.0
 * Author:            Your Name
 * Author URI:        https://example.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-movie-collector
 * Domain Path:       /languages
 */

// Suppress PHP deprecation notices
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('WP_MOVIE_COLLECTOR_VERSION', '1.0.0');
define('WP_MOVIE_COLLECTOR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_MOVIE_COLLECTOR_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_wp_movie_collector() {
    require_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'includes/class-wp-movie-collector-activator.php';
    WP_Movie_Collector_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_wp_movie_collector() {
    require_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'includes/class-wp-movie-collector-deactivator.php';
    WP_Movie_Collector_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_wp_movie_collector');
register_deactivation_hook(__FILE__, 'deactivate_wp_movie_collector');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'includes/class-wp-movie-collector.php';

/**
 * Begins execution of the plugin.
 *
 * @since    1.0.0
 */
function run_wp_movie_collector() {
    $plugin = new WP_Movie_Collector();
    $plugin->run();
}
run_wp_movie_collector();