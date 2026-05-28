<?php
/**
 * Unit tests for admin form export and validation logic.
 *
 * These exercise the pure-logic portions of WP_Movie_Collector_Admin that
 * can run without a live WordPress/database: CSV/JSON export formatting and
 * the happy-path of movie data validation/sanitization (invoked in edit
 * context so the DB-backed duplicate check is skipped).
 *
 * Invalid-input branches of validate_and_sanitize_*_data() end in
 * wp_safe_redirect()/exit and are covered by integration tests rather than
 * here.
 *
 * @package WP_Movie_Collector\Tests\Unit
 */

namespace WP_Movie_Collector\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;
use Stub_Wpdb;
use WP_Movie_Collector_Admin;

/**
 * Admin form/export unit tests.
 */
class AdminFormTest extends TestCase {

	/**
	 * The $wpdb value that existed before a test replaced it (if any).
	 *
	 * @var mixed
	 */
	private mixed $previous_wpdb = null;

	/**
	 * Restore any global $wpdb a test swapped in.
	 */
	protected function tearDown(): void {
		if ( null === $this->previous_wpdb ) {
			unset( $GLOBALS['wpdb'] );
		} else {
			$GLOBALS['wpdb'] = $this->previous_wpdb;
		}
		$this->previous_wpdb = null;
		parent::tearDown();
	}

	/**
	 * Install a mocked $wpdb global and return it for configuration.
	 *
	 * @return Stub_Wpdb&MockObject
	 */
	private function install_wpdb_mock() {
		$this->previous_wpdb = $GLOBALS['wpdb'] ?? null;
		$wpdb                = $this->createMock( Stub_Wpdb::class );
		$wpdb->prefix        = 'wp_';
		$GLOBALS['wpdb']     = $wpdb;
		return $wpdb;
	}

	/**
	 * Invoke a private/protected method on the admin object.
	 *
	 * @param string $method Method name.
	 * @param array  $args   Arguments.
	 * @return mixed
	 */
	private function call_private( string $method, array $args ) {
		$admin = new WP_Movie_Collector_Admin();
		$ref   = new ReflectionMethod( WP_Movie_Collector_Admin::class, $method );
		$ref->setAccessible( true );
		return $ref->invokeArgs( $admin, $args );
	}

	/**
	 * Capture the output of a private export method.
	 *
	 * @param string $method      Export method name.
	 * @param array  $data        Rows to export.
	 * @param string $export_type Export type argument.
	 * @return string Captured output.
	 */
	private function capture_export( string $method, array $data, string $export_type ): string {
		ob_start();
		$this->call_private( $method, array( $data, $export_type ) );
		return (string) ob_get_clean();
	}

	// ------------------------------------------------------------------
	// CSV export
	// ------------------------------------------------------------------

	public function test_export_as_csv_writes_header_and_rows(): void {
		$data = array(
			array(
				'id'    => 1,
				'title' => 'The Matrix',
				'year'  => 1999,
			),
			array(
				'id'    => 2,
				'title' => 'Inception',
				'year'  => 2010,
			),
		);

		$csv = $this->capture_export( 'export_as_csv', $data, 'movies_only' );

		$lines = array_values( array_filter( explode( "\n", trim( $csv ) ) ) );
		$this->assertSame( 'id,title,year', trim( $lines[0] ), 'Header row should be the data keys.' );
		$this->assertStringContainsString( 'The Matrix', $csv );
		$this->assertStringContainsString( 'Inception', $csv );
		$this->assertCount( 3, $lines, 'Expect a header row plus two data rows.' );
	}

	public function test_export_as_csv_quotes_values_with_commas(): void {
		$data = array(
			array(
				'title'  => 'Lock, Stock and Two Smoking Barrels',
				'studio' => 'PolyGram',
			),
		);

		$csv = $this->capture_export( 'export_as_csv', $data, 'movies_only' );

		// A field containing a comma must be wrapped in double quotes by fputcsv.
		$this->assertStringContainsString( '"Lock, Stock and Two Smoking Barrels"', $csv );
	}

	public function test_export_as_csv_emits_default_headers_when_empty(): void {
		$csv = $this->capture_export( 'export_as_csv', array(), 'all' );

		$this->assertStringContainsString( 'title', $csv );
		$this->assertStringContainsString( 'box_set_id', $csv );
		$this->assertStringContainsString( 'type', $csv );
	}

	// ------------------------------------------------------------------
	// JSON export
	// ------------------------------------------------------------------

	public function test_export_as_json_round_trips(): void {
		$data = array(
			array(
				'id'    => 7,
				'title' => 'Heat',
				'year'  => 1995,
			),
		);

		$json    = $this->capture_export( 'export_as_json', $data, 'movies_only' );
		$decoded = json_decode( $json, true );

		$this->assertIsArray( $decoded );
		$this->assertSame( 'Heat', $decoded[0]['title'] );
		$this->assertSame( 1995, $decoded[0]['year'] );
	}

	// ------------------------------------------------------------------
	// Movie validation (edit context skips the DB duplicate check)
	// ------------------------------------------------------------------

	public function test_validate_movie_data_returns_sanitized_fields(): void {
		$input = array(
			'title'        => 'Blade Runner',
			'release_year' => '1982',
			'format'       => 'Blu-ray',
			'region_code'  => 'A',
			'director'     => 'Ridley Scott',
		);

		$result = $this->call_private( 'validate_and_sanitize_movie_data', array( $input, 5 ) );

		$this->assertSame( 'Blade Runner', $result['title'] );
		$this->assertSame( 1982, $result['release_year'] );
		$this->assertSame( 'Blu-ray', $result['format'] );
		$this->assertSame( 'A', $result['region_code'] );
		$this->assertSame( 'Ridley Scott', $result['director'] );
	}

	public function test_validate_movie_data_strips_script_tags_from_text_fields(): void {
		$input = array(
			'title'        => 'Alien<script>alert(1)</script>',
			'release_year' => '1979',
			'format'       => 'DVD',
			'region_code'  => 'R1',
			'director'     => '<b>Ridley</b> Scott',
		);

		$result = $this->call_private( 'validate_and_sanitize_movie_data', array( $input, 5 ) );

		$this->assertStringNotContainsString( '<script>', $result['title'] );
		$this->assertStringNotContainsString( 'alert(1)', $result['title'] );
		$this->assertStringNotContainsString( '<b>', $result['director'] );
	}

	public function test_validate_movie_data_accepts_valid_acquisition_date(): void {
		$input = array(
			'title'            => 'Dune',
			'release_year'     => '2021',
			'format'           => '4K UHD',
			'region_code'      => 'A',
			'acquisition_date' => '2022-03-15',
		);

		$result = $this->call_private( 'validate_and_sanitize_movie_data', array( $input, 5 ) );

		$this->assertSame( '2022-03-15', $result['acquisition_date'] );
	}

	// ------------------------------------------------------------------
	// Import safety (issue #62).
	// ------------------------------------------------------------------

	/**
	 * A syntactically valid JSON scalar must be rejected before any DB write,
	 * so a malformed file can't wipe the collection in replace mode.
	 */
	public function test_import_from_json_rejects_scalar_before_any_db_write(): void {
		$wpdb = $this->install_wpdb_mock();
		// No destructive query and no insert may run for a rejected payload.
		$wpdb->expects( $this->never() )->method( 'insert' );
		$wpdb->expects( $this->never() )->method( 'query' );

		$file = tempnam( sys_get_temp_dir(), 'wpmc' );
		file_put_contents( $file, '42' );

		$result = $this->call_private( 'import_from_json', array( $file, 'replace' ) );

		unlink( $file );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'json_error', $result->get_error_code() );
	}

	/**
	 * Replace-mode import must ROLLBACK and report an error (leaving the
	 * collection intact) when a row fails to insert.
	 */
	public function test_persist_import_replace_rolls_back_on_insert_failure(): void {
		$wpdb = $this->install_wpdb_mock();
		// Transactional engine so the replace path proceeds.
		$wpdb->method( 'get_var' )->willReturn( 'InnoDB' );

		$queries = array();
		$wpdb->method( 'query' )->willReturnCallback(
			function ( $sql ) use ( &$queries ) {
				$queries[] = $sql;
				return 1;
			}
		);
		// Every row insert fails.
		$wpdb->method( 'insert' )->willReturn( false );

		$result = $this->call_private(
			'persist_import',
			array( array( array( 'title' => 'Doomed' ) ), array(), 'replace' )
		);

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'import_failed', $result->get_error_code() );
		$this->assertContains( 'ROLLBACK', $queries );
		$this->assertNotContains( 'COMMIT', $queries );
	}

	/**
	 * Replace-mode import must abort before deleting anything when the tables
	 * use a non-transactional engine (ROLLBACK would be a silent no-op).
	 */
	public function test_persist_import_replace_aborts_on_non_transactional_engine(): void {
		$wpdb = $this->install_wpdb_mock();
		$wpdb->method( 'get_var' )->willReturn( 'MyISAM' );
		// Nothing destructive may run.
		$wpdb->expects( $this->never() )->method( 'query' );
		$wpdb->expects( $this->never() )->method( 'insert' );

		$result = $this->call_private(
			'persist_import',
			array( array( array( 'title' => 'X' ) ), array(), 'replace' )
		);

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertSame( 'import_no_transaction', $result->get_error_code() );
	}

	/**
	 * A movie's denormalized box_set_id must be remapped to the id assigned to
	 * the box set imported alongside it.
	 */
	public function test_persist_import_remaps_box_set_id_references(): void {
		$wpdb = $this->install_wpdb_mock();
		$wpdb->insert_id = 200;

		$captured_movie = null;
		$wpdb->method( 'insert' )->willReturnCallback(
			function ( $table, $data ) use ( &$captured_movie ) {
				if ( false !== strpos( $table, 'movie_collection' ) ) {
					$captured_movie = $data;
				}
				return 1;
			}
		);

		$count = $this->call_private(
			'persist_import',
			array(
				array( array( 'title' => 'M', 'box_set_id' => 5 ) ),
				array( array( 'title' => 'BS', '__source_id' => 5 ) ),
				'append',
			)
		);

		$this->assertSame( 2, $count );
		$this->assertNotNull( $captured_movie );
		$this->assertSame( 200, $captured_movie['box_set_id'] );
	}
}
