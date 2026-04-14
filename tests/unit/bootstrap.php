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
	 * @param bool   $gmt     Whether to use GMT time. Ignored in polyfill.
	 * @return string|int     Formatted date string or Unix timestamp.
	 */
	function current_time( $type, $gmt = 0 ) {
		if ( 'mysql' === $type || 'Y-m-d H:i:s' === $type ) {
			return gmdate( 'Y-m-d H:i:s' );
		}
		if ( 'timestamp' === $type || 'U' === $type ) {
			return time();
		}
		return gmdate( $type );
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	/**
	 * Polyfill for WordPress get_transient().
	 *
	 * Always returns false (cache miss) in unit tests.
	 *
	 * @param string $transient Transient name.
	 * @return false
	 */
	function get_transient( $transient ) {
		return false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	/**
	 * Polyfill for WordPress set_transient().
	 *
	 * @param string $transient  Transient name.
	 * @param mixed  $value      Transient value.
	 * @param int    $expiration Time until expiration in seconds.
	 * @return true
	 */
	function set_transient( $transient, $value, $expiration = 0 ) {
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
	 * @return array|object|null Query results, or null on failure.
	 */
	public function get_results( ?string $query = null, string $output = OBJECT ): array|object|null {
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
	 * @return int|bool Number of rows affected, or false on error.
	 */
	public function query( string $query ): int|bool {
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
