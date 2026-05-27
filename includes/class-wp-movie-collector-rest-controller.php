<?php
/**
 * REST API controller for movies and box sets.
 *
 * Exposes the custom-table collection data over the WordPress REST API
 * under the `movie-collection/v1` namespace. The default WordPress REST
 * endpoints for the `movie`/`box_set` post types cannot serve this data
 * because the collection lives in custom tables rather than post meta.
 *
 * @since      1.3.0
 * @package    WP_Movie_Collector
 */

class WP_Movie_Collector_REST_Controller extends WP_REST_Controller {

	/**
	 * Allowed physical media formats.
	 *
	 * @since 1.3.0
	 * @var   string[]
	 */
	const VALID_FORMATS = array( 'DVD', 'Blu-ray', '4K UHD', 'VHS', 'LaserDisc' );

	/**
	 * Allowed region codes.
	 *
	 * @since 1.3.0
	 * @var   string[]
	 */
	const VALID_REGIONS = array( 'R0', 'R1', 'R2', 'R3', 'R4', 'R5', 'R6', 'A', 'B', 'C', 'ABC' );

	/**
	 * Maximum number of items returned per page.
	 *
	 * @since 1.3.0
	 * @var   int
	 */
	const MAX_PER_PAGE = 100;

	/**
	 * The database access layer.
	 *
	 * @since 1.3.0
	 * @var   WP_Movie_Collector_DB
	 */
	protected $db;

	/**
	 * Constructor.
	 *
	 * @since 1.3.0
	 * @param WP_Movie_Collector_DB|null $db Optional DB instance (injected for testing).
	 */
	public function __construct( $db = null ) {
		$this->namespace = 'movie-collection/v1';
		$this->db        = $db ? $db : new WP_Movie_Collector_DB();
	}

	/**
	 * Register the REST API routes.
	 *
	 * @since 1.3.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/movies',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_movies' ),
					'permission_callback' => array( $this, 'read_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_movie' ),
					'permission_callback' => array( $this, 'write_permissions_check' ),
					'args'                => $this->get_movie_endpoint_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/movies/(?P<id>[\d]+)',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the movie.', 'wp-movie-collector' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_movie' ),
					'permission_callback' => array( $this, 'read_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_movie' ),
					'permission_callback' => array( $this, 'write_permissions_check' ),
					'args'                => $this->get_movie_endpoint_args( false ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_movie' ),
					'permission_callback' => array( $this, 'write_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/box-sets',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_box_sets' ),
					'permission_callback' => array( $this, 'read_permissions_check' ),
					'args'                => $this->get_box_set_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_box_set' ),
					'permission_callback' => array( $this, 'write_permissions_check' ),
					'args'                => $this->get_box_set_endpoint_args(),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/box-sets/(?P<id>[\d]+)',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the box set.', 'wp-movie-collector' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_box_set' ),
					'permission_callback' => array( $this, 'read_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_box_set' ),
					'permission_callback' => array( $this, 'write_permissions_check' ),
					'args'                => $this->get_box_set_endpoint_args( false ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_box_set' ),
					'permission_callback' => array( $this, 'write_permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/box-sets/(?P<id>[\d]+)/movies',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the box set.', 'wp-movie-collector' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_box_set_movies' ),
					'permission_callback' => array( $this, 'read_permissions_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'add_movie_to_box_set' ),
					'permission_callback' => array( $this, 'write_permissions_check' ),
					'args'                => array(
						'movie_id' => array(
							'description'       => __( 'The movie to add to the box set.', 'wp-movie-collector' ),
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/box-sets/(?P<id>[\d]+)/movies/(?P<movie_id>[\d]+)',
			array(
				'args' => array(
					'id'       => array(
						'description' => __( 'Unique identifier for the box set.', 'wp-movie-collector' ),
						'type'        => 'integer',
					),
					'movie_id' => array(
						'description' => __( 'Unique identifier for the movie.', 'wp-movie-collector' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'remove_movie_from_box_set' ),
					'permission_callback' => array( $this, 'write_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Permission check for read (GET) endpoints.
	 *
	 * Defaults to requiring the `manage_options` capability. The
	 * `wp_movie_collector_rest_read_permission` filter can relax or
	 * tighten this (for example, to expose the collection publicly).
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error True if allowed, WP_Error otherwise.
	 */
	public function read_permissions_check( $request ) {
		$allowed = current_user_can( 'manage_options' );

		/**
		 * Filter whether the current request may read collection data over REST.
		 *
		 * @since 1.3.0
		 * @param bool            $allowed Whether the request is allowed.
		 * @param WP_REST_Request $request The request object.
		 */
		$allowed = apply_filters( 'wp_movie_collector_rest_read_permission', $allowed, $request );

		if ( ! $allowed ) {
			return new WP_Error(
				'wp_movie_collector_rest_forbidden',
				__( 'You are not allowed to access the movie collection.', 'wp-movie-collector' ),
				array( 'status' => $this->auth_error_status() )
			);
		}

		return true;
	}

	/**
	 * Permission check for write (POST/PUT/DELETE) endpoints.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request The request object.
	 * @return bool|WP_Error True if allowed, WP_Error otherwise.
	 */
	public function write_permissions_check( $request ) {
		$allowed = current_user_can( 'manage_options' );

		/**
		 * Filter whether the current request may modify collection data over REST.
		 *
		 * @since 1.3.0
		 * @param bool            $allowed Whether the request is allowed.
		 * @param WP_REST_Request $request The request object.
		 */
		$allowed = apply_filters( 'wp_movie_collector_rest_write_permission', $allowed, $request );

		if ( ! $allowed ) {
			return new WP_Error(
				'wp_movie_collector_rest_forbidden',
				__( 'You are not allowed to modify the movie collection.', 'wp-movie-collector' ),
				array( 'status' => $this->auth_error_status() )
			);
		}

		return true;
	}

	/**
	 * Determine the appropriate HTTP status for an auth failure.
	 *
	 * 401 when no user is logged in, 403 when logged in but lacking the
	 * capability — matching core REST conventions.
	 *
	 * @since 1.3.0
	 * @return int
	 */
	protected function auth_error_status() {
		return is_user_logged_in() ? 403 : 401;
	}

	/**
	 * List movies, with optional search/filter and pagination.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function get_movies( $request ) {
		$criteria = $this->build_query_criteria( $request );
		$total    = $this->db->count_movies( $criteria );
		$rows     = $this->db->search_movies( $criteria );

		$data = array();
		foreach ( (array) $rows as $row ) {
			$data[] = $this->prepare_movie_for_response( $row );
		}

		return $this->paginated_response( $data, $total, $criteria['per_page'] );
	}

	/**
	 * Get a single movie.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_movie( $request ) {
		$movie = $this->db->get_movie( (int) $request['id'] );

		if ( empty( $movie ) ) {
			return $this->not_found_error( __( 'Movie not found.', 'wp-movie-collector' ) );
		}

		return rest_ensure_response( $this->prepare_movie_for_response( $movie ) );
	}

	/**
	 * Create a movie.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_movie( $request ) {
		$prepared = $this->prepare_movie_for_database( $request, true );
		if ( is_wp_error( $prepared ) ) {
			return $prepared;
		}

		$id = $this->db->insert_movie( $prepared );
		if ( ! $id ) {
			return $this->server_error( __( 'Failed to create the movie.', 'wp-movie-collector' ) );
		}

		// Keep the box set relationship table in sync with the box_set_id
		// column, mirroring the admin add-movie flow. If the relationship
		// insert fails, surface a 500 so the client knows the movie row and
		// the box set membership are out of sync.
		if ( ! empty( $prepared['box_set_id'] ) ) {
			if ( ! $this->db->add_movie_to_box_set( (int) $id, (int) $prepared['box_set_id'] ) ) {
				return $this->server_error( __( 'The movie was created but could not be linked to the box set.', 'wp-movie-collector' ) );
			}
		}

		$movie    = $this->db->get_movie( (int) $id );
		$response = rest_ensure_response( $this->prepare_movie_for_response( $movie ) );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Update a movie.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_movie( $request ) {
		$id       = (int) $request['id'];
		$existing = $this->db->get_movie( $id );
		if ( empty( $existing ) ) {
			return $this->not_found_error( __( 'Movie not found.', 'wp-movie-collector' ) );
		}

		$prepared = $this->prepare_movie_for_database( $request, false );
		if ( is_wp_error( $prepared ) ) {
			return $prepared;
		}

		if ( empty( $prepared ) ) {
			return $this->invalid_param_error( __( 'No valid fields were supplied to update.', 'wp-movie-collector' ) );
		}

		$updated = $this->db->update_movie( $id, $prepared );
		if ( false === $updated ) {
			return $this->server_error( __( 'Failed to update the movie.', 'wp-movie-collector' ) );
		}

		// When box_set_id is part of the request, rewrite the relationship
		// table to match, mirroring the admin edit-movie flow: clear the
		// movie's existing relationships, then re-add the selected box set.
		// Surface a 500 if either sync step fails so the relationship table
		// can't silently drift from the movie row.
		if ( null !== $request->get_param( 'box_set_id' ) ) {
			if ( false === $this->db->remove_movie_from_all_box_sets( $id ) ) {
				return $this->server_error( __( 'Failed to update the box set membership.', 'wp-movie-collector' ) );
			}
			if ( ! empty( $prepared['box_set_id'] ) && ! $this->db->add_movie_to_box_set( $id, (int) $prepared['box_set_id'] ) ) {
				return $this->server_error( __( 'Failed to update the box set membership.', 'wp-movie-collector' ) );
			}
		}

		return rest_ensure_response( $this->prepare_movie_for_response( $this->db->get_movie( $id ) ) );
	}

	/**
	 * Delete a movie.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_movie( $request ) {
		$id    = (int) $request['id'];
		$movie = $this->db->get_movie( $id );
		if ( empty( $movie ) ) {
			return $this->not_found_error( __( 'Movie not found.', 'wp-movie-collector' ) );
		}

		$deleted = $this->db->delete_movie( $id );
		if ( false === $deleted ) {
			return $this->server_error( __( 'Failed to delete the movie.', 'wp-movie-collector' ) );
		}

		return rest_ensure_response(
			array(
				'deleted'  => true,
				'previous' => $this->prepare_movie_for_response( $movie ),
			)
		);
	}

	/**
	 * List box sets, with optional search/filter and pagination.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function get_box_sets( $request ) {
		$criteria = $this->build_query_criteria( $request );
		$total    = $this->db->count_box_sets( $criteria );
		$rows     = $this->db->search_box_sets( $criteria );

		$data = array();
		foreach ( (array) $rows as $row ) {
			$data[] = $this->prepare_box_set_for_response( $row );
		}

		return $this->paginated_response( $data, $total, $criteria['per_page'] );
	}

	/**
	 * Get a single box set.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_box_set( $request ) {
		$box_set = $this->db->get_box_set( (int) $request['id'] );

		if ( empty( $box_set ) ) {
			return $this->not_found_error( __( 'Box set not found.', 'wp-movie-collector' ) );
		}

		return rest_ensure_response( $this->prepare_box_set_for_response( $box_set ) );
	}

	/**
	 * Create a box set.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_box_set( $request ) {
		$prepared = $this->prepare_box_set_for_database( $request, true );
		if ( is_wp_error( $prepared ) ) {
			return $prepared;
		}

		$id = $this->db->insert_box_set( $prepared );
		if ( ! $id ) {
			return $this->server_error( __( 'Failed to create the box set.', 'wp-movie-collector' ) );
		}

		$box_set  = $this->db->get_box_set( (int) $id );
		$response = rest_ensure_response( $this->prepare_box_set_for_response( $box_set ) );
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Update a box set.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_box_set( $request ) {
		$id       = (int) $request['id'];
		$existing = $this->db->get_box_set( $id );
		if ( empty( $existing ) ) {
			return $this->not_found_error( __( 'Box set not found.', 'wp-movie-collector' ) );
		}

		$prepared = $this->prepare_box_set_for_database( $request, false );
		if ( is_wp_error( $prepared ) ) {
			return $prepared;
		}

		if ( empty( $prepared ) ) {
			return $this->invalid_param_error( __( 'No valid fields were supplied to update.', 'wp-movie-collector' ) );
		}

		$updated = $this->db->update_box_set( $id, $prepared );
		if ( false === $updated ) {
			return $this->server_error( __( 'Failed to update the box set.', 'wp-movie-collector' ) );
		}

		return rest_ensure_response( $this->prepare_box_set_for_response( $this->db->get_box_set( $id ) ) );
	}

	/**
	 * Delete a box set.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_box_set( $request ) {
		$id      = (int) $request['id'];
		$box_set = $this->db->get_box_set( $id );
		if ( empty( $box_set ) ) {
			return $this->not_found_error( __( 'Box set not found.', 'wp-movie-collector' ) );
		}

		$deleted = $this->db->delete_box_set( $id );
		if ( false === $deleted ) {
			return $this->server_error( __( 'Failed to delete the box set.', 'wp-movie-collector' ) );
		}

		return rest_ensure_response(
			array(
				'deleted'  => true,
				'previous' => $this->prepare_box_set_for_response( $box_set ),
			)
		);
	}

	/**
	 * List the movies contained in a box set.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_box_set_movies( $request ) {
		$id      = (int) $request['id'];
		$box_set = $this->db->get_box_set( $id );
		if ( empty( $box_set ) ) {
			return $this->not_found_error( __( 'Box set not found.', 'wp-movie-collector' ) );
		}

		$movies = $this->db->get_movies_in_box_set( $id );
		$data   = array();
		foreach ( (array) $movies as $movie ) {
			$data[] = $this->prepare_movie_for_response( $movie );
		}

		return rest_ensure_response( $data );
	}

	/**
	 * Add a movie to a box set.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function add_movie_to_box_set( $request ) {
		$box_set_id = (int) $request['id'];
		$movie_id   = (int) $request['movie_id'];

		if ( empty( $this->db->get_box_set( $box_set_id ) ) ) {
			return $this->not_found_error( __( 'Box set not found.', 'wp-movie-collector' ) );
		}

		if ( empty( $this->db->get_movie( $movie_id ) ) ) {
			return $this->not_found_error( __( 'Movie not found.', 'wp-movie-collector' ) );
		}

		$relationship_id = $this->db->add_movie_to_box_set( $movie_id, $box_set_id );
		if ( ! $relationship_id ) {
			return $this->server_error( __( 'Failed to add the movie to the box set.', 'wp-movie-collector' ) );
		}

		$response = rest_ensure_response(
			array(
				'box_set_id'      => $box_set_id,
				'movie_id'        => $movie_id,
				'relationship_id' => (int) $relationship_id,
			)
		);
		$response->set_status( 201 );

		return $response;
	}

	/**
	 * Remove a movie from a box set.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function remove_movie_from_box_set( $request ) {
		$box_set_id = (int) $request['id'];
		$movie_id   = (int) $request['movie_id'];

		if ( empty( $this->db->get_box_set( $box_set_id ) ) ) {
			return $this->not_found_error( __( 'Box set not found.', 'wp-movie-collector' ) );
		}

		if ( empty( $this->db->get_movie( $movie_id ) ) ) {
			return $this->not_found_error( __( 'Movie not found.', 'wp-movie-collector' ) );
		}

		// wpdb::delete() returns 0 (not false) when no row matches, which the
		// DB helper reports as success. Check the relationship exists first so
		// clients can distinguish "removed" from "nothing to remove".
		if ( ! $this->db->relationship_exists( $movie_id, $box_set_id ) ) {
			return $this->not_found_error( __( 'That movie is not in this box set.', 'wp-movie-collector' ) );
		}

		$removed = $this->db->remove_movie_from_box_set( $movie_id, $box_set_id );
		if ( false === $removed ) {
			return $this->server_error( __( 'Failed to remove the movie from the box set.', 'wp-movie-collector' ) );
		}

		return rest_ensure_response(
			array(
				'deleted'    => true,
				'box_set_id' => $box_set_id,
				'movie_id'   => $movie_id,
			)
		);
	}

	/**
	 * Build a DB search criteria array from request parameters.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request The request object.
	 * @return array Criteria array including 'page' and 'per_page'.
	 */
	public function build_query_criteria( $request ) {
		$criteria = array();

		foreach ( array( 'title', 'director', 'actor', 'genre', 'studio', 'format' ) as $key ) {
			$value = $request->get_param( $key );
			if ( null !== $value && '' !== $value ) {
				$criteria[ $key ] = sanitize_text_field( $value );
			}
		}

		$year = $request->get_param( 'year' );
		if ( null !== $year && '' !== $year ) {
			$criteria['year'] = absint( $year );
		}

		$orderby = $request->get_param( 'orderby' );
		if ( null !== $orderby && '' !== $orderby ) {
			$criteria['orderby'] = sanitize_key( $orderby );
		}

		$order = $request->get_param( 'order' );
		if ( null !== $order && '' !== $order ) {
			$criteria['order'] = strtoupper( $order ) === 'DESC' ? 'DESC' : 'ASC';
		}

		$per_page = absint( $request->get_param( 'per_page' ) );
		if ( $per_page < 1 ) {
			$per_page = 10;
		}
		$per_page = min( $per_page, self::MAX_PER_PAGE );

		$page = absint( $request->get_param( 'page' ) );
		if ( $page < 1 ) {
			$page = 1;
		}

		$criteria['per_page'] = $per_page;
		$criteria['page']     = $page;

		return $criteria;
	}

	/**
	 * Wrap a list of items in a paginated REST response with standard headers.
	 *
	 * @since 1.3.0
	 * @param array $data     The items for the current page.
	 * @param int   $total    Total number of matching items.
	 * @param int   $per_page Items per page.
	 * @return WP_REST_Response
	 */
	protected function paginated_response( $data, $total, $per_page ) {
		$response    = rest_ensure_response( $data );
		$total       = (int) $total;
		$per_page    = max( 1, (int) $per_page );
		$total_pages = (int) ceil( $total / $per_page );

		$response->header( 'X-WP-Total', (string) $total );
		$response->header( 'X-WP-TotalPages', (string) $total_pages );

		return $response;
	}

	/**
	 * Sanitize and validate a movie payload for insert/update.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request    The request object.
	 * @param bool            $is_create  Whether this is a create (required fields enforced).
	 * @return array|WP_Error Sanitized data, or WP_Error on validation failure.
	 */
	public function prepare_movie_for_database( $request, $is_create ) {
		$data   = array();
		$errors = array();

		$text_fields     = array( 'title', 'barcode', 'director', 'studio', 'genre', 'api_source' );
		$textarea_fields = array( 'actors', 'special_features', 'description', 'custom_notes' );

		foreach ( $text_fields as $field ) {
			$value = $request->get_param( $field );
			if ( null !== $value ) {
				$data[ $field ] = sanitize_text_field( $value );
			}
		}

		foreach ( $textarea_fields as $field ) {
			$value = $request->get_param( $field );
			if ( null !== $value ) {
				$data[ $field ] = sanitize_textarea_field( $value );
			}
		}

		$this->apply_common_media_fields( $request, $data, $errors );

		$box_set_id = $request->get_param( 'box_set_id' );
		if ( null !== $box_set_id && '' !== $box_set_id ) {
			$box_set_id = absint( $box_set_id );
			if ( $box_set_id > 0 && empty( $this->db->get_box_set( $box_set_id ) ) ) {
				$errors[] = __( 'The specified box set does not exist.', 'wp-movie-collector' );
			} else {
				$data['box_set_id'] = $box_set_id;
			}
		}

		return $this->finalize_prepared_data( $data, $errors, $is_create, array( 'title', 'release_year', 'format', 'region_code' ) );
	}

	/**
	 * Sanitize and validate a box set payload for insert/update.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request    The request object.
	 * @param bool            $is_create  Whether this is a create (required fields enforced).
	 * @return array|WP_Error Sanitized data, or WP_Error on validation failure.
	 */
	public function prepare_box_set_for_database( $request, $is_create ) {
		$data   = array();
		$errors = array();

		foreach ( array( 'title', 'barcode', 'api_source' ) as $field ) {
			$value = $request->get_param( $field );
			if ( null !== $value ) {
				$data[ $field ] = sanitize_text_field( $value );
			}
		}

		foreach ( array( 'special_features', 'description', 'custom_notes' ) as $field ) {
			$value = $request->get_param( $field );
			if ( null !== $value ) {
				$data[ $field ] = sanitize_textarea_field( $value );
			}
		}

		$this->apply_common_media_fields( $request, $data, $errors );

		return $this->finalize_prepared_data( $data, $errors, $is_create, array( 'title', 'release_year', 'format', 'region_code' ) );
	}

	/**
	 * Sanitize and validate fields common to movies and box sets.
	 *
	 * Mutates $data and $errors by reference.
	 *
	 * @since 1.3.0
	 * @param WP_REST_Request $request The request object.
	 * @param array           $data    Sanitized data accumulator (by reference).
	 * @param array           $errors  Error accumulator (by reference).
	 */
	protected function apply_common_media_fields( $request, &$data, &$errors ) {
		$year = $request->get_param( 'release_year' );
		if ( null !== $year && '' !== $year ) {
			$year = (int) $year;
			if ( $year < 1900 || $year > (int) gmdate( 'Y' ) ) {
				$errors[] = __( 'Release year must be between 1900 and the current year.', 'wp-movie-collector' );
			} else {
				$data['release_year'] = $year;
			}
		}

		$format = $request->get_param( 'format' );
		if ( null !== $format && '' !== $format ) {
			if ( ! in_array( $format, self::VALID_FORMATS, true ) ) {
				$errors[] = __( 'Invalid format.', 'wp-movie-collector' );
			} else {
				$data['format'] = sanitize_text_field( $format );
			}
		}

		$region = $request->get_param( 'region_code' );
		if ( null !== $region && '' !== $region ) {
			if ( ! in_array( $region, self::VALID_REGIONS, true ) ) {
				$errors[] = __( 'Invalid region code.', 'wp-movie-collector' );
			} else {
				$data['region_code'] = sanitize_text_field( $region );
			}
		}

		$cover_url = $request->get_param( 'cover_image_url' );
		if ( null !== $cover_url && '' !== $cover_url ) {
			$data['cover_image_url'] = esc_url_raw( $cover_url );
		}

		$cover_id = $request->get_param( 'cover_image_id' );
		if ( null !== $cover_id && '' !== $cover_id ) {
			$data['cover_image_id'] = absint( $cover_id );
		}

		$acq = $request->get_param( 'acquisition_date' );
		if ( null !== $acq && '' !== $acq ) {
			if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $acq, $m ) && checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
				$data['acquisition_date'] = $acq;
			} else {
				$errors[] = __( 'Acquisition date must be a valid date in YYYY-MM-DD format.', 'wp-movie-collector' );
			}
		}
	}

	/**
	 * Enforce required fields and return the prepared data or a WP_Error.
	 *
	 * @since 1.3.0
	 * @param array $data      Sanitized data.
	 * @param array $errors    Accumulated validation errors.
	 * @param bool  $is_create Whether required fields must be present.
	 * @param array $required  Required field keys (enforced on create only).
	 * @return array|WP_Error
	 */
	protected function finalize_prepared_data( $data, $errors, $is_create, $required ) {
		if ( $is_create ) {
			foreach ( $required as $field ) {
				if ( ! isset( $data[ $field ] ) || '' === $data[ $field ] ) {
					/* translators: %s: field name. */
					$errors[] = sprintf( __( 'The %s field is required.', 'wp-movie-collector' ), $field );
				}
			}
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error(
				'wp_movie_collector_rest_invalid_data',
				implode( ' ', $errors ),
				array( 'status' => 400 )
			);
		}

		return $data;
	}

	/**
	 * Shape a raw movie row for the REST response.
	 *
	 * @since 1.3.0
	 * @param array $movie Raw movie row.
	 * @return array
	 */
	public function prepare_movie_for_response( $movie ) {
		$movie = (array) $movie;

		$prepared = array(
			'id'               => isset( $movie['id'] ) ? (int) $movie['id'] : 0,
			'title'            => isset( $movie['title'] ) ? $movie['title'] : '',
			'release_year'     => isset( $movie['release_year'] ) ? (int) $movie['release_year'] : 0,
			'format'           => isset( $movie['format'] ) ? $movie['format'] : '',
			'region_code'      => isset( $movie['region_code'] ) ? $movie['region_code'] : '',
			'barcode'          => isset( $movie['barcode'] ) ? $movie['barcode'] : '',
			'director'         => isset( $movie['director'] ) ? $movie['director'] : '',
			'studio'           => isset( $movie['studio'] ) ? $movie['studio'] : '',
			'actors'           => isset( $movie['actors'] ) ? $movie['actors'] : '',
			'genre'            => isset( $movie['genre'] ) ? $movie['genre'] : '',
			'special_features' => isset( $movie['special_features'] ) ? $movie['special_features'] : '',
			'cover_image_url'  => isset( $movie['cover_image_url'] ) ? $movie['cover_image_url'] : '',
			'cover_image_id'   => isset( $movie['cover_image_id'] ) ? (int) $movie['cover_image_id'] : 0,
			'description'      => isset( $movie['description'] ) ? $movie['description'] : '',
			'acquisition_date' => isset( $movie['acquisition_date'] ) ? $movie['acquisition_date'] : null,
			'box_set_id'       => isset( $movie['box_set_id'] ) ? (int) $movie['box_set_id'] : 0,
			'api_source'       => isset( $movie['api_source'] ) ? $movie['api_source'] : '',
			'custom_notes'     => isset( $movie['custom_notes'] ) ? $movie['custom_notes'] : '',
			'created_at'       => isset( $movie['created_at'] ) ? $movie['created_at'] : null,
			'updated_at'       => isset( $movie['updated_at'] ) ? $movie['updated_at'] : null,
		);

		return $prepared;
	}

	/**
	 * Shape a raw box set row for the REST response.
	 *
	 * @since 1.3.0
	 * @param array $box_set Raw box set row.
	 * @return array
	 */
	public function prepare_box_set_for_response( $box_set ) {
		$box_set = (array) $box_set;

		$prepared = array(
			'id'               => isset( $box_set['id'] ) ? (int) $box_set['id'] : 0,
			'title'            => isset( $box_set['title'] ) ? $box_set['title'] : '',
			'release_year'     => isset( $box_set['release_year'] ) ? (int) $box_set['release_year'] : 0,
			'format'           => isset( $box_set['format'] ) ? $box_set['format'] : '',
			'region_code'      => isset( $box_set['region_code'] ) ? $box_set['region_code'] : '',
			'barcode'          => isset( $box_set['barcode'] ) ? $box_set['barcode'] : '',
			'cover_image_url'  => isset( $box_set['cover_image_url'] ) ? $box_set['cover_image_url'] : '',
			'cover_image_id'   => isset( $box_set['cover_image_id'] ) ? (int) $box_set['cover_image_id'] : 0,
			'description'      => isset( $box_set['description'] ) ? $box_set['description'] : '',
			'acquisition_date' => isset( $box_set['acquisition_date'] ) ? $box_set['acquisition_date'] : null,
			'special_features' => isset( $box_set['special_features'] ) ? $box_set['special_features'] : '',
			'api_source'       => isset( $box_set['api_source'] ) ? $box_set['api_source'] : '',
			'custom_notes'     => isset( $box_set['custom_notes'] ) ? $box_set['custom_notes'] : '',
			'created_at'       => isset( $box_set['created_at'] ) ? $box_set['created_at'] : null,
			'updated_at'       => isset( $box_set['updated_at'] ) ? $box_set['updated_at'] : null,
		);

		return $prepared;
	}

	/**
	 * Shared query parameters for list (collection) endpoints.
	 *
	 * Used by the movies list endpoint, which supports the full set of
	 * filters implemented by WP_Movie_Collector_DB::search_movies().
	 *
	 * @since 1.3.0
	 * @return array
	 */
	public function get_collection_params() {
		$params = $this->base_collection_params();

		$params['genre']    = array(
			'description' => __( 'Limit results to a genre.', 'wp-movie-collector' ),
			'type'        => 'string',
		);
		$params['director'] = array(
			'description' => __( 'Limit results to a director.', 'wp-movie-collector' ),
			'type'        => 'string',
		);
		$params['studio']   = array(
			'description' => __( 'Limit results to a studio.', 'wp-movie-collector' ),
			'type'        => 'string',
		);
		$params['actor']    = array(
			'description' => __( 'Limit results to an actor.', 'wp-movie-collector' ),
			'type'        => 'string',
		);

		return $params;
	}

	/**
	 * Query parameters for the box sets list endpoint.
	 *
	 * Box set search (WP_Movie_Collector_DB::search_box_sets()) only
	 * supports title, year, and format filters, so the schema advertises
	 * just those rather than the movie-only filters.
	 *
	 * @since 1.3.0
	 * @return array
	 */
	public function get_box_set_collection_params() {
		return $this->base_collection_params();
	}

	/**
	 * Pagination, sorting, and the filters common to both list endpoints.
	 *
	 * @since 1.3.0
	 * @return array
	 */
	protected function base_collection_params() {
		return array(
			'page'     => array(
				'description'       => __( 'Current page of the collection.', 'wp-movie-collector' ),
				'type'              => 'integer',
				'default'           => 1,
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'description'       => __( 'Maximum number of items to be returned in the result set.', 'wp-movie-collector' ),
				'type'              => 'integer',
				'default'           => 10,
				'minimum'           => 1,
				'maximum'           => self::MAX_PER_PAGE,
				'sanitize_callback' => 'absint',
			),
			'title'    => array(
				'description' => __( 'Limit results to those matching a title.', 'wp-movie-collector' ),
				'type'        => 'string',
			),
			'year'     => array(
				'description' => __( 'Limit results to a release year.', 'wp-movie-collector' ),
				'type'        => 'integer',
			),
			'format'   => array(
				'description' => __( 'Limit results to a media format.', 'wp-movie-collector' ),
				'type'        => 'string',
				'enum'        => self::VALID_FORMATS,
			),
			'orderby'  => array(
				'description' => __( 'Sort collection by attribute.', 'wp-movie-collector' ),
				'type'        => 'string',
				'default'     => 'title',
			),
			'order'    => array(
				'description' => __( 'Order sort attribute ascending or descending.', 'wp-movie-collector' ),
				'type'        => 'string',
				'enum'        => array( 'ASC', 'DESC', 'asc', 'desc' ),
				'default'     => 'ASC',
			),
		);
	}

	/**
	 * Endpoint args (schema) for movie create/update.
	 *
	 * @since 1.3.0
	 * @param bool $is_create Whether the args are for a create request.
	 * @return array
	 */
	public function get_movie_endpoint_args( $is_create = true ) {
		$args = $this->common_media_args( $is_create );

		$args['director']   = array(
			'type' => 'string',
		);
		$args['studio']     = array(
			'type' => 'string',
		);
		$args['actors']     = array(
			'type' => 'string',
		);
		$args['genre']      = array(
			'type' => 'string',
		);
		$args['box_set_id'] = array(
			'type' => 'integer',
		);

		return $args;
	}

	/**
	 * Endpoint args (schema) for box set create/update.
	 *
	 * @since 1.3.0
	 * @param bool $is_create Whether the args are for a create request.
	 * @return array
	 */
	public function get_box_set_endpoint_args( $is_create = true ) {
		return $this->common_media_args( $is_create );
	}

	/**
	 * Endpoint args shared by movies and box sets.
	 *
	 * @since 1.3.0
	 * @param bool $is_create Whether required flags should be applied.
	 * @return array
	 */
	protected function common_media_args( $is_create ) {
		return array(
			'title'            => array(
				'type'     => 'string',
				'required' => $is_create,
			),
			'release_year'     => array(
				'type'     => 'integer',
				'required' => $is_create,
			),
			'format'           => array(
				'type'     => 'string',
				'enum'     => self::VALID_FORMATS,
				'required' => $is_create,
			),
			'region_code'      => array(
				'type'     => 'string',
				'enum'     => self::VALID_REGIONS,
				'required' => $is_create,
			),
			'barcode'          => array(
				'type' => 'string',
			),
			'special_features' => array(
				'type' => 'string',
			),
			'cover_image_url'  => array(
				'type'   => 'string',
				'format' => 'uri',
			),
			'cover_image_id'   => array(
				'type' => 'integer',
			),
			'description'      => array(
				'type' => 'string',
			),
			'acquisition_date' => array(
				'type' => 'string',
			),
			'api_source'       => array(
				'type' => 'string',
			),
			'custom_notes'     => array(
				'type' => 'string',
			),
		);
	}

	/**
	 * Build a 404 WP_Error.
	 *
	 * @since 1.3.0
	 * @param string $message Error message.
	 * @return WP_Error
	 */
	protected function not_found_error( $message ) {
		return new WP_Error( 'wp_movie_collector_rest_not_found', $message, array( 'status' => 404 ) );
	}

	/**
	 * Build a 400 WP_Error.
	 *
	 * @since 1.3.0
	 * @param string $message Error message.
	 * @return WP_Error
	 */
	protected function invalid_param_error( $message ) {
		return new WP_Error( 'wp_movie_collector_rest_invalid_data', $message, array( 'status' => 400 ) );
	}

	/**
	 * Build a 500 WP_Error.
	 *
	 * @since 1.3.0
	 * @param string $message Error message.
	 * @return WP_Error
	 */
	protected function server_error( $message ) {
		return new WP_Error( 'wp_movie_collector_rest_server_error', $message, array( 'status' => 500 ) );
	}
}
