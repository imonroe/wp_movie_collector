<?php
/**
 * Smoke tests for the WP Movie Collector plugin.
 *
 * Verifies that the plugin files are structured correctly and
 * core classes are loadable.
 *
 * @package WP_Movie_Collector\Tests\Unit
 */

namespace WP_Movie_Collector\Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Plugin smoke tests.
 */
class PluginTest extends TestCase {

	/**
	 * Test that the main plugin file exists.
	 */
	public function test_main_plugin_file_exists(): void {
		$this->assertFileExists( WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'wp-movie-collector.php' );
	}

	/**
	 * Test that all required class files exist.
	 */
	public function test_required_class_files_exist(): void {
		$required_files = array(
			'includes/class-wp-movie-collector.php',
			'includes/class-wp-movie-collector-loader.php',
			'includes/class-wp-movie-collector-activator.php',
			'includes/class-wp-movie-collector-deactivator.php',
			'includes/class-wp-movie-collector-post-types.php',
			'includes/class-wp-movie-collector-api.php',
			'includes/db/class-wp-movie-collector-db.php',
			'admin/class-wp-movie-collector-admin.php',
			'public/class-wp-movie-collector-public.php',
		);

		foreach ( $required_files as $file ) {
			$this->assertFileExists(
				WP_MOVIE_COLLECTOR_PLUGIN_DIR . $file,
				"Required file {$file} is missing."
			);
		}
	}

	/**
	 * Test that the plugin version constant is defined.
	 */
	public function test_plugin_version_constant_defined(): void {
		$this->assertTrue( defined( 'WP_MOVIE_COLLECTOR_VERSION' ) );
		$this->assertNotEmpty( WP_MOVIE_COLLECTOR_VERSION );
	}

	/**
	 * Test that the plugin directory constant is defined.
	 */
	public function test_plugin_dir_constant_defined(): void {
		$this->assertTrue( defined( 'WP_MOVIE_COLLECTOR_PLUGIN_DIR' ) );
		$this->assertDirectoryExists( WP_MOVIE_COLLECTOR_PLUGIN_DIR );
	}

	/**
	 * Test that the plugin main file contains required header fields.
	 */
	public function test_plugin_header_fields(): void {
		$plugin_file = file_get_contents( WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'wp-movie-collector.php' );

		$this->assertStringContainsString( 'Plugin Name:', $plugin_file );
		$this->assertStringContainsString( 'Version:', $plugin_file );
		$this->assertStringContainsString( 'Text Domain:', $plugin_file );
		$this->assertStringContainsString( 'wp-movie-collector', $plugin_file );
	}

	/**
	 * Test that the Loader class can be instantiated.
	 */
	public function test_loader_class_exists(): void {
		$this->assertTrue( class_exists( 'WP_Movie_Collector_Loader' ) );
	}

	/**
	 * Test that the Loader class has required methods.
	 */
	public function test_loader_has_required_methods(): void {
		$loader = new \WP_Movie_Collector_Loader();

		$this->assertTrue( method_exists( $loader, 'add_action' ) );
		$this->assertTrue( method_exists( $loader, 'add_filter' ) );
		$this->assertTrue( method_exists( $loader, 'run' ) );
	}
}
