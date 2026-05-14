<?php
/**
 * Unit tests for API input validation and early-return error paths.
 *
 * Covers the validation surface of WP_Movie_Collector_API public methods
 * that returns WP_Error before any HTTP request is made (missing API
 * keys, malformed barcodes, invalid IMDb IDs) and the cache-versioning
 * helper used to invalidate cached responses.
 *
 * HTTP-mocked fallback-chain coverage is intentionally not in this file
 * and remains a follow-up to issue #17.
 *
 * @package WP_Movie_Collector\Tests\Unit
 */

namespace WP_Movie_Collector\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WP_Error;
use WP_Movie_Collector_API;

/**
 * API validation unit tests.
 */
class ApiValidationTest extends TestCase {

	/**
	 * Reset the in-memory options store before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['wp_movie_test_options'] = array();
	}

	/**
	 * Helper to construct an API instance with a chosen set of API keys.
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
	 * Assert a value is a WP_Error with the given code.
	 *
	 * @param mixed  $actual   The value to check.
	 * @param string $expected The expected error code.
	 */
	private function assertWpError( $actual, string $expected ): void {
		$this->assertInstanceOf( WP_Error::class, $actual );
		$this->assertSame( $expected, $actual->get_error_code() );
	}

	// ------------------------------------------------------------------
	// Missing API key — early return WP_Error('no_api_key')
	// ------------------------------------------------------------------

	/**
	 * Test that search_movie_by_title returns no_api_key when the TMDb key is unset.
	 */
	public function test_search_movie_by_title_no_tmdb_key(): void {
		$api    = $this->api_with_keys();
		$result = $api->search_movie_by_title( 'The Thing' );

		$this->assertWpError( $result, 'no_api_key' );
	}

	/**
	 * Test that get_movie_details returns no_api_key when the TMDb key is unset.
	 */
	public function test_get_movie_details_no_tmdb_key(): void {
		$api    = $this->api_with_keys();
		$result = $api->get_movie_details( 12345 );

		$this->assertWpError( $result, 'no_api_key' );
	}

	/**
	 * Test that lookup_by_barcode returns no_api_key when the BarcodeLookup key is unset.
	 */
	public function test_lookup_by_barcode_no_barcode_key(): void {
		$api    = $this->api_with_keys();
		$result = $api->lookup_by_barcode( '025192110825' );

		$this->assertWpError( $result, 'no_api_key' );
	}

	/**
	 * Test that get_movie_details_by_imdb returns no_api_key when the OMDb key is unset.
	 */
	public function test_get_movie_details_by_imdb_no_omdb_key(): void {
		$api    = $this->api_with_keys();
		$result = $api->get_movie_details_by_imdb( 'tt0084787' );

		$this->assertWpError( $result, 'no_api_key' );
	}

	// ------------------------------------------------------------------
	// Barcode sanitization
	// ------------------------------------------------------------------

	/**
	 * Test that a barcode with no digits returns invalid_barcode (does not hit network).
	 *
	 * @return array<string, array<int, string>>
	 */
	public static function non_numeric_barcodes(): array {
		return array(
			'empty string'   => array( '' ),
			'whitespace'     => array( '   ' ),
			'letters only'   => array( 'abcdef' ),
			'symbols only'   => array( '!@#$%' ),
			'letters+spaces' => array( 'no digits here' ),
		);
	}

	#[DataProvider( 'non_numeric_barcodes' )]
	public function test_lookup_by_barcode_invalid_barcode( string $barcode ): void {
		$api    = $this->api_with_keys( array( 'wp_movie_collector_barcode_api_key' => 'key' ) );
		$result = $api->lookup_by_barcode( $barcode );

		$this->assertWpError( $result, 'invalid_barcode' );
	}

	// ------------------------------------------------------------------
	// IMDb ID validation
	// ------------------------------------------------------------------

	/**
	 * Test that obviously-malformed IMDb IDs return invalid_imdb_id.
	 *
	 * @return array<string, array<int, string>>
	 */
	public static function invalid_imdb_ids(): array {
		return array(
			'empty'           => array( '' ),
			'no prefix'       => array( '0084787' ),
			'too short'       => array( 'tt12' ),
			'six digits'      => array( 'tt123456' ),
			'wrong prefix'    => array( 'xx0084787' ),
			'letters in body' => array( 'ttabcdefg' ),
			'symbols'         => array( 'tt!084787' ),
		);
	}

	#[DataProvider( 'invalid_imdb_ids' )]
	public function test_get_movie_details_by_imdb_invalid_id( string $imdb_id ): void {
		$api    = $this->api_with_keys( array( 'wp_movie_collector_omdb_api_key' => 'key' ) );
		$result = $api->get_movie_details_by_imdb( $imdb_id );

		$this->assertWpError( $result, 'invalid_imdb_id' );
	}

	/**
	 * Test that the invalid_imdb_id check fires before any cache lookup.
	 *
	 * If a malformed IMDb ID slipped past validation it could poison the
	 * cache for subsequent valid lookups. The validation runs before the
	 * get_transient call, so even a permissive cache should not affect the
	 * return value for bad input.
	 */
	public function test_get_movie_details_by_imdb_invalid_id_skips_cache(): void {
		$api    = $this->api_with_keys( array( 'wp_movie_collector_omdb_api_key' => 'key' ) );
		$result = $api->get_movie_details_by_imdb( 'tt12' );

		$this->assertWpError( $result, 'invalid_imdb_id' );
		$this->assertSame( 'Invalid IMDb ID.', $result->get_error_message() );
	}

	// ------------------------------------------------------------------
	// Cache versioning / invalidation
	// ------------------------------------------------------------------

	/**
	 * Test that clear_api_cache increments the cache version option.
	 */
	public function test_clear_api_cache_increments_version(): void {
		$GLOBALS['wp_movie_test_options']['wp_movie_collector_cache_version'] = 3;

		WP_Movie_Collector_API::clear_api_cache();

		$this->assertSame( 4, $GLOBALS['wp_movie_test_options']['wp_movie_collector_cache_version'] );
	}

	/**
	 * Test that clear_api_cache starts the version at 2 when no option is stored
	 * (default version 1 is bumped to 2).
	 */
	public function test_clear_api_cache_starts_from_default_one(): void {
		WP_Movie_Collector_API::clear_api_cache();

		$this->assertSame( 2, $GLOBALS['wp_movie_test_options']['wp_movie_collector_cache_version'] );
	}

	/**
	 * Test that clear_api_cache treats a non-integer stored version as the
	 * default (int cast of false/null/'' yields 0, then +1 = 1).
	 */
	public function test_clear_api_cache_coerces_non_int_version(): void {
		$GLOBALS['wp_movie_test_options']['wp_movie_collector_cache_version'] = 'garbage';

		WP_Movie_Collector_API::clear_api_cache();

		$this->assertSame( 1, $GLOBALS['wp_movie_test_options']['wp_movie_collector_cache_version'] );
	}

	/**
	 * Test that make_cache_key (private) embeds the current cache version
	 * so that bumping the version invalidates prior keys.
	 */
	public function test_make_cache_key_embeds_version(): void {
		$reflection = new ReflectionClass( WP_Movie_Collector_API::class );
		$method     = $reflection->getMethod( 'make_cache_key' );
		$method->setAccessible( true );

		$GLOBALS['wp_movie_test_options']['wp_movie_collector_cache_version'] = 5;
		$key_v5 = $method->invoke( null, 'wp_movie_details_', '12345' );

		$GLOBALS['wp_movie_test_options']['wp_movie_collector_cache_version'] = 6;
		$key_v6 = $method->invoke( null, 'wp_movie_details_', '12345' );

		$this->assertSame( 'wp_movie_details_5_12345', $key_v5 );
		$this->assertSame( 'wp_movie_details_6_12345', $key_v6 );
		$this->assertNotSame( $key_v5, $key_v6 );
	}

	/**
	 * Test that make_cache_key uses version 1 by default.
	 */
	public function test_make_cache_key_default_version_is_one(): void {
		$reflection = new ReflectionClass( WP_Movie_Collector_API::class );
		$method     = $reflection->getMethod( 'make_cache_key' );
		$method->setAccessible( true );

		$key = $method->invoke( null, 'wp_movie_search_', 'abcdef' );

		$this->assertSame( 'wp_movie_search_1_abcdef', $key );
	}
}
