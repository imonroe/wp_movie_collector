<?php
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'wp-movie-collector' ), '', array( 'response' => 403 ) );
}

$db = new WP_Movie_Collector_DB();

// Handle search and filter parameters
$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
$format   = isset( $_GET['format'] ) ? sanitize_text_field( wp_unslash( $_GET['format'] ) ) : '';
$orderby  = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'title';
$order    = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'ASC';
$paged    = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
$per_page = 20;

// Build search criteria
$criteria = array(
	'orderby'  => $orderby,
	'order'    => $order,
	'per_page' => $per_page,
	'page'     => $paged,
);

if ( ! empty( $search ) ) {
	$criteria['title'] = $search;
}

if ( ! empty( $format ) ) {
	$criteria['format'] = $format;
}

// Get movies and total count
$movies      = $db->search_movies( $criteria );
$total_items = $db->count_movies( $criteria );
$total_pages = ceil( $total_items / $per_page );

// Toggle sort order for column headers
$toggle_order = ( $order === 'ASC' ) ? 'DESC' : 'ASC';

// Base URL for this page
$base_url = admin_url( 'admin.php?page=wp-movie-collector-movies' );
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'All Movies', 'wp-movie-collector' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-add-movie' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Movie', 'wp-movie-collector' ); ?></a>
	<hr class="wp-header-end">

	<?php
	// Show success/error messages
	if ( isset( $_GET['message'] ) ) {
		$message_type = sanitize_text_field( wp_unslash( $_GET['message'] ) );
		$message      = '';

		switch ( $message_type ) {
			case 'movie_deleted':
				$message = __( 'Movie deleted successfully.', 'wp-movie-collector' );
				break;
			case 'movie_updated':
				$message = __( 'Movie updated successfully.', 'wp-movie-collector' );
				break;
		}

		if ( $message ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
		}
	}

	if ( isset( $_GET['error'] ) ) {
		$error_type = sanitize_text_field( wp_unslash( $_GET['error'] ) );
		$error_msg  = '';

		switch ( $error_type ) {
			case 'delete_failed':
				$error_msg = __( 'Failed to delete movie.', 'wp-movie-collector' );
				break;
			case 'invalid_movie':
				$error_msg = __( 'Invalid movie ID.', 'wp-movie-collector' );
				break;
		}

		if ( $error_msg ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error_msg ) . '</p></div>';
		}
	}
	?>

	<form method="get">
		<input type="hidden" name="page" value="wp-movie-collector-movies">
		<?php if ( ! empty( $format ) ) : ?>
			<input type="hidden" name="format" value="<?php echo esc_attr( $format ); ?>">
		<?php endif; ?>

		<p class="search-box">
			<label class="screen-reader-text" for="movie-search-input"><?php esc_html_e( 'Search Movies', 'wp-movie-collector' ); ?></label>
			<input type="search" id="movie-search-input" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search by title...', 'wp-movie-collector' ); ?>">
			<input type="submit" id="search-submit" class="button" value="<?php esc_attr_e( 'Search Movies', 'wp-movie-collector' ); ?>">
		</p>
	</form>

	<div class="tablenav top">
		<div class="alignleft actions">
			<form method="get">
				<input type="hidden" name="page" value="wp-movie-collector-movies">
				<?php if ( ! empty( $search ) ) : ?>
					<input type="hidden" name="s" value="<?php echo esc_attr( $search ); ?>">
				<?php endif; ?>
				<select name="format">
					<option value=""><?php esc_html_e( 'All Formats', 'wp-movie-collector' ); ?></option>
					<?php
					$formats = array( 'DVD', 'Blu-ray', '4K UHD', 'VHS', 'LaserDisc' );
					foreach ( $formats as $fmt ) {
						printf(
							'<option value="%s"%s>%s</option>',
							esc_attr( $fmt ),
							selected( $format, $fmt, false ),
							esc_html( $fmt )
						);
					}
					?>
				</select>
				<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'wp-movie-collector' ); ?>">
			</form>
		</div>

		<?php if ( $total_pages > 1 ) : ?>
		<div class="tablenav-pages">
			<span class="displaying-num">
				<?php
				printf(
					/* translators: %s: number of items */
					esc_html( _n( '%s item', '%s items', $total_items, 'wp-movie-collector' ) ),
					esc_html( number_format_i18n( $total_items ) )
				);
				?>
			</span>
			<span class="pagination-links">
				<?php
				$pagination_args = array(
					'page'    => 'wp-movie-collector-movies',
					's'       => $search,
					'format'  => $format,
					'orderby' => $orderby,
					'order'   => $order,
				);
				$pagination_args = array_filter( $pagination_args );

				if ( $paged > 1 ) :
					$first_url = add_query_arg( array_merge( $pagination_args, array( 'paged' => 1 ) ), $base_url );
					$prev_url  = add_query_arg( array_merge( $pagination_args, array( 'paged' => $paged - 1 ) ), $base_url );
					?>
					<a class="first-page button" href="<?php echo esc_url( $first_url ); ?>">&laquo;</a>
					<a class="prev-page button" href="<?php echo esc_url( $prev_url ); ?>">&lsaquo;</a>
				<?php else : ?>
					<span class="tablenav-pages-navspan button disabled">&laquo;</span>
					<span class="tablenav-pages-navspan button disabled">&lsaquo;</span>
				<?php endif; ?>

				<span class="paging-input">
					<?php echo esc_html( $paged ); ?>
					<?php esc_html_e( 'of', 'wp-movie-collector' ); ?>
					<span class="total-pages"><?php echo esc_html( $total_pages ); ?></span>
				</span>

				<?php
				if ( $paged < $total_pages ) :
					$next_url = add_query_arg( array_merge( $pagination_args, array( 'paged' => $paged + 1 ) ), $base_url );
					$last_url = add_query_arg( array_merge( $pagination_args, array( 'paged' => $total_pages ) ), $base_url );
					?>
					<a class="next-page button" href="<?php echo esc_url( $next_url ); ?>">&rsaquo;</a>
					<a class="last-page button" href="<?php echo esc_url( $last_url ); ?>">&raquo;</a>
				<?php else : ?>
					<span class="tablenav-pages-navspan button disabled">&rsaquo;</span>
					<span class="tablenav-pages-navspan button disabled">&raquo;</span>
				<?php endif; ?>
			</span>
		</div>
		<?php endif; ?>
	</div>

	<table class="wp-list-table widefat fixed striped table-view-list movies">
		<thead>
			<tr>
				<?php
				$columns = array(
					'title'            => __( 'Title', 'wp-movie-collector' ),
					'release_year'     => __( 'Year', 'wp-movie-collector' ),
					'format'           => __( 'Format', 'wp-movie-collector' ),
					'director'         => __( 'Director', 'wp-movie-collector' ),
					'genre'            => __( 'Genre', 'wp-movie-collector' ),
					'acquisition_date' => __( 'Date Added', 'wp-movie-collector' ),
				);

				foreach ( $columns as $col_key => $col_label ) {
					$col_order    = ( $orderby === $col_key ) ? $toggle_order : 'ASC';
					$sorted_class = ( $orderby === $col_key ) ? 'sorted ' . strtolower( $order ) : 'sortable asc';
					$sort_url     = add_query_arg(
						array(
							'page'    => 'wp-movie-collector-movies',
							's'       => $search,
							'format'  => $format,
							'orderby' => $col_key,
							'order'   => $col_order,
							'paged'   => 1,
						),
						$base_url
					);
					printf(
						'<th scope="col" class="manage-column column-%1$s %2$s"><a href="%3$s"><span>%4$s</span><span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span></a></th>',
						esc_attr( $col_key ),
						esc_attr( $sorted_class ),
						esc_url( $sort_url ),
						esc_html( $col_label )
					);
				}
				?>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $movies ) ) : ?>
				<?php foreach ( $movies as $movie ) : ?>
				<tr>
					<td class="title column-title has-row-actions column-primary" data-colname="<?php esc_attr_e( 'Title', 'wp-movie-collector' ); ?>">
						<strong>
							<a class="row-title" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-edit-movie&id=' . intval( $movie['id'] ) ) ); ?>">
								<?php echo esc_html( $movie['title'] ); ?>
							</a>
						</strong>
						<div class="row-actions">
							<span class="edit">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-edit-movie&id=' . intval( $movie['id'] ) ) ); ?>">
									<?php esc_html_e( 'Edit', 'wp-movie-collector' ); ?>
								</a> |
							</span>
							<span class="trash">
								<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wp-movie-collector-movies&action=wp_movie_collector_delete_movie&id=' . intval( $movie['id'] ) ), 'wp_movie_collector_delete_movie_' . intval( $movie['id'] ), 'wp_movie_collector_nonce' ) ); ?>"
									class="submitdelete"
									onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this movie? This action cannot be undone.', 'wp-movie-collector' ) ); ?>');">
									<?php esc_html_e( 'Delete', 'wp-movie-collector' ); ?>
								</a>
							</span>
						</div>
					</td>
					<td data-colname="<?php esc_attr_e( 'Year', 'wp-movie-collector' ); ?>"><?php echo esc_html( $movie['release_year'] ); ?></td>
					<td data-colname="<?php esc_attr_e( 'Format', 'wp-movie-collector' ); ?>"><?php echo esc_html( $movie['format'] ); ?></td>
					<td data-colname="<?php esc_attr_e( 'Director', 'wp-movie-collector' ); ?>"><?php echo esc_html( $movie['director'] ); ?></td>
					<td data-colname="<?php esc_attr_e( 'Genre', 'wp-movie-collector' ); ?>"><?php echo esc_html( $movie['genre'] ); ?></td>
					<td data-colname="<?php esc_attr_e( 'Date Added', 'wp-movie-collector' ); ?>">
						<?php
						if ( ! empty( $movie['acquisition_date'] ) && $movie['acquisition_date'] !== '0000-00-00' ) {
							echo esc_html( $movie['acquisition_date'] );
						} elseif ( ! empty( $movie['created_at'] ) ) {
							echo esc_html( wp_date( 'Y-m-d', strtotime( $movie['created_at'] ) ) );
						} else {
							echo '&mdash;';
						}
						?>
					</td>
				</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="6">
						<?php esc_html_e( 'No movies found.', 'wp-movie-collector' ); ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-add-movie' ) ); ?>"><?php esc_html_e( 'Add your first movie!', 'wp-movie-collector' ); ?></a>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
		<tfoot>
			<tr>
				<?php
				foreach ( $columns as $col_key => $col_label ) {
					$col_order    = ( $orderby === $col_key ) ? $toggle_order : 'ASC';
					$sorted_class = ( $orderby === $col_key ) ? 'sorted ' . strtolower( $order ) : 'sortable asc';
					$sort_url     = add_query_arg(
						array(
							'page'    => 'wp-movie-collector-movies',
							's'       => $search,
							'format'  => $format,
							'orderby' => $col_key,
							'order'   => $col_order,
							'paged'   => 1,
						),
						$base_url
					);
					printf(
						'<th scope="col" class="manage-column column-%1$s %2$s"><a href="%3$s"><span>%4$s</span><span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span></a></th>',
						esc_attr( $col_key ),
						esc_attr( $sorted_class ),
						esc_url( $sort_url ),
						esc_html( $col_label )
					);
				}
				?>
			</tr>
		</tfoot>
	</table>
</div>
