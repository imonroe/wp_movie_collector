<?php
/**
 * Synchronizes the custom-table records with WordPress custom post types.
 *
 * The custom tables (wp_movie_collection, wp_movie_box_sets) remain the
 * source of truth; the `movie` and `box_set` posts are a "view layer" that
 * makes collection items work with permalinks, WordPress search, taxonomies,
 * and SEO plugins. Each post stores its source table ID in post meta so the
 * two stay cross-referenced.
 *
 * @since      1.4.0
 * @package    WP_Movie_Collector
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WP_Movie_Collector_Sync {

	/**
	 * Post meta key storing the source movie table ID.
	 */
	const MOVIE_ID_META = '_wp_movie_collector_movie_id';

	/**
	 * Post meta key storing the source box set table ID.
	 */
	const BOX_SET_ID_META = '_wp_movie_collector_box_set_id';

	/**
	 * Create or update the `movie` post mirroring a custom-table movie row.
	 *
	 * @since 1.4.0
	 * @param int $movie_id The movie table ID.
	 * @param array|null $movie Optional pre-fetched movie row, to avoid a
	 *                          redundant query during bulk sync.
	 * @return int|null The post ID, or null if the movie no longer exists.
	 */
	public function sync_movie( $movie_id, $movie = null ) {
		if ( null === $movie ) {
			$db    = new WP_Movie_Collector_DB();
			$movie = $db->get_movie( (int) $movie_id );
		}
		if ( empty( $movie ) ) {
			return null;
		}

		$post_id = $this->upsert_post(
			'movie',
			self::MOVIE_ID_META,
			(int) $movie_id,
			$movie['title'],
			isset( $movie['description'] ) ? $movie['description'] : ''
		);

		if ( ! $post_id ) {
			return null;
		}

		$this->set_featured_image( $post_id, $movie );

		// Map metadata fields onto taxonomy terms.
		$this->assign_terms( $post_id, 'genre', $this->split_terms( $movie['genre'] ?? '' ) );
		$this->assign_terms( $post_id, 'director', $this->split_terms( $movie['director'] ?? '' ) );
		$this->assign_terms( $post_id, 'studio', $this->split_terms( $movie['studio'] ?? '' ) );
		$this->assign_terms( $post_id, 'actor', $this->split_terms( $movie['actors'] ?? '' ) );

		return $post_id;
	}

	/**
	 * Create or update the `box_set` post mirroring a box set row.
	 *
	 * @since 1.4.0
	 * @param int        $box_set_id The box set table ID.
	 * @param array|null $box_set    Optional pre-fetched box set row, to avoid
	 *                               a redundant query during bulk sync.
	 * @return int|null The post ID, or null if the box set no longer exists.
	 */
	public function sync_box_set( $box_set_id, $box_set = null ) {
		if ( null === $box_set ) {
			$db      = new WP_Movie_Collector_DB();
			$box_set = $db->get_box_set( (int) $box_set_id );
		}
		if ( empty( $box_set ) ) {
			return null;
		}

		$post_id = $this->upsert_post(
			'box_set',
			self::BOX_SET_ID_META,
			(int) $box_set_id,
			$box_set['title'],
			isset( $box_set['description'] ) ? $box_set['description'] : ''
		);

		if ( ! $post_id ) {
			return null;
		}

		$this->set_featured_image( $post_id, $box_set );

		return $post_id;
	}

	/**
	 * Delete the `movie` post mirroring a deleted movie row.
	 *
	 * @since 1.4.0
	 * @param int $movie_id The movie table ID.
	 * @return void
	 */
	public function delete_movie( $movie_id ) {
		$this->delete_synced_post( self::MOVIE_ID_META, (int) $movie_id );
	}

	/**
	 * Delete the `box_set` post mirroring a deleted box set row.
	 *
	 * @since 1.4.0
	 * @param int $box_set_id The box set table ID.
	 * @return void
	 */
	public function delete_box_set( $box_set_id ) {
		$this->delete_synced_post( self::BOX_SET_ID_META, (int) $box_set_id );
	}

	/**
	 * Bulk-sync every movie and box set in the custom tables to posts.
	 *
	 * Used by the admin "Sync to posts" tool to backfill posts for data
	 * that pre-dates the sync feature. Rows are processed in pages rather
	 * than loaded all at once, so the request stays bounded in memory on
	 * large collections.
	 *
	 * @since 1.4.0
	 * @param int $batch_size Rows to load per page.
	 * @return array{movies:int, box_sets:int} Counts of synced items.
	 */
	public function sync_all( $batch_size = 100 ) {
		$db          = new WP_Movie_Collector_DB();
		$batch_size  = max( 1, (int) $batch_size );

		$counts = array(
			'movies'   => $this->sync_all_of_type( array( $db, 'search_movies' ), array( $this, 'sync_movie' ), $batch_size ),
			'box_sets' => $this->sync_all_of_type( array( $db, 'search_box_sets' ), array( $this, 'sync_box_set' ), $batch_size ),
		);

		return $counts;
	}

	/**
	 * Page through one collection type and sync each row to a post.
	 *
	 * @since 1.4.0
	 * @param callable $fetch      Search method returning rows for a page.
	 * @param callable $sync       Sync method accepting ( id, row ).
	 * @param int      $batch_size Rows per page.
	 * @return int Number of rows synced.
	 */
	private function sync_all_of_type( $fetch, $sync, $batch_size ) {
		$synced = 0;
		$page   = 1;

		do {
			$rows = call_user_func(
				$fetch,
				array(
					'per_page' => $batch_size,
					'page'     => $page,
				)
			);

			if ( ! is_array( $rows ) || empty( $rows ) ) {
				break;
			}

			foreach ( $rows as $row ) {
				// Reuse the already-fetched row to avoid an N+1 re-query.
				if ( ! empty( $row['id'] ) && call_user_func( $sync, (int) $row['id'], $row ) ) {
					$synced++;
				}
			}

			$page++;
		} while ( is_array( $rows ) && count( $rows ) === $batch_size );

		return $synced;
	}

	/**
	 * Find the post mirroring a given source row, if any.
	 *
	 * @since 1.4.0
	 * @param string $post_type  Post type ('movie' or 'box_set').
	 * @param string $meta_key   Meta key storing the source ID.
	 * @param int    $source_id  Source table ID.
	 * @return int Post ID, or 0 if none.
	 */
	private function find_post( $post_type, $meta_key, $source_id ) {
		$posts = get_posts(
			array(
				'post_type'        => $post_type,
				'post_status'      => 'any',
				'numberposts'      => 1,
				'fields'           => 'ids',
				'meta_key'         => $meta_key,
				'meta_value'       => (int) $source_id,
				'suppress_filters' => false,
			)
		);

		return ! empty( $posts ) ? (int) $posts[0] : 0;
	}

	/**
	 * Insert or update the mirror post for a source row.
	 *
	 * @since 1.4.0
	 * @param string $post_type Post type.
	 * @param string $meta_key  Source-ID meta key.
	 * @param int    $source_id Source table ID.
	 * @param string $title     Post title.
	 * @param string $content   Post content.
	 * @return int Post ID, or 0 on failure.
	 */
	private function upsert_post( $post_type, $meta_key, $source_id, $title, $content ) {
		$existing = $this->find_post( $post_type, $meta_key, $source_id );

		$postarr = array(
			'post_type'    => $post_type,
			'post_title'   => $title,
			'post_content' => $content,
			'post_status'  => 'publish',
		);

		if ( $existing ) {
			$postarr['ID'] = $existing;
			$post_id       = wp_update_post( $postarr, true );
		} else {
			$post_id = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			return 0;
		}

		update_post_meta( $post_id, $meta_key, (int) $source_id );

		return (int) $post_id;
	}

	/**
	 * Delete the mirror post for a source row.
	 *
	 * @since 1.4.0
	 * @param string $meta_key  Source-ID meta key.
	 * @param int    $source_id Source table ID.
	 * @return void
	 */
	private function delete_synced_post( $meta_key, $source_id ) {
		$post_type = ( self::MOVIE_ID_META === $meta_key ) ? 'movie' : 'box_set';
		$post_id   = $this->find_post( $post_type, $meta_key, $source_id );
		if ( $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}

	/**
	 * Set the post's featured image from the source row's cover image ID.
	 *
	 * @since 1.4.0
	 * @param int   $post_id The post ID.
	 * @param array $row     The source row.
	 * @return void
	 */
	private function set_featured_image( $post_id, $row ) {
		if ( ! empty( $row['cover_image_id'] ) ) {
			set_post_thumbnail( $post_id, (int) $row['cover_image_id'] );
		} else {
			// Clear any stale thumbnail so the post mirrors the table state
			// after a cover image is removed.
			delete_post_thumbnail( $post_id );
		}
	}

	/**
	 * Assign taxonomy terms to a post, creating them if needed.
	 *
	 * @since 1.4.0
	 * @param int      $post_id  The post ID.
	 * @param string   $taxonomy The taxonomy.
	 * @param string[] $terms    Term names.
	 * @return void
	 */
	private function assign_terms( $post_id, $taxonomy, $terms ) {
		// Always set (replacing prior terms) so edits stay in sync; an empty
		// array clears the taxonomy for this post.
		wp_set_object_terms( $post_id, $terms, $taxonomy, false );
	}

	/**
	 * Split a comma-separated field into trimmed, non-empty term names.
	 *
	 * @since 1.4.0
	 * @param string $value The raw field value.
	 * @return string[] Term names.
	 */
	private function split_terms( $value ) {
		if ( '' === trim( (string) $value ) ) {
			return array();
		}

		$terms = array_map( 'trim', explode( ',', (string) $value ) );

		return array_values( array_filter( $terms, static function ( $t ) {
			return '' !== $t;
		} ) );
	}
}
