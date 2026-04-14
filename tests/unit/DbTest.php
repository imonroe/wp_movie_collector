<?php
/**
 * Unit tests for the database layer.
 *
 * Verifies that WP_Movie_Collector_DB correctly delegates to the
 * WordPress $wpdb global for CRUD, relationship, and search operations.
 * Uses a mocked $wpdb so tests run without a real database.
 *
 * @package WP_Movie_Collector\Tests\Unit
 */

namespace WP_Movie_Collector\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Stub_Wpdb;
use WP_Movie_Collector_DB;

/**
 * Database layer unit tests.
 */
class DbTest extends TestCase {

	/**
	 * Mock for the $wpdb global.
	 *
	 * @var Stub_Wpdb&MockObject
	 */
	private $wpdb;

	/**
	 * The database class under test.
	 *
	 * @var WP_Movie_Collector_DB
	 */
	private WP_Movie_Collector_DB $db;

	/**
	 * The $wpdb value that existed before setUp replaced it (if any).
	 *
	 * @var mixed
	 */
	private mixed $previous_wpdb = null;

	/**
	 * Sample movie fixture.
	 *
	 * @var array<string, mixed>
	 */
	private array $movie_fixture = array(
		'title'            => 'The Thing',
		'release_year'     => 1982,
		'format'           => 'Blu-ray',
		'region_code'      => 'R1',
		'barcode'          => '025192110825',
		'director'         => 'John Carpenter',
		'studio'           => 'Universal',
		'actors'           => 'Kurt Russell, Wilford Brimley',
		'genre'            => 'Horror, Sci-Fi',
		'special_features' => 'Commentary',
		'cover_image_url'  => 'https://example.com/thing.jpg',
		'description'      => 'A shape-shifting alien.',
		'acquisition_date' => '2024-01-15',
	);

	/**
	 * Set up the test fixture.
	 */
	protected function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'WP_Movie_Collector_DB' ) ) {
			$this->fail( 'WP_Movie_Collector_DB class does not exist or could not be autoloaded.' );
		}

		// Preserve any pre-existing $wpdb so tearDown can restore it.
		$this->previous_wpdb = $GLOBALS['wpdb'] ?? null;

		$this->wpdb         = $this->createMock( Stub_Wpdb::class );
		$this->wpdb->prefix = 'wp_';

		$GLOBALS['wpdb'] = $this->wpdb;

		$this->db = new WP_Movie_Collector_DB();
	}

	/**
	 * Tear down the test fixture.
	 */
	protected function tearDown(): void {
		// Restore the original $wpdb rather than always unsetting it.
		if ( null === $this->previous_wpdb ) {
			unset( $GLOBALS['wpdb'] );
		} else {
			$GLOBALS['wpdb'] = $this->previous_wpdb;
		}
		parent::tearDown();
	}

	// ------------------------------------------------------------------
	// Table name configuration.
	// ------------------------------------------------------------------

	/**
	 * Table names should be prefixed with the WordPress table prefix.
	 */
	public function test_table_names_use_wpdb_prefix(): void {
		$this->assertSame( 'wp_movie_collection', $this->db->get_movies_table() );
		$this->assertSame( 'wp_movie_box_sets', $this->db->get_box_sets_table() );
		$this->assertSame( 'wp_movie_box_set_relationships', $this->db->get_relationships_table() );
	}

	/**
	 * Table names should honor a custom wpdb prefix.
	 */
	public function test_table_names_respect_custom_prefix(): void {
		$this->wpdb->prefix = 'custom_';
		$db                 = new WP_Movie_Collector_DB();

		$this->assertSame( 'custom_movie_collection', $db->get_movies_table() );
		$this->assertSame( 'custom_movie_box_sets', $db->get_box_sets_table() );
		$this->assertSame( 'custom_movie_box_set_relationships', $db->get_relationships_table() );
	}

	// ------------------------------------------------------------------
	// Movie CRUD.
	// ------------------------------------------------------------------

	/**
	 * insert_movie should insert data and return the new insert_id.
	 */
	public function test_insert_movie_returns_new_id_on_success(): void {
		$captured = null;

		$this->wpdb->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->equalTo( 'wp_movie_collection' ),
				$this->callback(
					function ( $data ) use ( &$captured ) {
						$captured = $data;
						return is_array( $data ) && 'The Thing' === $data['title'];
					}
				)
			)
			->willReturn( 1 );

		$this->wpdb->insert_id = 42;

		$result = $this->db->insert_movie( $this->movie_fixture );

		$this->assertSame( 42, $result );
		$this->assertArrayHasKey( 'created_at', $captured );
		$this->assertArrayHasKey( 'updated_at', $captured );
	}

	/**
	 * insert_movie should return false when wpdb->insert fails.
	 */
	public function test_insert_movie_returns_false_on_failure(): void {
		$this->wpdb->method( 'insert' )->willReturn( false );
		$this->wpdb->insert_id = 0;

		$this->assertFalse( $this->db->insert_movie( $this->movie_fixture ) );
	}

	/**
	 * get_movie should return the matching row as an associative array.
	 */
	public function test_get_movie_returns_row_for_existing_id(): void {
		$row = array_merge( $this->movie_fixture, array( 'id' => 7 ) );

		$this->wpdb->method( 'prepare' )->willReturn( 'SELECT prepared' );
		$this->wpdb->expects( $this->once() )
			->method( 'get_row' )
			->with( 'SELECT prepared', ARRAY_A )
			->willReturn( $row );

		$this->assertSame( $row, $this->db->get_movie( 7 ) );
	}

	/**
	 * get_movie should return null for a non-existent ID.
	 */
	public function test_get_movie_returns_null_when_not_found(): void {
		$this->wpdb->method( 'prepare' )->willReturn( 'SELECT prepared' );
		$this->wpdb->method( 'get_row' )->willReturn( null );

		$this->assertNull( $this->db->get_movie( 999 ) );
	}

	/**
	 * get_movie_by_barcode should return the matching row.
	 */
	public function test_get_movie_by_barcode_returns_row_when_found(): void {
		$row = array_merge( $this->movie_fixture, array( 'id' => 3 ) );

		$this->wpdb->method( 'prepare' )->willReturn( 'SELECT prepared' );
		$this->wpdb->expects( $this->once() )
			->method( 'get_row' )
			->willReturn( $row );

		$this->assertSame( $row, $this->db->get_movie_by_barcode( '025192110825' ) );
	}

	/**
	 * get_movie_by_barcode should return null when not found.
	 */
	public function test_get_movie_by_barcode_returns_null_when_not_found(): void {
		$this->wpdb->method( 'prepare' )->willReturn( 'SELECT prepared' );
		$this->wpdb->method( 'get_row' )->willReturn( null );

		$this->assertNull( $this->db->get_movie_by_barcode( '000' ) );
	}

	/**
	 * update_movie should call wpdb->update and set updated_at.
	 */
	public function test_update_movie_succeeds_when_rows_changed(): void {
		$captured = null;

		$this->wpdb->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->equalTo( 'wp_movie_collection' ),
				$this->callback(
					function ( $data ) use ( &$captured ) {
						$captured = $data;
						return is_array( $data ) && isset( $data['updated_at'] );
					}
				),
				$this->equalTo( array( 'id' => 5 ) )
			)
			->willReturn( 1 );

		$this->assertTrue( $this->db->update_movie( 5, array( 'title' => 'Updated' ) ) );
		$this->assertArrayHasKey( 'updated_at', $captured );
		$this->assertSame( 'Updated', $captured['title'] );
	}

	/**
	 * update_movie should treat zero affected rows as success (idempotent).
	 */
	public function test_update_movie_returns_true_when_no_rows_changed(): void {
		$this->wpdb->method( 'update' )->willReturn( 0 );

		$this->assertTrue( $this->db->update_movie( 5, array( 'title' => 'Same' ) ) );
	}

	/**
	 * update_movie should return false on wpdb error.
	 */
	public function test_update_movie_returns_false_on_error(): void {
		$this->wpdb->method( 'update' )->willReturn( false );

		$this->assertFalse( $this->db->update_movie( 5, array( 'title' => 'x' ) ) );
	}

	/**
	 * delete_movie should delete relationships first, then the movie.
	 */
	public function test_delete_movie_removes_relationships_and_record(): void {
		$delete_calls = array();

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->willReturnCallback(
				function ( $table, $where ) use ( &$delete_calls ) {
					$delete_calls[] = array(
						'table' => $table,
						'where' => $where,
					);
					return 1;
				}
			);

		$this->assertTrue( $this->db->delete_movie( 9 ) );
		$this->assertCount( 2, $delete_calls );
		$this->assertSame( 'wp_movie_box_set_relationships', $delete_calls[0]['table'] );
		$this->assertSame( array( 'movie_id' => 9 ), $delete_calls[0]['where'] );
		$this->assertSame( 'wp_movie_collection', $delete_calls[1]['table'] );
		$this->assertSame( array( 'id' => 9 ), $delete_calls[1]['where'] );
	}

	/**
	 * delete_movie should propagate wpdb->delete failure for the movie row.
	 */
	public function test_delete_movie_returns_false_when_delete_fails(): void {
		$this->wpdb->method( 'delete' )->willReturnOnConsecutiveCalls( 1, false );

		$this->assertFalse( $this->db->delete_movie( 9 ) );
	}

	// ------------------------------------------------------------------
	// Box Set CRUD.
	// ------------------------------------------------------------------

	/**
	 * insert_box_set should insert data and return the new ID.
	 */
	public function test_insert_box_set_returns_new_id_on_success(): void {
		$this->wpdb->method( 'insert' )->willReturn( 1 );
		$this->wpdb->insert_id = 77;

		$result = $this->db->insert_box_set(
			array(
				'title'        => 'Nightmare on Elm Street Collection',
				'release_year' => 1999,
				'format'       => 'Blu-ray',
				'region_code'  => 'R1',
				'barcode'      => '883929123456',
			)
		);

		$this->assertSame( 77, $result );
	}

	/**
	 * insert_box_set should return false on wpdb failure.
	 */
	public function test_insert_box_set_returns_false_on_failure(): void {
		$this->wpdb->method( 'insert' )->willReturn( false );

		$this->assertFalse( $this->db->insert_box_set( array( 'title' => 'x' ) ) );
	}

	/**
	 * get_box_set should return the row for an existing ID.
	 */
	public function test_get_box_set_returns_row_when_found(): void {
		$row = array(
			'id'    => 4,
			'title' => 'Star Wars Saga',
		);

		$this->wpdb->method( 'prepare' )->willReturn( 'SELECT prepared' );
		$this->wpdb->method( 'get_row' )->willReturn( $row );

		$this->assertSame( $row, $this->db->get_box_set( 4 ) );
	}

	/**
	 * get_box_set should return null when not found.
	 */
	public function test_get_box_set_returns_null_when_not_found(): void {
		$this->wpdb->method( 'prepare' )->willReturn( 'SELECT prepared' );
		$this->wpdb->method( 'get_row' )->willReturn( null );

		$this->assertNull( $this->db->get_box_set( 999 ) );
	}

	/**
	 * get_box_set_by_barcode should return the row for a matching barcode.
	 */
	public function test_get_box_set_by_barcode_returns_row_when_found(): void {
		$row = array(
			'id'      => 4,
			'title'   => 'Matrix Trilogy',
			'barcode' => '883929999999',
		);

		$this->wpdb->method( 'prepare' )->willReturn( 'SELECT prepared' );
		$this->wpdb->method( 'get_row' )->willReturn( $row );

		$this->assertSame( $row, $this->db->get_box_set_by_barcode( '883929999999' ) );
	}

	/**
	 * update_box_set should set updated_at and return true on success.
	 */
	public function test_update_box_set_returns_true_when_rows_changed(): void {
		$captured = null;

		$this->wpdb->expects( $this->once() )
			->method( 'update' )
			->with(
				$this->equalTo( 'wp_movie_box_sets' ),
				$this->callback(
					function ( $data ) use ( &$captured ) {
						$captured = $data;
						return true;
					}
				),
				$this->equalTo( array( 'id' => 3 ) )
			)
			->willReturn( 1 );

		$this->assertTrue( $this->db->update_box_set( 3, array( 'title' => 'Renamed' ) ) );
		$this->assertArrayHasKey( 'updated_at', $captured );
	}

	/**
	 * delete_box_set should delete relationships first, then the box set.
	 */
	public function test_delete_box_set_removes_relationships_and_record(): void {
		$delete_calls = array();

		$this->wpdb->expects( $this->exactly( 2 ) )
			->method( 'delete' )
			->willReturnCallback(
				function ( $table, $where ) use ( &$delete_calls ) {
					$delete_calls[] = array(
						'table' => $table,
						'where' => $where,
					);
					return 1;
				}
			);

		$this->assertTrue( $this->db->delete_box_set( 8 ) );
		$this->assertCount( 2, $delete_calls );
		$this->assertSame( 'wp_movie_box_set_relationships', $delete_calls[0]['table'] );
		$this->assertSame( array( 'box_set_id' => 8 ), $delete_calls[0]['where'] );
		$this->assertSame( 'wp_movie_box_sets', $delete_calls[1]['table'] );
		$this->assertSame( array( 'id' => 8 ), $delete_calls[1]['where'] );
	}

	// ------------------------------------------------------------------
	// Box set relationships.
	// ------------------------------------------------------------------

	/**
	 * add_movie_to_box_set should short-circuit when the pairing already exists.
	 */
	public function test_add_movie_to_box_set_returns_existing_relationship(): void {
		$this->wpdb->method( 'prepare' )->willReturn( 'SELECT prepared' );
		// wpdb->get_var() returns string values in practice; use loose equality to
		// accept either a string or int return from the DB layer.
		$this->wpdb->method( 'get_var' )->willReturn( '99' );

		// Should NOT call insert when relationship exists.
		$this->wpdb->expects( $this->never() )->method( 'insert' );

		$this->assertEquals( 99, $this->db->add_movie_to_box_set( 3, 5 ) );
	}

	/**
	 * add_movie_to_box_set should insert a new relationship with next display_order.
	 */
	public function test_add_movie_to_box_set_creates_new_relationship(): void {
		// First get_var returns null (no existing), second returns "3" (next order).
		$this->wpdb->method( 'prepare' )->willReturn( 'SELECT prepared' );
		$this->wpdb->method( 'get_var' )->willReturnOnConsecutiveCalls( null, '3' );

		$captured_data = null;

		$this->wpdb->expects( $this->once() )
			->method( 'insert' )
			->with(
				$this->equalTo( 'wp_movie_box_set_relationships' ),
				$this->callback(
					function ( $data ) use ( &$captured_data ) {
						$captured_data = $data;
						return true;
					}
				)
			)
			->willReturn( 1 );

		$this->wpdb->insert_id = 101;

		$this->assertSame( 101, $this->db->add_movie_to_box_set( 3, 5 ) );
		$this->assertSame( 3, $captured_data['movie_id'] );
		$this->assertSame( 5, $captured_data['box_set_id'] );
		$this->assertSame( 3, $captured_data['display_order'] );
	}

	/**
	 * add_movie_to_box_set should return false if the insert fails.
	 */
	public function test_add_movie_to_box_set_returns_false_on_insert_failure(): void {
		$this->wpdb->method( 'prepare' )->willReturn( 'SELECT prepared' );
		$this->wpdb->method( 'get_var' )->willReturnOnConsecutiveCalls( null, '1' );
		$this->wpdb->method( 'insert' )->willReturn( false );

		$this->assertFalse( $this->db->add_movie_to_box_set( 3, 5 ) );
	}

	/**
	 * remove_movie_from_box_set should delete the relationship.
	 */
	public function test_remove_movie_from_box_set_returns_true_on_success(): void {
		$this->wpdb->expects( $this->once() )
			->method( 'delete' )
			->with(
				$this->equalTo( 'wp_movie_box_set_relationships' ),
				$this->equalTo(
					array(
						'movie_id'   => 3,
						'box_set_id' => 5,
					)
				)
			)
			->willReturn( 1 );

		$this->assertTrue( $this->db->remove_movie_from_box_set( 3, 5 ) );
	}

	/**
	 * remove_movie_from_box_set should treat zero deletions as success.
	 */
	public function test_remove_movie_from_box_set_returns_true_when_not_found(): void {
		$this->wpdb->method( 'delete' )->willReturn( 0 );

		$this->assertTrue( $this->db->remove_movie_from_box_set( 3, 5 ) );
	}

	/**
	 * remove_movie_from_box_set should return false on a wpdb error.
	 */
	public function test_remove_movie_from_box_set_returns_false_on_error(): void {
		$this->wpdb->method( 'delete' )->willReturn( false );

		$this->assertFalse( $this->db->remove_movie_from_box_set( 3, 5 ) );
	}

	/**
	 * get_movies_in_box_set should return the list of movies.
	 */
	public function test_get_movies_in_box_set_returns_rows(): void {
		$expected = array(
			array(
				'id'    => 1,
				'title' => 'A New Hope',
			),
			array(
				'id'    => 2,
				'title' => 'Empire Strikes Back',
			),
		);

		$this->wpdb->method( 'prepare' )->willReturn( 'JOIN prepared' );
		$this->wpdb->method( 'get_results' )->willReturn( $expected );

		$this->assertSame( $expected, $this->db->get_movies_in_box_set( 1 ) );
	}

	/**
	 * get_box_sets_containing_movie should return the list of box sets.
	 */
	public function test_get_box_sets_containing_movie_returns_rows(): void {
		$expected = array(
			array(
				'id'    => 7,
				'title' => 'Sci-Fi Classics',
			),
		);

		$this->wpdb->method( 'prepare' )->willReturn( 'JOIN prepared' );
		$this->wpdb->method( 'get_results' )->willReturn( $expected );

		$this->assertSame( $expected, $this->db->get_box_sets_containing_movie( 42 ) );
	}

	// ------------------------------------------------------------------
	// Search & count.
	// ------------------------------------------------------------------

	/**
	 * search_movies with no criteria should run an unfiltered query.
	 */
	public function test_search_movies_with_no_criteria_returns_all(): void {
		$expected = array(
			array(
				'id'    => 1,
				'title' => 'A',
			),
			array(
				'id'    => 2,
				'title' => 'B',
			),
		);

		// Verify the query has no WHERE clause when no criteria are provided.
		$this->wpdb->expects( $this->once() )
			->method( 'get_results' )
			->with(
				$this->callback(
					static function ( $sql ): bool {
						return is_string( $sql ) && false === stripos( $sql, 'WHERE' );
					}
				),
				ARRAY_A
			)
			->willReturn( $expected );

		$this->assertSame( $expected, $this->db->search_movies( array() ) );
	}

	/**
	 * search_movies should build a LIKE clause for title and invoke prepare.
	 */
	public function test_search_movies_with_title_uses_like_clause(): void {
		$prepare_args = array();

		$this->wpdb->expects( $this->once() )
			->method( 'esc_like' )
			->with( 'Thing' )
			->willReturn( 'Thing' );

		$this->wpdb->expects( $this->atLeastOnce() )
			->method( 'prepare' )
			->willReturnCallback(
				function ( $sql, ...$args ) use ( &$prepare_args ) {
					$prepare_args[] = array(
						'sql'  => $sql,
						'args' => $args,
					);
					return $sql;
				}
			);

		$this->wpdb->method( 'get_results' )->willReturn( array() );

		$this->db->search_movies( array( 'title' => 'Thing' ) );

		// Find the prepare() call that contains the title LIKE predicate.
		$title_like_call = null;
		foreach ( $prepare_args as $call ) {
			if ( is_string( $call['sql'] ) && false !== stripos( $call['sql'], 'title LIKE' ) ) {
				$title_like_call = $call;
				break;
			}
		}

		$this->assertNotNull( $title_like_call, 'Expected a prepare() call containing a title LIKE predicate.' );

		// The LIKE value (%Thing%) should be in the prepare() arguments.
		$args = $title_like_call['args'];
		if ( 1 === count( $args ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}
		$this->assertContains( '%Thing%', $args );
	}

	/**
	 * search_movies should apply LIMIT/OFFSET when paginated.
	 */
	public function test_search_movies_applies_pagination(): void {
		$prepare_args = array();

		$this->wpdb->expects( $this->atLeastOnce() )
			->method( 'prepare' )
			->willReturnCallback(
				function ( $sql, ...$args ) use ( &$prepare_args ) {
					$prepare_args[] = array(
						'sql'  => $sql,
						'args' => $args,
					);
					return $sql;
				}
			);
		$this->wpdb->method( 'get_results' )->willReturn( array() );

		$this->db->search_movies(
			array(
				'per_page' => 10,
				'page'     => 3,
			)
		);

		$pagination_call = null;
		foreach ( $prepare_args as $call ) {
			if ( str_contains( $call['sql'], 'LIMIT' ) ) {
				$pagination_call = $call;
				break;
			}
		}

		$this->assertNotNull( $pagination_call, 'Expected a prepare() call with LIMIT/OFFSET.' );
		// Args may be a single array or spread values depending on call site; accept either.
		$args = $pagination_call['args'];
		if ( 1 === count( $args ) && is_array( $args[0] ) ) {
			$args = $args[0];
		}
		$this->assertSame( 10, $args[0] );
		$this->assertSame( 20, $args[1] );
	}

	/**
	 * search_movies should sanitize the ORDER BY to a whitelisted column.
	 */
	public function test_search_movies_rejects_unsafe_orderby(): void {
		$captured_sql = '';

		$this->wpdb->method( 'get_results' )
			->willReturnCallback(
				function ( $sql, ...$rest ) use ( &$captured_sql ) {
					$captured_sql = $sql;
					return array();
				}
			);

		$this->db->search_movies( array( 'orderby' => 'DROP TABLE users' ) );

		// Whitelist should reject the unsafe value and default to 'title'.
		$this->assertStringContainsString( 'ORDER BY title', $captured_sql );
		$this->assertStringNotContainsString( 'DROP TABLE', $captured_sql );
	}

	/**
	 * search_movies should allow a whitelisted ORDER BY and ASC/DESC.
	 */
	public function test_search_movies_accepts_valid_orderby_and_order(): void {
		$captured_sql = '';

		$this->wpdb->method( 'get_results' )
			->willReturnCallback(
				function ( $sql, ...$rest ) use ( &$captured_sql ) {
					$captured_sql = $sql;
					return array();
				}
			);

		$this->db->search_movies(
			array(
				'orderby' => 'release_year',
				'order'   => 'desc',
			)
		);

		$this->assertStringContainsString( 'ORDER BY release_year DESC', $captured_sql );
	}

	/**
	 * count_movies with no criteria should run an unfiltered COUNT(*).
	 */
	public function test_count_movies_returns_total_with_no_criteria(): void {
		$this->wpdb->expects( $this->never() )->method( 'prepare' );
		$this->wpdb->expects( $this->once() )
			->method( 'get_var' )
			->willReturn( '42' );

		$this->assertSame( 42, $this->db->count_movies() );
	}

	/**
	 * count_movies should prepare the query when criteria are provided.
	 */
	public function test_count_movies_prepares_with_criteria(): void {
		$this->wpdb->method( 'esc_like' )->willReturn( 'Horror' );
		$this->wpdb->expects( $this->atLeastOnce() )
			->method( 'prepare' )
			->willReturn( 'prepared' );
		$this->wpdb->method( 'get_var' )->willReturn( '5' );

		$this->assertSame( 5, $this->db->count_movies( array( 'genre' => 'Horror' ) ) );
	}

	/**
	 * count_box_sets with no criteria should run an unfiltered COUNT(*).
	 */
	public function test_count_box_sets_returns_total_with_no_criteria(): void {
		$this->wpdb->method( 'get_var' )->willReturn( '12' );

		$this->assertSame( 12, $this->db->count_box_sets() );
	}

	/**
	 * search_box_sets with no criteria should run an unfiltered query.
	 */
	public function test_search_box_sets_with_no_criteria_returns_all(): void {
		$expected = array(
			array(
				'id'    => 1,
				'title' => 'Box A',
			),
		);

		$this->wpdb->method( 'get_results' )->willReturn( $expected );

		$this->assertSame( $expected, $this->db->search_box_sets( array() ) );
	}

	// ------------------------------------------------------------------
	// Duplicate detection.
	// ------------------------------------------------------------------

	/**
	 * find_duplicate_movies should return both match sets when both matches are found.
	 */
	public function test_find_duplicate_movies_returns_barcode_and_title_matches(): void {
		$barcode_row = array(
			'id'           => 1,
			'title'        => 'The Thing',
			'release_year' => 1982,
			'format'       => 'Blu-ray',
			'barcode'      => '025192110825',
		);
		$title_rows  = array(
			array(
				'id'           => 1,
				'title'        => 'The Thing',
				'release_year' => 1982,
				'format'       => 'Blu-ray',
				'barcode'      => '025192110825',
			),
		);

		$this->wpdb->method( 'prepare' )->willReturn( 'prepared' );
		$this->wpdb->method( 'get_row' )->willReturn( $barcode_row );
		$this->wpdb->method( 'get_results' )->willReturn( $title_rows );

		$result = $this->db->find_duplicate_movies( 'The Thing', 1982, '025192110825' );

		$this->assertSame( $barcode_row, $result['barcode_match'] );
		$this->assertSame( $title_rows, $result['title_matches'] );
	}

	/**
	 * find_duplicate_movies should skip the barcode query when no barcode is given.
	 */
	public function test_find_duplicate_movies_skips_barcode_check_when_empty(): void {
		$this->wpdb->method( 'prepare' )->willReturn( 'prepared' );
		$this->wpdb->expects( $this->never() )->method( 'get_row' );
		$this->wpdb->method( 'get_results' )->willReturn( array() );

		$result = $this->db->find_duplicate_movies( 'The Thing', 1982, '' );

		$this->assertNull( $result['barcode_match'] );
		$this->assertSame( array(), $result['title_matches'] );
	}

	/**
	 * find_duplicate_movies should return empty results when no title/year/barcode are given.
	 */
	public function test_find_duplicate_movies_returns_empty_when_no_input(): void {
		$this->wpdb->expects( $this->never() )->method( 'prepare' );
		$this->wpdb->expects( $this->never() )->method( 'get_row' );
		$this->wpdb->expects( $this->never() )->method( 'get_results' );

		$result = $this->db->find_duplicate_movies( '', 0, '' );

		$this->assertNull( $result['barcode_match'] );
		$this->assertSame( array(), $result['title_matches'] );
	}

	/**
	 * find_duplicate_box_sets should mirror the movie duplicate detection.
	 */
	public function test_find_duplicate_box_sets_returns_matches(): void {
		$barcode_row = array(
			'id'      => 9,
			'title'   => 'Matrix Trilogy',
			'barcode' => '883929999999',
		);

		$this->wpdb->method( 'prepare' )->willReturn( 'prepared' );
		$this->wpdb->method( 'get_row' )->willReturn( $barcode_row );
		$this->wpdb->method( 'get_results' )->willReturn( array() );

		$result = $this->db->find_duplicate_box_sets( 'Matrix Trilogy', 1999, '883929999999' );

		$this->assertSame( $barcode_row, $result['barcode_match'] );
		$this->assertSame( array(), $result['title_matches'] );
	}

	// ------------------------------------------------------------------
	// Cache invalidation.
	// ------------------------------------------------------------------

	/**
	 * invalidate_stats_cache should be callable without error.
	 *
	 * The transient functions are polyfilled to no-ops, so the method
	 * should simply execute cleanly.
	 */
	public function test_invalidate_stats_cache_runs_without_error(): void {
		$this->expectNotToPerformAssertions();
		$this->db->invalidate_stats_cache();
	}
}
