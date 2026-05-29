<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @since      1.0.0
 * @package    WP_Movie_Collector
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

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
        wp_enqueue_style(
            'wp-movie-collector-public',
            WP_MOVIE_COLLECTOR_PLUGIN_URL . 'public/css/wp-movie-collector-public.css',
            array(),
            WP_MOVIE_COLLECTOR_VERSION,
            'all'
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        wp_enqueue_script(
            'wp-movie-collector-public',
            WP_MOVIE_COLLECTOR_PLUGIN_URL . 'public/js/wp-movie-collector-public.js',
            array( 'jquery' ),
            WP_MOVIE_COLLECTOR_VERSION,
            false
        );
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
            'orderby' => '',
            'order' => '',
        ), $atts, 'movie_collection');

        // Grid items link back to this same page with a movie_id / box_set_id
        // query arg to request a single-item detail view. Render that view when
        // requested, otherwise fall back to the collection grid.
        // Guard with is_scalar(): intval() on an array returns 1, so a crafted
        // ?movie_id[]=5 would otherwise route to item ID 1.
        $movie_id   = ( isset($_GET['movie_id']) && is_scalar($_GET['movie_id']) ) ? intval(wp_unslash($_GET['movie_id'])) : 0;
        $box_set_id = ( isset($_GET['box_set_id']) && is_scalar($_GET['box_set_id']) ) ? intval(wp_unslash($_GET['box_set_id'])) : 0;

        if ($movie_id > 0) {
            return $this->display_movie($movie_id);
        }

        if ($box_set_id > 0) {
            return $this->display_box_set($box_set_id);
        }

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
}