<?php
/**
 * Unit tests for the database schema definitions and migration.
 *
 * Verifies that WP_Movie_Collector_DB::create_tables() defines the
 * expected indexes for fresh installs and that update_tables() adds
 * any missing indexes for existing installs. Index coverage matters
 * because both columns indexed here (created_at, acquisition_date)
 * appear in the search ORDER BY whitelist and power the "recently
 * added" / "recently acquired" admin listings — without indexes
 * those queries degrade to filesorts as the collection grows.
 *
 * Source-level assertions (rather than running the schema against a
 * real database) match the pattern established by ApiCacheTest and
 * keep the test suite WordPress-free.
 *
 * @package WP_Movie_Collector\Tests\Unit
 */

namespace WP_Movie_Collector\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Database schema unit tests.
 */
class DbSchemaTest extends TestCase {

	/**
	 * Read the DB class source file once for all assertions.
	 *
	 * @return string The source file contents.
	 */
	private function read_db_source(): string {
		$path = WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'includes/db/class-wp-movie-collector-db.php';

		$this->assertFileExists( $path, "DB class source file does not exist at {$path}" );
		$this->assertIsReadable( $path, "DB class source file is not readable at {$path}" );

		return file_get_contents( $path );
	}

	/**
	 * Extract the create_tables() method body from the source.
	 *
	 * @param string $source The full file contents.
	 * @return string The method body, or an empty string if not found.
	 */
	private function extract_create_tables_body( string $source ): string {
		if ( ! preg_match( '/public function create_tables\(\)\s*\{(.*?)\n\t\}/s', $source, $m ) ) {
			$this->fail( 'Could not locate create_tables() in the DB class source.' );
		}
		return $m[1];
	}

	/**
	 * Extract the update_tables() method body from the source.
	 *
	 * @param string $source The full file contents.
	 * @return string The method body, or an empty string if not found.
	 */
	private function extract_update_tables_body( string $source ): string {
		if ( ! preg_match( '/public function update_tables\(\)\s*\{(.*?)\n\t\}/s', $source, $m ) ) {
			$this->fail( 'Could not locate update_tables() in the DB class source.' );
		}
		return $m[1];
	}

	// ------------------------------------------------------------------
	// create_tables() — schema for new installs
	// ------------------------------------------------------------------

	/**
	 * Provider of (table-variable, index-key) pairs the schema must declare.
	 *
	 * @return array<string, array{0:string,1:string}>
	 */
	public static function expected_schema_indexes(): array {
		return array(
			'movies created_at'           => array( '$this->movies_table', 'KEY created_at (created_at)' ),
			'movies acquisition_date'     => array( '$this->movies_table', 'KEY acquisition_date (acquisition_date)' ),
			'box_sets created_at'         => array( '$this->box_sets_table', 'KEY created_at (created_at)' ),
			'box_sets acquisition_date'   => array( '$this->box_sets_table', 'KEY acquisition_date (acquisition_date)' ),
			// Sanity-check that existing indexes are still present.
			'movies barcode'              => array( '$this->movies_table', 'KEY barcode (barcode)' ),
			'movies title_year composite' => array( '$this->movies_table', 'KEY title_year (title, release_year)' ),
		);
	}

	/**
	 * Test that create_tables() declares the expected index for the named table.
	 */
	#[DataProvider( 'expected_schema_indexes' )]
	public function test_create_tables_declares_index( string $table_var, string $key_clause ): void {
		$body = $this->extract_create_tables_body( $this->read_db_source() );

		// Split on CREATE TABLE so we can scope the assertion to the
		// correct table block. The body contains all three CREATE TABLE
		// statements concatenated; the section for a given table runs
		// from its "CREATE TABLE {$table_var}" header to the next
		// "CREATE TABLE" header (or end of body).
		$pattern = '/CREATE TABLE\s+' . preg_quote( $table_var, '/' ) . '\b(.*?)(?=CREATE TABLE|\Z)/s';
		if ( ! preg_match( $pattern, $body, $m ) ) {
			$this->fail( "CREATE TABLE block for {$table_var} not found in create_tables()." );
		}

		$this->assertStringContainsString(
			$key_clause,
			$m[1],
			"Expected {$key_clause} in CREATE TABLE for {$table_var}"
		);
	}

	// ------------------------------------------------------------------
	// update_tables() — migration for existing installs
	// ------------------------------------------------------------------

	/**
	 * Test that update_tables() guards each new index behind a SHOW INDEX
	 * existence check so re-running the migration is a no-op.
	 *
	 * @return array<string, array<int, string>>
	 */
	public static function expected_migration_index_names(): array {
		return array(
			'created_at'       => array( 'created_at' ),
			'acquisition_date' => array( 'acquisition_date' ),
		);
	}

	#[DataProvider( 'expected_migration_index_names' )]
	public function test_update_tables_guards_index_creation( string $index_name ): void {
		$body = $this->extract_update_tables_body( $this->read_db_source() );

		$this->assertMatchesRegularExpression(
			'/SHOW INDEX FROM[^`]*`\{\$table\}`[^`]*WHERE Key_name = %s/',
			$body,
			'Migration should use a parameterized SHOW INDEX guard via the $table loop.'
		);
		$this->assertStringContainsString(
			"'{$index_name}'",
			$body,
			"Migration loop should iterate over the {$index_name} index."
		);
	}

	/**
	 * Test that the migration issues the parameterized ALTER TABLE for
	 * each missing index, scoped to both the movies and box_sets tables.
	 *
	 * Missing keys are batched into a single ALTER per table so the
	 * underlying engine only rebuilds each table once.
	 */
	public function test_update_tables_adds_index_for_both_tables(): void {
		$body = $this->extract_update_tables_body( $this->read_db_source() );

		// The migration loops over both tables and both index columns.
		$this->assertMatchesRegularExpression(
			'/foreach\s*\(\s*array\(\s*\$this->get_movies_table\(\),\s*\$this->get_box_sets_table\(\)\s*\)/',
			$body,
			'Migration should loop over both tables.'
		);
		$this->assertMatchesRegularExpression(
			'/foreach\s*\(\s*array\(\s*\'created_at\',\s*\'acquisition_date\'\s*\)/',
			$body,
			'Migration should loop over both index columns.'
		);
		// Missing keys are collected into a list of ADD INDEX clauses...
		$this->assertMatchesRegularExpression(
			'/"ADD INDEX \{\$key\} \(\{\$key\}\)"/',
			$body,
			'Migration should build per-key ADD INDEX clauses scoped to the $key loop variable.'
		);
		// ...and applied as a single ALTER TABLE per table.
		$this->assertMatchesRegularExpression(
			'/ALTER TABLE \{\$table\} "\s*\.\s*implode\(\s*\',\s*\',\s*\$add_clauses\s*\)/',
			$body,
			'Migration should batch missing indexes into a single ALTER TABLE per table.'
		);
	}
}
