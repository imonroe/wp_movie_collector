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

// Polyfill WordPress functions used in unit-testable code.
if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url, $component = -1 ) {
		return parse_url( $url, $component );
	}
}

