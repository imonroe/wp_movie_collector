<?php
/**
 * API integration for fetching movie metadata.
 *
 * @since      1.0.0
 * @package    WP_Movie_Collector
 */

class WP_Movie_Collector_API {

    /**
     * The TMDb API key.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $tmdb_api_key    The TMDb API key.
     */
    private $tmdb_api_key;

    /**
     * The OMDb API key.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $omdb_api_key    The OMDb API key.
     */
    private $omdb_api_key;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->tmdb_api_key = get_option('wp_movie_collector_tmdb_api_key');
        $this->omdb_api_key = get_option('wp_movie_collector_omdb_api_key');
    }

    /**
     * Search for a movie by title using TMDb API.
     *
     * @since    1.0.0
     * @param    string    $title    The movie title to search for.
     * @param    int       $year     Optional. The release year to filter by.
     * @return   array|WP_Error     The search results or error.
     */
    public function search_movie_by_title($title, $year = null) {
        if (empty($this->tmdb_api_key)) {
            return new WP_Error('no_api_key', __('TMDb API key is not set.', 'wp-movie-collector'));
        }

        $args = array(
            'query' => $title,
        );

        if (!empty($year)) {
            $args['year'] = intval($year);
        }

        $url = add_query_arg($args, 'https://api.themoviedb.org/3/search/movie');
        $url = add_query_arg('api_key', $this->tmdb_api_key, $url);

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['results'])) {
            return $this->fallback_to_omdb($title, $year);
        }

        return $data['results'];
    }

    /**
     * Fallback to OMDb API if TMDb fails.
     *
     * @since    1.0.0
     * @param    string    $title    The movie title to search for.
     * @param    int       $year     Optional. The release year to filter by.
     * @return   array|WP_Error     The search results or error.
     */
    private function fallback_to_omdb($title, $year = null) {
        if (empty($this->omdb_api_key)) {
            return new WP_Error('no_api_key', __('OMDb API key is not set.', 'wp-movie-collector'));
        }

        $args = array(
            's' => $title,
            'apikey' => $this->omdb_api_key,
        );

        if (!empty($year)) {
            $args['y'] = intval($year);
        }

        $url = add_query_arg($args, 'https://www.omdbapi.com/');

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['Response']) && $data['Response'] === 'False') {
            return new WP_Error('no_results', $data['Error']);
        }

        return isset($data['Search']) ? $data['Search'] : array();
    }

    /**
     * Get detailed movie information from TMDb.
     *
     * @since    1.0.0
     * @param    int       $tmdb_id    The TMDb movie ID.
     * @return   array|WP_Error       The movie details or error.
     */
    public function get_movie_details($tmdb_id) {
        if (empty($this->tmdb_api_key)) {
            return new WP_Error('no_api_key', __('TMDb API key is not set.', 'wp-movie-collector'));
        }

        $url = "https://api.themoviedb.org/3/movie/{$tmdb_id}?api_key={$this->tmdb_api_key}&append_to_response=credits,images";

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data) || isset($data['success']) && $data['success'] === false) {
            return new WP_Error('api_error', __('Failed to retrieve movie details.', 'wp-movie-collector'));
        }

        return $this->format_movie_data($data);
    }

    /**
     * Format movie data from TMDb API.
     *
     * @since    1.0.0
     * @param    array    $data    The raw movie data from TMDb.
     * @return   array            The formatted movie data.
     */
    private function format_movie_data($data) {
        $movie = array(
            'title' => $data['title'],
            'release_year' => substr($data['release_date'], 0, 4),
            'description' => $data['overview'],
            'cover_image_url' => !empty($data['poster_path']) ? 'https://image.tmdb.org/t/p/w500' . $data['poster_path'] : '',
            'api_source' => 'TMDb',
        );

        // Extract directors from credits
        if (!empty($data['credits']['crew'])) {
            $directors = array();
            foreach ($data['credits']['crew'] as $crew) {
                if ($crew['job'] === 'Director') {
                    $directors[] = $crew['name'];
                }
            }
            $movie['director'] = implode(', ', $directors);
        }

        // Extract actors from credits
        if (!empty($data['credits']['cast'])) {
            $actors = array();
            $count = 0;
            foreach ($data['credits']['cast'] as $cast) {
                if ($count < 10) { // Limit to top 10 cast members
                    $actors[] = $cast['name'];
                    $count++;
                } else {
                    break;
                }
            }
            $movie['actors'] = implode(', ', $actors);
        }

        // Extract genres
        if (!empty($data['genres'])) {
            $genres = array();
            foreach ($data['genres'] as $genre) {
                $genres[] = $genre['name'];
            }
            $movie['genre'] = implode(', ', $genres);
        }

        // Extract production companies as studios
        if (!empty($data['production_companies'])) {
            $studios = array();
            foreach ($data['production_companies'] as $company) {
                $studios[] = $company['name'];
            }
            $movie['studio'] = implode(', ', $studios);
        }

        return $movie;
    }

    /**
     * Look up movie by barcode using external API.
     *
     * @since    1.0.0
     * @param    string    $barcode    The movie barcode.
     * @return   array|WP_Error       The movie details or error.
     */
    public function lookup_by_barcode($barcode) {
        // This is a placeholder for barcode lookup functionality
        // In a real implementation, you would use a barcode lookup service
        
        // For now, we'll return a fake response
        return new WP_Error('not_implemented', __('Barcode lookup is not implemented yet.', 'wp-movie-collector'));
    }

    /**
     * Get movie details from OMDb by IMDb ID.
     *
     * @since    1.0.0
     * @param    string    $imdb_id    The IMDb ID.
     * @return   array|WP_Error       The movie details or error.
     */
    public function get_movie_details_by_imdb($imdb_id) {
        if (empty($this->omdb_api_key)) {
            return new WP_Error('no_api_key', __('OMDb API key is not set.', 'wp-movie-collector'));
        }

        $url = add_query_arg(array(
            'i' => $imdb_id,
            'apikey' => $this->omdb_api_key,
            'plot' => 'full',
        ), 'https://www.omdbapi.com/');

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return $response;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['Response']) && $data['Response'] === 'False') {
            return new WP_Error('api_error', $data['Error']);
        }

        return $this->format_omdb_movie_data($data);
    }

    /**
     * Format movie data from OMDb API.
     *
     * @since    1.0.0
     * @param    array    $data    The raw movie data from OMDb.
     * @return   array            The formatted movie data.
     */
    private function format_omdb_movie_data($data) {
        $movie = array(
            'title' => $data['Title'],
            'release_year' => $data['Year'],
            'director' => $data['Director'],
            'actors' => $data['Actors'],
            'description' => $data['Plot'],
            'cover_image_url' => $data['Poster'] !== 'N/A' ? $data['Poster'] : '',
            'genre' => $data['Genre'],
            'api_source' => 'OMDb',
        );

        // Add studio if available (OMDb calls it Production)
        if (isset($data['Production']) && $data['Production'] !== 'N/A') {
            $movie['studio'] = $data['Production'];
        }

        return $movie;
    }
}