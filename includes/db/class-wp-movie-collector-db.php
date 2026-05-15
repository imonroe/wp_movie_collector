<?php
/**
 * Database operations for the plugin.
 *
 * @since      1.0.0
 * @package    WP_Movie_Collector
 */

class WP_Movie_Collector_DB {

	/**
	 * The table name for movies
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $movies_table    The table name for movies.
	 */
	private $movies_table;

	/**
	 * The table name for box sets
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $box_sets_table    The table name for box sets.
	 */
	private $box_sets_table;

	/**
	 * The table name for relationships
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $relationships_table    The table name for relationships.
	 */
	private $relationships_table;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->movies_table        = $wpdb->prefix . 'movie_collection';
		$this->box_sets_table      = $wpdb->prefix . 'movie_box_sets';
		$this->relationships_table = $wpdb->prefix . 'movie_box_set_relationships';
	}

	/**
	 * Get the movies table name.
	 *
	 * @since    1.0.0
	 * @return   string    The movies table name.
	 */
	public function get_movies_table() {
		return $this->movies_table;
	}

	/**
	 * Get the box sets table name.
	 *
	 * @since    1.0.0
	 * @return   string    The box sets table name.
	 */
	public function get_box_sets_table() {
		return $this->box_sets_table;
	}

	/**
	 * Get the relationships table name.
	 *
	 * @since    1.0.0
	 * @return   string    The relationships table name.
	 */
	public function get_relationships_table() {
		return $this->relationships_table;
	}

	/**
	 * Update database tables structure.
	 *
	 * @since    1.0.0
	 */
	public function update_tables() {
		global $wpdb;

		// Check if cover_image_id column exists in movies table
		$movies_table  = $this->get_movies_table();
		$column_exists = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM `{$movies_table}` WHERE Field = %s", 'cover_image_id' ) );

		if ( empty( $column_exists ) ) {
			$wpdb->query( "ALTER TABLE $movies_table ADD COLUMN cover_image_id bigint(20) NULL AFTER cover_image_url, ADD INDEX (cover_image_id)" );
		}

		// Check if cover_image_id column exists in box sets table
		$box_sets_table = $this->get_box_sets_table();
		$column_exists  = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM `{$box_sets_table}` WHERE Field = %s", 'cover_image_id' ) );

		if ( empty( $column_exists ) ) {
			$wpdb->query( "ALTER TABLE $box_sets_table ADD COLUMN cover_image_id bigint(20) NULL AFTER cover_image_url, ADD INDEX (cover_image_id)" );
		}

		// Check if display_order column exists in relationships table
		$relationships_table = $this->get_relationships_table();
		$column_exists       = $wpdb->get_results( $wpdb->prepare( "SHOW COLUMNS FROM `{$relationships_table}` WHERE Field = %s", 'display_order' ) );

		if ( empty( $column_exists ) ) {
			$wpdb->query( "ALTER TABLE $relationships_table ADD COLUMN display_order int(11) NOT NULL DEFAULT 0" );
		}

		// Add composite index (box_set_id, display_order) if missing.
		$index_exists = $wpdb->get_results(
			$wpdb->prepare(
				"SHOW INDEX FROM `{$relationships_table}` WHERE Key_name = %s",
				'box_set_order'
			)
		);

		if ( empty( $index_exists ) ) {
			$wpdb->query( "ALTER TABLE $relationships_table ADD INDEX box_set_order (box_set_id, display_order)" );
		}

		// Add composite index (title, release_year) on movies table for duplicate detection.
		$movies_table = $this->get_movies_table();
		$index_exists = $wpdb->get_results(
			$wpdb->prepare(
				"SHOW INDEX FROM `{$movies_table}` WHERE Key_name = %s",
				'title_year'
			)
		);

		if ( empty( $index_exists ) ) {
			$wpdb->query( "ALTER TABLE $movies_table ADD INDEX title_year (title, release_year)" );
		}

		// Add composite index (title, release_year) on box sets table for duplicate detection.
		$box_sets_table = $this->get_box_sets_table();
		$index_exists   = $wpdb->get_results(
			$wpdb->prepare(
				"SHOW INDEX FROM `{$box_sets_table}` WHERE Key_name = %s",
				'title_year'
			)
		);

		if ( empty( $index_exists ) ) {
			$wpdb->query( "ALTER TABLE $box_sets_table ADD INDEX title_year (title, release_year)" );
		}

		// Add single-column indexes on created_at and acquisition_date to
		// both the movies and box sets tables. Both columns appear in the
		// search ORDER BY whitelist (search_movies / search_box_sets) and
		// power "recently added" / "recently acquired" listings; without
		// indexes those queries degrade to filesorts as the collection
		// grows.
		foreach ( array( $this->get_movies_table(), $this->get_box_sets_table() ) as $table ) {
			foreach ( array( 'created_at', 'acquisition_date' ) as $key ) {
				$exists = $wpdb->get_results(
					$wpdb->prepare(
						"SHOW INDEX FROM `{$table}` WHERE Key_name = %s",
						$key
					)
				);

				if ( empty( $exists ) ) {
					$wpdb->query( "ALTER TABLE {$table} ADD INDEX {$key} ({$key})" );
				}
			}
		}
	}

	/**
	 * Create the database tables.
	 *
	 * @since    1.0.0
	 */
	public function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $this->movies_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            release_year year NOT NULL,
            format varchar(50) NOT NULL,
            region_code varchar(10) NOT NULL,
            barcode varchar(50) NOT NULL,
            director varchar(255) NOT NULL,
            studio varchar(255) NOT NULL,
            actors text NOT NULL,
            genre varchar(255) NOT NULL,
            special_features text,
            cover_image_url text,
            cover_image_id bigint(20),
            description text,
            acquisition_date date,
            box_set_id bigint(20),
            api_source varchar(100),
            custom_notes text,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY barcode (barcode),
            KEY release_year (release_year),
            KEY format (format),
            KEY box_set_id (box_set_id),
            KEY cover_image_id (cover_image_id),
            KEY title_year (title, release_year),
            KEY created_at (created_at),
            KEY acquisition_date (acquisition_date)
        ) $charset_collate;

        CREATE TABLE $this->box_sets_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            release_year year NOT NULL,
            format varchar(50) NOT NULL,
            region_code varchar(10) NOT NULL,
            barcode varchar(50) NOT NULL,
            cover_image_url text,
            cover_image_id bigint(20),
            description text,
            acquisition_date date,
            special_features text,
            api_source varchar(100),
            custom_notes text,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY barcode (barcode),
            KEY release_year (release_year),
            KEY format (format),
            KEY cover_image_id (cover_image_id),
            KEY title_year (title, release_year),
            KEY created_at (created_at),
            KEY acquisition_date (acquisition_date)
        ) $charset_collate;

        CREATE TABLE $this->relationships_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            movie_id bigint(20) NOT NULL,
            box_set_id bigint(20) NOT NULL,
            display_order int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            KEY movie_id (movie_id),
            KEY box_set_id (box_set_id),
            KEY box_set_order (box_set_id, display_order)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Insert a movie into the database.
	 *
	 * @since    1.0.0
	 * @param    array $movie                     The movie data.
	 * @param    bool  $skip_cache_invalidation   Optional. Whether to skip invalidating the stats cache after insert. Default false.
	 * @return   int|false                        The movie ID on success, false on failure.
	 */
	public function insert_movie( $movie, $skip_cache_invalidation = false ) {
		global $wpdb;

		// Set timestamps
		$movie['created_at'] = current_time( 'mysql' );
		$movie['updated_at'] = current_time( 'mysql' );

		$result = $wpdb->insert( $this->movies_table, $movie );

		if ( $result ) {
			if ( ! $skip_cache_invalidation ) {
				$this->invalidate_stats_cache();
			}
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Update a movie in the database.
	 *
	 * @since    1.0.0
	 * @param    int   $movie_id    The movie ID.
	 * @param    array $movie       The movie data.
	 * @return   bool                  True on success, false on failure.
	 */
	public function update_movie( $movie_id, $movie ) {
		global $wpdb;

		// Set updated timestamp
		$movie['updated_at'] = current_time( 'mysql' );

		$result = $wpdb->update(
			$this->movies_table,
			$movie,
			array( 'id' => $movie_id )
		);

		if ( $result > 0 ) {
			$this->invalidate_stats_cache();
		}

		return $result !== false;
	}

	/**
	 * Get a movie from the database.
	 *
	 * @since    1.0.0
	 * @param    int $movie_id    The movie ID.
	 * @return   array|null            The movie data, or null if not found.
	 */
	public function get_movie( $movie_id ) {
		global $wpdb;

		$movie = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $this->movies_table WHERE id = %d",
				$movie_id
			),
			ARRAY_A
		);

		return $movie;
	}

	/**
	 * Get a movie by barcode.
	 *
	 * @since    1.0.0
	 * @param    string $barcode    The movie barcode.
	 * @return   array|null            The movie data, or null if not found.
	 */
	public function get_movie_by_barcode( $barcode ) {
		global $wpdb;

		$movie = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $this->movies_table WHERE barcode = %s",
				$barcode
			),
			ARRAY_A
		);

		return $movie;
	}

	/**
	 * Find duplicate movies by barcode or title+year.
	 *
	 * @since    1.0.0
	 * @param    string $title       The movie title.
	 * @param    int    $year        The release year.
	 * @param    string $barcode     Optional barcode to check.
	 * @param    int    $exclude_id  Optional movie ID to exclude (for edit context).
	 * @return   array               Array with 'barcode_match' and 'title_matches' keys.
	 */
	public function find_duplicate_movies( $title, $year, $barcode = '', $exclude_id = 0 ) {
		global $wpdb;

		$result = array(
			'barcode_match' => null,
			'title_matches' => array(),
		);

		// Check barcode match (exact).
		if ( ! empty( $barcode ) ) {
			$sql    = "SELECT id, title, release_year, format, barcode FROM $this->movies_table WHERE barcode = %s";
			$params = array( $barcode );

			if ( $exclude_id ) {
				$sql     .= ' AND id != %d';
				$params[] = $exclude_id;
			}

			$result['barcode_match'] = $wpdb->get_row(
				$wpdb->prepare( $sql, $params ),
				ARRAY_A
			);
		}

		// Check title+year match.
		if ( ! empty( $title ) && ! empty( $year ) ) {
			$sql    = "SELECT id, title, release_year, format, barcode FROM $this->movies_table WHERE title = %s AND release_year = %d";
			$params = array( $title, $year );

			if ( $exclude_id ) {
				$sql     .= ' AND id != %d';
				$params[] = $exclude_id;
			}

			$sql .= ' LIMIT 5';

			$result['title_matches'] = $wpdb->get_results(
				$wpdb->prepare( $sql, $params ),
				ARRAY_A
			);
		}

		return $result;
	}

	/**
	 * Delete a movie from the database.
	 *
	 * @since    1.0.0
	 * @param    int $movie_id    The movie ID.
	 * @return   bool                  True on success, false on failure.
	 */
	public function delete_movie( $movie_id ) {
		global $wpdb;

		// First delete any relationships
		$wpdb->delete(
			$this->relationships_table,
			array( 'movie_id' => $movie_id )
		);

		// Then delete the movie
		$result = $wpdb->delete(
			$this->movies_table,
			array( 'id' => $movie_id )
		);

		if ( $result !== false ) {
			$this->invalidate_stats_cache();
		}

		return $result !== false;
	}

	/**
	 * Insert a box set into the database.
	 *
	 * @since    1.0.0
	 * @param    array $box_set                    The box set data.
	 * @param    bool  $skip_cache_invalidation   Whether to skip stats cache invalidation. Default false.
	 * @return   int|false                        The box set ID on success, false on failure.
	 */
	public function insert_box_set( $box_set, $skip_cache_invalidation = false ) {
		global $wpdb;

		// Set timestamps
		$box_set['created_at'] = current_time( 'mysql' );
		$box_set['updated_at'] = current_time( 'mysql' );

		$result = $wpdb->insert( $this->box_sets_table, $box_set );

		if ( $result ) {
			if ( ! $skip_cache_invalidation ) {
				$this->invalidate_stats_cache();
			}
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Update a box set in the database.
	 *
	 * @since    1.0.0
	 * @param    int   $box_set_id    The box set ID.
	 * @param    array $box_set       The box set data.
	 * @return   bool                    True on success, false on failure.
	 */
	public function update_box_set( $box_set_id, $box_set ) {
		global $wpdb;

		// Set updated timestamp
		$box_set['updated_at'] = current_time( 'mysql' );

		$result = $wpdb->update(
			$this->box_sets_table,
			$box_set,
			array( 'id' => $box_set_id )
		);

		if ( $result > 0 ) {
			$this->invalidate_stats_cache();
		}

		return $result !== false;
	}

	/**
	 * Get a box set from the database.
	 *
	 * @since    1.0.0
	 * @param    int $box_set_id    The box set ID.
	 * @return   array|null              The box set data, or null if not found.
	 */
	public function get_box_set( $box_set_id ) {
		global $wpdb;

		$box_set = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $this->box_sets_table WHERE id = %d",
				$box_set_id
			),
			ARRAY_A
		);

		return $box_set;
	}

	/**
	 * Get a box set by barcode.
	 *
	 * @since    1.0.0
	 * @param    string $barcode    The box set barcode.
	 * @return   array|null            The box set data, or null if not found.
	 */
	public function get_box_set_by_barcode( $barcode ) {
		global $wpdb;

		$box_set = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM $this->box_sets_table WHERE barcode = %s",
				$barcode
			),
			ARRAY_A
		);

		return $box_set;
	}

	/**
	 * Find duplicate box sets by barcode or title+year.
	 *
	 * @since    1.0.0
	 * @param    string $title       The box set title.
	 * @param    int    $year        The release year.
	 * @param    string $barcode     Optional barcode to check.
	 * @param    int    $exclude_id  Optional box set ID to exclude (for edit context).
	 * @return   array               Array with 'barcode_match' and 'title_matches' keys.
	 */
	public function find_duplicate_box_sets( $title, $year, $barcode = '', $exclude_id = 0 ) {
		global $wpdb;

		$result = array(
			'barcode_match' => null,
			'title_matches' => array(),
		);

		// Check barcode match (exact).
		if ( ! empty( $barcode ) ) {
			$sql    = "SELECT id, title, release_year, format, barcode FROM $this->box_sets_table WHERE barcode = %s";
			$params = array( $barcode );

			if ( $exclude_id ) {
				$sql     .= ' AND id != %d';
				$params[] = $exclude_id;
			}

			$result['barcode_match'] = $wpdb->get_row(
				$wpdb->prepare( $sql, $params ),
				ARRAY_A
			);
		}

		// Check title+year match.
		if ( ! empty( $title ) && ! empty( $year ) ) {
			$sql    = "SELECT id, title, release_year, format, barcode FROM $this->box_sets_table WHERE title = %s AND release_year = %d";
			$params = array( $title, $year );

			if ( $exclude_id ) {
				$sql     .= ' AND id != %d';
				$params[] = $exclude_id;
			}

			$sql .= ' LIMIT 5';

			$result['title_matches'] = $wpdb->get_results(
				$wpdb->prepare( $sql, $params ),
				ARRAY_A
			);
		}

		return $result;
	}

	/**
	 * Delete a box set from the database.
	 *
	 * @since    1.0.0
	 * @param    int $box_set_id    The box set ID.
	 * @return   bool                    True on success, false on failure.
	 */
	public function delete_box_set( $box_set_id ) {
		global $wpdb;

		// First delete any relationships
		$wpdb->delete(
			$this->relationships_table,
			array( 'box_set_id' => $box_set_id )
		);

		// Then delete the box set
		$result = $wpdb->delete(
			$this->box_sets_table,
			array( 'id' => $box_set_id )
		);

		if ( $result !== false ) {
			$this->invalidate_stats_cache();
		}

		return $result !== false;
	}

	/**
	 * Add a movie to a box set.
	 *
	 * @since    1.0.0
	 * @param    int $movie_id      The movie ID.
	 * @param    int $box_set_id    The box set ID.
	 * @return   int|false               The relationship ID on success, false on failure.
	 */
	public function add_movie_to_box_set( $movie_id, $box_set_id ) {
		global $wpdb;

		// Check if the relationship already exists
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM $this->relationships_table WHERE movie_id = %d AND box_set_id = %d",
				$movie_id,
				$box_set_id
			)
		);

		if ( $existing ) {
			return $existing;
		}

		// Get the next display_order value for this box set.
		$next_order = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(MAX(display_order), 0) + 1 FROM $this->relationships_table WHERE box_set_id = %d",
				$box_set_id
			)
		);

		// Add the relationship
		$result = $wpdb->insert(
			$this->relationships_table,
			array(
				'movie_id'      => $movie_id,
				'box_set_id'    => $box_set_id,
				'display_order' => $next_order,
			)
		);

		if ( $result ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Remove a movie from a box set.
	 *
	 * @since    1.0.0
	 * @param    int $movie_id      The movie ID.
	 * @param    int $box_set_id    The box set ID.
	 * @return   bool                    True on success, false on failure.
	 */
	public function remove_movie_from_box_set( $movie_id, $box_set_id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$this->relationships_table,
			array(
				'movie_id'   => $movie_id,
				'box_set_id' => $box_set_id,
			)
		);

		return $result !== false;
	}

	/**
	 * Get all movies in a box set.
	 *
	 * @since    1.0.0
	 * @param    int $box_set_id    The box set ID.
	 * @return   array                   The movies in the box set.
	 */
	public function get_movies_in_box_set( $box_set_id ) {
		global $wpdb;

		$movies = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT m.* FROM $this->movies_table m
                JOIN $this->relationships_table r ON m.id = r.movie_id
                WHERE r.box_set_id = %d
                ORDER BY r.display_order ASC, m.title ASC",
				$box_set_id
			),
			ARRAY_A
		);

		return $movies;
	}

	/**
	 * Get all box sets containing a movie.
	 *
	 * @since    1.0.0
	 * @param    int $movie_id    The movie ID.
	 * @return   array                 The box sets containing the movie.
	 */
	public function get_box_sets_containing_movie( $movie_id ) {
		global $wpdb;

		$box_sets = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT b.* FROM $this->box_sets_table b
                JOIN $this->relationships_table r ON b.id = r.box_set_id
                WHERE r.movie_id = %d
                ORDER BY b.title ASC",
				$movie_id
			),
			ARRAY_A
		);

		return $box_sets;
	}

	/**
	 * Search movies by criteria.
	 *
	 * @since    1.0.0
	 * @param    array $criteria    The search criteria.
	 * @return   array                 The matching movies.
	 */
	public function search_movies( $criteria ) {
		global $wpdb;

		$where  = array();
		$values = array();

		// Build the WHERE clause based on criteria
		if ( ! empty( $criteria['title'] ) ) {
			$where[]  = 'title LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $criteria['title'] ) . '%';
		}

		if ( ! empty( $criteria['year'] ) ) {
			$where[]  = 'release_year = %d';
			$values[] = $criteria['year'];
		}

		if ( ! empty( $criteria['format'] ) ) {
			$where[]  = 'format = %s';
			$values[] = $criteria['format'];
		}

		if ( ! empty( $criteria['director'] ) ) {
			$where[]  = 'director LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $criteria['director'] ) . '%';
		}

		if ( ! empty( $criteria['actor'] ) ) {
			$where[]  = 'actors LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $criteria['actor'] ) . '%';
		}

		if ( ! empty( $criteria['genre'] ) ) {
			$where[]  = 'genre LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $criteria['genre'] ) . '%';
		}

		if ( ! empty( $criteria['studio'] ) ) {
			$where[]  = 'studio LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $criteria['studio'] ) . '%';
		}

		// Whitelist allowed ORDER BY columns to prevent SQL injection
		$allowed_orderby = array( 'id', 'title', 'release_year', 'format', 'director', 'studio', 'genre', 'created_at', 'updated_at', 'acquisition_date' );
		$orderby         = ! empty( $criteria['orderby'] ) && in_array( $criteria['orderby'], $allowed_orderby, true ) ? $criteria['orderby'] : 'title';
		$allowed_order   = array( 'ASC', 'DESC' );
		$order           = ! empty( $criteria['order'] ) && in_array( strtoupper( $criteria['order'] ), $allowed_order, true ) ? strtoupper( $criteria['order'] ) : 'ASC';

		// Build the query
		$sql = "SELECT * FROM $this->movies_table";

		if ( ! empty( $where ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
		}

		$sql .= " ORDER BY $orderby $order";

		// Apply pagination if provided
		if ( isset( $criteria['per_page'] ) && isset( $criteria['page'] ) ) {
			$per_page = intval( $criteria['per_page'] );
			$offset   = intval( $criteria['page'] - 1 ) * $per_page;
			$sql     .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $per_page, $offset );
		}

		// Prepare and execute the query
		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $sql, $values );
		}

		$results = $wpdb->get_results( $sql, ARRAY_A );

		return $results;
	}

	/**
	 * Count movies matching search criteria.
	 *
	 * @since    1.0.0
	 * @param    array $criteria    The search criteria.
	 * @return   int                   The count of matching movies.
	 */
	public function count_movies( $criteria = array() ) {
		global $wpdb;

		$where  = array();
		$values = array();

		if ( ! empty( $criteria['title'] ) ) {
			$where[]  = 'title LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $criteria['title'] ) . '%';
		}

		if ( ! empty( $criteria['year'] ) ) {
			$where[]  = 'release_year = %d';
			$values[] = $criteria['year'];
		}

		if ( ! empty( $criteria['format'] ) ) {
			$where[]  = 'format = %s';
			$values[] = $criteria['format'];
		}

		if ( ! empty( $criteria['director'] ) ) {
			$where[]  = 'director LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $criteria['director'] ) . '%';
		}

		if ( ! empty( $criteria['actor'] ) ) {
			$where[]  = 'actors LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $criteria['actor'] ) . '%';
		}

		if ( ! empty( $criteria['genre'] ) ) {
			$where[]  = 'genre LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $criteria['genre'] ) . '%';
		}

		if ( ! empty( $criteria['studio'] ) ) {
			$where[]  = 'studio LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $criteria['studio'] ) . '%';
		}

		$sql = "SELECT COUNT(*) FROM $this->movies_table";

		if ( ! empty( $where ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
		}

		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $sql, $values );
		}

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Count box sets matching criteria.
	 *
	 * @since    1.1.0
	 * @param    array $criteria    Search criteria.
	 * @return   int                The number of matching box sets.
	 */
	public function count_box_sets( $criteria = array() ) {
		global $wpdb;

		$where  = array();
		$values = array();

		if ( ! empty( $criteria['title'] ) ) {
			$where[]  = 'title LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $criteria['title'] ) . '%';
		}

		if ( ! empty( $criteria['year'] ) ) {
			$where[]  = 'release_year = %d';
			$values[] = $criteria['year'];
		}

		if ( ! empty( $criteria['format'] ) ) {
			$where[]  = 'format = %s';
			$values[] = $criteria['format'];
		}

		$sql = "SELECT COUNT(*) FROM $this->box_sets_table";

		if ( ! empty( $where ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
		}

		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $sql, $values );
		}

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Get comprehensive collection statistics.
	 *
	 * @since    1.1.0
	 * @return   array    Associative array of collection statistics.
	 */
	public function get_collection_stats() {
		$cached = get_transient( 'wp_movie_collector_stats' );
		if (
			is_array( $cached ) &&
			array_key_exists( 'total_movies', $cached ) &&
			array_key_exists( 'total_box_sets', $cached ) &&
			array_key_exists( 'format_breakdown', $cached )
		) {
			return $cached;
		}

		if ( false !== $cached ) {
			delete_transient( 'wp_movie_collector_stats' );
		}

		global $wpdb;

		$stats = array();

		// Total counts.
		$stats['total_movies']   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->movies_table}" );
		$stats['total_box_sets'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->box_sets_table}" );

		// Format breakdown for movies.
		$format_rows               = $wpdb->get_results(
			"SELECT format, COUNT(*) as count FROM {$this->movies_table} WHERE format IS NOT NULL AND TRIM(format) != '' GROUP BY format ORDER BY count DESC",
			ARRAY_A
		);
		$stats['format_breakdown'] = array();
		if ( $format_rows ) {
			foreach ( $format_rows as $row ) {
				$stats['format_breakdown'][ $row['format'] ] = (int) $row['count'];
			}
		}

		// Top genres (from comma-separated genre field).
		$all_genres   = $wpdb->get_col( "SELECT genre FROM {$this->movies_table} WHERE genre != ''" );
		$genre_counts = array();
		if ( $all_genres ) {
			foreach ( $all_genres as $genre_string ) {
				$genres = array_map( 'trim', explode( ',', $genre_string ) );
				foreach ( $genres as $genre ) {
					if ( '' !== $genre ) {
						$genre_counts[ $genre ] = isset( $genre_counts[ $genre ] ) ? $genre_counts[ $genre ] + 1 : 1;
					}
				}
			}
			arsort( $genre_counts );
		}
		$stats['top_genres'] = array_slice( $genre_counts, 0, 5, true );

		// Top directors.
		$director_rows          = $wpdb->get_results(
			"SELECT director, COUNT(*) as count FROM {$this->movies_table} WHERE director != '' GROUP BY director ORDER BY count DESC LIMIT 5",
			ARRAY_A
		);
		$stats['top_directors'] = array();
		if ( $director_rows ) {
			foreach ( $director_rows as $row ) {
				$stats['top_directors'][ $row['director'] ] = (int) $row['count'];
			}
		}

		// Top studios.
		$studio_rows          = $wpdb->get_results(
			"SELECT studio, COUNT(*) as count FROM {$this->movies_table} WHERE studio != '' GROUP BY studio ORDER BY count DESC LIMIT 5",
			ARRAY_A
		);
		$stats['top_studios'] = array();
		if ( $studio_rows ) {
			foreach ( $studio_rows as $row ) {
				$stats['top_studios'][ $row['studio'] ] = (int) $row['count'];
			}
		}

		// Unique counts.
		$stats['unique_directors'] = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT director) FROM {$this->movies_table} WHERE director != ''" );
		$stats['unique_studios']   = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT studio) FROM {$this->movies_table} WHERE studio != ''" );

		// Recent additions (last 30 days). Use current_time() to match how created_at is stored.
		$recent_cutoff           = gmdate( 'Y-m-d H:i:s', current_time( 'timestamp' ) - ( 30 * DAY_IN_SECONDS ) );
		$recent_movies           = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->movies_table} WHERE created_at >= %s",
				$recent_cutoff
			)
		);
		$recent_box_sets         = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->box_sets_table} WHERE created_at >= %s",
				$recent_cutoff
			)
		);
		$stats['recent_count']   = $recent_movies + $recent_box_sets;

		// Year range across the whole collection, ignoring invalid years (0/NULL).
		$year_range = $wpdb->get_row(
			"
			SELECT MIN(release_year) AS earliest_year, MAX(release_year) AS latest_year
			FROM (
				SELECT release_year FROM {$this->movies_table} WHERE release_year IS NOT NULL AND release_year > 0
				UNION ALL
				SELECT release_year FROM {$this->box_sets_table} WHERE release_year IS NOT NULL AND release_year > 0
			) AS collection_years
			",
			ARRAY_A
		);
		$stats['earliest_year'] = $year_range ? $year_range['earliest_year'] : null;
		$stats['latest_year']   = $year_range ? $year_range['latest_year'] : null;

		set_transient( 'wp_movie_collector_stats', $stats, HOUR_IN_SECONDS );

		return $stats;
	}

	/**
	 * Invalidate the collection statistics cache.
	 *
	 * Call this after any movie or box set insert, update, or delete.
	 *
	 * @since    1.1.0
	 */
	public function invalidate_stats_cache() {
		delete_transient( 'wp_movie_collector_stats' );
	}

	/**
	 * Search box sets by criteria.
	 *
	 * @since    1.0.0
	 * @param    array $criteria    The search criteria.
	 * @return   array                 The matching box sets.
	 */
	public function search_box_sets( $criteria ) {
		global $wpdb;

		$where  = array();
		$values = array();

		// Build the WHERE clause based on criteria
		if ( ! empty( $criteria['title'] ) ) {
			$where[]  = 'title LIKE %s';
			$values[] = '%' . $wpdb->esc_like( $criteria['title'] ) . '%';
		}

		if ( ! empty( $criteria['year'] ) ) {
			$where[]  = 'release_year = %d';
			$values[] = $criteria['year'];
		}

		if ( ! empty( $criteria['format'] ) ) {
			$where[]  = 'format = %s';
			$values[] = $criteria['format'];
		}

		// Whitelist allowed ORDER BY columns to prevent SQL injection
		$allowed_orderby = array( 'id', 'title', 'release_year', 'format', 'created_at', 'updated_at', 'acquisition_date' );
		$orderby         = ! empty( $criteria['orderby'] ) && in_array( $criteria['orderby'], $allowed_orderby, true ) ? $criteria['orderby'] : 'title';
		$allowed_order   = array( 'ASC', 'DESC' );
		$order           = ! empty( $criteria['order'] ) && in_array( strtoupper( $criteria['order'] ), $allowed_order, true ) ? strtoupper( $criteria['order'] ) : 'ASC';

		// Build the query
		$sql = "SELECT * FROM $this->box_sets_table";

		if ( ! empty( $where ) ) {
			$sql .= ' WHERE ' . implode( ' AND ', $where );
		}

		$sql .= " ORDER BY $orderby $order";

		// Apply pagination if provided
		if ( isset( $criteria['per_page'] ) && isset( $criteria['page'] ) ) {
			$per_page = intval( $criteria['per_page'] );
			$offset   = intval( $criteria['page'] - 1 ) * $per_page;
			$sql     .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $per_page, $offset );
		}

		// Prepare and execute the query
		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $sql, $values );
		}

		$results = $wpdb->get_results( $sql, ARRAY_A );

		return $results;
	}
}
