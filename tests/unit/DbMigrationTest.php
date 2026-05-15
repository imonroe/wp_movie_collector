<?php
/**
 * Runtime unit tests for WP_Movie_Collector_DB::update_tables().
 *
 * Exercises the migration end-to-end against a mocked $wpdb so we can
 * assert that the created_at and acquisition_date index additions:
 *   - run against both the movies and box_sets tables,
 *   - batch missing keys into a single ALTER TABLE per table,
 *   - and are a no-op when the indexes already exist.
 *
 * @package WP_Movie_Collector\Tests\Unit
 */

namespace WP_Movie_Collector\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Stub_Wpdb;
use WP_Movie_Collector_DB;

/**
 * Database migration runtime tests.
 */
class DbMigrationTest extends TestCase {

	/**
	 * Captured $wpdb->query() invocations during update_tables().
	 *
	 * @var array<int, string>
	 */
	private array $captured_queries;

	/**
	 * Original $wpdb (if any) to restore on tearDown.
	 *
	 * @var mixed
	 */
	private mixed $previous_wpdb = null;

	/**
	 * Names of indexes the mocked SHOW INDEX should report as missing.
	 *
	 * Defaults to "everything exists"; tests override this to simulate
	 * missing indexes before invoking update_tables().
	 *
	 * @var array<int, string>
	 */
	private array $missing_indexes = array();

	/**
	 * Tear down: restore the original $wpdb.
	 */
	protected function tearDown(): void {
		if ( null === $this->previous_wpdb ) {
			unset( $GLOBALS['wpdb'] );
		} else {
			$GLOBALS['wpdb'] = $this->previous_wpdb;
		}
		parent::tearDown();
	}

	/**
	 * Install a $wpdb mock that simulates a given set of missing indexes.
	 *
	 * The mock's prepare() substitutes %s/%d placeholders so subsequent
	 * get_results() callbacks can inspect the literal SQL and decide what
	 * to return. SHOW COLUMNS always returns a non-empty result (so the
	 * pre-existing column-add migrations no-op). SHOW INDEX returns empty
	 * for any index name listed in $this->missing_indexes, otherwise a
	 * non-empty result. Every query() call is captured for later
	 * assertion.
	 *
	 * @param array<int, string> $missing_indexes Names of indexes to
	 *                                            report as missing.
	 */
	private function install_wpdb_mock( array $missing_indexes = array() ): void {
		$this->captured_queries = array();
		$this->missing_indexes  = $missing_indexes;

		$this->previous_wpdb = $GLOBALS['wpdb'] ?? null;

		$wpdb         = $this->createMock( Stub_Wpdb::class );
		$wpdb->prefix = 'wp_';

		// Substitute %s / %d placeholders with quoted args so the
		// get_results callback can see which index is being checked.
		$wpdb->method( 'prepare' )->willReturnCallback(
			function ( string $query, ...$args ): string {
				foreach ( $args as $arg ) {
					$replacement = is_int( $arg )
						? (string) $arg
						: "'" . addslashes( (string) $arg ) . "'";
					$query       = preg_replace( '/%[sd]/', $replacement, $query, 1 );
				}
				return $query;
			}
		);

		// SHOW COLUMNS: always present (column-add migrations no-op).
		// SHOW INDEX: empty iff the literal key name appears in
		// $this->missing_indexes.
		$missing = $this->missing_indexes;
		$wpdb->method( 'get_results' )->willReturnCallback(
			function ( ?string $query ) use ( $missing ): array {
				if ( null === $query ) {
					return array();
				}
				if ( false !== stripos( $query, 'SHOW INDEX' )
					&& preg_match( "/Key_name = '([^']+)'/", $query, $m )
					&& in_array( $m[1], $missing, true )
				) {
					return array();
				}
				// Default: index/column exists.
				return array( (object) array( 'Field' => 'present' ) );
			}
		);

		$captured = &$this->captured_queries;
		$wpdb->method( 'query' )->willReturnCallback(
			function ( string $query ) use ( &$captured ): int {
				$captured[] = $query;
				return 1;
			}
		);

		$GLOBALS['wpdb'] = $wpdb;
	}

	/**
	 * Get the captured ALTER TABLE queries scoped to a given table.
	 *
	 * @param string $table Table name to match.
	 * @return array<int, string>
	 */
	private function alter_queries_for( string $table ): array {
		return array_values(
			array_filter(
				$this->captured_queries,
				static fn( string $q ): bool => str_contains( $q, "ALTER TABLE {$table} " )
			)
		);
	}

	// ------------------------------------------------------------------
	// "Already migrated" — no ALTER fires for the new indexes
	// ------------------------------------------------------------------

	/**
	 * Test that no ALTER TABLE for created_at / acquisition_date is issued
	 * when both indexes already exist on both tables.
	 */
	public function test_update_tables_is_noop_when_indexes_exist(): void {
		$this->install_wpdb_mock( array() );

		$db = new WP_Movie_Collector_DB();
		$db->update_tables();

		$matching = array_filter(
			$this->captured_queries,
			static fn( string $q ): bool =>
				str_contains( $q, 'created_at' ) || str_contains( $q, 'acquisition_date' )
		);

		$this->assertSame(
			array(),
			$matching,
			'No ALTER TABLE should be issued for indexes that already exist.'
		);
	}

	// ------------------------------------------------------------------
	// All-missing case — one batched ALTER per table
	// ------------------------------------------------------------------

	/**
	 * Test that when both indexes are missing on both tables, the
	 * migration issues exactly one ALTER TABLE per table with both
	 * ADD INDEX clauses batched together.
	 */
	public function test_update_tables_batches_both_missing_indexes_per_table(): void {
		$this->install_wpdb_mock( array( 'created_at', 'acquisition_date' ) );

		$db = new WP_Movie_Collector_DB();
		$db->update_tables();

		$movies   = $this->alter_queries_for( 'wp_movie_collection' );
		$box_sets = $this->alter_queries_for( 'wp_movie_box_sets' );

		$this->assertCount( 1, $movies, 'Movies table should receive one ALTER TABLE.' );
		$this->assertCount( 1, $box_sets, 'Box sets table should receive one ALTER TABLE.' );

		foreach ( array( $movies[0], $box_sets[0] ) as $query ) {
			$this->assertStringContainsString( 'ADD INDEX created_at (created_at)', $query );
			$this->assertStringContainsString( 'ADD INDEX acquisition_date (acquisition_date)', $query );
		}
	}

	// ------------------------------------------------------------------
	// Partial-missing case — only the missing index is added
	// ------------------------------------------------------------------

	/**
	 * Test that when only one of the two indexes is missing, the
	 * resulting ALTER TABLE contains only that ADD INDEX clause.
	 */
	public function test_update_tables_only_adds_the_missing_index(): void {
		$this->install_wpdb_mock( array( 'acquisition_date' ) );

		$db = new WP_Movie_Collector_DB();
		$db->update_tables();

		$movies = $this->alter_queries_for( 'wp_movie_collection' );

		$this->assertCount( 1, $movies );
		$this->assertStringContainsString( 'ADD INDEX acquisition_date (acquisition_date)', $movies[0] );
		$this->assertStringNotContainsString( 'ADD INDEX created_at (created_at)', $movies[0] );
	}
}
