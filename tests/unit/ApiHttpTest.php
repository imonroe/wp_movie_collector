<?php
/**
 * Unit tests for API HTTP integration with mocked wp_remote_get.
 *
 * Exercises the orchestration logic of WP_Movie_Collector_API end-to-end
 * by queueing canned responses through the wp_remote_get polyfill in
 * tests/unit/bootstrap.php. Covers the public methods' happy paths, the
 * TMDb -> OMDb fallback chain, the BarcodeLookup -> Open Library
 * fallback chain, error normalization, and cache hit behavior.
 *
 * Completes the HTTP-mocked portion of issue #17 that was deferred from
 * PRs #43 (formatter tests) and #45 (validation tests).
 *
 * @package WP_Movie_Collector\Tests\Unit
 */

namespace WP_Movie_Collector\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WP_Error;
use WP_Movie_Collector_API;

/**
 * API HTTP integration unit tests.
 */
class ApiHttpTest extends TestCase {

	/**
	 * Reset all in-memory polyfill stores before each test.
	 *
	 * Also disables API_Client retries via a filter override so that
	 * failure-path tests do not sleep through exponential backoff.
	 */
	protected function setUp(): void {
		parent::setUp();

		$GLOBALS['wp_movie_test_options']    = array();
		$GLOBALS['wp_movie_test_transients'] = array();
		$GLOBALS['wp_movie_test_http_queue'] = array();
		$GLOBALS['wp_movie_test_http_log']   = array();
		$GLOBALS['wp_movie_test_filters']    = array(
			'wp_movie_collector_api_retry_enabled' => false,
		);
	}

	// ------------------------------------------------------------------
	// Helpers
	// ------------------------------------------------------------------

	/**
	 * Build an API instance with the given API keys configured.
	 *
	 * @param array<string, string> $keys Option name => value pairs.
	 * @return WP_Movie_Collector_API
	 */
	private function api_with_keys( array $keys = array() ): WP_Movie_Collector_API {
		foreach ( $keys as $option => $value ) {
			$GLOBALS['wp_movie_test_options'][ $option ] = $value;
		}
		return new WP_Movie_Collector_API();
	}

	/**
	 * Push a canned wp_remote_get response onto the queue.
	 *
	 * @param int    $code HTTP status code.
	 * @param array  $body Response body as a structure that will be JSON-encoded.
	 * @param array  $headers Optional response headers.
	 */
	private function queue_response( int $code, array $body = array(), array $headers = array() ): void {
		$GLOBALS['wp_movie_test_http_queue'][] = array(
			'response' => array( 'code' => $code ),
			'body'     => wp_json_encode( $body ),
			'headers'  => $headers,
		);
	}

	/**
	 * Push a WP_Error onto the response queue.
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 */
	private function queue_wp_error( string $code, string $message = 'mocked' ): void {
		$GLOBALS['wp_movie_test_http_queue'][] = new WP_Error( $code, $message );
	}

	// ------------------------------------------------------------------
	// search_movie_by_title — happy path & fallback chain
	// ------------------------------------------------------------------

	/**
	 * Test that a successful TMDb search returns the results array.
	 */
	public function test_search_movie_by_title_tmdb_success(): void {
		$results = array(
			array(
				'id'           => 1091,
				'title'        => 'The Thing',
				'release_date' => '1982-06-25',
			),
		);
		$this->queue_response( 200, array( 'results' => $results ) );

		$api    = $this->api_with_keys( array( 'wp_movie_collector_tmdb_api_key' => 'tmdb-key' ) );
		$result = $api->search_movie_by_title( 'The Thing', 1982 );

		$this->assertSame( $results, $result );
		$this->assertCount( 1, $GLOBALS['wp_movie_test_http_log'] );
		$this->assertStringContainsString( 'api.themoviedb.org', $GLOBALS['wp_movie_test_http_log'][0] );
	}

	/**
	 * Test that a TMDb non-200 response triggers the OMDb fallback.
	 */
	public function test_search_movie_by_title_tmdb_404_falls_back_to_omdb(): void {
		$this->queue_response( 404, array( 'status_message' => 'Not found' ) );
		$omdb_results = array( array( 'Title' => 'The Thing', 'Year' => '1982' ) );
		$this->queue_response( 200, array( 'Search' => $omdb_results ) );

		$api    = $this->api_with_keys(
			array(
				'wp_movie_collector_tmdb_api_key' => 'tmdb-key',
				'wp_movie_collector_omdb_api_key' => 'omdb-key',
			)
		);
		$result = $api->search_movie_by_title( 'The Thing' );

		$this->assertSame( $omdb_results, $result );
		$this->assertCount( 2, $GLOBALS['wp_movie_test_http_log'] );
		$this->assertStringContainsString( 'omdbapi.com', $GLOBALS['wp_movie_test_http_log'][1] );
	}

	/**
	 * Test that an empty TMDb results array also triggers the OMDb fallback.
	 */
	public function test_search_movie_by_title_empty_tmdb_falls_back_to_omdb(): void {
		$this->queue_response( 200, array( 'results' => array() ) );
		$omdb_results = array( array( 'Title' => 'The Thing' ) );
		$this->queue_response( 200, array( 'Search' => $omdb_results ) );

		$api    = $this->api_with_keys(
			array(
				'wp_movie_collector_tmdb_api_key' => 'tmdb-key',
				'wp_movie_collector_omdb_api_key' => 'omdb-key',
			)
		);
		$result = $api->search_movie_by_title( 'The Thing' );

		$this->assertSame( $omdb_results, $result );
	}

	/**
	 * Test that a TMDb wp_remote_get error returns the original error
	 * when the OMDb fallback also fails (no OMDb key configured).
	 */
	public function test_search_movie_by_title_tmdb_error_no_omdb_key_returns_original(): void {
		$this->queue_wp_error( 'http_request_failed', 'connection refused' );

		$api    = $this->api_with_keys( array( 'wp_movie_collector_tmdb_api_key' => 'tmdb-key' ) );
		$result = $api->search_movie_by_title( 'The Thing' );

		$this->assertInstanceOf( WP_Error::class, $result );
		// API_Client wraps the underlying error; the original message
		// surfaces in the returned WP_Error's message.
		$this->assertSame( 'connection refused', $result->get_error_message() );
	}

	// ------------------------------------------------------------------
	// get_movie_details — happy path & error
	// ------------------------------------------------------------------

	/**
	 * Test that a successful get_movie_details call returns formatted data.
	 */
	public function test_get_movie_details_success(): void {
		$this->queue_response(
			200,
			array(
				'title'        => 'The Thing',
				'release_date' => '1982-06-25',
				'overview'     => 'A shape-shifting alien.',
				'poster_path'  => '/abc.jpg',
				'genres'       => array( array( 'name' => 'Horror' ) ),
				'credits'      => array(
					'crew' => array( array( 'job' => 'Director', 'name' => 'John Carpenter' ) ),
					'cast' => array( array( 'name' => 'Kurt Russell' ) ),
				),
			)
		);

		$api    = $this->api_with_keys( array( 'wp_movie_collector_tmdb_api_key' => 'tmdb-key' ) );
		$result = $api->get_movie_details( 1091 );

		$this->assertSame( 'The Thing', $result['title'] );
		$this->assertSame( '1982', $result['release_year'] );
		$this->assertSame( 'John Carpenter', $result['director'] );
		$this->assertSame( 'Kurt Russell', $result['actors'] );
		$this->assertSame( 'Horror', $result['genre'] );
		$this->assertSame( 'TMDb', $result['api_source'] );
	}

	/**
	 * Test that a TMDb success-false body returns api_error.
	 */
	public function test_get_movie_details_tmdb_success_false_returns_api_error(): void {
		$this->queue_response( 200, array( 'success' => false, 'status_message' => 'Not found' ) );

		$api    = $this->api_with_keys( array( 'wp_movie_collector_tmdb_api_key' => 'tmdb-key' ) );
		$result = $api->get_movie_details( 999999 );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'api_error', $result->get_error_code() );
	}

	/**
	 * Test that get_movie_details caches its result (second call hits no HTTP).
	 */
	public function test_get_movie_details_caches_response(): void {
		$this->queue_response(
			200,
			array(
				'title'        => 'The Thing',
				'release_date' => '1982-06-25',
				'overview'     => '',
				'poster_path'  => '',
			)
		);

		$api = $this->api_with_keys( array( 'wp_movie_collector_tmdb_api_key' => 'tmdb-key' ) );

		$first  = $api->get_movie_details( 1091 );
		$second = $api->get_movie_details( 1091 );

		$this->assertSame( $first, $second );
		$this->assertCount( 1, $GLOBALS['wp_movie_test_http_log'], 'Second call should be served from cache.' );
	}

	// ------------------------------------------------------------------
	// get_movie_details_by_imdb — happy path & error
	// ------------------------------------------------------------------

	/**
	 * Test that a successful OMDb-by-IMDb-id call returns formatted data.
	 */
	public function test_get_movie_details_by_imdb_success(): void {
		$this->queue_response(
			200,
			array(
				'Title'    => 'The Thing',
				'Year'     => '1982',
				'Director' => 'John Carpenter',
				'Actors'   => 'Kurt Russell',
				'Plot'     => 'A shape-shifting alien.',
				'Poster'   => 'https://example.com/p.jpg',
				'Genre'    => 'Horror',
			)
		);

		$api    = $this->api_with_keys( array( 'wp_movie_collector_omdb_api_key' => 'omdb-key' ) );
		$result = $api->get_movie_details_by_imdb( 'tt0084787' );

		$this->assertSame( 'The Thing', $result['title'] );
		$this->assertSame( 'John Carpenter', $result['director'] );
		$this->assertSame( 'OMDb', $result['api_source'] );
	}

	/**
	 * Test that an OMDb Response:False body returns api_error with the
	 * provider's error message preserved.
	 */
	public function test_get_movie_details_by_imdb_response_false_returns_api_error(): void {
		$this->queue_response(
			200,
			array( 'Response' => 'False', 'Error' => 'Movie not found!' )
		);

		$api    = $this->api_with_keys( array( 'wp_movie_collector_omdb_api_key' => 'omdb-key' ) );
		$result = $api->get_movie_details_by_imdb( 'tt9999999' );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'api_error', $result->get_error_code() );
		$this->assertSame( 'Movie not found!', $result->get_error_message() );
	}

	// ------------------------------------------------------------------
	// lookup_by_barcode — fallback chain
	// ------------------------------------------------------------------

	/**
	 * Test that a BarcodeLookup hit on a movie-keyword product returns
	 * the formatted barcode data without any fallback queries.
	 */
	public function test_lookup_by_barcode_movie_product_success(): void {
		$this->queue_response(
			200,
			array(
				'products' => array(
					array(
						'title'       => 'The Thing (1982)',
						'barcode'     => '025192110825',
						'description' => 'Horror classic on Blu-ray.',
						'images'      => array( 'https://example.com/thing.jpg' ),
						'brand'       => 'Universal',
						'category'    => 'Movies > Horror',
					),
				),
			)
		);

		$api    = $this->api_with_keys( array( 'wp_movie_collector_barcode_api_key' => 'barcode-key' ) );
		$result = $api->lookup_by_barcode( '025192110825' );

		$this->assertSame( 'The Thing', $result['title'] );
		$this->assertSame( '1982', $result['release_year'] );
		$this->assertSame( '025192110825', $result['barcode'] );
		$this->assertSame( 'Universal', $result['studio'] );
		$this->assertSame( 'BarcodeLookup', $result['api_source'] );
		$this->assertCount( 1, $GLOBALS['wp_movie_test_http_log'], 'Movie-keyword product should not trigger fallback.' );
	}

	/**
	 * Test that a BarcodeLookup non-200 falls back to Open Library.
	 */
	public function test_lookup_by_barcode_falls_back_to_open_library(): void {
		$this->queue_response( 404 );
		$this->queue_response(
			200,
			array(
				'ISBN:9780000000001' => array(
					'title'        => 'Some Bound Edition',
					'publish_date' => 'June 1982',
					'cover'        => array( 'large' => 'https://example.com/cover.jpg' ),
					'authors'      => array( array( 'name' => 'A. N. Author' ) ),
					'publishers'   => array( array( 'name' => 'Acme Press' ) ),
				),
			)
		);

		$api    = $this->api_with_keys( array( 'wp_movie_collector_barcode_api_key' => 'barcode-key' ) );
		$result = $api->lookup_by_barcode( '9780000000001' );

		$this->assertSame( 'Some Bound Edition', $result['title'] );
		$this->assertSame( '1982', $result['release_year'] );
		$this->assertSame( '9780000000001', $result['barcode'] );
		$this->assertSame( 'A. N. Author', $result['director'] );
		$this->assertSame( 'Acme Press', $result['studio'] );
		$this->assertSame( 'Open Library', $result['api_source'] );
		$this->assertCount( 2, $GLOBALS['wp_movie_test_http_log'] );
		$this->assertStringContainsString( 'openlibrary.org', $GLOBALS['wp_movie_test_http_log'][1] );
	}

	/**
	 * Test that a BarcodeLookup empty-products response also falls back.
	 */
	public function test_lookup_by_barcode_empty_products_falls_back(): void {
		$this->queue_response( 200, array( 'products' => array() ) );
		$this->queue_response(
			200,
			array(
				'ISBN:9780000000002' => array(
					'title' => 'Other Edition',
				),
			)
		);

		$api    = $this->api_with_keys( array( 'wp_movie_collector_barcode_api_key' => 'barcode-key' ) );
		$result = $api->lookup_by_barcode( '9780000000002' );

		$this->assertSame( 'Other Edition', $result['title'] );
		$this->assertSame( 'Open Library', $result['api_source'] );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * Polyfill for WordPress wp_json_encode().
	 *
	 * Lives at the bottom of this test file rather than in
	 * tests/unit/bootstrap.php because no other test currently needs it
	 * and it is trivially a wrapper around json_encode.
	 *
	 * @param mixed $data    The value to encode.
	 * @param int   $options json_encode flags.
	 * @return string|false Encoded JSON, or false on failure.
	 */
	function wp_json_encode( $data, $options = 0 ) {
		return json_encode( $data, $options );
	}
}
