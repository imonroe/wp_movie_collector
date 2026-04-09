<?php
/**
 * Unit tests for API caching functionality.
 *
 * Verifies that the API class has the correct caching methods,
 * parameters, and structure without requiring WordPress.
 *
 * @package WP_Movie_Collector\Tests\Unit
 */

namespace WP_Movie_Collector\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * API cache unit tests.
 */
class ApiCacheTest extends TestCase {

	/**
	 * The reflection class for the API.
	 *
	 * @var ReflectionClass
	 */
	private ReflectionClass $reflection;

	/**
	 * Set up the test fixture.
	 */
	protected function setUp(): void {
		if ( ! class_exists( 'WP_Movie_Collector_API' ) ) {
			$this->fail( 'WP_Movie_Collector_API class does not exist or could not be autoloaded.' );
		}
		$this->reflection = new ReflectionClass( 'WP_Movie_Collector_API' );
	}

	/**
	 * Test that the API class exists and can be reflected.
	 */
	public function test_api_class_exists(): void {
		$this->assertTrue( class_exists( 'WP_Movie_Collector_API' ) );
	}

	/**
	 * Test that search_movie_by_title accepts a bypass_cache parameter.
	 */
	public function test_search_movie_by_title_has_bypass_cache_parameter(): void {
		$method = $this->reflection->getMethod( 'search_movie_by_title' );
		$params = $method->getParameters();

		$this->assertCount( 3, $params );
		$this->assertSame( 'title', $params[0]->getName() );
		$this->assertSame( 'year', $params[1]->getName() );
		$this->assertSame( 'bypass_cache', $params[2]->getName() );
		$this->assertTrue( $params[2]->isDefaultValueAvailable() );
		$this->assertFalse( $params[2]->getDefaultValue() );
	}

	/**
	 * Test that get_movie_details accepts a bypass_cache parameter.
	 */
	public function test_get_movie_details_has_bypass_cache_parameter(): void {
		$method = $this->reflection->getMethod( 'get_movie_details' );
		$params = $method->getParameters();

		$this->assertCount( 2, $params );
		$this->assertSame( 'tmdb_id', $params[0]->getName() );
		$this->assertSame( 'bypass_cache', $params[1]->getName() );
		$this->assertTrue( $params[1]->isDefaultValueAvailable() );
		$this->assertFalse( $params[1]->getDefaultValue() );
	}

	/**
	 * Test that lookup_by_barcode accepts a bypass_cache parameter.
	 */
	public function test_lookup_by_barcode_has_bypass_cache_parameter(): void {
		$method = $this->reflection->getMethod( 'lookup_by_barcode' );
		$params = $method->getParameters();

		$this->assertCount( 2, $params );
		$this->assertSame( 'barcode', $params[0]->getName() );
		$this->assertSame( 'bypass_cache', $params[1]->getName() );
		$this->assertTrue( $params[1]->isDefaultValueAvailable() );
		$this->assertFalse( $params[1]->getDefaultValue() );
	}

	/**
	 * Test that get_movie_details_by_imdb accepts a bypass_cache parameter.
	 */
	public function test_get_movie_details_by_imdb_has_bypass_cache_parameter(): void {
		$method = $this->reflection->getMethod( 'get_movie_details_by_imdb' );
		$params = $method->getParameters();

		$this->assertCount( 2, $params );
		$this->assertSame( 'imdb_id', $params[0]->getName() );
		$this->assertSame( 'bypass_cache', $params[1]->getName() );
		$this->assertTrue( $params[1]->isDefaultValueAvailable() );
		$this->assertFalse( $params[1]->getDefaultValue() );
	}

	/**
	 * Test that clear_api_cache is a public static method.
	 */
	public function test_clear_api_cache_is_public_static(): void {
		$method = $this->reflection->getMethod( 'clear_api_cache' );

		$this->assertTrue( $method->isPublic() );
		$this->assertTrue( $method->isStatic() );
	}

	/**
	 * Test that clear_api_cache takes no parameters.
	 */
	public function test_clear_api_cache_has_no_parameters(): void {
		$method = $this->reflection->getMethod( 'clear_api_cache' );
		$this->assertCount( 0, $method->getParameters() );
	}

	/**
	 * Read the API class source file, asserting it exists and is readable.
	 *
	 * @return string The file contents.
	 */
	private function read_api_source(): string {
		$path   = WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'includes/class-wp-movie-collector-api.php';
		$source = file_get_contents( $path );

		$this->assertIsString( $source, "Failed to read API class source at {$path}" );

		return $source;
	}

	/**
	 * Test that the API class source contains expected cache key prefixes.
	 */
	public function test_cache_key_prefixes_used(): void {
		$source = $this->read_api_source();

		$this->assertStringContainsString( 'wp_movie_search_', $source );
		$this->assertStringContainsString( 'wp_movie_details_', $source );
		$this->assertStringContainsString( 'wp_movie_barcode_', $source );
		$this->assertStringContainsString( 'wp_movie_imdb_', $source );
	}

	/**
	 * Test that debug error_log calls have been removed from the API class.
	 */
	public function test_no_debug_error_log_calls(): void {
		$source = $this->read_api_source();

		$this->assertStringNotContainsString( 'error_log(', $source );
	}

	/**
	 * Test that caching is not disabled (no commented-out set_transient calls).
	 */
	public function test_caching_not_disabled(): void {
		$source = $this->read_api_source();

		$this->assertStringNotContainsString( '// set_transient(', $source );
		$this->assertStringNotContainsString( 'Caching disabled', $source );
	}

	/**
	 * Test that set_transient is called with appropriate TTL values.
	 */
	public function test_transient_ttl_values(): void {
		$source = $this->read_api_source();

		// Search results: 12h or 24h
		$this->assertStringContainsString( 'HOUR_IN_SECONDS * 12', $source );
		$this->assertStringContainsString( 'HOUR_IN_SECONDS * 24', $source );

		// Details/barcode: 7 days
		$this->assertStringContainsString( 'DAY_IN_SECONDS * 7', $source );

		// Not-found: 1 hour
		$this->assertStringContainsString( 'HOUR_IN_SECONDS)', $source );
	}
}
