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
        $this->movies_table = $wpdb->prefix . 'movie_collection';
        $this->box_sets_table = $wpdb->prefix . 'movie_box_sets';
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
        $movies_table = $this->get_movies_table();
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $movies_table LIKE 'cover_image_id'");
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $movies_table ADD COLUMN cover_image_id bigint(20) NULL AFTER cover_image_url, ADD INDEX (cover_image_id)");
        }
        
        // Check if cover_image_id column exists in box sets table
        $box_sets_table = $this->get_box_sets_table();
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $box_sets_table LIKE 'cover_image_id'");
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $box_sets_table ADD COLUMN cover_image_id bigint(20) NULL AFTER cover_image_url, ADD INDEX (cover_image_id)");
        }
        
        // Check if display_order column exists in relationships table
        $relationships_table = $this->get_relationships_table();
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $relationships_table LIKE 'display_order'");
        
        if (empty($column_exists)) {
            $wpdb->query("ALTER TABLE $relationships_table ADD COLUMN display_order int(11) DEFAULT 0");
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
            KEY cover_image_id (cover_image_id)
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
            KEY cover_image_id (cover_image_id)
        ) $charset_collate;

        CREATE TABLE $this->relationships_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            movie_id bigint(20) NOT NULL,
            box_set_id bigint(20) NOT NULL,
            PRIMARY KEY  (id),
            KEY movie_id (movie_id),
            KEY box_set_id (box_set_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Insert a movie into the database.
     *
     * @since    1.0.0
     * @param    array    $movie    The movie data.
     * @return   int|false          The movie ID on success, false on failure.
     */
    public function insert_movie($movie) {
        global $wpdb;
        
        // Set timestamps
        $movie['created_at'] = current_time('mysql');
        $movie['updated_at'] = current_time('mysql');
        
        $result = $wpdb->insert($this->movies_table, $movie);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Update a movie in the database.
     *
     * @since    1.0.0
     * @param    int      $movie_id    The movie ID.
     * @param    array    $movie       The movie data.
     * @return   bool                  True on success, false on failure.
     */
    public function update_movie($movie_id, $movie) {
        global $wpdb;
        
        // Set updated timestamp
        $movie['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update(
            $this->movies_table,
            $movie,
            array('id' => $movie_id)
        );
        
        return $result !== false;
    }

    /**
     * Get a movie from the database.
     *
     * @since    1.0.0
     * @param    int      $movie_id    The movie ID.
     * @return   array|null            The movie data, or null if not found.
     */
    public function get_movie($movie_id) {
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
     * @param    string    $barcode    The movie barcode.
     * @return   array|null            The movie data, or null if not found.
     */
    public function get_movie_by_barcode($barcode) {
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
     * Delete a movie from the database.
     *
     * @since    1.0.0
     * @param    int      $movie_id    The movie ID.
     * @return   bool                  True on success, false on failure.
     */
    public function delete_movie($movie_id) {
        global $wpdb;
        
        // First delete any relationships
        $wpdb->delete(
            $this->relationships_table,
            array('movie_id' => $movie_id)
        );
        
        // Then delete the movie
        $result = $wpdb->delete(
            $this->movies_table,
            array('id' => $movie_id)
        );
        
        return $result !== false;
    }

    /**
     * Insert a box set into the database.
     *
     * @since    1.0.0
     * @param    array    $box_set    The box set data.
     * @return   int|false            The box set ID on success, false on failure.
     */
    public function insert_box_set($box_set) {
        global $wpdb;
        
        // Set timestamps
        $box_set['created_at'] = current_time('mysql');
        $box_set['updated_at'] = current_time('mysql');
        
        $result = $wpdb->insert($this->box_sets_table, $box_set);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Update a box set in the database.
     *
     * @since    1.0.0
     * @param    int      $box_set_id    The box set ID.
     * @param    array    $box_set       The box set data.
     * @return   bool                    True on success, false on failure.
     */
    public function update_box_set($box_set_id, $box_set) {
        global $wpdb;
        
        // Set updated timestamp
        $box_set['updated_at'] = current_time('mysql');
        
        $result = $wpdb->update(
            $this->box_sets_table,
            $box_set,
            array('id' => $box_set_id)
        );
        
        return $result !== false;
    }

    /**
     * Get a box set from the database.
     *
     * @since    1.0.0
     * @param    int      $box_set_id    The box set ID.
     * @return   array|null              The box set data, or null if not found.
     */
    public function get_box_set($box_set_id) {
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
     * @param    string    $barcode    The box set barcode.
     * @return   array|null            The box set data, or null if not found.
     */
    public function get_box_set_by_barcode($barcode) {
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
     * Delete a box set from the database.
     *
     * @since    1.0.0
     * @param    int      $box_set_id    The box set ID.
     * @return   bool                    True on success, false on failure.
     */
    public function delete_box_set($box_set_id) {
        global $wpdb;
        
        // First delete any relationships
        $wpdb->delete(
            $this->relationships_table,
            array('box_set_id' => $box_set_id)
        );
        
        // Then delete the box set
        $result = $wpdb->delete(
            $this->box_sets_table,
            array('id' => $box_set_id)
        );
        
        return $result !== false;
    }

    /**
     * Add a movie to a box set.
     *
     * @since    1.0.0
     * @param    int      $movie_id      The movie ID.
     * @param    int      $box_set_id    The box set ID.
     * @return   int|false               The relationship ID on success, false on failure.
     */
    public function add_movie_to_box_set($movie_id, $box_set_id) {
        global $wpdb;
        
        // Check if the relationship already exists
        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $this->relationships_table WHERE movie_id = %d AND box_set_id = %d",
                $movie_id,
                $box_set_id
            )
        );
        
        if ($existing) {
            return $existing;
        }
        
        // Add the relationship
        $result = $wpdb->insert(
            $this->relationships_table,
            array(
                'movie_id' => $movie_id,
                'box_set_id' => $box_set_id
            )
        );
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Remove a movie from a box set.
     *
     * @since    1.0.0
     * @param    int      $movie_id      The movie ID.
     * @param    int      $box_set_id    The box set ID.
     * @return   bool                    True on success, false on failure.
     */
    public function remove_movie_from_box_set($movie_id, $box_set_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->relationships_table,
            array(
                'movie_id' => $movie_id,
                'box_set_id' => $box_set_id
            )
        );
        
        return $result !== false;
    }

    /**
     * Get all movies in a box set.
     *
     * @since    1.0.0
     * @param    int      $box_set_id    The box set ID.
     * @return   array                   The movies in the box set.
     */
    public function get_movies_in_box_set($box_set_id) {
        global $wpdb;
        
        $movies = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT m.* FROM $this->movies_table m
                JOIN $this->relationships_table r ON m.id = r.movie_id
                WHERE r.box_set_id = %d
                ORDER BY m.title ASC",
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
     * @param    int      $movie_id    The movie ID.
     * @return   array                 The box sets containing the movie.
     */
    public function get_box_sets_containing_movie($movie_id) {
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
     * @param    array    $criteria    The search criteria.
     * @return   array                 The matching movies.
     */
    public function search_movies($criteria) {
        global $wpdb;
        
        $where = array();
        $values = array();
        
        // Build the WHERE clause based on criteria
        if (!empty($criteria['title'])) {
            $where[] = "title LIKE %s";
            $values[] = '%' . $wpdb->esc_like($criteria['title']) . '%';
        }
        
        if (!empty($criteria['year'])) {
            $where[] = "release_year = %d";
            $values[] = $criteria['year'];
        }
        
        if (!empty($criteria['format'])) {
            $where[] = "format = %s";
            $values[] = $criteria['format'];
        }
        
        if (!empty($criteria['director'])) {
            $where[] = "director LIKE %s";
            $values[] = '%' . $wpdb->esc_like($criteria['director']) . '%';
        }
        
        if (!empty($criteria['actor'])) {
            $where[] = "actors LIKE %s";
            $values[] = '%' . $wpdb->esc_like($criteria['actor']) . '%';
        }
        
        if (!empty($criteria['genre'])) {
            $where[] = "genre LIKE %s";
            $values[] = '%' . $wpdb->esc_like($criteria['genre']) . '%';
        }
        
        if (!empty($criteria['studio'])) {
            $where[] = "studio LIKE %s";
            $values[] = '%' . $wpdb->esc_like($criteria['studio']) . '%';
        }
        
        // Default ordering
        $orderby = !empty($criteria['orderby']) ? $criteria['orderby'] : 'title';
        $order = !empty($criteria['order']) ? $criteria['order'] : 'ASC';
        
        // Build the query
        $sql = "SELECT * FROM $this->movies_table";
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY $orderby $order";
        
        // Apply pagination if provided
        if (isset($criteria['per_page']) && isset($criteria['page'])) {
            $per_page = intval($criteria['per_page']);
            $offset = intval($criteria['page'] - 1) * $per_page;
            $sql .= " LIMIT $per_page OFFSET $offset";
        }
        
        // Prepare and execute the query
        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }
        
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        return $results;
    }

    /**
     * Search box sets by criteria.
     *
     * @since    1.0.0
     * @param    array    $criteria    The search criteria.
     * @return   array                 The matching box sets.
     */
    public function search_box_sets($criteria) {
        global $wpdb;
        
        $where = array();
        $values = array();
        
        // Build the WHERE clause based on criteria
        if (!empty($criteria['title'])) {
            $where[] = "title LIKE %s";
            $values[] = '%' . $wpdb->esc_like($criteria['title']) . '%';
        }
        
        if (!empty($criteria['year'])) {
            $where[] = "release_year = %d";
            $values[] = $criteria['year'];
        }
        
        if (!empty($criteria['format'])) {
            $where[] = "format = %s";
            $values[] = $criteria['format'];
        }
        
        // Default ordering
        $orderby = !empty($criteria['orderby']) ? $criteria['orderby'] : 'title';
        $order = !empty($criteria['order']) ? $criteria['order'] : 'ASC';
        
        // Build the query
        $sql = "SELECT * FROM $this->box_sets_table";
        
        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }
        
        $sql .= " ORDER BY $orderby $order";
        
        // Apply pagination if provided
        if (isset($criteria['per_page']) && isset($criteria['page'])) {
            $per_page = intval($criteria['per_page']);
            $offset = intval($criteria['page'] - 1) * $per_page;
            $sql .= " LIMIT $per_page OFFSET $offset";
        }
        
        // Prepare and execute the query
        if (!empty($values)) {
            $sql = $wpdb->prepare($sql, $values);
        }
        
        $results = $wpdb->get_results($sql, ARRAY_A);
        
        return $results;
    }
}