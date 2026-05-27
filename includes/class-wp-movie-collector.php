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
		$this->maybe_update();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_post_types();
		$this->define_rest_routes();
	}

	/**
	 * Check and perform plugin updates if needed.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function maybe_update() {
		$current_version = get_option( 'wp_movie_collector_version', '0.0.0' );

		if ( version_compare( $current_version, WP_MOVIE_COLLECTOR_VERSION, '<' ) ) {
			require_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'includes/class-wp-movie-collector-activator.php';
			WP_Movie_Collector_Activator::update();
		}
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
		require_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'includes/class-wp-movie-collector-api-client.php';
		require_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'includes/class-wp-movie-collector-api.php';

		// The class responsible for the REST API endpoints.
		require_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'includes/class-wp-movie-collector-rest-controller.php';

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

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );

		// Process form submissions
		$this->loader->add_action( 'admin_init', $plugin_admin, 'process_add_movie_form' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'process_edit_movie_form' );
		$this->loader->add_action( 'admin_post_wp_movie_collector_delete_movie', $plugin_admin, 'process_delete_movie' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'process_add_box_set_form' );
		$this->loader->add_action( 'admin_post_wp_movie_collector_update_box_set', $plugin_admin, 'process_edit_box_set_form' );
		$this->loader->add_action( 'admin_post_wp_movie_collector_delete_box_set', $plugin_admin, 'process_delete_box_set' );
		$this->loader->add_action( 'admin_post_wp_movie_collector_add_movies_to_box_set', $plugin_admin, 'process_add_movies_to_box_set' );
		$this->loader->add_action( 'admin_post_wp_movie_collector_remove_movie', $plugin_admin, 'process_remove_movie_from_box_set' );
		$this->loader->add_action( 'admin_post_wp_movie_collector_reorder_movies', $plugin_admin, 'process_reorder_movies' );

		// Register import/export handlers
		$this->loader->add_action( 'admin_post_wp_movie_collector_export_movies', $plugin_admin, 'process_export_movies' );
		$this->loader->add_action( 'admin_post_wp_movie_collector_import_movies', $plugin_admin, 'process_import_movies' );
		$this->loader->add_action( 'admin_post_wp_movie_collector_download_csv_template', $plugin_admin, 'download_csv_template' );

		// Register AJAX handlers
		$this->loader->add_action( 'wp_ajax_wp_movie_collector_barcode_lookup', $plugin_admin, 'ajax_barcode_lookup' );
		$this->loader->add_action( 'wp_ajax_wp_movie_collector_movie_search', $plugin_admin, 'ajax_movie_search' );
		$this->loader->add_action( 'wp_ajax_wp_movie_collector_get_movie_details', $plugin_admin, 'ajax_get_movie_details' );
		$this->loader->add_action( 'wp_ajax_wp_movie_collector_clear_api_cache', $plugin_admin, 'ajax_clear_api_cache' );
		$this->loader->add_action( 'wp_ajax_wp_movie_collector_search_available_movies', $plugin_admin, 'ajax_search_available_movies' );
		$this->loader->add_action( 'wp_ajax_wp_movie_collector_check_duplicate_movie', $plugin_admin, 'ajax_check_duplicate_movie' );
		$this->loader->add_action( 'wp_ajax_wp_movie_collector_check_duplicate_box_set', $plugin_admin, 'ajax_check_duplicate_box_set' );

		// Admin notices for API issues
		$this->loader->add_action( 'admin_notices', $plugin_admin, 'display_api_issue_notices' );
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

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'init', $plugin_public, 'register_shortcodes' );

		// Register AJAX handlers for public-facing functionality
		$this->loader->add_action( 'wp_ajax_wp_movie_collector_load_more', $plugin_public, 'ajax_load_more' );
		$this->loader->add_action( 'wp_ajax_nopriv_wp_movie_collector_load_more', $plugin_public, 'ajax_load_more' );
	}

	/**
	 * Register custom post types and taxonomies
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_post_types() {
		$post_types = new WP_Movie_Collector_Post_Types();

		$this->loader->add_action( 'init', $post_types, 'register_post_types' );
		$this->loader->add_action( 'init', $post_types, 'register_taxonomies' );
	}

	/**
	 * Register the REST API routes.
	 *
	 * @since    1.3.0
	 * @access   private
	 */
	private function define_rest_routes() {
		$rest_controller = new WP_Movie_Collector_REST_Controller();

		$this->loader->add_action( 'rest_api_init', $rest_controller, 'register_routes' );
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
