<?php
/**
 * Integration tests for plugin activation.
 *
 * These tests require the WordPress test suite. Run bin/install-wp-tests.sh
 * to set up the test environment before running integration tests.
 *
 * Usage: composer run test -- --testsuite integration --bootstrap tests/bootstrap.php
 *
 * @package WP_Movie_Collector\Tests\Integration
 */

namespace WP_Movie_Collector\Tests\Integration;

use WP_UnitTestCase;

/**
 * Test plugin activation and setup.
 */
class PluginActivationTest extends WP_UnitTestCase {

	/**
	 * Test that the plugin is loaded.
	 */
	public function test_plugin_is_loaded(): void {
		$this->assertTrue( defined( 'WP_MOVIE_COLLECTOR_VERSION' ) );
	}

	/**
	 * Test that plugin activation creates database tables.
	 */
	public function test_activation_creates_tables(): void {
		global $wpdb;

		activate_wp_movie_collector();

		$movies_table = $wpdb->prefix . 'movie_collection';
		$box_sets_table = $wpdb->prefix . 'movie_box_sets';
		$relationships_table = $wpdb->prefix . 'movie_box_set_relationships';

		$this->assertSame(
			$movies_table,
			$wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $movies_table ) )
		);
		$this->assertSame(
			$box_sets_table,
			$wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $box_sets_table ) )
		);
		$this->assertSame(
			$relationships_table,
			$wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $relationships_table ) )
		);
	}

	/**
	 * Test that the plugin version option is set after activation.
	 */
	public function test_activation_sets_version_option(): void {
		activate_wp_movie_collector();

		$this->assertSame( WP_MOVIE_COLLECTOR_VERSION, get_option( 'wp_movie_collector_version' ) );
	}

	/**
	 * Test that custom post types are registered.
	 */
	public function test_custom_post_types_registered(): void {
		$this->assertTrue( post_type_exists( 'movie' ) );
		$this->assertTrue( post_type_exists( 'box_set' ) );
	}

	/**
	 * Test that custom taxonomies are registered.
	 */
	public function test_custom_taxonomies_registered(): void {
		$this->assertTrue( taxonomy_exists( 'genre' ) );
		$this->assertTrue( taxonomy_exists( 'director' ) );
		$this->assertTrue( taxonomy_exists( 'studio' ) );
		$this->assertTrue( taxonomy_exists( 'actor' ) );
	}
}
