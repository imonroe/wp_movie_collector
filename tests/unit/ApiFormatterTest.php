<?php
/**
 * Unit tests for API response formatter methods.
 *
 * Exercises the private format_* methods on WP_Movie_Collector_API
 * that translate raw API responses (TMDb, OMDb, BarcodeLookup) into
 * the plugin's normalized movie array shape. These methods are pure
 * data transformations and can be tested in isolation without HTTP
 * mocking or WordPress.
 *
 * @package WP_Movie_Collector\Tests\Unit
 */

namespace WP_Movie_Collector\Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

/**
 * API formatter unit tests.
 */
class ApiFormatterTest extends TestCase {

	/**
	 * An API instance with the constructor bypassed (no get_option calls).
	 *
	 * @var object
	 */
	private object $api;

	/**
	 * Reflection of the API class.
	 *
	 * @var ReflectionClass
	 */
	private ReflectionClass $reflection;

	/**
	 * Set up the test fixture.
	 */
	protected function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'WP_Movie_Collector_API' ) ) {
			$this->fail( 'WP_Movie_Collector_API class does not exist or could not be autoloaded.' );
		}

		$this->reflection = new ReflectionClass( 'WP_Movie_Collector_API' );

		// Bypass the constructor so we don't need a get_option polyfill.
		$this->api = $this->reflection->newInstanceWithoutConstructor();
	}

	/**
	 * Invoke a private/protected method on the API instance.
	 *
	 * @param string $name Method name.
	 * @param array  $args Arguments.
	 * @return mixed The method's return value.
	 */
	private function invoke( string $name, array $args ) {
		$method = $this->reflection->getMethod( $name );
		$method->setAccessible( true );
		return $method->invokeArgs( $this->api, $args );
	}

	// ------------------------------------------------------------------
	// format_movie_data (TMDb)
	// ------------------------------------------------------------------

	/**
	 * Test that format_movie_data extracts the core TMDb fields.
	 */
	public function test_format_movie_data_extracts_core_fields(): void {
		$data = array(
			'title'        => 'The Thing',
			'release_date' => '1982-06-25',
			'overview'     => 'A shape-shifting alien.',
			'poster_path'  => '/abc123.jpg',
		);

		$result = $this->invoke( 'format_movie_data', array( $data ) );

		$this->assertSame( 'The Thing', $result['title'] );
		$this->assertSame( '1982', $result['release_year'] );
		$this->assertSame( 'A shape-shifting alien.', $result['description'] );
		$this->assertSame( 'https://image.tmdb.org/t/p/w500/abc123.jpg', $result['cover_image_url'] );
		$this->assertSame( 'TMDb', $result['api_source'] );
	}

	/**
	 * Test that an empty poster_path yields an empty cover image URL.
	 */
	public function test_format_movie_data_empty_poster_yields_empty_url(): void {
		$data = array(
			'title'        => 'No Poster',
			'release_date' => '2020-01-01',
			'overview'     => 'desc',
			'poster_path'  => '',
		);

		$result = $this->invoke( 'format_movie_data', array( $data ) );

		$this->assertSame( '', $result['cover_image_url'] );
	}

	/**
	 * Test that a missing release_date produces an empty release_year string.
	 */
	public function test_format_movie_data_missing_release_date(): void {
		$data = array(
			'title'        => 'Untitled',
			'release_date' => '',
			'overview'     => 'desc',
			'poster_path'  => null,
		);

		$result = $this->invoke( 'format_movie_data', array( $data ) );

		$this->assertSame( '', $result['release_year'] );
	}

	/**
	 * Test that only crew members with job=Director are extracted.
	 */
	public function test_format_movie_data_extracts_only_directors(): void {
		$data = array(
			'title'        => 't',
			'release_date' => '2000-01-01',
			'overview'     => '',
			'poster_path'  => '',
			'credits'      => array(
				'crew' => array(
					array(
						'job'  => 'Director',
						'name' => 'Jane Doe',
					),
					array(
						'job'  => 'Producer',
						'name' => 'Bob Smith',
					),
					array(
						'job'  => 'Director',
						'name' => 'John Roe',
					),
				),
			),
		);

		$result = $this->invoke( 'format_movie_data', array( $data ) );

		$this->assertSame( 'Jane Doe, John Roe', $result['director'] );
	}

	/**
	 * Test that actor extraction is capped at 10 entries.
	 */
	public function test_format_movie_data_caps_actors_at_ten(): void {
		$cast = array();
		for ( $i = 1; $i <= 15; $i++ ) {
			$cast[] = array( 'name' => "Actor {$i}" );
		}

		$data = array(
			'title'        => 't',
			'release_date' => '2000-01-01',
			'overview'     => '',
			'poster_path'  => '',
			'credits'      => array( 'cast' => $cast ),
		);

		$result = $this->invoke( 'format_movie_data', array( $data ) );

		$actors = explode( ', ', $result['actors'] );
		$this->assertCount( 10, $actors );
		$this->assertSame( 'Actor 1', $actors[0] );
		$this->assertSame( 'Actor 10', $actors[9] );
	}

	/**
	 * Test that genres and production_companies are flattened to comma lists.
	 */
	public function test_format_movie_data_flattens_genres_and_studios(): void {
		$data = array(
			'title'                => 't',
			'release_date'         => '2000-01-01',
			'overview'             => '',
			'poster_path'          => '',
			'genres'               => array(
				array( 'name' => 'Horror' ),
				array( 'name' => 'Sci-Fi' ),
			),
			'production_companies' => array(
				array( 'name' => 'Universal' ),
				array( 'name' => 'Turman-Foster Company' ),
			),
		);

		$result = $this->invoke( 'format_movie_data', array( $data ) );

		$this->assertSame( 'Horror, Sci-Fi', $result['genre'] );
		$this->assertSame( 'Universal, Turman-Foster Company', $result['studio'] );
	}

	/**
	 * Test that missing optional sections are simply absent from the output.
	 */
	public function test_format_movie_data_omits_missing_optional_sections(): void {
		$data = array(
			'title'        => 't',
			'release_date' => '2000-01-01',
			'overview'     => '',
			'poster_path'  => '',
		);

		$result = $this->invoke( 'format_movie_data', array( $data ) );

		$this->assertArrayNotHasKey( 'director', $result );
		$this->assertArrayNotHasKey( 'actors', $result );
		$this->assertArrayNotHasKey( 'genre', $result );
		$this->assertArrayNotHasKey( 'studio', $result );
	}

	/**
	 * Test that a TMDb payload missing core keys yields empty strings
	 * rather than emitting undefined-index warnings.
	 */
	public function test_format_movie_data_missing_core_fields(): void {
		$result = $this->invoke( 'format_movie_data', array( array() ) );

		$this->assertSame( '', $result['title'] );
		$this->assertSame( '', $result['description'] );
		$this->assertSame( '', $result['release_year'] );
		$this->assertSame( '', $result['cover_image_url'] );
		$this->assertSame( 'TMDb', $result['api_source'] );
	}

	// ------------------------------------------------------------------
	// format_omdb_movie_data
	// ------------------------------------------------------------------

	/**
	 * Test that OMDb data is mapped to the plugin's normalized shape.
	 */
	public function test_format_omdb_movie_data_maps_all_fields(): void {
		$data = array(
			'Title'      => 'The Thing',
			'Year'       => '1982',
			'Director'   => 'John Carpenter',
			'Actors'     => 'Kurt Russell, Wilford Brimley',
			'Plot'       => 'A shape-shifting alien.',
			'Poster'     => 'https://example.com/thing.jpg',
			'Genre'      => 'Horror, Sci-Fi',
			'Production' => 'Universal',
		);

		$result = $this->invoke( 'format_omdb_movie_data', array( $data ) );

		$this->assertSame( 'The Thing', $result['title'] );
		$this->assertSame( '1982', $result['release_year'] );
		$this->assertSame( 'John Carpenter', $result['director'] );
		$this->assertSame( 'Kurt Russell, Wilford Brimley', $result['actors'] );
		$this->assertSame( 'A shape-shifting alien.', $result['description'] );
		$this->assertSame( 'https://example.com/thing.jpg', $result['cover_image_url'] );
		$this->assertSame( 'Horror, Sci-Fi', $result['genre'] );
		$this->assertSame( 'Universal', $result['studio'] );
		$this->assertSame( 'OMDb', $result['api_source'] );
	}

	/**
	 * Test that an OMDb poster of "N/A" is normalized to an empty string.
	 */
	public function test_format_omdb_movie_data_handles_na_poster(): void {
		$data = array(
			'Title'    => 't',
			'Year'     => '2000',
			'Director' => 'd',
			'Actors'   => 'a',
			'Plot'     => 'p',
			'Poster'   => 'N/A',
			'Genre'    => 'g',
		);

		$result = $this->invoke( 'format_omdb_movie_data', array( $data ) );

		$this->assertSame( '', $result['cover_image_url'] );
		$this->assertArrayNotHasKey( 'studio', $result );
	}

	/**
	 * Test that an OMDb Production of "N/A" is omitted from the studio field.
	 */
	public function test_format_omdb_movie_data_skips_na_production(): void {
		$data = array(
			'Title'      => 't',
			'Year'       => '2000',
			'Director'   => 'd',
			'Actors'     => 'a',
			'Plot'       => 'p',
			'Poster'     => 'https://example.com/p.jpg',
			'Genre'      => 'g',
			'Production' => 'N/A',
		);

		$result = $this->invoke( 'format_omdb_movie_data', array( $data ) );

		$this->assertArrayNotHasKey( 'studio', $result );
	}

	/**
	 * Test that an OMDb payload missing mapped keys yields empty strings
	 * (and still normalizes cover_image_url) rather than warning.
	 */
	public function test_format_omdb_movie_data_missing_fields(): void {
		$result = $this->invoke( 'format_omdb_movie_data', array( array() ) );

		$this->assertSame( '', $result['title'] );
		$this->assertSame( '', $result['release_year'] );
		$this->assertSame( '', $result['director'] );
		$this->assertSame( '', $result['actors'] );
		$this->assertSame( '', $result['description'] );
		$this->assertSame( '', $result['genre'] );
		$this->assertSame( '', $result['cover_image_url'] );
		$this->assertSame( 'OMDb', $result['api_source'] );
		$this->assertArrayNotHasKey( 'studio', $result );
	}

	// ------------------------------------------------------------------
	// format_barcode_data
	// ------------------------------------------------------------------

	/**
	 * Test that a year in parentheses is split out of the barcode title.
	 */
	public function test_format_barcode_data_extracts_year_from_title(): void {
		$data = array(
			'title'        => 'The Thing (1982)',
			'barcode'      => '025192110825',
			'description'  => 'Horror classic.',
			'images'       => array( 'https://example.com/thing.jpg' ),
			'brand'        => 'Universal',
			'category'     => 'Movies > Horror',
		);

		$result = $this->invoke( 'format_barcode_data', array( $data ) );

		$this->assertSame( 'The Thing', $result['title'] );
		$this->assertSame( '1982', $result['release_year'] );
		$this->assertSame( '025192110825', $result['barcode'] );
		$this->assertSame( 'Horror classic.', $result['description'] );
		$this->assertSame( 'https://example.com/thing.jpg', $result['cover_image_url'] );
		$this->assertSame( 'Universal', $result['studio'] );
		$this->assertSame( 'Movies > Horror', $result['genre'] );
		$this->assertSame( 'BarcodeLookup', $result['api_source'] );
	}

	/**
	 * Test that a title without a parenthesized year is passed through unchanged
	 * and no release_year key is set.
	 */
	public function test_format_barcode_data_no_year_in_title(): void {
		$data = array(
			'title' => 'Mystery Disc',
		);

		$result = $this->invoke( 'format_barcode_data', array( $data ) );

		$this->assertSame( 'Mystery Disc', $result['title'] );
		$this->assertArrayNotHasKey( 'release_year', $result );
	}

	/**
	 * Test that manufacturer is used when brand is absent.
	 */
	public function test_format_barcode_data_falls_back_to_manufacturer(): void {
		$data = array(
			'title'        => 'Some Disc',
			'manufacturer' => 'Acme Pictures',
		);

		$result = $this->invoke( 'format_barcode_data', array( $data ) );

		$this->assertSame( 'Acme Pictures', $result['studio'] );
	}

	/**
	 * Test that brand takes precedence over manufacturer when both are present.
	 */
	public function test_format_barcode_data_brand_takes_precedence_over_manufacturer(): void {
		$data = array(
			'title'        => 'Some Disc',
			'brand'        => 'Studio Brand',
			'manufacturer' => 'OEM Manufacturer',
		);

		$result = $this->invoke( 'format_barcode_data', array( $data ) );

		$this->assertSame( 'Studio Brand', $result['studio'] );
	}

	/**
	 * Test that completely empty input produces a minimal but valid record.
	 */
	public function test_format_barcode_data_handles_empty_input(): void {
		$result = $this->invoke( 'format_barcode_data', array( array() ) );

		$this->assertSame( '', $result['title'] );
		$this->assertSame( '', $result['barcode'] );
		$this->assertSame( 'BarcodeLookup', $result['api_source'] );
		$this->assertArrayNotHasKey( 'release_year', $result );
		$this->assertArrayNotHasKey( 'description', $result );
		$this->assertArrayNotHasKey( 'cover_image_url', $result );
		$this->assertArrayNotHasKey( 'studio', $result );
		$this->assertArrayNotHasKey( 'genre', $result );
	}

	/**
	 * Test that an empty images array does not set a cover_image_url key.
	 */
	public function test_format_barcode_data_empty_images_array(): void {
		$data = array(
			'title'  => 'Some Disc',
			'images' => array(),
		);

		$result = $this->invoke( 'format_barcode_data', array( $data ) );

		$this->assertArrayNotHasKey( 'cover_image_url', $result );
	}

	// ------------------------------------------------------------------
	// Method visibility contracts
	// ------------------------------------------------------------------

	/**
	 * Test that the formatter methods remain private — they're implementation
	 * details and should not be part of the public API surface.
	 *
	 * @return array<string, array<int, string>>
	 */
	public static function private_formatter_methods(): array {
		return array(
			'format_movie_data'      => array( 'format_movie_data' ),
			'format_omdb_movie_data' => array( 'format_omdb_movie_data' ),
			'format_barcode_data'    => array( 'format_barcode_data' ),
		);
	}

	#[DataProvider( 'private_formatter_methods' )]
	public function test_formatter_method_is_private( string $method_name ): void {
		$method = new ReflectionMethod( 'WP_Movie_Collector_API', $method_name );
		$this->assertTrue( $method->isPrivate(), "{$method_name} should be private." );
	}
}
