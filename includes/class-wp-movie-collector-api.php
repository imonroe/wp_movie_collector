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
     * The BarcodeLookup API key.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $barcode_api_key    The BarcodeLookup API key.
     */
    private $barcode_api_key;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->tmdb_api_key = get_option('wp_movie_collector_tmdb_api_key');
        $this->omdb_api_key = get_option('wp_movie_collector_omdb_api_key');
        $this->barcode_api_key = get_option('wp_movie_collector_barcode_api_key');
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
            return new WP_Error('no_api_key', __('TMDb API key is not set. Please set it in the settings page.', 'wp-movie-collector'));
        }

        // Check the cache first
        $cache_key = 'wp_movie_search_' . md5($title . '_' . $year);
        $cached_results = get_transient($cache_key);

        if (false !== $cached_results) {
            return $cached_results;
        }

        $args = array(
            'query' => $title,
        );

        if (!empty($year)) {
            $args['year'] = intval($year);
        }

        $url = add_query_arg($args, 'https://api.themoviedb.org/3/search/movie');
        $url = add_query_arg('api_key', $this->tmdb_api_key, $url);

        $response = wp_remote_get($url, array('timeout' => 15));

        if (is_wp_error($response)) {
            // If there's a connection error, try the fallback
            $fallback_results = $this->fallback_to_omdb($title, $year);

            // If fallback also fails, return the original error
            if (is_wp_error($fallback_results)) {
                return $response;
            }

            // Caching disabled for debugging purposes
            // set_transient($cache_key, $fallback_results, HOUR_IN_SECONDS * 12);

            return $fallback_results;
        }

        $response_code = wp_remote_retrieve_response_code($response);

        // If TMDb API returns an error, try the fallback
        if ($response_code !== 200) {
            $fallback_results = $this->fallback_to_omdb($title, $year);

            // Cache the fallback results if successful
            if (!is_wp_error($fallback_results)) {
                set_transient($cache_key, $fallback_results, HOUR_IN_SECONDS * 12);
            }

            return $fallback_results;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['results'])) {
            $fallback_results = $this->fallback_to_omdb($title, $year);

            // Cache the fallback results if successful
            if (!is_wp_error($fallback_results)) {
                set_transient($cache_key, $fallback_results, HOUR_IN_SECONDS * 12);
            }

            return $fallback_results;
        }

        // Caching disabled for debugging purposes
        // set_transient($cache_key, $data['results'], HOUR_IN_SECONDS * 24);

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
     * Look up movie by barcode using BarcodeLookup.com API and other sources.
     *
     * @since    1.0.0
     * @param    string    $barcode    The movie barcode.
     * @return   array|WP_Error       The movie details or error.
     */
    public function lookup_by_barcode($barcode) {
        // First, check if we have BarcodeLookup API key
        if (empty($this->barcode_api_key)) {
            return new WP_Error(
                'no_api_key',
                __('BarcodeLookup API key is not set. Please set it in the settings page.', 'wp-movie-collector')
            );
        }

        // Sanitize the barcode
        $barcode = preg_replace('/[^0-9]/', '', $barcode);

        // If barcode is empty after sanitization, return error
        if (empty($barcode)) {
            return new WP_Error('invalid_barcode', __('Invalid barcode format.', 'wp-movie-collector'));
        }

        // Caching disabled for debugging purposes
        // $cache_key = 'wp_movie_barcode_' . $barcode;
        // $cached_result = get_transient($cache_key);
        //
        // if (false !== $cached_result) {
        //     return $cached_result;
        // }

        // Make request to BarcodeLookup API
        $url = add_query_arg(array(
            'barcode' => $barcode,
            'formatted' => 'y',
            'key' => $this->barcode_api_key,
        ), 'https://api.barcodelookup.com/v3/products');
        
        error_log('BarcodeLookup API Request URL: ' . $url);

        $args = array(
            'timeout' => 15
        );

        $response = wp_remote_get($url, $args);

        // Check for WP error
        if (is_wp_error($response)) {
            error_log('BarcodeLookup API Error: ' . $response->get_error_message());
            return $response;
        }

        // Get response code
        $response_code = wp_remote_retrieve_response_code($response);
        error_log('BarcodeLookup API Response Code: ' . $response_code);

        // If response code is not 200, try OpenLibrary as fallback (for books/DVDs with ISBN)
        if ($response_code !== 200) {
            return $this->fallback_to_open_library($barcode);
        }

        // Get response body
        $body = wp_remote_retrieve_body($response);
        error_log('BarcodeLookup API Response Body: ' . $body);
        $data = json_decode($body, true);

        // Check if we got a valid response with products
        if (empty($data) || !isset($data['products']) || empty($data['products'])) {
            return $this->fallback_to_open_library($barcode);
        }

        // Get the first product
        $product = $data['products'][0];

        // Check if it's a movie or video title (check description, title, or category)
        $is_movie = false;

        // Look for keywords in title, description, or category that indicate it's a movie
        $movie_keywords = array(
            'DVD', 'Blu-ray', 'Blu ray', 'BluRay', '4K', 'UHD', 'Ultra HD',
            'movie', 'film', 'video', 'series', 'season', 'disc', 'disk',
            'box set', 'boxset', 'collection', 'trilogy', 'director', 'cut'
        );

        foreach ($movie_keywords as $keyword) {
            if (
                (isset($product['title']) && stripos($product['title'], $keyword) !== false) ||
                (isset($product['description']) && stripos($product['description'], $keyword) !== false) ||
                (isset($product['category']) && stripos($product['category'], $keyword) !== false)
            ) {
                $is_movie = true;
                break;
            }
        }

        // If it doesn't look like a movie, try to search for the title on TMDb
        if (!$is_movie) {
            if (isset($product['title'])) {
                $search_result = $this->search_movie_by_title($product['title']);

                if (!is_wp_error($search_result) && !empty($search_result)) {
                    // Get the first match
                    $movie_id = $search_result[0]['id'];
                    return $this->get_movie_details($movie_id);
                }
            }

            // If no movie found, return the barcode data as-is
            return $this->format_barcode_data($product);
        }

        // Format the data from BarcodeLookup
        $result = $this->format_barcode_data($product);

        // Caching disabled for debugging purposes
        // set_transient($cache_key, $result, DAY_IN_SECONDS * 7);

        return $result;
    }

    /**
     * Format data from BarcodeLookup API.
     *
     * @since    1.0.0
     * @param    array    $data    The raw data from BarcodeLookup.
     * @return   array            The formatted movie data.
     */
    private function format_barcode_data($data) {
        $movie = array(
            'title' => isset($data['title']) ? $data['title'] : '',
            'barcode' => isset($data['barcode']) ? $data['barcode'] : '',
            'api_source' => 'BarcodeLookup',
        );

        // Try to extract year from title if it's in parentheses
        if (isset($data['title']) && preg_match('/\((\d{4})\)/', $data['title'], $matches)) {
            $movie['release_year'] = $matches[1];

            // Remove the year from the title
            $movie['title'] = trim(str_replace('(' . $matches[1] . ')', '', $data['title']));
        }

        // Extract description if available
        if (isset($data['description'])) {
            $movie['description'] = $data['description'];
        }

        // Extract cover image URL if available
        if (isset($data['images']) && !empty($data['images'])) {
            $movie['cover_image_url'] = $data['images'][0];
        }

        // Try to extract studio/publisher
        if (isset($data['brand']) || isset($data['manufacturer'])) {
            $movie['studio'] = isset($data['brand']) ? $data['brand'] : $data['manufacturer'];
        }

        // Try to extract genre from category
        if (isset($data['category'])) {
            $movie['genre'] = $data['category'];
        }

        return $movie;
    }

    /**
     * Fallback to Open Library for ISBN lookup.
     *
     * @since    1.0.0
     * @param    string    $barcode    The barcode/ISBN.
     * @return   array|WP_Error       The movie/book details or error.
     */
    private function fallback_to_open_library($barcode) {
        // Try looking up as ISBN (for books/DVDs that might have ISBN)
        $url = "https://openlibrary.org/api/books?bibkeys=ISBN:{$barcode}&format=json&jscmd=data";
        error_log('OpenLibrary API Request URL: ' . $url);

        $response = wp_remote_get($url);

        // Check for WP error
        if (is_wp_error($response)) {
            error_log('OpenLibrary API Error: ' . $response->get_error_message());
            return $response;
        }

        // Get response code
        $response_code = wp_remote_retrieve_response_code($response);
        error_log('OpenLibrary API Response Code: ' . $response_code);

        // If response code is not 200, return error
        if ($response_code !== 200) {
            return new WP_Error(
                'api_error',
                __('No product found with this barcode.', 'wp-movie-collector')
            );
        }

        // Get response body
        $body = wp_remote_retrieve_body($response);
        error_log('OpenLibrary API Response Body: ' . $body);
        $data = json_decode($body, true);

        // Check if we got a valid response
        if (empty($data) || empty($data["ISBN:{$barcode}"])) {
            // Last resort - search for an exact barcode match on TMDb
            return $this->search_tmdb_by_external_id($barcode);
        }

        // Format the data from Open Library
        $book_data = $data["ISBN:{$barcode}"];

        $movie = array(
            'title' => $book_data['title'],
            'barcode' => $barcode,
            'api_source' => 'Open Library',
        );

        // Try to extract year from publish date
        if (isset($book_data['publish_date']) && preg_match('/(\d{4})/', $book_data['publish_date'], $matches)) {
            $movie['release_year'] = $matches[1];
        }

        // Extract cover image URL if available
        if (isset($book_data['cover']) && isset($book_data['cover']['large'])) {
            $movie['cover_image_url'] = $book_data['cover']['large'];
        }

        // Extract description if available
        if (isset($book_data['description'])) {
            if (is_array($book_data['description'])) {
                $movie['description'] = isset($book_data['description']['value'])
                    ? $book_data['description']['value']
                    : '';
            } else {
                $movie['description'] = $book_data['description'];
            }
        }

        // Extract authors
        if (isset($book_data['authors']) && is_array($book_data['authors'])) {
            $authors = array();
            foreach ($book_data['authors'] as $author) {
                $authors[] = $author['name'];
            }
            $movie['director'] = implode(', ', $authors);
        }

        // Extract publisher as studio
        if (isset($book_data['publishers']) && is_array($book_data['publishers'])) {
            $publishers = array();
            foreach ($book_data['publishers'] as $publisher) {
                $publishers[] = $publisher['name'];
            }
            $movie['studio'] = implode(', ', $publishers);
        }

        // Caching disabled for debugging purposes
        // set_transient('wp_movie_barcode_' . $barcode, $movie, DAY_IN_SECONDS * 7);

        return $movie;
    }

    /**
     * Search TMDb by external ID (last resort for barcode lookup).
     *
     * @since    1.0.0
     * @param    string    $external_id    The external ID (barcode/UPC/EAN).
     * @return   array|WP_Error           The movie details or error.
     */
    private function search_tmdb_by_external_id($external_id) {
        if (empty($this->tmdb_api_key)) {
            return new WP_Error('no_api_key', __('TMDb API key is not set.', 'wp-movie-collector'));
        }

        // We'll try to search by external IDs supported by TMDb
        $external_sources = array('imdb_id', 'freebase_mid', 'freebase_id', 'tvdb_id', 'tvrage_id');

        foreach ($external_sources as $source) {
            $url = "https://api.themoviedb.org/3/find/{$external_id}?api_key={$this->tmdb_api_key}&external_source={$source}";
            error_log('TMDb External ID API Request URL: ' . $url);

            $response = wp_remote_get($url);

            if (is_wp_error($response)) {
                error_log('TMDb External ID API Error: ' . $response->get_error_message());
                continue;
            }

            $body = wp_remote_retrieve_body($response);
            error_log('TMDb External ID API Response Body: ' . $body);
            $data = json_decode($body, true);

            // Check if we got movie results
            if (!empty($data['movie_results'])) {
                $movie_id = $data['movie_results'][0]['id'];
                return $this->get_movie_details($movie_id);
            }

            // Check if we got TV results
            if (!empty($data['tv_results'])) {
                $tv_data = $data['tv_results'][0];

                $movie = array(
                    'title' => $tv_data['name'],
                    'release_year' => substr($tv_data['first_air_date'], 0, 4),
                    'description' => $tv_data['overview'],
                    'cover_image_url' => !empty($tv_data['poster_path'])
                        ? 'https://image.tmdb.org/t/p/w500' . $tv_data['poster_path']
                        : '',
                    'barcode' => $external_id,
                    'api_source' => 'TMDb',
                );

                return $movie;
            }
        }

        // If we get here, we couldn't find anything
        $error = new WP_Error(
            'not_found',
            __('No movie found with this barcode.', 'wp-movie-collector')
        );

        // Caching disabled for debugging purposes
        // set_transient('wp_movie_barcode_' . $external_id, $error, HOUR_IN_SECONDS);

        return $error;
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
