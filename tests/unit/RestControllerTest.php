<?php
/**
 * Unit tests for the REST API controller.
 *
 * These tests exercise the controller's logic in isolation using a mocked
 * database layer and the lightweight REST stubs defined in the unit
 * bootstrap. They cover response shaping, request-to-criteria mapping,
 * payload validation, pagination headers, permission checks, and the
 * CRUD/relationship handlers.
 *
 * @package WP_Movie_Collector\Tests\Unit
 */

use PHPUnit\Framework\TestCase;

require_once dirname( __DIR__, 2 ) . '/includes/db/class-wp-movie-collector-db.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-wp-movie-collector-rest-controller.php';

class RestControllerTest extends TestCase {

	/**
	 * Mocked database layer.
	 *
	 * @var WP_Movie_Collector_DB&\PHPUnit\Framework\MockObject\MockObject
	 */
	private $db;

	/**
	 * Controller under test.
	 *
	 * @var WP_Movie_Collector_REST_Controller
	 */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		// Reset auth globals to the permissive default.
		$GLOBALS['wp_movie_test_current_user_can'] = true;
		$GLOBALS['wp_movie_test_user_logged_in']   = true;
		$GLOBALS['wp_movie_test_rest_routes']      = array();

		$this->db         = $this->createMock( WP_Movie_Collector_DB::class );
		$this->controller = new WP_Movie_Collector_REST_Controller( $this->db );
	}

	/**
	 * A sample raw movie row as it would come from the database.
	 *
	 * @return array
	 */
	private function sample_movie_row() {
		return array(
			'id'               => '7',
			'title'            => 'The Thing',
			'release_year'     => '1982',
			'format'           => 'Blu-ray',
			'region_code'      => 'A',
			'barcode'          => '012345678905',
			'director'         => 'John Carpenter',
			'studio'           => 'Universal',
			'actors'           => 'Kurt Russell',
			'genre'            => 'Horror',
			'special_features' => 'Commentary',
			'cover_image_url'  => 'http://example.com/thing.jpg',
			'cover_image_id'   => '42',
			'description'      => 'Antarctic dread.',
			'acquisition_date' => '2020-01-01',
			'box_set_id'       => '3',
			'api_source'       => 'TMDb',
			'custom_notes'     => 'Mint condition.',
			'created_at'       => '2020-01-01 10:00:00',
			'updated_at'       => '2020-01-02 10:00:00',
		);
	}

	public function test_register_routes_registers_all_endpoints() {
		$this->controller->register_routes();

		$routes = $GLOBALS['wp_movie_test_rest_routes'];

		$this->assertContains( 'movie-collection/v1/movies', $routes );
		$this->assertContains( 'movie-collection/v1/movies/(?P<id>[\d]+)', $routes );
		$this->assertContains( 'movie-collection/v1/box-sets', $routes );
		$this->assertContains( 'movie-collection/v1/box-sets/(?P<id>[\d]+)', $routes );
		$this->assertContains( 'movie-collection/v1/box-sets/(?P<id>[\d]+)/movies', $routes );
		$this->assertContains( 'movie-collection/v1/box-sets/(?P<id>[\d]+)/movies/(?P<movie_id>[\d]+)', $routes );
	}

	public function test_prepare_movie_for_response_casts_types() {
		$prepared = $this->controller->prepare_movie_for_response( $this->sample_movie_row() );

		$this->assertSame( 7, $prepared['id'] );
		$this->assertSame( 1982, $prepared['release_year'] );
		$this->assertSame( 42, $prepared['cover_image_id'] );
		$this->assertSame( 3, $prepared['box_set_id'] );
		$this->assertSame( 'The Thing', $prepared['title'] );
		$this->assertArrayHasKey( 'created_at', $prepared );
	}

	public function test_prepare_box_set_for_response_casts_types() {
		$prepared = $this->controller->prepare_box_set_for_response(
			array(
				'id'           => '5',
				'title'        => 'Nightmare Collection',
				'release_year' => '1999',
			)
		);

		$this->assertSame( 5, $prepared['id'] );
		$this->assertSame( 1999, $prepared['release_year'] );
		$this->assertSame( '', $prepared['format'] );
		$this->assertArrayNotHasKey( 'box_set_id', $prepared );
	}

	public function test_build_query_criteria_maps_and_clamps_params() {
		$request = new WP_REST_Request(
			array(
				'title'    => '  Alien  ',
				'year'     => '1979',
				'format'   => 'DVD',
				'order'    => 'desc',
				'orderby'  => 'release_year',
				'per_page' => '500',
				'page'     => '0',
			)
		);

		$criteria = $this->controller->build_query_criteria( $request );

		$this->assertSame( 'Alien', $criteria['title'] );
		$this->assertSame( 1979, $criteria['year'] );
		$this->assertSame( 'DVD', $criteria['format'] );
		$this->assertSame( 'DESC', $criteria['order'] );
		$this->assertSame( 'release_year', $criteria['orderby'] );
		$this->assertSame( WP_Movie_Collector_REST_Controller::MAX_PER_PAGE, $criteria['per_page'] );
		$this->assertSame( 1, $criteria['page'] );
	}

	public function test_get_movies_returns_data_with_pagination_headers() {
		$this->db->method( 'count_movies' )->willReturn( 25 );
		$this->db->method( 'search_movies' )->willReturn( array( $this->sample_movie_row() ) );

		$request  = new WP_REST_Request( array( 'per_page' => '10' ) );
		$response = $this->controller->get_movies( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$data = $response->get_data();
		$this->assertCount( 1, $data );
		$this->assertSame( 7, $data[0]['id'] );

		$headers = $response->get_headers();
		$this->assertSame( '25', $headers['X-WP-Total'] );
		$this->assertSame( '3', $headers['X-WP-TotalPages'] );
	}

	public function test_get_movie_not_found_returns_404_error() {
		$this->db->method( 'get_movie' )->willReturn( null );

		$request = new WP_REST_Request( array( 'id' => 999 ) );
		$result  = $this->controller->get_movie( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	public function test_create_movie_validates_required_fields() {
		$request = new WP_REST_Request( array( 'title' => 'Missing Fields' ) );
		$result  = $this->controller->create_movie( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	public function test_create_movie_rejects_invalid_format() {
		$request = new WP_REST_Request(
			array(
				'title'        => 'Bad Format',
				'release_year' => 2000,
				'format'       => 'Betamax',
				'region_code'  => 'A',
			)
		);
		$result = $this->controller->create_movie( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertStringContainsString( 'Invalid format', $result->get_error_message() );
	}

	public function test_create_movie_success_returns_201() {
		$this->db->method( 'insert_movie' )->willReturn( 7 );
		$this->db->method( 'get_movie' )->willReturn( $this->sample_movie_row() );

		$request = new WP_REST_Request(
			array(
				'title'        => 'The Thing',
				'release_year' => 1982,
				'format'       => 'Blu-ray',
				'region_code'  => 'A',
			)
		);
		$response = $this->controller->create_movie( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 201, $response->get_status() );
		$this->assertSame( 7, $response->get_data()['id'] );
	}

	public function test_update_movie_missing_record_returns_404() {
		$this->db->method( 'get_movie' )->willReturn( null );

		$request = new WP_REST_Request(
			array(
				'id'    => 12,
				'title' => 'Updated',
			)
		);
		$result = $this->controller->update_movie( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	public function test_update_movie_partial_update_succeeds() {
		$row = $this->sample_movie_row();
		$this->db->method( 'get_movie' )->willReturn( $row );
		$this->db->method( 'update_movie' )->willReturn( true );

		$request = new WP_REST_Request(
			array(
				'id'    => 7,
				'title' => 'The Thing (Remastered)',
			)
		);
		$response = $this->controller->update_movie( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );
	}

	public function test_delete_movie_returns_previous_record() {
		$row = $this->sample_movie_row();
		$this->db->method( 'get_movie' )->willReturn( $row );
		$this->db->method( 'delete_movie' )->willReturn( true );

		$request  = new WP_REST_Request( array( 'id' => 7 ) );
		$response = $this->controller->delete_movie( $request );

		$data = $response->get_data();
		$this->assertTrue( $data['deleted'] );
		$this->assertSame( 7, $data['previous']['id'] );
	}

	public function test_add_movie_to_box_set_validates_existence() {
		$this->db->method( 'get_box_set' )->willReturn( array( 'id' => 3 ) );
		$this->db->method( 'get_movie' )->willReturn( null );

		$request = new WP_REST_Request(
			array(
				'id'       => 3,
				'movie_id' => 99,
			)
		);
		$result = $this->controller->add_movie_to_box_set( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	public function test_add_movie_to_box_set_success_returns_201() {
		$this->db->method( 'get_box_set' )->willReturn( array( 'id' => 3 ) );
		$this->db->method( 'get_movie' )->willReturn( array( 'id' => 7 ) );
		$this->db->method( 'add_movie_to_box_set' )->willReturn( 55 );

		$request = new WP_REST_Request(
			array(
				'id'       => 3,
				'movie_id' => 7,
			)
		);
		$response = $this->controller->add_movie_to_box_set( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 201, $response->get_status() );
		$this->assertSame( 55, $response->get_data()['relationship_id'] );
	}

	public function test_add_movie_to_box_set_already_linked_returns_200() {
		$this->db->method( 'get_box_set' )->willReturn( array( 'id' => 3 ) );
		$this->db->method( 'get_movie' )->willReturn( array( 'id' => 7 ) );
		$this->db->method( 'relationship_exists' )->willReturn( true );
		$this->db->method( 'add_movie_to_box_set' )->willReturn( 55 );

		$request = new WP_REST_Request(
			array(
				'id'       => 3,
				'movie_id' => 7,
			)
		);
		$response = $this->controller->add_movie_to_box_set( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['already_linked'] );
	}

	public function test_create_movie_rolls_back_when_relationship_fails() {
		$this->db->method( 'get_box_set' )->willReturn( array( 'id' => 3 ) );
		$this->db->method( 'insert_movie' )->willReturn( 7 );
		$this->db->method( 'get_movie' )->willReturn( $this->sample_movie_row() );
		$this->db->method( 'add_movie_to_box_set' )->willReturn( false );

		// The orphaned movie row must be deleted on relationship-sync failure.
		$this->db->expects( $this->once() )
			->method( 'delete_movie' )
			->with( 7 );

		$request = new WP_REST_Request(
			array(
				'title'        => 'The Thing',
				'release_year' => 1982,
				'format'       => 'Blu-ray',
				'region_code'  => 'A',
				'box_set_id'   => 3,
			)
		);
		$result = $this->controller->create_movie( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 500, $result->get_error_data()['status'] );
	}

	public function test_update_movie_box_set_id_zero_clears_membership() {
		$this->db->method( 'get_movie' )->willReturn( $this->sample_movie_row() );
		$this->db->method( 'update_movie' )->willReturn( true );

		// box_set_id=0 should clear all relationships and not re-add any.
		$this->db->expects( $this->once() )
			->method( 'remove_movie_from_all_box_sets' )
			->with( 7 )
			->willReturn( true );
		$this->db->expects( $this->never() )
			->method( 'add_movie_to_box_set' );

		$request = new WP_REST_Request(
			array(
				'id'         => 7,
				'box_set_id' => 0,
			)
		);
		$response = $this->controller->update_movie( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );
	}

	public function test_remove_movie_from_box_set_success() {
		$this->db->method( 'get_box_set' )->willReturn( array( 'id' => 3 ) );
		$this->db->method( 'get_movie' )->willReturn( array( 'id' => 7 ) );
		$this->db->method( 'relationship_exists' )->willReturn( true );
		$this->db->method( 'remove_movie_from_box_set' )->willReturn( true );

		$request = new WP_REST_Request(
			array(
				'id'       => 3,
				'movie_id' => 7,
			)
		);
		$response = $this->controller->remove_movie_from_box_set( $request );

		$this->assertTrue( $response->get_data()['deleted'] );
	}

	public function test_remove_movie_from_box_set_missing_relationship_returns_404() {
		$this->db->method( 'get_box_set' )->willReturn( array( 'id' => 3 ) );
		$this->db->method( 'get_movie' )->willReturn( array( 'id' => 7 ) );
		$this->db->method( 'relationship_exists' )->willReturn( false );

		$request = new WP_REST_Request(
			array(
				'id'       => 3,
				'movie_id' => 7,
			)
		);
		$result = $this->controller->remove_movie_from_box_set( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	public function test_create_movie_with_box_set_syncs_relationship() {
		$this->db->method( 'get_box_set' )->willReturn( array( 'id' => 3 ) );
		$this->db->method( 'insert_movie' )->willReturn( 7 );
		$this->db->method( 'get_movie' )->willReturn( $this->sample_movie_row() );

		// The relationship must be created to match the admin add-movie flow.
		$this->db->expects( $this->once() )
			->method( 'add_movie_to_box_set' )
			->with( 7, 3 )
			->willReturn( 99 );

		$request = new WP_REST_Request(
			array(
				'title'        => 'The Thing',
				'release_year' => 1982,
				'format'       => 'Blu-ray',
				'region_code'  => 'A',
				'box_set_id'   => 3,
			)
		);
		$response = $this->controller->create_movie( $request );

		$this->assertSame( 201, $response->get_status() );
	}

	public function test_update_movie_with_box_set_rewrites_relationships() {
		$this->db->method( 'get_movie' )->willReturn( $this->sample_movie_row() );
		$this->db->method( 'get_box_set' )->willReturn( array( 'id' => 4 ) );
		$this->db->method( 'update_movie' )->willReturn( true );

		$this->db->expects( $this->once() )
			->method( 'remove_movie_from_all_box_sets' )
			->with( 7 )
			->willReturn( true );
		$this->db->expects( $this->once() )
			->method( 'add_movie_to_box_set' )
			->with( 7, 4 )
			->willReturn( 99 );

		$request = new WP_REST_Request(
			array(
				'id'         => 7,
				'box_set_id' => 4,
			)
		);
		$response = $this->controller->update_movie( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
	}

	public function test_create_movie_returns_500_when_relationship_sync_fails() {
		$this->db->method( 'get_box_set' )->willReturn( array( 'id' => 3 ) );
		$this->db->method( 'insert_movie' )->willReturn( 7 );
		$this->db->method( 'get_movie' )->willReturn( $this->sample_movie_row() );
		$this->db->method( 'add_movie_to_box_set' )->willReturn( false );

		$request = new WP_REST_Request(
			array(
				'title'        => 'The Thing',
				'release_year' => 1982,
				'format'       => 'Blu-ray',
				'region_code'  => 'A',
				'box_set_id'   => 3,
			)
		);
		$result = $this->controller->create_movie( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 500, $result->get_error_data()['status'] );
	}

	public function test_prepare_movie_for_response_box_set_id_null_when_unset() {
		$row               = $this->sample_movie_row();
		$row['box_set_id'] = null;

		$prepared = $this->controller->prepare_movie_for_response( $row );

		$this->assertNull( $prepared['box_set_id'] );
	}

	public function test_create_movie_defaults_not_null_text_fields_to_blank() {
		$captured = null;
		$this->db->method( 'insert_movie' )->willReturnCallback(
			function ( $data ) use ( &$captured ) {
				$captured = $data;
				return 7;
			}
		);
		$this->db->method( 'get_movie' )->willReturn( $this->sample_movie_row() );

		$request = new WP_REST_Request(
			array(
				'title'        => 'Minimal',
				'release_year' => 2000,
				'format'       => 'DVD',
				'region_code'  => 'R1',
			)
		);
		$this->controller->create_movie( $request );

		foreach ( array( 'barcode', 'director', 'studio', 'actors', 'genre' ) as $field ) {
			$this->assertArrayHasKey( $field, $captured );
			$this->assertSame( '', $captured[ $field ] );
		}
	}

	public function test_create_box_set_defaults_barcode_to_blank() {
		$captured = null;
		$this->db->method( 'insert_box_set' )->willReturnCallback(
			function ( $data ) use ( &$captured ) {
				$captured = $data;
				return 5;
			}
		);
		$this->db->method( 'get_box_set' )->willReturn( array( 'id' => 5 ) );

		$request = new WP_REST_Request(
			array(
				'title'        => 'Minimal Set',
				'release_year' => 2000,
				'format'       => 'DVD',
				'region_code'  => 'R1',
			)
		);
		$this->controller->create_box_set( $request );

		$this->assertArrayHasKey( 'barcode', $captured );
		$this->assertSame( '', $captured['barcode'] );
	}

	public function test_box_set_collection_params_exclude_movie_only_filters() {
		$params = $this->controller->get_box_set_collection_params();

		$this->assertArrayHasKey( 'title', $params );
		$this->assertArrayHasKey( 'year', $params );
		$this->assertArrayHasKey( 'format', $params );
		$this->assertArrayNotHasKey( 'director', $params );
		$this->assertArrayNotHasKey( 'actor', $params );
		$this->assertArrayNotHasKey( 'genre', $params );
		$this->assertArrayNotHasKey( 'studio', $params );
	}

	public function test_movie_collection_params_include_all_filters() {
		$params = $this->controller->get_collection_params();

		foreach ( array( 'director', 'actor', 'genre', 'studio' ) as $key ) {
			$this->assertArrayHasKey( $key, $params );
		}
	}

	public function test_get_box_set_not_found_returns_404() {
		$this->db->method( 'get_box_set' )->willReturn( null );

		$request = new WP_REST_Request( array( 'id' => 404 ) );
		$result  = $this->controller->get_box_set( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	public function test_get_box_set_returns_record() {
		$this->db->method( 'get_box_set' )->willReturn(
			array(
				'id'    => 5,
				'title' => 'Nightmare Collection',
			)
		);

		$request  = new WP_REST_Request( array( 'id' => 5 ) );
		$response = $this->controller->get_box_set( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 5, $response->get_data()['id'] );
	}

	public function test_get_box_sets_returns_data_with_pagination_headers() {
		$this->db->method( 'count_box_sets' )->willReturn( 4 );
		$this->db->method( 'search_box_sets' )->willReturn(
			array( array( 'id' => 5, 'title' => 'Set' ) )
		);

		$request  = new WP_REST_Request( array( 'per_page' => '2' ) );
		$response = $this->controller->get_box_sets( $request );

		$this->assertCount( 1, $response->get_data() );
		$headers = $response->get_headers();
		$this->assertSame( '4', $headers['X-WP-Total'] );
		$this->assertSame( '2', $headers['X-WP-TotalPages'] );
	}

	public function test_create_box_set_validates_required_fields() {
		$request = new WP_REST_Request( array( 'title' => 'Incomplete' ) );
		$result  = $this->controller->create_box_set( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 400, $result->get_error_data()['status'] );
	}

	public function test_create_box_set_success_returns_201() {
		$this->db->method( 'insert_box_set' )->willReturn( 5 );
		$this->db->method( 'get_box_set' )->willReturn(
			array(
				'id'    => 5,
				'title' => 'Nightmare Collection',
			)
		);

		$request = new WP_REST_Request(
			array(
				'title'        => 'Nightmare Collection',
				'release_year' => 1999,
				'format'       => 'DVD',
				'region_code'  => 'R1',
			)
		);
		$response = $this->controller->create_box_set( $request );

		$this->assertSame( 201, $response->get_status() );
		$this->assertSame( 5, $response->get_data()['id'] );
	}

	public function test_update_box_set_missing_record_returns_404() {
		$this->db->method( 'get_box_set' )->willReturn( null );

		$request = new WP_REST_Request(
			array(
				'id'    => 5,
				'title' => 'Updated',
			)
		);
		$result = $this->controller->update_box_set( $request );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	public function test_update_box_set_partial_update_succeeds() {
		$this->db->method( 'get_box_set' )->willReturn(
			array(
				'id'    => 5,
				'title' => 'Original',
			)
		);
		$this->db->method( 'update_box_set' )->willReturn( true );

		$request = new WP_REST_Request(
			array(
				'id'    => 5,
				'title' => 'Renamed Collection',
			)
		);
		$response = $this->controller->update_box_set( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertSame( 200, $response->get_status() );
	}

	public function test_delete_box_set_returns_previous_record() {
		$box_set = array(
			'id'    => 5,
			'title' => 'Nightmare Collection',
		);
		$this->db->method( 'get_box_set' )->willReturn( $box_set );
		$this->db->method( 'delete_box_set' )->willReturn( true );

		$request  = new WP_REST_Request( array( 'id' => 5 ) );
		$response = $this->controller->delete_box_set( $request );

		$data = $response->get_data();
		$this->assertTrue( $data['deleted'] );
		$this->assertSame( 5, $data['previous']['id'] );
	}

	public function test_get_box_set_movies_lists_contained_movies() {
		$this->db->method( 'get_box_set' )->willReturn( array( 'id' => 3 ) );
		$this->db->method( 'get_movies_in_box_set' )->willReturn( array( $this->sample_movie_row() ) );

		$request  = new WP_REST_Request( array( 'id' => 3 ) );
		$response = $this->controller->get_box_set_movies( $request );

		$this->assertInstanceOf( WP_REST_Response::class, $response );
		$this->assertCount( 1, $response->get_data() );
	}

	public function test_read_permission_denied_when_capability_missing() {
		$GLOBALS['wp_movie_test_current_user_can'] = false;
		$GLOBALS['wp_movie_test_user_logged_in']   = true;

		$result = $this->controller->read_permissions_check( new WP_REST_Request() );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 403, $result->get_error_data()['status'] );
	}

	public function test_write_permission_denied_returns_401_when_logged_out() {
		$GLOBALS['wp_movie_test_current_user_can'] = false;
		$GLOBALS['wp_movie_test_user_logged_in']   = false;

		$result = $this->controller->write_permissions_check( new WP_REST_Request() );

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 401, $result->get_error_data()['status'] );
	}

	public function test_write_permission_allowed_when_capable() {
		$GLOBALS['wp_movie_test_current_user_can'] = true;

		$this->assertTrue( $this->controller->write_permissions_check( new WP_REST_Request() ) );
	}
}
