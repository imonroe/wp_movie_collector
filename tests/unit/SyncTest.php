<?php
/**
 * Unit tests for the CPT sync layer.
 *
 * Covers the unit-testable pieces of WP_Movie_Collector_Sync: the
 * comma-separated field → term-name parsing, and the early-return when the
 * source row no longer exists (which must not attempt to create a post).
 * Full create/update/delete behavior against WordPress posts is exercised by
 * the integration suite.
 *
 * @package WP_Movie_Collector\Tests\Unit
 */

namespace WP_Movie_Collector\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Stub_Wpdb;
use WP_Movie_Collector_Sync;

/**
 * Sync layer unit tests.
 */
class SyncTest extends TestCase {

	/**
	 * Prior $wpdb global, restored in tearDown.
	 *
	 * @var mixed
	 */
	private $previous_wpdb = null;

	protected function setUp(): void {
		parent::setUp();
		$this->previous_wpdb = $GLOBALS['wpdb'] ?? null;
	}

	protected function tearDown(): void {
		$GLOBALS['wpdb'] = $this->previous_wpdb;
		parent::tearDown();
	}

	#[DataProvider( 'split_terms_provider' )]
	public function test_split_terms( string $input, array $expected ): void {
		$sync = new WP_Movie_Collector_Sync();
		$ref  = new ReflectionMethod( WP_Movie_Collector_Sync::class, 'split_terms' );
		$ref->setAccessible( true );

		$this->assertSame( $expected, $ref->invoke( $sync, $input ) );
	}

	public static function split_terms_provider(): array {
		return array(
			'empty'              => array( '', array() ),
			'whitespace only'    => array( '   ', array() ),
			'single'             => array( 'Horror', array( 'Horror' ) ),
			'multiple trimmed'   => array( 'Horror, Sci-Fi ,  Thriller', array( 'Horror', 'Sci-Fi', 'Thriller' ) ),
			'drops empty pieces' => array( 'Action,,, Comedy', array( 'Action', 'Comedy' ) ),
		);
	}

	public function test_sync_movie_returns_null_when_row_missing(): void {
		// get_movie() -> $wpdb->get_row() returns null for a missing row.
		$wpdb = $this->getMockBuilder( Stub_Wpdb::class )
			->onlyMethods( array( 'prepare', 'get_row' ) )
			->getMock();
		$wpdb->method( 'prepare' )->willReturnArgument( 0 );
		$wpdb->method( 'get_row' )->willReturn( null );
		$GLOBALS['wpdb'] = $wpdb;

		$sync = new WP_Movie_Collector_Sync();

		$this->assertNull( $sync->sync_movie( 999 ) );
	}

	public function test_sync_box_set_returns_null_when_row_missing(): void {
		$wpdb = $this->getMockBuilder( Stub_Wpdb::class )
			->onlyMethods( array( 'prepare', 'get_row' ) )
			->getMock();
		$wpdb->method( 'prepare' )->willReturnArgument( 0 );
		$wpdb->method( 'get_row' )->willReturn( null );
		$GLOBALS['wpdb'] = $wpdb;

		$sync = new WP_Movie_Collector_Sync();

		$this->assertNull( $sync->sync_box_set( 999 ) );
	}
}
