<?php
/**
 * PHPUnit bootstrap file for integration tests.
 *
 * Integration tests require the WordPress test suite and a test database.
 * See bin/install-wp-tests.sh for setup instructions.
 *
 * @package WP_Movie_Collector
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Check for the WordPress test suite.
if ( ! file_exists( "{$_tests_dir}/includes/functions.php" ) ) {
	throw new RuntimeException(
		"Could not find {$_tests_dir}/includes/functions.php." . PHP_EOL .
		'Run bin/install-wp-tests.sh to set up the WordPress test suite.'
	);
}

// Give access to tests_add_filter() function.
require_once "{$_tests_dir}/includes/functions.php";

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( __DIR__ ) . '/wp-movie-collector.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require "{$_tests_dir}/includes/bootstrap.php";
