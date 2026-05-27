<?php
/**
 * HTTP client wrapper with rate limiting, retry, and circuit breaker.
 *
 * Provides a resilient HTTP transport layer for external API calls.
 * All requests go through this class to enforce rate limits, retry
 * with exponential backoff on transient failures, and trip a circuit
 * breaker when an API is persistently failing.
 *
 * @since      1.0.0
 * @package    WP_Movie_Collector
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * API client with rate limiting, exponential backoff, and circuit breaker.
 */
class WP_Movie_Collector_API_Client {

	/**
	 * Maximum retry attempts for transient failures.
	 *
	 * @var int
	 */
	const MAX_RETRIES = 2;

	/**
	 * Base delay in seconds for exponential backoff.
	 *
	 * @var int
	 */
	const BASE_RETRY_DELAY = 1;

	/**
	 * Maximum delay in seconds for any single retry sleep.
	 *
	 * Caps both exponential backoff and Retry-After headers to prevent
	 * a single request from blocking a PHP worker for too long.
	 *
	 * @var int
	 */
	const MAX_RETRY_DELAY = 5;

	/**
	 * Number of consecutive failures to trip the circuit breaker.
	 *
	 * @var int
	 */
	const CIRCUIT_FAILURE_THRESHOLD = 5;

	/**
	 * Seconds to keep the circuit open before allowing a retry.
	 *
	 * @var int
	 */
	const CIRCUIT_COOLDOWN_SECONDS = 300;

	/**
	 * Rate limit configuration per provider.
	 *
	 * Each entry maps a provider key to its max_requests and
	 * window_seconds values.
	 *
	 * @var array
	 */
	private static $rate_limits = array(
		'tmdb'          => array(
			'max_requests'   => 35,
			'window_seconds' => 10,
		),
		'omdb'          => array(
			'max_requests'   => 900,
			'window_seconds' => 86400,
		),
		'barcodelookup' => array(
			'max_requests'   => 45,
			'window_seconds' => 60,
		),
		'openlibrary'   => array(
			'max_requests'   => 90,
			'window_seconds' => 60,
		),
	);

	/**
	 * Map of URL hostname patterns to provider keys.
	 *
	 * @var array
	 */
	private static $provider_map = array(
		'api.themoviedb.org'    => 'tmdb',
		'www.omdbapi.com'       => 'omdb',
		'api.barcodelookup.com' => 'barcodelookup',
		'openlibrary.org'       => 'openlibrary',
	);

	/**
	 * Make an HTTP GET request with rate limiting, retry, and circuit breaker.
	 *
	 * @param string $url  The request URL.
	 * @param array  $args Optional. wp_remote_get arguments.
	 * @return array|WP_Error The HTTP response or error.
	 */
	public static function get( $url, $args = array() ) {
		$provider = self::detect_provider( $url );

		// Check circuit breaker.
		if ( self::is_circuit_open( $provider ) ) {
			self::log_failure( $provider, $url, 'Circuit breaker is open — skipping request' );
			return new WP_Error(
				'circuit_open',
				sprintf(
					/* translators: %s: API provider name */
					__( 'The %s API is temporarily unavailable due to repeated failures. Please try again later.', 'wp-movie-collector' ),
					$provider
				)
			);
		}

		// Check rate limit before first attempt.
		if ( self::is_rate_limited( $provider ) ) {
			self::log_failure( $provider, $url, 'Rate limit reached — request blocked' );
			return new WP_Error(
				'rate_limited',
				sprintf(
					/* translators: %s: API provider name */
					__( 'The %s API rate limit has been reached. Please wait and try again.', 'wp-movie-collector' ),
					$provider
				)
			);
		}

		$last_error = null;

		/**
		 * Filters whether API request retries are enabled.
		 *
		 * When disabled, the client returns immediately on any transient
		 * failure (429, 5xx, WP_Error) instead of sleeping and retrying.
		 * Useful for AJAX or time-sensitive requests.
		 *
		 * @since 1.0.0
		 * @param bool   $enabled  Whether retries are enabled. Default true.
		 * @param string $provider The API provider key.
		 */
		$retries_enabled = apply_filters( 'wp_movie_collector_api_retry_enabled', true, $provider );
		$max_attempts    = $retries_enabled ? self::MAX_RETRIES : 0;

		for ( $attempt = 0; $attempt <= $max_attempts; $attempt++ ) {
			// Exponential backoff for retries.
			if ( $attempt > 0 ) {
				$delay = self::BASE_RETRY_DELAY * pow( 2, $attempt - 1 );
				sleep( min( $delay, self::MAX_RETRY_DELAY ) );

				// Re-check rate limit before each retry to avoid exceeding quota.
				if ( self::is_rate_limited( $provider ) ) {
					self::log_failure( $provider, $url, 'Rate limit reached during retry', $attempt );
					break;
				}
			}

			self::increment_request_count( $provider );
			$response = wp_remote_get( $url, $args );

			if ( is_wp_error( $response ) ) {
				$last_error = $response;
				self::record_failure( $provider );
				self::log_failure( $provider, $url, $response->get_error_message(), $attempt );
				continue;
			}

			$code = wp_remote_retrieve_response_code( $response );

			// Success — reset circuit breaker.
			if ( $code >= 200 && $code < 300 ) {
				self::record_success( $provider );
				return $response;
			}

			// Rate limited (429) — honor Retry-After header if present.
			if ( 429 === $code ) {
				$last_error = new WP_Error(
					'rate_limited',
					__( 'Too many requests. Please wait a moment and try again.', 'wp-movie-collector' )
				);
				self::record_failure( $provider );
				self::log_failure( $provider, $url, 'HTTP 429 rate limited', $attempt );

				if ( $retries_enabled && $attempt < $max_attempts ) {
					$retry_after = (int) wp_remote_retrieve_header( $response, 'retry-after' );
					if ( $retry_after > 0 ) {
						sleep( min( $retry_after, self::MAX_RETRY_DELAY ) );
					}
				}
				continue;
			}

			// Server error (5xx) — retry with backoff.
			if ( $code >= 500 ) {
				$last_error = new WP_Error(
					'server_error',
					__( 'The movie service is temporarily unavailable. Please try again later.', 'wp-movie-collector' )
				);
				self::record_failure( $provider );
				self::log_failure( $provider, $url, 'HTTP ' . $code . ' server error', $attempt );
				continue;
			}

			// Client error (4xx except 429) — do not retry, return as-is.
			return $response;
		}

		// All retries exhausted.
		return $last_error;
	}

	/**
	 * Detect the API provider from a URL.
	 *
	 * @param string $url The request URL.
	 * @return string The provider key, or 'unknown'.
	 */
	public static function detect_provider( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );

		if ( $host && isset( self::$provider_map[ $host ] ) ) {
			return self::$provider_map[ $host ];
		}

		return 'unknown';
	}

	/**
	 * Check whether the provider is currently rate-limited.
	 *
	 * @param string $provider The provider key.
	 * @return bool True if rate-limited.
	 */
	private static function is_rate_limited( $provider ) {
		if ( ! isset( self::$rate_limits[ $provider ] ) ) {
			return false;
		}

		$limit = self::$rate_limits[ $provider ];
		$data  = get_transient( "wp_movie_api_rate_{$provider}" );

		if ( ! is_array( $data ) || ! isset( $data['count'], $data['window_start'] ) ) {
			return false;
		}

		// Window expired — not rate-limited.
		if ( ( time() - $data['window_start'] ) >= $limit['window_seconds'] ) {
			return false;
		}

		return $data['count'] >= $limit['max_requests'];
	}

	/**
	 * Increment the request counter for a provider.
	 *
	 * Uses a fixed-window approach: stores the count and the window
	 * start timestamp. The counter resets when the window expires.
	 * The transient TTL is set once when the window starts and is
	 * not extended on subsequent increments.
	 *
	 * Note: the get/set cycle is not atomic. Under high concurrency
	 * the counter may under-count, allowing a few extra requests
	 * through. This is acceptable because the API providers' own
	 * rate limiting is the authoritative backstop, and our retry
	 * logic handles the resulting 429 responses gracefully.
	 *
	 * @param string $provider The provider key.
	 */
	private static function increment_request_count( $provider ) {
		if ( ! isset( self::$rate_limits[ $provider ] ) ) {
			return;
		}

		$key   = "wp_movie_api_rate_{$provider}";
		$limit = self::$rate_limits[ $provider ];
		$data  = get_transient( $key );

		$now = time();

		// Start a new window if no data or window expired.
		if ( ! is_array( $data ) || ! isset( $data['count'], $data['window_start'] ) || ( $now - $data['window_start'] ) >= $limit['window_seconds'] ) {
			$data = array(
				'count'        => 1,
				'window_start' => $now,
			);
			set_transient( $key, $data, $limit['window_seconds'] );
			return;
		}

		// Increment within the existing window — do not reset TTL.
		$data['count'] = $data['count'] + 1;
		$remaining_ttl = $limit['window_seconds'] - ( $now - $data['window_start'] );
		set_transient( $key, $data, max( 1, $remaining_ttl ) );
	}

	/**
	 * Check whether the circuit breaker is open for a provider.
	 *
	 * When open, requests are blocked until the cooldown expires. After
	 * cooldown the circuit enters half-open state: a short-lived probe
	 * lock ensures only one request passes through. If that request
	 * succeeds the circuit resets; if it fails the circuit re-opens.
	 *
	 * @param string $provider The provider key.
	 * @return bool True if the circuit is open and requests should be blocked.
	 */
	private static function is_circuit_open( $provider ) {
		$state = get_transient( "wp_movie_api_circuit_{$provider}" );

		if ( false === $state || ! is_array( $state ) ) {
			return false;
		}

		$failures  = isset( $state['failures'] ) ? (int) $state['failures'] : 0;
		$opened_at = isset( $state['opened_at'] ) ? (int) $state['opened_at'] : 0;

		if ( $failures < self::CIRCUIT_FAILURE_THRESHOLD ) {
			return false;
		}

		$elapsed = time() - $opened_at;

		// Cooldown expired — enter half-open state.
		if ( $elapsed >= self::CIRCUIT_COOLDOWN_SECONDS ) {
			return self::acquire_half_open_lock( $provider );
		}

		return true;
	}

	/**
	 * Try to acquire the half-open probe lock for a provider.
	 *
	 * Only one request is allowed through after cooldown. A short-lived
	 * transient acts as a lock: the first caller acquires it and
	 * proceeds (returns false = circuit not open); subsequent callers
	 * see the lock and are blocked (returns true = circuit still open).
	 *
	 * @param string $provider The provider key.
	 * @return bool True if lock was NOT acquired (circuit stays open for this caller).
	 */
	private static function acquire_half_open_lock( $provider ) {
		$lock_key = "wp_movie_api_halfopen_{$provider}";

		// If the lock already exists, another request is probing.
		if ( false !== get_transient( $lock_key ) ) {
			return true;
		}

		// Acquire the lock for 30 seconds (enough for one request + timeout).
		set_transient( $lock_key, 1, 30 );
		return false;
	}

	/**
	 * Record a failed request for the circuit breaker.
	 *
	 * Increments the failure counter and, when the threshold is reached,
	 * records the time the circuit opened. Also flags the provider for
	 * admin notices.
	 *
	 * @param string $provider The provider key.
	 */
	private static function record_failure( $provider ) {
		$key   = "wp_movie_api_circuit_{$provider}";
		$state = get_transient( $key );

		if ( false === $state || ! is_array( $state ) ) {
			$state = array(
				'failures'  => 0,
				'opened_at' => 0,
			);
		}

		++$state['failures'];

		if ( $state['failures'] >= self::CIRCUIT_FAILURE_THRESHOLD ) {
			$state['opened_at'] = time();
			self::flag_api_issue( $provider );
		}

		// Keep state for cooldown plus a buffer.
		set_transient( $key, $state, self::CIRCUIT_COOLDOWN_SECONDS + 60 );

		// Release the half-open probe lock so the circuit re-opens.
		delete_transient( "wp_movie_api_halfopen_{$provider}" );
	}

	/**
	 * Record a successful request — reset the circuit breaker.
	 *
	 * @param string $provider The provider key.
	 */
	private static function record_success( $provider ) {
		$key = "wp_movie_api_circuit_{$provider}";

		if ( false !== get_transient( $key ) ) {
			delete_transient( $key );
		}

		// Release the half-open probe lock on success.
		delete_transient( "wp_movie_api_halfopen_{$provider}" );
	}

	/**
	 * Flag a provider as having issues so admin notices can be shown.
	 *
	 * Stores a transient that the admin notice hook checks.
	 *
	 * @param string $provider The provider key.
	 */
	private static function flag_api_issue( $provider ) {
		$issues = get_transient( 'wp_movie_api_issues' );

		if ( ! is_array( $issues ) ) {
			$issues = array();
		}

		$issues[ $provider ] = array(
			'provider'  => $provider,
			'timestamp' => time(),
		);

		set_transient( 'wp_movie_api_issues', $issues, self::CIRCUIT_COOLDOWN_SECONDS + 60 );
	}

	/**
	 * Get the list of API providers currently experiencing issues.
	 *
	 * @return array Provider issue entries, or empty array.
	 */
	public static function get_api_issues() {
		$issues = get_transient( 'wp_movie_api_issues' );

		if ( ! is_array( $issues ) ) {
			return array();
		}

		// Filter out stale entries.
		$active = array();
		foreach ( $issues as $provider => $info ) {
			if ( self::is_circuit_open( $provider ) ) {
				$active[ $provider ] = $info;
			}
		}

		// Clean up if no active issues.
		if ( empty( $active ) && ! empty( $issues ) ) {
			delete_transient( 'wp_movie_api_issues' );
		}

		return $active;
	}

	/**
	 * Log an API failure with context.
	 *
	 * @param string $provider The provider key.
	 * @param string $url      The request URL (API key is redacted).
	 * @param string $message  The error description.
	 * @param int    $attempt  The retry attempt number (0-based).
	 */
	private static function log_failure( $provider, $url, $message, $attempt = 0 ) {
		// Redact API keys from the URL.
		$safe_url = preg_replace( '/([?&])(api_key|apikey|key)=[^&]+/', '$1$2=REDACTED', $url );

		$entry = sprintf(
			'[WP Movie Collector] API failure — provider: %s, attempt: %d, url: %s, error: %s, time: %s',
			$provider,
			$attempt,
			$safe_url,
			$message,
			gmdate( 'Y-m-d H:i:s' )
		);

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional structured API failure logging.
			error_log( $entry );
		}
	}

	/**
	 * Get the rate limit configuration for a provider.
	 *
	 * Useful for testing and admin display.
	 *
	 * @param string $provider The provider key.
	 * @return array|null The rate limit config, or null if unknown.
	 */
	public static function get_rate_limit_config( $provider ) {
		return isset( self::$rate_limits[ $provider ] ) ? self::$rate_limits[ $provider ] : null;
	}

	/**
	 * Get all known provider keys.
	 *
	 * @return array List of provider key strings.
	 */
	public static function get_providers() {
		return array_values( self::$provider_map );
	}
}
