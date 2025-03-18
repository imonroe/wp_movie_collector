<?php
/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    WP_Movie_Collector
 */

class WP_Movie_Collector {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @since    1.0.0
     * @access   protected
     * @var      WP_Movie_Collector_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * Define the core functionality of the plugin.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_post_types();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // The class responsible for orchestrating the actions and filters of the core plugin.
        require_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'includes/class-wp-movie-collector-loader.php';

        // The class responsible for defining database operations
        require_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'includes/db/class-wp-movie-collector-db.php';

        // The class responsible for defining all actions that occur in the admin area.
        require_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'admin/class-wp-movie-collector-admin.php';

        // The class responsible for defining all actions that occur in the public-facing area.
        require_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'public/class-wp-movie-collector-public.php';

        // The class responsible for defining custom post types
        require_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'includes/class-wp-movie-collector-post-types.php';

        // The class responsible for API integrations
        require_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'includes/class-wp-movie-collector-api.php';

        $this->loader = new WP_Movie_Collector_Loader();
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new WP_Movie_Collector_Admin();

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        $this->loader->add_action('admin_init', $plugin_admin, 'register_settings');
        
        // Register AJAX handlers
        $this->loader->add_action('wp_ajax_wp_movie_collector_barcode_lookup', $plugin_admin, 'ajax_barcode_lookup');
        $this->loader->add_action('wp_ajax_wp_movie_collector_movie_search', $plugin_admin, 'ajax_movie_search');
        $this->loader->add_action('wp_ajax_wp_movie_collector_get_movie_details', $plugin_admin, 'ajax_get_movie_details');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        $plugin_public = new WP_Movie_Collector_Public();

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
        
        // Register AJAX handlers for public-facing functionality
        $this->loader->add_action('wp_ajax_wp_movie_collector_load_more', $plugin_public, 'ajax_load_more');
        $this->loader->add_action('wp_ajax_nopriv_wp_movie_collector_load_more', $plugin_public, 'ajax_load_more');
    }

    /**
     * Register custom post types and taxonomies
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_post_types() {
        $post_types = new WP_Movie_Collector_Post_Types();

        $this->loader->add_action('init', $post_types, 'register_post_types');
        $this->loader->add_action('init', $post_types, 'register_taxonomies');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }
}
