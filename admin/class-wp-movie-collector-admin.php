<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    WP_Movie_Collector
 */

class WP_Movie_Collector_Admin {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style('wp-movie-collector-admin', WP_MOVIE_COLLECTOR_PLUGIN_URL . 'admin/css/wp-movie-collector-admin.css', array(), WP_MOVIE_COLLECTOR_VERSION, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script('wp-movie-collector-admin', WP_MOVIE_COLLECTOR_PLUGIN_URL . 'admin/js/wp-movie-collector-admin.js', array('jquery'), WP_MOVIE_COLLECTOR_VERSION, false);
        
        // Localize the script with new data
        $localize_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_movie_collector_nonce'),
        );
        wp_localize_script('wp-movie-collector-admin', 'wp_movie_collector_admin', $localize_data);
    }

    /**
     * Add plugin admin menu.
     *
     * @since    1.0.0
     */
    public function add_plugin_admin_menu() {
        // Main menu item
        add_menu_page(
            __('Movie Collection', 'wp-movie-collector'),
            __('Movies', 'wp-movie-collector'),
            'manage_options',
            'wp-movie-collector',
            array($this, 'display_plugin_admin_dashboard'),
            'dashicons-video-alt2',
            20
        );
        
        // Movies submenu
        add_submenu_page(
            'wp-movie-collector',
            __('All Movies', 'wp-movie-collector'),
            __('All Movies', 'wp-movie-collector'),
            'manage_options',
            'wp-movie-collector',
            array($this, 'display_plugin_admin_dashboard')
        );
        
        // Add New Movie submenu
        add_submenu_page(
            'wp-movie-collector',
            __('Add New Movie', 'wp-movie-collector'),
            __('Add New Movie', 'wp-movie-collector'),
            'manage_options',
            'wp-movie-collector-add-movie',
            array($this, 'display_plugin_admin_add_movie')
        );
        
        // Box Sets submenu
        add_submenu_page(
            'wp-movie-collector',
            __('Box Sets', 'wp-movie-collector'),
            __('Box Sets', 'wp-movie-collector'),
            'manage_options',
            'wp-movie-collector-box-sets',
            array($this, 'display_plugin_admin_box_sets')
        );
        
        // Add New Box Set submenu
        add_submenu_page(
            'wp-movie-collector',
            __('Add New Box Set', 'wp-movie-collector'),
            __('Add New Box Set', 'wp-movie-collector'),
            'manage_options',
            'wp-movie-collector-add-box-set',
            array($this, 'display_plugin_admin_add_box_set')
        );
        
        // Import/Export submenu
        add_submenu_page(
            'wp-movie-collector',
            __('Import/Export', 'wp-movie-collector'),
            __('Import/Export', 'wp-movie-collector'),
            'manage_options',
            'wp-movie-collector-import-export',
            array($this, 'display_plugin_admin_import_export')
        );
        
        // Settings submenu
        add_submenu_page(
            'wp-movie-collector',
            __('Settings', 'wp-movie-collector'),
            __('Settings', 'wp-movie-collector'),
            'manage_options',
            'wp-movie-collector-settings',
            array($this, 'display_plugin_admin_settings')
        );
    }

    /**
     * Display the admin dashboard.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_dashboard() {
        include_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'admin/partials/wp-movie-collector-admin-dashboard.php';
    }

    /**
     * Display the add movie page.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_add_movie() {
        include_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'admin/partials/wp-movie-collector-admin-add-movie.php';
    }

    /**
     * Display the box sets page.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_box_sets() {
        include_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'admin/partials/wp-movie-collector-admin-box-sets.php';
    }

    /**
     * Display the add box set page.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_add_box_set() {
        include_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'admin/partials/wp-movie-collector-admin-add-box-set.php';
    }

    /**
     * Display the import/export page.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_import_export() {
        include_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'admin/partials/wp-movie-collector-admin-import-export.php';
    }

    /**
     * Display the settings page.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_settings() {
        include_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'admin/partials/wp-movie-collector-admin-settings.php';
    }
    
    /**
     * Register admin settings.
     *
     * @since    1.0.0
     */
    public function register_settings() {
        register_setting('wp_movie_collector_settings', 'wp_movie_collector_tmdb_api_key');
        register_setting('wp_movie_collector_settings', 'wp_movie_collector_omdb_api_key');
        
        add_settings_section(
            'wp_movie_collector_api_settings',
            __('API Settings', 'wp-movie-collector'),
            array($this, 'settings_section_callback'),
            'wp_movie_collector_settings'
        );
        
        add_settings_field(
            'wp_movie_collector_tmdb_api_key',
            __('TMDb API Key', 'wp-movie-collector'),
            array($this, 'tmdb_api_key_callback'),
            'wp_movie_collector_settings',
            'wp_movie_collector_api_settings'
        );
        
        add_settings_field(
            'wp_movie_collector_omdb_api_key',
            __('OMDb API Key', 'wp-movie-collector'),
            array($this, 'omdb_api_key_callback'),
            'wp_movie_collector_settings',
            'wp_movie_collector_api_settings'
        );
    }
    
    /**
     * Settings section callback.
     *
     * @since    1.0.0
     */
    public function settings_section_callback() {
        echo '<p>' . __('Enter your API keys for movie data services.', 'wp-movie-collector') . '</p>';
    }
    
    /**
     * TMDb API key field callback.
     *
     * @since    1.0.0
     */
    public function tmdb_api_key_callback() {
        $api_key = get_option('wp_movie_collector_tmdb_api_key');
        echo '<input type="text" name="wp_movie_collector_tmdb_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">' . __('Get your API key from <a href="https://www.themoviedb.org/settings/api" target="_blank">TMDb</a>.', 'wp-movie-collector') . '</p>';
    }
    
    /**
     * OMDb API key field callback.
     *
     * @since    1.0.0
     */
    public function omdb_api_key_callback() {
        $api_key = get_option('wp_movie_collector_omdb_api_key');
        echo '<input type="text" name="wp_movie_collector_omdb_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">' . __('Get your API key from <a href="https://www.omdbapi.com/apikey.aspx" target="_blank">OMDb</a>.', 'wp-movie-collector') . '</p>';
    }

    /**
     * AJAX handler for barcode lookup.
     *
     * @since    1.0.0
     */
    public function ajax_barcode_lookup() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_movie_collector_nonce')) {
            wp_send_json_error(__('Security check failed.', 'wp-movie-collector'));
        }

        // Check if barcode is provided
        if (empty($_POST['barcode'])) {
            wp_send_json_error(__('No barcode provided.', 'wp-movie-collector'));
        }

        $barcode = sanitize_text_field($_POST['barcode']);

        // First check if we already have this barcode in our database
        $db = new WP_Movie_Collector_DB();
        $movie = $db->get_movie_by_barcode($barcode);

        if ($movie) {
            wp_send_json_success($movie);
        }

        // If not in our database, try to look it up via API
        $api = new WP_Movie_Collector_API();
        $result = $api->lookup_by_barcode($barcode);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX handler for movie search.
     *
     * @since    1.0.0
     */
    public function ajax_movie_search() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_movie_collector_nonce')) {
            wp_send_json_error(__('Security check failed.', 'wp-movie-collector'));
        }

        // Check if title is provided
        if (empty($_POST['title'])) {
            wp_send_json_error(__('No title provided.', 'wp-movie-collector'));
        }

        $title = sanitize_text_field($_POST['title']);
        $year = isset($_POST['year']) ? intval($_POST['year']) : null;

        // Search for movie via API
        $api = new WP_Movie_Collector_API();
        $results = $api->search_movie_by_title($title, $year);

        if (is_wp_error($results)) {
            wp_send_json_error($results->get_error_message());
        }

        wp_send_json_success($results);
    }

    /**
     * AJAX handler for getting movie details.
     *
     * @since    1.0.0
     */
    public function ajax_get_movie_details() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_movie_collector_nonce')) {
            wp_send_json_error(__('Security check failed.', 'wp-movie-collector'));
        }

        // Check if movie_id is provided
        if (empty($_POST['movie_id'])) {
            wp_send_json_error(__('No movie ID provided.', 'wp-movie-collector'));
        }

        $movie_id = intval($_POST['movie_id']);

        // Get movie details via API
        $api = new WP_Movie_Collector_API();
        $movie = $api->get_movie_details($movie_id);

        if (is_wp_error($movie)) {
            wp_send_json_error($movie->get_error_message());
        }

        wp_send_json_success($movie);
    }
}