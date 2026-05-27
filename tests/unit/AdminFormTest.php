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
use ReflectionMethod;
use WP_Movie_Collector_Admin;

/**
 * Admin form/export unit tests.
 */
class AdminFormTest extends TestCase {

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
}
