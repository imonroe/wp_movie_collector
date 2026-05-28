<?php
/**
 * PHPUnit bootstrap file for unit tests.
 *
 * Unit tests run without WordPress loaded. They test pure PHP logic
 * in isolation using mocks/stubs for WordPress functions.
 *
 * @package WP_Movie_Collector
 */

// Load Composer autoloader.
$autoloader = dirname( __DIR__, 2 ) . '/vendor/autoload.php';
if ( ! file_exists( $autoloader ) ) {
	throw new RuntimeException( 'Composer autoloader not found. Run `composer install` first.' );
}
require_once $autoloader;

// Define WordPress constants that plugin code expects.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}
if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}
if ( ! defined( 'WP_MOVIE_COLLECTOR_VERSION' ) ) {
	define( 'WP_MOVIE_COLLECTOR_VERSION', '1.0.0' );
}
if ( ! defined( 'WP_MOVIE_COLLECTOR_PLUGIN_DIR' ) ) {
	define( 'WP_MOVIE_COLLECTOR_PLUGIN_DIR', dirname( __DIR__, 2 ) . '/' );
}
if ( ! defined( 'WP_MOVIE_COLLECTOR_PLUGIN_URL' ) ) {
	define( 'WP_MOVIE_COLLECTOR_PLUGIN_URL', 'http://example.com/wp-content/plugins/wp-movie-collector/' );
}

// WordPress query output type constants.
if ( ! defined( 'ARRAY_A' ) ) {
	define( 'ARRAY_A', 'ARRAY_A' );
}
if ( ! defined( 'ARRAY_N' ) ) {
	define( 'ARRAY_N', 'ARRAY_N' );
}
if ( ! defined( 'OBJECT' ) ) {
	define( 'OBJECT', 'OBJECT' );
}

// WordPress time constants.
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}
if ( ! defined( 'DAY_IN_SECONDS' ) ) {
	define( 'DAY_IN_SECONDS', 86400 );
}
if ( ! defined( 'WEEK_IN_SECONDS' ) ) {
	define( 'WEEK_IN_SECONDS', 604800 );
}

// Polyfill WordPress functions used in unit-testable code.
if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( $url, $component );
	}
}

if ( ! function_exists( 'current_time' ) ) {
	/**
	 * Polyfill for WordPress current_time().
	 *
	 * @param string $type    'mysql', 'timestamp', or a date format string.
	 * @param bool   $gmt     Whether to use GMT time.
	 * @return string|int     Formatted date string or Unix timestamp.
	 */
	function current_time( $type, $gmt = 0 ) {
		// Use PHP's UTC offset (date('Z') returns seconds east of UTC).
		$local_offset = $gmt ? 0 : (int) date( 'Z' );
		if ( 'mysql' === $type || 'Y-m-d H:i:s' === $type ) {
			return gmdate( 'Y-m-d H:i:s', time() + $local_offset );
		}
		if ( 'timestamp' === $type || 'U' === $type ) {
			return time() + $local_offset;
		}
		return gmdate( $type, time() + $local_offset );
	}
}

/**
 * In-memory transient store for unit tests.
 *
 * Tests that need to assert cache-hit behavior can pre-populate
 * $GLOBALS['wp_movie_test_transients'] with a sentinel value for a
 * specific cache key. Tests should reset this store in setUp() if
 * isolation between cases matters.
 *
 * @var array<string, mixed>
 */
if ( ! isset( $GLOBALS['wp_movie_test_transients'] ) ) {
	$GLOBALS['wp_movie_test_transients'] = array();
}

if ( ! function_exists( 'get_transient' ) ) {
	/**
	 * Polyfill for WordPress get_transient().
	 *
	 * Reads from $GLOBALS['wp_movie_test_transients']. Returns false
	 * (cache miss) when the key is unset, matching WordPress semantics.
	 *
	 * @param string $transient Transient name.
	 * @return mixed The stored value, or false on miss.
	 */
	function get_transient( $transient ) {
		return array_key_exists( $transient, $GLOBALS['wp_movie_test_transients'] )
			? $GLOBALS['wp_movie_test_transients'][ $transient ]
			: false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	/**
	 * Polyfill for WordPress set_transient().
	 *
	 * Writes to $GLOBALS['wp_movie_test_transients']. The expiration
	 * argument is accepted for signature compatibility but ignored.
	 *
	 * @param string $transient  Transient name.
	 * @param mixed  $value      Transient value.
	 * @param int    $expiration Time until expiration in seconds (ignored).
	 * @return true
	 */
	function set_transient( $transient, $value, $expiration = 0 ) {
		$GLOBALS['wp_movie_test_transients'][ $transient ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	/**
	 * Polyfill for WordPress delete_transient().
	 *
	 * @param string $transient Transient name.
	 * @return true
	 */
	function delete_transient( $transient ) {
		unset( $GLOBALS['wp_movie_test_transients'][ $transient ] );
		return true;
	}
}

if ( ! isset( $GLOBALS['wp_movie_test_object_cache'] ) ) {
	$GLOBALS['wp_movie_test_object_cache'] = array();
}

if ( ! function_exists( 'wp_cache_get' ) ) {
	/**
	 * Polyfill for WordPress wp_cache_get().
	 *
	 * @param string $key   Cache key.
	 * @param string $group Cache group.
	 * @return mixed The stored value, or false on miss.
	 */
	function wp_cache_get( $key, $group = '' ) {
		$ck = $group . ':' . $key;
		return array_key_exists( $ck, $GLOBALS['wp_movie_test_object_cache'] )
			? $GLOBALS['wp_movie_test_object_cache'][ $ck ]
			: false;
	}
}

if ( ! function_exists( 'wp_cache_set' ) ) {
	/**
	 * Polyfill for WordPress wp_cache_set().
	 *
	 * @param string $key    Cache key.
	 * @param mixed  $value  Value to store.
	 * @param string $group  Cache group.
	 * @param int    $expire Expiration in seconds (ignored).
	 * @return true
	 */
	function wp_cache_set( $key, $value, $group = '', $expire = 0 ) {
		$GLOBALS['wp_movie_test_object_cache'][ $group . ':' . $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'wp_cache_delete' ) ) {
	/**
	 * Polyfill for WordPress wp_cache_delete().
	 *
	 * @param string $key   Cache key.
	 * @param string $group Cache group.
	 * @return true
	 */
	function wp_cache_delete( $key, $group = '' ) {
		unset( $GLOBALS['wp_movie_test_object_cache'][ $group . ':' . $key ] );
		return true;
	}
}

if ( ! function_exists( 'dbDelta' ) ) {
	/**
	 * Polyfill for WordPress dbDelta().
	 *
	 * @param string|array $queries SQL queries to run.
	 * @param bool         $execute Whether to execute the queries.
	 * @return array Empty array in unit tests.
	 */
	function dbDelta( $queries = '', $execute = true ) {
		return array();
	}
}

if ( ! function_exists( '__' ) ) {
	/**
	 * Polyfill for WordPress __() translation function.
	 *
	 * @param string $text   The string to translate.
	 * @param string $domain Optional. Text domain.
	 * @return string The same string, unchanged.
	 */
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	/**
	 * Polyfill for WordPress sanitize_key().
	 *
	 * @param string $key The key to sanitize.
	 * @return string Sanitized key (lowercase alphanumeric, underscore, hyphen).
	 */
	function sanitize_key( $key ) {
		if ( ! is_scalar( $key ) ) {
			return '';
		}
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
	}
}

/**
 * In-memory options store for unit tests.
 *
 * Tests can populate $GLOBALS['wp_movie_test_options'] before instantiating
 * code that calls get_option(). The polyfills below read from and write to
 * this store. The store is intentionally simple — tests should reset it in
 * setUp() if isolation between cases matters.
 *
 * @var array<string, mixed>
 */
if ( ! isset( $GLOBALS['wp_movie_test_options'] ) ) {
	$GLOBALS['wp_movie_test_options'] = array();
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * Polyfill for WordPress get_option().
	 *
	 * Reads from $GLOBALS['wp_movie_test_options']. Uses array_key_exists
	 * (rather than ??) so a stored null/false is returned as-is and only
	 * a genuinely missing key falls back to $default — matching WordPress
	 * semantics where a stored value is distinguishable from "not set".
	 *
	 * @param string $option  Option name.
	 * @param mixed  $default Optional. Default value.
	 * @return mixed The stored value, or $default if the option is not set.
	 */
	function get_option( $option, $default = false ) {
		return array_key_exists( $option, $GLOBALS['wp_movie_test_options'] )
			? $GLOBALS['wp_movie_test_options'][ $option ]
			: $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * Polyfill for WordPress update_option().
	 *
	 * Writes to $GLOBALS['wp_movie_test_options'].
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  Value to store.
	 * @return true
	 */
	function update_option( $option, $value ) {
		$GLOBALS['wp_movie_test_options'][ $option ] = $value;
		return true;
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Minimal polyfill for the WordPress WP_Error class.
	 *
	 * Supports the subset of the API surface used by plugin code under test:
	 * construction with code/message/data and retrieval of those fields.
	 */
	class WP_Error {

		/**
		 * The error code.
		 *
		 * @var string|int
		 */
		private $code;

		/**
		 * The error message.
		 *
		 * @var string
		 */
		private $message;

		/**
		 * The error data.
		 *
		 * @var mixed
		 */
		private $data;

		/**
		 * Constructor.
		 *
		 * @param string|int $code    Error code.
		 * @param string     $message Error message.
		 * @param mixed      $data    Optional. Error data.
		 */
		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		/**
		 * Get the error code.
		 *
		 * @return string|int
		 */
		public function get_error_code() {
			return $this->code;
		}

		/**
		 * Get the error message.
		 *
		 * @return string
		 */
		public function get_error_message() {
			return $this->message;
		}

		/**
		 * Get the error data.
		 *
		 * @return mixed
		 */
		public function get_error_data() {
			return $this->data;
		}
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	/**
	 * Polyfill for WordPress is_wp_error().
	 *
	 * @param mixed $thing The value to check.
	 * @return bool True if $thing is a WP_Error.
	 */
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}

/**
 * In-memory queue of HTTP responses for unit tests.
 *
 * Tests push wp_remote_get-shaped response arrays (or WP_Error instances)
 * into this queue; the wp_remote_get polyfill below pops the next entry
 * for each request. Reset in setUp() for isolation.
 *
 * Each entry is either:
 *   - array( 'response' => array( 'code' => int ),
 *            'body' => string,
 *            'headers' => array<string, string> )
 *   - WP_Error
 *
 * @var array<int, array|\WP_Error>
 */
if ( ! isset( $GLOBALS['wp_movie_test_http_queue'] ) ) {
	$GLOBALS['wp_movie_test_http_queue'] = array();
}

/**
 * Recorded URLs of every wp_remote_get call, in order.
 *
 * Tests can inspect this to assert the API hit the expected provider(s)
 * and in the expected order. Reset in setUp() for isolation.
 *
 * @var array<int, string>
 */
if ( ! isset( $GLOBALS['wp_movie_test_http_log'] ) ) {
	$GLOBALS['wp_movie_test_http_log'] = array();
}

/**
 * Per-filter override values for apply_filters().
 *
 * Tests can register a filter override (e.g. disable retries) by setting
 * $GLOBALS['wp_movie_test_filters'][ $filter_name ] = $value. The polyfill
 * below returns the override when present, or the passed-through value
 * otherwise. Reset in setUp() for isolation.
 *
 * @var array<string, mixed>
 */
if ( ! isset( $GLOBALS['wp_movie_test_filters'] ) ) {
	$GLOBALS['wp_movie_test_filters'] = array();
}

if ( ! function_exists( 'wp_remote_get' ) ) {
	/**
	 * Polyfill for WordPress wp_remote_get().
	 *
	 * Pops the next response from $GLOBALS['wp_movie_test_http_queue']
	 * and records the URL in $GLOBALS['wp_movie_test_http_log']. Returns
	 * a WP_Error when the queue is empty so tests fail loudly on
	 * unexpected requests instead of silently making real network calls.
	 *
	 * @param string $url  Request URL.
	 * @param array  $args wp_remote_get args (ignored by this polyfill).
	 * @return array|\WP_Error The queued response.
	 */
	function wp_remote_get( $url, $args = array() ) {
		$GLOBALS['wp_movie_test_http_log'][] = $url;

		if ( empty( $GLOBALS['wp_movie_test_http_queue'] ) ) {
			return new WP_Error(
				'http_queue_empty',
				'No queued HTTP response for URL: ' . $url
			);
		}

		return array_shift( $GLOBALS['wp_movie_test_http_queue'] );
	}
}

if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	/**
	 * Polyfill for WordPress wp_remote_retrieve_response_code().
	 *
	 * Matches core behavior: returns an empty string when passed a
	 * WP_Error or when the code is missing.
	 *
	 * @param mixed $response Response array or WP_Error.
	 * @return int|string The HTTP status code, or '' on error/missing.
	 */
	function wp_remote_retrieve_response_code( $response ) {
		if ( is_wp_error( $response ) || ! is_array( $response ) ) {
			return '';
		}
		return $response['response']['code'] ?? '';
	}
}

if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	/**
	 * Polyfill for WordPress wp_remote_retrieve_body().
	 *
	 * Matches core behavior: returns an empty string when passed a
	 * WP_Error or when the body is missing.
	 *
	 * @param mixed $response Response array or WP_Error.
	 * @return string The response body, or '' on error/missing.
	 */
	function wp_remote_retrieve_body( $response ) {
		if ( is_wp_error( $response ) || ! is_array( $response ) ) {
			return '';
		}
		return $response['body'] ?? '';
	}
}

if ( ! function_exists( 'wp_remote_retrieve_header' ) ) {
	/**
	 * Polyfill for WordPress wp_remote_retrieve_header().
	 *
	 * Matches core behavior: returns an empty string when passed a
	 * WP_Error or when the header is missing. Header names are
	 * compared case-insensitively, matching core.
	 *
	 * @param mixed  $response Response array or WP_Error.
	 * @param string $header   Header name.
	 * @return string Header value, or '' on error/missing.
	 */
	function wp_remote_retrieve_header( $response, $header ) {
		if ( is_wp_error( $response ) || ! is_array( $response ) || empty( $response['headers'] ) ) {
			return '';
		}
		$needle = strtolower( $header );
		foreach ( $response['headers'] as $name => $value ) {
			if ( strtolower( $name ) === $needle ) {
				return $value;
			}
		}
		return '';
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	/**
	 * Polyfill for WordPress add_query_arg().
	 *
	 * Supports the two call styles used by the plugin:
	 *   add_query_arg( array( $key => $value, ... ), $url )
	 *   add_query_arg( $key, $value, $url )
	 *
	 * @return string URL with the query args appended.
	 */
	function add_query_arg( ...$args ) {
		if ( is_array( $args[0] ) ) {
			$params = $args[0];
			$url    = $args[1];
		} else {
			$params = array( $args[0] => $args[1] );
			$url    = $args[2];
		}

		$separator = strpos( $url, '?' ) === false ? '?' : '&';
		return $url . $separator . http_build_query( $params );
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Polyfill for WordPress apply_filters().
	 *
	 * Returns the override registered in $GLOBALS['wp_movie_test_filters']
	 * for the named filter, or the passed-through value if no override
	 * is registered. Uses array_key_exists (rather than ??) so a test
	 * can intentionally override a filter to null.
	 *
	 * @param string $tag   Filter name.
	 * @param mixed  $value Value to filter.
	 * @return mixed The (possibly overridden) value.
	 */
	function apply_filters( $tag, $value, ...$args ) {
		return array_key_exists( $tag, $GLOBALS['wp_movie_test_filters'] )
			? $GLOBALS['wp_movie_test_filters'][ $tag ]
			: $value;
	}
}

if ( ! function_exists( 'do_action' ) ) {
	/**
	 * Polyfill for WordPress do_action().
	 *
	 * No-op in unit tests: plugin code fires action hooks (e.g. the CPT
	 * sync triggers), but unit tests do not register listeners.
	 *
	 * @param string $tag     Action name.
	 * @param mixed  ...$args Action arguments.
	 * @return void
	 */
	function do_action( $tag, ...$args ) {}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Polyfill for WordPress sanitize_text_field().
	 *
	 * Strips tags and collapses whitespace, approximating core behavior
	 * closely enough for unit tests.
	 *
	 * @param string $str The string to sanitize.
	 * @return string Sanitized single-line string.
	 */
	function sanitize_text_field( $str ) {
		$str = (string) $str;
		$str = wp_strip_all_tags( $str );
		$str = preg_replace( '/[\r\n\t ]+/', ' ', $str );
		return trim( $str );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	/**
	 * Polyfill for WordPress sanitize_textarea_field().
	 *
	 * Strips tags but preserves newlines.
	 *
	 * @param string $str The string to sanitize.
	 * @return string Sanitized multi-line string.
	 */
	function sanitize_textarea_field( $str ) {
		return trim( wp_strip_all_tags( (string) $str ) );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * Polyfill for WordPress wp_strip_all_tags().
	 *
	 * @param string $string The string to strip tags from.
	 * @return string Stripped string.
	 */
	function wp_strip_all_tags( $string ) {
		$string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $string );
		return trim( wp_strip_tags_simple( $string ) );
	}
}

if ( ! function_exists( 'wp_strip_tags_simple' ) ) {
	/**
	 * Helper used by the wp_strip_all_tags polyfill.
	 *
	 * @param string $string The string to strip tags from.
	 * @return string Stripped string.
	 */
	function wp_strip_tags_simple( $string ) {
		return strip_tags( $string );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * Polyfill for WordPress esc_url_raw().
	 *
	 * @param string $url The URL to sanitize.
	 * @return string Sanitized URL.
	 */
	function esc_url_raw( $url ) {
		return filter_var( (string) $url, FILTER_SANITIZE_URL );
	}
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Polyfill for WordPress absint().
	 *
	 * @param mixed $maybeint The value to convert.
	 * @return int Non-negative integer.
	 */
	function absint( $maybeint ) {
		return abs( (int) $maybeint );
	}
}

// ---------------------------------------------------------------------------
// Escaping / templating polyfills used when rendering public templates.
// ---------------------------------------------------------------------------

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		// Explicit UTF-8 + ENT_SUBSTITUTE keeps escaping deterministic and
		// avoids warnings/blank output on invalid byte sequences.
		return htmlspecialchars( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		// Delegate sanitization to esc_url_raw() for consistency, drop URLs
		// with a disallowed scheme (rough mirror of core's allowlist), then
		// attribute-escape (encode &, quotes) for output safety.
		$sanitized = esc_url_raw( (string) $url );
		if ( '' !== $sanitized ) {
			$scheme = wp_parse_url( $sanitized, PHP_URL_SCHEME );
			if ( null !== $scheme && ! in_array( strtolower( (string) $scheme ), array( 'http', 'https', 'mailto', 'ftp' ), true ) ) {
				$sanitized = '';
			}
		}
		return htmlspecialchars( $sanitized, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( $text, $domain = 'default' ) {
		echo esc_html( $text );
	}
}

if ( ! function_exists( 'esc_attr_e' ) ) {
	function esc_attr_e( $text, $domain = 'default' ) {
		echo esc_attr( $text );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text, $domain = 'default' ) {
		return esc_html( $text );
	}
}

if ( ! function_exists( 'esc_attr__' ) ) {
	function esc_attr__( $text, $domain = 'default' ) {
		return esc_attr( $text );
	}
}

if ( ! function_exists( '_e' ) ) {
	function _e( $text, $domain = 'default' ) {
		echo $text;
	}
}

if ( ! function_exists( 'selected' ) ) {
	function selected( $selected, $current = true, $echo = true ) {
		$result = ( (string) $selected === (string) $current ) ? ' selected="selected"' : '';
		if ( $echo ) {
			echo $result;
		}
		return $result;
	}
}

if ( ! function_exists( 'get_permalink' ) ) {
	function get_permalink( $post = 0 ) {
		return 'http://example.com/collection/';
	}
}

if ( ! function_exists( 'get_query_var' ) ) {
	function get_query_var( $var, $default = '' ) {
		return $default;
	}
}

if ( ! function_exists( 'get_pagenum_link' ) ) {
	function get_pagenum_link( $pagenum = 1 ) {
		return 'http://example.com/collection/page/' . (int) $pagenum . '/';
	}
}

if ( ! function_exists( 'get_terms' ) ) {
	function get_terms( $args = array() ) {
		return array();
	}
}

if ( ! function_exists( 'shortcode_atts' ) ) {
	function shortcode_atts( $defaults, $atts, $shortcode = '' ) {
		$atts = (array) $atts;
		$out  = array();
		foreach ( $defaults as $name => $default ) {
			$out[ $name ] = array_key_exists( $name, $atts ) ? $atts[ $name ] : $default;
		}
		// Mirror core: run the shortcode-specific filter when a tag is given.
		if ( '' !== (string) $shortcode ) {
			$out = apply_filters( "shortcode_atts_{$shortcode}", $out, $defaults, $atts, $shortcode );
		}
		return $out;
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * Polyfill for WordPress wp_unslash(): recursively strip slashes.
	 *
	 * Public templates call this when reading $_GET; provide it so tests
	 * that populate $_GET don't fatal in the WordPress-free suite.
	 *
	 * @param string|array $value Value to unslash.
	 * @return string|array
	 */
	function wp_unslash( $value ) {
		if ( is_array( $value ) ) {
			return array_map( 'wp_unslash', $value );
		}
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}

if ( ! function_exists( 'remove_query_arg' ) ) {
	/**
	 * Minimal polyfill for WordPress remove_query_arg().
	 *
	 * Used by the single movie/box-set templates. Returns the base URL
	 * without attempting full query-string surgery, which is sufficient
	 * for rendering assertions.
	 *
	 * @param string|array $key Query key(s) to remove (ignored).
	 * @param string       $url URL to operate on.
	 * @return string
	 */
	function remove_query_arg( $key, $url = '' ) {
		return (string) $url;
	}
}

if ( ! function_exists( 'sanitize_file_name' ) ) {
	/**
	 * Polyfill for WordPress sanitize_file_name().
	 *
	 * @param string $filename The filename to sanitize.
	 * @return string Sanitized filename.
	 */
	function sanitize_file_name( $filename ) {
		$filename = preg_replace( '/[^a-zA-Z0-9._-]/', '-', (string) $filename );
		return preg_replace( '/-+/', '-', $filename );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * Polyfill for WordPress wp_json_encode().
	 *
	 * @param mixed $data    Data to encode.
	 * @param int   $options json_encode options bitmask.
	 * @param int   $depth   Maximum depth.
	 * @return string|false JSON string, or false on failure.
	 */
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

/**
 * Controls the return value of the current_user_can() polyfill.
 *
 * @var bool
 */
if ( ! isset( $GLOBALS['wp_movie_test_current_user_can'] ) ) {
	$GLOBALS['wp_movie_test_current_user_can'] = true;
}

if ( ! function_exists( 'current_user_can' ) ) {
	/**
	 * Polyfill for WordPress current_user_can().
	 *
	 * Returns the value of $GLOBALS['wp_movie_test_current_user_can'] so
	 * tests can simulate authorized and unauthorized requests.
	 *
	 * @param string $capability The capability being checked (ignored).
	 * @return bool
	 */
	function current_user_can( $capability ) {
		return (bool) $GLOBALS['wp_movie_test_current_user_can'];
	}
}

/**
 * Controls the return value of the is_user_logged_in() polyfill.
 *
 * @var bool
 */
if ( ! isset( $GLOBALS['wp_movie_test_user_logged_in'] ) ) {
	$GLOBALS['wp_movie_test_user_logged_in'] = true;
}

if ( ! function_exists( 'is_user_logged_in' ) ) {
	/**
	 * Polyfill for WordPress is_user_logged_in().
	 *
	 * @return bool
	 */
	function is_user_logged_in() {
		return (bool) $GLOBALS['wp_movie_test_user_logged_in'];
	}
}

if ( ! function_exists( 'register_rest_route' ) ) {
	/**
	 * No-op polyfill for WordPress register_rest_route().
	 *
	 * Records registered routes in $GLOBALS['wp_movie_test_rest_routes']
	 * so tests can assert route registration without a REST server.
	 *
	 * @param string $namespace Route namespace.
	 * @param string $route     Route pattern.
	 * @param array  $args      Route args.
	 * @param bool   $override  Whether to override.
	 * @return true
	 */
	function register_rest_route( $namespace, $route, $args = array(), $override = false ) {
		if ( ! isset( $GLOBALS['wp_movie_test_rest_routes'] ) ) {
			$GLOBALS['wp_movie_test_rest_routes'] = array();
		}
		$GLOBALS['wp_movie_test_rest_routes'][] = $namespace . $route;
		return true;
	}
}

if ( ! function_exists( 'rest_ensure_response' ) ) {
	/**
	 * Polyfill for WordPress rest_ensure_response().
	 *
	 * Wraps non-response, non-error data in a WP_REST_Response.
	 *
	 * @param mixed $response The data to wrap.
	 * @return WP_REST_Response|WP_Error
	 */
	function rest_ensure_response( $response ) {
		if ( is_wp_error( $response ) || $response instanceof WP_REST_Response ) {
			return $response;
		}
		return new WP_REST_Response( $response );
	}
}

if ( ! class_exists( 'WP_REST_Server' ) ) {
	/**
	 * Minimal polyfill for WP_REST_Server providing the HTTP method constants.
	 */
	class WP_REST_Server {
		const READABLE  = 'GET';
		const CREATABLE = 'POST';
		const EDITABLE  = 'POST, PUT, PATCH';
		const DELETABLE = 'DELETE';
	}
}

if ( ! class_exists( 'WP_REST_Controller' ) ) {
	/**
	 * Minimal polyfill base class for WP_REST_Controller.
	 *
	 * Provides the `$namespace`/`$rest_base` properties the plugin
	 * controller assigns. Real route registration is exercised via the
	 * register_rest_route() polyfill.
	 */
	class WP_REST_Controller {

		/**
		 * Route namespace.
		 *
		 * @var string
		 */
		protected $namespace = '';

		/**
		 * Route base.
		 *
		 * @var string
		 */
		protected $rest_base = '';
	}
}

if ( ! class_exists( 'WP_REST_Request' ) ) {
	/**
	 * Minimal polyfill for WP_REST_Request.
	 *
	 * Supports get_param()/set_param() and ArrayAccess for route params,
	 * which is the surface the plugin controller relies on.
	 */
	class WP_REST_Request implements ArrayAccess {

		/**
		 * Request parameters.
		 *
		 * @var array
		 */
		private $params;

		/**
		 * Constructor.
		 *
		 * @param array $params Initial parameters.
		 */
		public function __construct( $params = array() ) {
			$this->params = $params;
		}

		/**
		 * Get a parameter value.
		 *
		 * @param string $key Parameter name.
		 * @return mixed Parameter value, or null if unset.
		 */
		public function get_param( $key ) {
			return array_key_exists( $key, $this->params ) ? $this->params[ $key ] : null;
		}

		/**
		 * Set a parameter value.
		 *
		 * @param string $key   Parameter name.
		 * @param mixed  $value Parameter value.
		 */
		public function set_param( $key, $value ) {
			$this->params[ $key ] = $value;
		}

		#[\ReturnTypeWillChange]
		public function offsetExists( $offset ) {
			return isset( $this->params[ $offset ] );
		}

		#[\ReturnTypeWillChange]
		public function offsetGet( $offset ) {
			return $this->get_param( $offset );
		}

		#[\ReturnTypeWillChange]
		public function offsetSet( $offset, $value ) {
			$this->params[ $offset ] = $value;
		}

		#[\ReturnTypeWillChange]
		public function offsetUnset( $offset ) {
			unset( $this->params[ $offset ] );
		}
	}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	/**
	 * Minimal polyfill for WP_REST_Response.
	 *
	 * Captures data, status, and headers for assertions in unit tests.
	 */
	class WP_REST_Response {

		/**
		 * Response payload.
		 *
		 * @var mixed
		 */
		private $data;

		/**
		 * HTTP status code.
		 *
		 * @var int
		 */
		private $status = 200;

		/**
		 * Response headers.
		 *
		 * @var array<string, string>
		 */
		private $headers = array();

		/**
		 * Constructor.
		 *
		 * @param mixed $data   Response data.
		 * @param int   $status HTTP status.
		 */
		public function __construct( $data = null, $status = 200 ) {
			$this->data   = $data;
			$this->status = $status;
		}

		/**
		 * Get the response data.
		 *
		 * @return mixed
		 */
		public function get_data() {
			return $this->data;
		}

		/**
		 * Set the HTTP status.
		 *
		 * @param int $status HTTP status code.
		 */
		public function set_status( $status ) {
			$this->status = (int) $status;
		}

		/**
		 * Get the HTTP status.
		 *
		 * @return int
		 */
		public function get_status() {
			return $this->status;
		}

		/**
		 * Set a header.
		 *
		 * @param string $key   Header name.
		 * @param string $value Header value.
		 */
		public function header( $key, $value ) {
			$this->headers[ $key ] = $value;
		}

		/**
		 * Get all headers.
		 *
		 * @return array<string, string>
		 */
		public function get_headers() {
			return $this->headers;
		}
	}
}

/**
 * Stub class for the WordPress $wpdb global.
 *
 * Used in unit tests to mock database interactions. Tests use
 * PHPUnit's createMock(Stub_Wpdb::class) to get full mock capabilities
 * while this class provides the method/property signatures.
 *
 * @package WP_Movie_Collector\Tests\Unit
 */
class Stub_Wpdb {

	/**
	 * Table prefix.
	 *
	 * @var string
	 */
	public string $prefix = 'wp_';

	/**
	 * The ID generated for an AUTO_INCREMENT column by the last INSERT.
	 *
	 * @var int
	 */
	public int $insert_id = 0;

	/**
	 * The most recent database error message.
	 *
	 * @var string
	 */
	public string $last_error = '';

	/**
	 * Prepare a SQL query for safe execution.
	 *
	 * @param string $query   SQL query with placeholders.
	 * @param mixed  ...$args Values to substitute.
	 * @return string Prepared SQL query.
	 */
	public function prepare( string $query, ...$args ): string {
		return $query;
	}

	/**
	 * Insert a row into a table.
	 *
	 * @param string       $table  Table name.
	 * @param array        $data   Data to insert.
	 * @param array|string $format Optional format specifiers.
	 * @return int|false Number of rows inserted, or false on error.
	 */
	public function insert( string $table, array $data, $format = null ): int|false {
		return 1;
	}

	/**
	 * Update a row in the table.
	 *
	 * @param string       $table        Table name.
	 * @param array        $data         Data to update.
	 * @param array        $where        WHERE clause conditions.
	 * @param array|string $format       Optional format specifiers for data.
	 * @param array|string $where_format Optional format specifiers for where.
	 * @return int|false Number of rows updated, or false on error.
	 */
	public function update( string $table, array $data, array $where, $format = null, $where_format = null ): int|false {
		return 1;
	}

	/**
	 * Delete a row in the table.
	 *
	 * @param string       $table        Table name.
	 * @param array        $where        WHERE clause conditions.
	 * @param array|string $where_format Optional format specifiers.
	 * @return int|false Number of rows deleted, or false on error.
	 */
	public function delete( string $table, array $where, $where_format = null ): int|false {
		return 1;
	}

	/**
	 * Retrieve one row from the database.
	 *
	 * @param string|null $query  SQL query.
	 * @param string      $output Output type constant.
	 * @param int         $y      Row to return (0-indexed).
	 * @return array|object|null Query result row, or null on failure.
	 */
	public function get_row( ?string $query = null, string $output = OBJECT, int $y = 0 ): array|object|null {
		return null;
	}

	/**
	 * Retrieve an entire SQL result set from the database.
	 *
	 * @param string|null $query  SQL query.
	 * @param string      $output Output type constant.
	 * @return array|null Query results, or null on failure.
	 */
	public function get_results( ?string $query = null, string $output = OBJECT ): array|null {
		return array();
	}

	/**
	 * Retrieve one variable from the database.
	 *
	 * @param string|null $query SQL query.
	 * @param int         $x     Column to return (0-indexed).
	 * @param int         $y     Row to return (0-indexed).
	 * @return string|null Database query result, or null on failure.
	 */
	public function get_var( ?string $query = null, int $x = 0, int $y = 0 ): ?string {
		return null;
	}

	/**
	 * Retrieve one column from the database.
	 *
	 * @param string|null $query SQL query.
	 * @param int         $x     Column to return (0-indexed).
	 * @return array Column results as an indexed array.
	 */
	public function get_col( ?string $query = null, int $x = 0 ): array {
		return array();
	}

	/**
	 * Perform a MySQL database query.
	 *
	 * @param string $query Database query.
	 * @return int|false Number of rows affected, or false on error.
	 */
	public function query( string $query ): int|false {
		return 1;
	}

	/**
	 * Retrieve the character set and collation for the current database connection.
	 *
	 * @return string Character set and collation.
	 */
	public function get_charset_collate(): string {
		return 'DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
	}

	/**
	 * Escapes content for use in LIKE clauses.
	 *
	 * @param string $text The text to escape.
	 * @return string Escaped text.
	 */
	public function esc_like( string $text ): string {
		return addcslashes( $text, '_%\\' );
	}
}
