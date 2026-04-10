<?php
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'wp-movie-collector' ), '', array( 'response' => 403 ) );
}

$db = new WP_Movie_Collector_DB();
global $wpdb;

// Get box sets.
$box_sets = $db->search_box_sets(
	array(
		'orderby' => 'title',
		'order'   => 'ASC',
	)
);

// Fetch all movie counts in a single query to avoid N+1.
$movie_counts = array();
if ( ! empty( $box_sets ) ) {
	$counts_results = $wpdb->get_results(
		"SELECT box_set_id, COUNT(*) as movie_count FROM {$db->get_relationships_table()} GROUP BY box_set_id",
		ARRAY_A
	);
	foreach ( $counts_results as $row ) {
		$movie_counts[ $row['box_set_id'] ] = intval( $row['movie_count'] );
	}
}
?>
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'All Box Sets', 'wp-movie-collector' ); ?></h1>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-add-box-set' ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New Box Set', 'wp-movie-collector' ); ?></a>
	<hr class="wp-header-end">

	<?php
	// Show success/error messages.
	if ( isset( $_GET['message'] ) ) {
		$message_type = sanitize_text_field( wp_unslash( $_GET['message'] ) );
		$message      = '';

		switch ( $message_type ) {
			case 'box_set_added':
				$message = __( 'Box set added successfully!', 'wp-movie-collector' );
				break;
			case 'box_set_updated':
				$message = __( 'Box set updated successfully!', 'wp-movie-collector' );
				break;
			case 'box_set_deleted':
				$message = __( 'Box set deleted successfully!', 'wp-movie-collector' );
				break;
			default:
				$message = __( 'Operation completed successfully.', 'wp-movie-collector' );
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
				$error_msg = __( 'Failed to delete box set.', 'wp-movie-collector' );
				break;
			case 'invalid_box_set':
				$error_msg = __( 'Invalid box set ID.', 'wp-movie-collector' );
				break;
		}

		if ( $error_msg ) {
			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error_msg ) . '</p></div>';
		}
	}
	?>

	<table class="wp-list-table widefat fixed striped table-view-list box-sets">
		<thead>
			<tr>
				<th scope="col" class="manage-column column-title column-primary"><?php esc_html_e( 'Title', 'wp-movie-collector' ); ?></th>
				<th scope="col" class="manage-column column-release_year"><?php esc_html_e( 'Year', 'wp-movie-collector' ); ?></th>
				<th scope="col" class="manage-column column-format"><?php esc_html_e( 'Format', 'wp-movie-collector' ); ?></th>
				<th scope="col" class="manage-column column-region"><?php esc_html_e( 'Region', 'wp-movie-collector' ); ?></th>
				<th scope="col" class="manage-column column-movies"><?php esc_html_e( 'Movies', 'wp-movie-collector' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if ( ! empty( $box_sets ) ) : ?>
				<?php
				foreach ( $box_sets as $box_set ) :
					$movie_count = isset( $movie_counts[ $box_set['id'] ] ) ? $movie_counts[ $box_set['id'] ] : 0;
					?>
				<tr>
					<td class="title column-title has-row-actions column-primary" data-colname="<?php esc_attr_e( 'Title', 'wp-movie-collector' ); ?>">
						<strong>
							<a class="row-title" href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-edit-box-set&id=' . intval( $box_set['id'] ) ) ); ?>">
								<?php echo esc_html( $box_set['title'] ); ?>
							</a>
						</strong>
						<div class="row-actions">
							<span class="edit">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-edit-box-set&id=' . intval( $box_set['id'] ) ) ); ?>">
									<?php esc_html_e( 'Edit', 'wp-movie-collector' ); ?>
								</a> |
							</span>
							<span class="manage">
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-manage-box-set&id=' . intval( $box_set['id'] ) ) ); ?>">
									<?php esc_html_e( 'Manage Movies', 'wp-movie-collector' ); ?>
								</a> |
							</span>
							<div class="trash" style="display:inline;">
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
									<input type="hidden" name="action" value="wp_movie_collector_delete_box_set">
									<input type="hidden" name="id" value="<?php echo intval( $box_set['id'] ); ?>">
									<input type="hidden" name="redirect_to" value="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-box-sets' ) ); ?>">
									<?php wp_nonce_field( 'wp_movie_collector_delete_box_set_' . intval( $box_set['id'] ), 'wp_movie_collector_nonce' ); ?>
									<button type="submit"
										class="button-link submitdelete"
										onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this box set? This action cannot be undone.', 'wp-movie-collector' ) ); ?>');">
										<?php esc_html_e( 'Delete', 'wp-movie-collector' ); ?>
									</button>
								</form>
							</div>
						</div>
					</td>
					<td data-colname="<?php esc_attr_e( 'Year', 'wp-movie-collector' ); ?>"><?php echo esc_html( $box_set['release_year'] ); ?></td>
					<td data-colname="<?php esc_attr_e( 'Format', 'wp-movie-collector' ); ?>"><?php echo esc_html( $box_set['format'] ); ?></td>
					<td data-colname="<?php esc_attr_e( 'Region', 'wp-movie-collector' ); ?>"><?php echo esc_html( $box_set['region_code'] ); ?></td>
					<td data-colname="<?php esc_attr_e( 'Movies', 'wp-movie-collector' ); ?>"><?php echo intval( $movie_count ); ?></td>
				</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="5">
						<?php esc_html_e( 'No box sets found.', 'wp-movie-collector' ); ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-add-box-set' ) ); ?>"><?php esc_html_e( 'Add your first box set!', 'wp-movie-collector' ); ?></a>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
		<tfoot>
			<tr>
				<th scope="col" class="manage-column column-title column-primary"><?php esc_html_e( 'Title', 'wp-movie-collector' ); ?></th>
				<th scope="col" class="manage-column column-release_year"><?php esc_html_e( 'Year', 'wp-movie-collector' ); ?></th>
				<th scope="col" class="manage-column column-format"><?php esc_html_e( 'Format', 'wp-movie-collector' ); ?></th>
				<th scope="col" class="manage-column column-region"><?php esc_html_e( 'Region', 'wp-movie-collector' ); ?></th>
				<th scope="col" class="manage-column column-movies"><?php esc_html_e( 'Movies', 'wp-movie-collector' ); ?></th>
			</tr>
		</tfoot>
	</table>
</div>
