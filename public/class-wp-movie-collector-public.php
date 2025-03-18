<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    WP_Movie_Collector
 */

class WP_Movie_Collector_Public {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style('wp-movie-collector-public', WP_MOVIE_COLLECTOR_PLUGIN_URL . 'public/css/wp-movie-collector-public.css', array(), WP_MOVIE_COLLECTOR_VERSION, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script('wp-movie-collector-public', WP_MOVIE_COLLECTOR_PLUGIN_URL . 'public/js/wp-movie-collector-public.js', array('jquery'), WP_MOVIE_COLLECTOR_VERSION, false);
        
        // Localize the script with new data
        $localize_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_movie_collector_public_nonce'),
        );
        wp_localize_script('wp-movie-collector-public', 'wp_movie_collector_public', $localize_data);
    }

    /**
     * Register shortcodes.
     *
     * @since    1.0.0
     */
    public function register_shortcodes() {
        add_shortcode('movie_collection', array($this, 'movie_collection_shortcode'));
    }

    /**
     * The [movie_collection] shortcode.
     *
     * Displays the movie collection with search and filtering options.
     *
     * @since    1.0.0
     * @param    array    $atts    The shortcode attributes.
     * @return   string            The shortcode output.
     */
    public function movie_collection_shortcode($atts) {
        // Parse attributes
        $atts = shortcode_atts(array(
            'type' => 'all', // all, movies, box_sets
            'per_page' => 12,
        ), $atts, 'movie_collection');

        // Start output buffering
        ob_start();

        // Include the template
        include WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'public/partials/wp-movie-collector-public-display.php';

        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * Display a single movie.
     *
     * @since    1.0.0
     * @param    int      $movie_id    The movie ID.
     * @return   string                The HTML output.
     */
    public function display_movie($movie_id) {
        // Get the movie data
        $db = new WP_Movie_Collector_DB();
        $movie = $db->get_movie($movie_id);

        if (!$movie) {
            return '<p>' . __('Movie not found.', 'wp-movie-collector') . '</p>';
        }

        // Get box sets that contain this movie
        $box_sets = $db->get_box_sets_containing_movie($movie_id);

        // Start output buffering
        ob_start();

        // Include the template
        include WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'public/partials/wp-movie-collector-public-movie.php';

        // Return the buffered content
        return ob_get_clean();
    }

    /**
     * Display a single box set.
     *
     * @since    1.0.0
     * @param    int      $box_set_id    The box set ID.
     * @return   string                  The HTML output.
     */
    public function display_box_set($box_set_id) {
        // Get the box set data
        $db = new WP_Movie_Collector_DB();
        $box_set = $db->get_box_set($box_set_id);

        if (!$box_set) {
            return '<p>' . __('Box set not found.', 'wp-movie-collector') . '</p>';
        }

        // Get movies in this box set
        $movies = $db->get_movies_in_box_set($box_set_id);

        // Start output buffering
        ob_start();

        // Include the template
        include WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'public/partials/wp-movie-collector-public-box-set.php';

        // Return the buffered content
        return ob_get_clean();
    }
    
    /**
     * AJAX handler for loading more movies/box sets.
     *
     * @since    1.0.0
     */
    public function ajax_load_more() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_movie_collector_public_nonce')) {
            wp_send_json_error(__('Security check failed.', 'wp-movie-collector'));
        }

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $args = isset($_POST['args']) ? $_POST['args'] : array();

        // Sanitize args
        $sanitized_args = array();
        if (is_array($args)) {
            foreach ($args as $key => $value) {
                $sanitized_args[sanitize_key($key)] = sanitize_text_field($value);
            }
        }

        // Set defaults
        $type = isset($sanitized_args['type']) ? $sanitized_args['type'] : 'all';
        $per_page = isset($sanitized_args['per_page']) ? intval($sanitized_args['per_page']) : 12;

        // Get the results
        $db = new WP_Movie_Collector_DB();
        $results = array();
        $total_items = 0;

        // Build search criteria
        $criteria = array(
            'per_page' => $per_page,
            'page' => $page,
            'orderby' => isset($sanitized_args['orderby']) ? $sanitized_args['orderby'] : 'title',
            'order' => isset($sanitized_args['order']) ? $sanitized_args['order'] : 'ASC',
        );

        // Add any filter criteria from args
        foreach (array('title', 'format', 'genre', 'year', 'director', 'studio') as $filter) {
            if (!empty($sanitized_args[$filter])) {
                $criteria[$filter] = $sanitized_args[$filter];
            }
        }

        if ($type === 'movies' || $type === 'all') {
            $results['movies'] = $db->search_movies($criteria);
            $total_items += count($results['movies']);
        }

        if ($type === 'box_sets' || $type === 'all') {
            $results['box_sets'] = $db->search_box_sets($criteria);
            $total_items += count($results['box_sets']);
        }

        // Generate HTML for the items
        ob_start();
        
        if ($type === 'movies' || $type === 'all') {
            foreach ($results['movies'] as $movie) {
                include WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'public/partials/wp-movie-collector-public-movie-item.php';
            }
        }
        
        if ($type === 'box_sets' || $type === 'all') {
            foreach ($results['box_sets'] as $box_set) {
                include WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'public/partials/wp-movie-collector-public-box-set-item.php';
            }
        }
        
        $html = ob_get_clean();

        // Calculate if there are more items to load
        $criteria['page'] = $page + 1;
        $next_page_results = array();
        $has_more = false;

        if ($type === 'movies' || $type === 'all') {
            $next_page_results['movies'] = $db->search_movies($criteria);
            if (!empty($next_page_results['movies'])) {
                $has_more = true;
            }
        }

        if (!$has_more && ($type === 'box_sets' || $type === 'all')) {
            $next_page_results['box_sets'] = $db->search_box_sets($criteria);
            if (!empty($next_page_results['box_sets'])) {
                $has_more = true;
            }
        }

        wp_send_json_success(array(
            'html' => $html,
            'has_more' => $has_more,
            'page' => $page,
        ));
    }
}