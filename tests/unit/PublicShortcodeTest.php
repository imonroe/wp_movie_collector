<?php
/**
 * Unit tests for the public [movie_collection] shortcode rendering.
 *
 * Renders the shortcode through WP_Movie_Collector_Public with a mocked
 * $wpdb so the display template runs without a real database. Verifies that
 * the shortcode produces the expected container markup, reflects the
 * requested type, renders item data, and shows the empty-state message when
 * there are no results.
 *
 * @package WP_Movie_Collector\Tests\Unit
 */

namespace WP_Movie_Collector\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Stub_Wpdb;
use WP_Movie_Collector_Public;

/**
 * Public shortcode rendering tests.
 */
class PublicShortcodeTest extends TestCase {

	/**
	 * Prior $wpdb global, restored in tearDown.
	 *
	 * @var mixed
	 */
	private $previous_wpdb = null;

	/**
	 * Snapshot of $_GET, restored in tearDown.
	 *
	 * @var array
	 */
	private $previous_get = array();

	protected function setUp(): void {
		parent::setUp();
		$this->previous_wpdb = $GLOBALS['wpdb'] ?? null;
		// The display template reads filters/search/sort from $_GET; start
		// from a clean slate so these tests stay isolated and deterministic.
		$this->previous_get = $_GET;
		$_GET               = array();
	}

	protected function tearDown(): void {
		$GLOBALS['wpdb'] = $this->previous_wpdb;
		$_GET            = $this->previous_get;
		parent::tearDown();
	}

	/**
	 * Install a mocked $wpdb that returns the given rows and count.
	 *
	 * @param array $rows  Rows returned by get_results().
	 * @param string $count Value returned by get_var() (COUNT).
	 * @return void
	 */
	private function mock_wpdb( array $rows, string $count ): void {
		$wpdb = $this->getMockBuilder( Stub_Wpdb::class )
			->onlyMethods( array( 'prepare', 'get_results', 'get_var' ) )
			->getMock();
		$wpdb->method( 'prepare' )->willReturnArgument( 0 );
		$wpdb->method( 'get_results' )->willReturn( $rows );
		$wpdb->method( 'get_var' )->willReturn( $count );
		$GLOBALS['wpdb'] = $wpdb;
	}

	private function render( array $atts ): string {
		$public = new WP_Movie_Collector_Public();
		return $public->movie_collection_shortcode( $atts );
	}

	public function test_shortcode_renders_container_and_movie(): void {
		$this->mock_wpdb(
			array(
				array(
					'id'              => 1,
					'title'           => 'Repo Man',
					'release_year'    => 1984,
					'format'          => 'Blu-ray',
					'director'        => 'Alex Cox',
					'cover_image_url' => '',
				),
			),
			'1'
		);

		$html = $this->render( array( 'type' => 'movies' ) );

		$this->assertStringContainsString( 'wp-movie-collector-container', $html );
		$this->assertStringContainsString( 'wp-movie-collector-grid', $html );
		$this->assertStringContainsString( 'Repo Man', $html );
	}

	public function test_shortcode_shows_empty_message_when_no_results(): void {
		$this->mock_wpdb( array(), '0' );

		$html = $this->render( array( 'type' => 'movies' ) );

		$this->assertStringContainsString( 'wp-movie-collector-no-results', $html );
		$this->assertStringNotContainsString( 'wp-movie-collector-grid', $html );
	}

	public function test_shortcode_renders_search_and_filter_controls(): void {
		$this->mock_wpdb( array(), '0' );

		$html = $this->render( array( 'type' => 'movies' ) );

		// Search field and the format filter select should be present.
		$this->assertStringContainsString( 'name="search"', $html );
		$this->assertStringContainsString( 'id="format-filter"', $html );
		$this->assertStringContainsString( 'name="format"', $html );
	}
}
