<?php
/**
 * Unit tests for API rate limiting and resilience.
 *
 * Verifies that the API client class has the correct structure,
 * constants, and methods for rate limiting, circuit breaker,
 * and retry logic.
 *
 * @package WP_Movie_Collector\Tests\Unit
 */

namespace WP_Movie_Collector\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * API rate limit unit tests.
 */
class ApiRateLimitTest extends TestCase {

	/**
	 * The reflection class for the API client.
	 *
	 * @var ReflectionClass
	 */
	private ReflectionClass $reflection;

	/**
	 * Set up the test fixture.
	 */
	protected function setUp(): void {
		if ( ! class_exists( 'WP_Movie_Collector_API_Client' ) ) {
			$this->fail( 'WP_Movie_Collector_API_Client class does not exist or could not be autoloaded.' );
		}
		$this->reflection = new ReflectionClass( 'WP_Movie_Collector_API_Client' );
	}

	/**
	 * Test that the API client class exists.
	 */
	public function test_api_client_class_exists(): void {
		$this->assertTrue( class_exists( 'WP_Movie_Collector_API_Client' ) );
	}

	/**
	 * Test that the get method is public and static.
	 */
	public function test_get_is_public_static(): void {
		$method = $this->reflection->getMethod( 'get' );

		$this->assertTrue( $method->isPublic() );
		$this->assertTrue( $method->isStatic() );
	}

	/**
	 * Test that the get method accepts url and args parameters.
	 */
	public function test_get_method_parameters(): void {
		$method = $this->reflection->getMethod( 'get' );
		$params = $method->getParameters();

		$this->assertCount( 2, $params );
		$this->assertSame( 'url', $params[0]->getName() );
		$this->assertSame( 'args', $params[1]->getName() );
		$this->assertTrue( $params[1]->isDefaultValueAvailable() );
		$this->assertSame( array(), $params[1]->getDefaultValue() );
	}

	/**
	 * Test that MAX_RETRIES constant is defined.
	 */
	public function test_max_retries_constant(): void {
		$this->assertTrue( $this->reflection->hasConstant( 'MAX_RETRIES' ) );
		$value = $this->reflection->getConstant( 'MAX_RETRIES' );
		$this->assertIsInt( $value );
		$this->assertGreaterThan( 0, $value );
		$this->assertLessThanOrEqual( 5, $value );
	}

	/**
	 * Test that BASE_RETRY_DELAY constant is defined.
	 */
	public function test_base_retry_delay_constant(): void {
		$this->assertTrue( $this->reflection->hasConstant( 'BASE_RETRY_DELAY' ) );
		$value = $this->reflection->getConstant( 'BASE_RETRY_DELAY' );
		$this->assertIsInt( $value );
		$this->assertGreaterThan( 0, $value );
	}

	/**
	 * Test that MAX_RETRY_DELAY constant is defined and reasonable.
	 */
	public function test_max_retry_delay_constant(): void {
		$this->assertTrue( $this->reflection->hasConstant( 'MAX_RETRY_DELAY' ) );
		$value = $this->reflection->getConstant( 'MAX_RETRY_DELAY' );
		$this->assertIsInt( $value );
		$this->assertGreaterThan( 0, $value );
		$this->assertLessThanOrEqual( 10, $value );
	}

	/**
	 * Test that CIRCUIT_FAILURE_THRESHOLD constant is defined.
	 */
	public function test_circuit_failure_threshold_constant(): void {
		$this->assertTrue( $this->reflection->hasConstant( 'CIRCUIT_FAILURE_THRESHOLD' ) );
		$value = $this->reflection->getConstant( 'CIRCUIT_FAILURE_THRESHOLD' );
		$this->assertIsInt( $value );
		$this->assertGreaterThan( 0, $value );
	}

	/**
	 * Test that CIRCUIT_COOLDOWN_SECONDS constant is defined.
	 */
	public function test_circuit_cooldown_seconds_constant(): void {
		$this->assertTrue( $this->reflection->hasConstant( 'CIRCUIT_COOLDOWN_SECONDS' ) );
		$value = $this->reflection->getConstant( 'CIRCUIT_COOLDOWN_SECONDS' );
		$this->assertIsInt( $value );
		$this->assertGreaterThanOrEqual( 60, $value );
	}

	/**
	 * Test that detect_provider maps TMDb URLs correctly.
	 */
	public function test_detect_provider_tmdb(): void {
		$result = \WP_Movie_Collector_API_Client::detect_provider(
			'https://api.themoviedb.org/3/search/movie?query=test'
		);
		$this->assertSame( 'tmdb', $result );
	}

	/**
	 * Test that detect_provider maps OMDb URLs correctly.
	 */
	public function test_detect_provider_omdb(): void {
		$result = \WP_Movie_Collector_API_Client::detect_provider(
			'https://www.omdbapi.com/?s=test&apikey=xyz'
		);
		$this->assertSame( 'omdb', $result );
	}

	/**
	 * Test that detect_provider maps BarcodeLookup URLs correctly.
	 */
	public function test_detect_provider_barcodelookup(): void {
		$result = \WP_Movie_Collector_API_Client::detect_provider(
			'https://api.barcodelookup.com/v3/products?barcode=123'
		);
		$this->assertSame( 'barcodelookup', $result );
	}

	/**
	 * Test that detect_provider maps Open Library URLs correctly.
	 */
	public function test_detect_provider_openlibrary(): void {
		$result = \WP_Movie_Collector_API_Client::detect_provider(
			'https://openlibrary.org/api/books?bibkeys=ISBN:123'
		);
		$this->assertSame( 'openlibrary', $result );
	}

	/**
	 * Test that detect_provider returns 'unknown' for unrecognized URLs.
	 */
	public function test_detect_provider_unknown(): void {
		$result = \WP_Movie_Collector_API_Client::detect_provider(
			'https://example.com/api'
		);
		$this->assertSame( 'unknown', $result );
	}

	/**
	 * Test that get_rate_limit_config returns config for known providers.
	 */
	public function test_get_rate_limit_config_known_provider(): void {
		$config = \WP_Movie_Collector_API_Client::get_rate_limit_config( 'tmdb' );

		$this->assertIsArray( $config );
		$this->assertArrayHasKey( 'max_requests', $config );
		$this->assertArrayHasKey( 'window_seconds', $config );
		$this->assertIsInt( $config['max_requests'] );
		$this->assertIsInt( $config['window_seconds'] );
		$this->assertGreaterThan( 0, $config['max_requests'] );
		$this->assertGreaterThan( 0, $config['window_seconds'] );
	}

	/**
	 * Test that get_rate_limit_config returns null for unknown providers.
	 */
	public function test_get_rate_limit_config_unknown_provider(): void {
		$this->assertNull(
			\WP_Movie_Collector_API_Client::get_rate_limit_config( 'nonexistent' )
		);
	}

	/**
	 * Test that get_providers returns all four expected providers.
	 */
	public function test_get_providers_returns_expected_list(): void {
		$providers = \WP_Movie_Collector_API_Client::get_providers();

		$this->assertIsArray( $providers );
		$this->assertContains( 'tmdb', $providers );
		$this->assertContains( 'omdb', $providers );
		$this->assertContains( 'barcodelookup', $providers );
		$this->assertContains( 'openlibrary', $providers );
	}

	/**
	 * Test that rate limits have been defined for all known providers.
	 */
	public function test_all_providers_have_rate_limits(): void {
		$providers = \WP_Movie_Collector_API_Client::get_providers();

		foreach ( $providers as $provider ) {
			$config = \WP_Movie_Collector_API_Client::get_rate_limit_config( $provider );
			$this->assertNotNull( $config, "Rate limit config missing for provider: {$provider}" );
		}
	}

	/**
	 * Test that the is_rate_limited method exists and is private/static.
	 */
	public function test_is_rate_limited_method_exists(): void {
		$this->assertTrue( $this->reflection->hasMethod( 'is_rate_limited' ) );

		$method = $this->reflection->getMethod( 'is_rate_limited' );
		$this->assertTrue( $method->isStatic() );
	}

	/**
	 * Test that the is_circuit_open method exists and is private/static.
	 */
	public function test_is_circuit_open_method_exists(): void {
		$this->assertTrue( $this->reflection->hasMethod( 'is_circuit_open' ) );

		$method = $this->reflection->getMethod( 'is_circuit_open' );
		$this->assertTrue( $method->isStatic() );
	}

	/**
	 * Test that the record_failure method exists.
	 */
	public function test_record_failure_method_exists(): void {
		$this->assertTrue( $this->reflection->hasMethod( 'record_failure' ) );
	}

	/**
	 * Test that the record_success method exists.
	 */
	public function test_record_success_method_exists(): void {
		$this->assertTrue( $this->reflection->hasMethod( 'record_success' ) );
	}

	/**
	 * Test that the log_failure method exists.
	 */
	public function test_log_failure_method_exists(): void {
		$this->assertTrue( $this->reflection->hasMethod( 'log_failure' ) );
	}

	/**
	 * Test that get_api_issues is public and static.
	 */
	public function test_get_api_issues_is_public_static(): void {
		$method = $this->reflection->getMethod( 'get_api_issues' );

		$this->assertTrue( $method->isPublic() );
		$this->assertTrue( $method->isStatic() );
	}

	/**
	 * Test that get_api_issues() does not mutate circuit-breaker state.
	 *
	 * Regression: after cooldown, the old code routed through
	 * is_circuit_open(), which acquired the single half-open probe lock,
	 * so merely rendering an admin notice blocked the next real request.
	 */
	public function test_get_api_issues_does_not_acquire_half_open_lock(): void {
		$GLOBALS['wp_movie_test_transients'] = array();

		$provider  = 'tmdb';
		$threshold = \WP_Movie_Collector_API_Client::CIRCUIT_FAILURE_THRESHOLD;
		$cooldown  = \WP_Movie_Collector_API_Client::CIRCUIT_COOLDOWN_SECONDS;

		// Circuit opened long enough ago that the cooldown has elapsed —
		// the old code would acquire the half-open probe lock here.
		set_transient(
			"wp_movie_api_circuit_{$provider}",
			array(
				'failures'  => $threshold,
				'opened_at' => time() - ( $cooldown + 10 ),
			),
			0
		);
		set_transient(
			'wp_movie_api_issues',
			array(
				$provider => array(
					'provider'  => $provider,
					'timestamp' => time(),
				),
			),
			0
		);

		\WP_Movie_Collector_API_Client::get_api_issues();

		$this->assertFalse(
			get_transient( "wp_movie_api_halfopen_{$provider}" ),
			'get_api_issues() must not acquire the half-open probe lock.'
		);

		$GLOBALS['wp_movie_test_transients'] = array();
	}

	/**
	 * Read the API client source file.
	 *
	 * @return string The file contents.
	 */
	private function read_api_client_source(): string {
		$path = WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'includes/class-wp-movie-collector-api-client.php';

		$this->assertFileExists( $path, "API client source does not exist at {$path}" );
		$this->assertIsReadable( $path, "API client source is not readable at {$path}" );

		$source = file_get_contents( $path );

		$this->assertIsString( $source, "Failed to read API client source at {$path}" );

		return $source;
	}

	/**
	 * Test that the source uses exponential backoff with pow().
	 */
	public function test_source_uses_exponential_backoff(): void {
		$source = $this->read_api_client_source();

		$this->assertStringContainsString( 'pow(', $source );
		$this->assertStringContainsString( 'sleep(', $source );
	}

	/**
	 * Test that the source handles HTTP 429 responses.
	 */
	public function test_source_handles_429_responses(): void {
		$source = $this->read_api_client_source();

		$this->assertStringContainsString( '429', $source );
	}

	/**
	 * Test that the source handles 5xx server errors.
	 */
	public function test_source_handles_5xx_responses(): void {
		$source = $this->read_api_client_source();

		$this->assertStringContainsString( '>= 500', $source );
	}

	/**
	 * Test that the source redacts API keys in log messages.
	 */
	public function test_log_redacts_api_keys(): void {
		$source = $this->read_api_client_source();

		$this->assertStringContainsString( 'REDACTED', $source );
	}

	/**
	 * Test that the source only logs when WP_DEBUG is enabled.
	 */
	public function test_logging_respects_wp_debug(): void {
		$source = $this->read_api_client_source();

		$this->assertStringContainsString( 'WP_DEBUG', $source );
	}

	/**
	 * Test that the source provides a filter to disable retries.
	 */
	public function test_source_has_retry_filter(): void {
		$source = $this->read_api_client_source();

		$this->assertStringContainsString( 'wp_movie_collector_api_retry_enabled', $source );
		$this->assertStringContainsString( 'apply_filters', $source );
	}

	/**
	 * Test that the source honors Retry-After header on 429 responses.
	 */
	public function test_source_honors_retry_after_header(): void {
		$source = $this->read_api_client_source();

		$this->assertStringContainsString( 'retry-after', $source );
	}

	/**
	 * Test that the API class source no longer uses wp_remote_get directly.
	 */
	public function test_api_class_uses_client_wrapper(): void {
		$path = WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'includes/class-wp-movie-collector-api.php';

		$this->assertFileExists( $path, "API class source does not exist at {$path}" );
		$this->assertIsReadable( $path, "API class source is not readable at {$path}" );

		$source = file_get_contents( $path );

		$this->assertIsString( $source, "Failed to read API class source at {$path}" );

		$this->assertStringNotContainsString(
			'wp_remote_get(',
			$source,
			'API class should use WP_Movie_Collector_API_Client::get() instead of wp_remote_get()'
		);

		$this->assertStringContainsString(
			'WP_Movie_Collector_API_Client::get(',
			$source,
			'API class should call WP_Movie_Collector_API_Client::get()'
		);
	}
}
