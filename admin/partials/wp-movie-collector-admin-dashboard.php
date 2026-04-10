<?php
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'wp-movie-collector' ), '', array( 'response' => 403 ) );
}
?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php
	// Show success message if there is one
	if ( isset( $_GET['message'] ) ) {
		$message_type = sanitize_text_field( wp_unslash( $_GET['message'] ) );
		$message      = '';

		switch ( $message_type ) {
			case 'movie_added':
				$message = __( 'Movie added successfully!', 'wp-movie-collector' );
				break;
			case 'movie_updated':
				$message = __( 'Movie updated successfully!', 'wp-movie-collector' );
				break;
			case 'movie_deleted':
				$message = __( 'Movie deleted successfully!', 'wp-movie-collector' );
				break;
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

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}
	?>
	
	<div class="wp-movie-collector-dashboard">
		<div class="wp-movie-collector-stats">
			<?php
			global $wpdb;
			$db = new WP_Movie_Collector_DB();

			// Get count of movies
			$movies_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$db->get_movies_table()}" );
			$movies_count = $movies_count ? $movies_count : 0;

			// Get count of box sets
			$box_sets_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$db->get_box_sets_table()}" );
			$box_sets_count = $box_sets_count ? $box_sets_count : 0;
			?>
			
			<div class="wp-movie-collector-stat-box">
				<h2><?php echo intval( $movies_count ); ?></h2>
				<p><?php esc_html_e( 'Movies', 'wp-movie-collector' ); ?></p>
			</div>
			
			<div class="wp-movie-collector-stat-box">
				<h2><?php echo intval( $box_sets_count ); ?></h2>
				<p><?php esc_html_e( 'Box Sets', 'wp-movie-collector' ); ?></p>
			</div>
		</div>
		
		<div class="wp-movie-collector-actions">
			<div class="wp-movie-collector-action-box">
				<h3><?php esc_html_e( 'Quick Actions', 'wp-movie-collector' ); ?></h3>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-add-movie' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Add New Movie', 'wp-movie-collector' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-add-box-set' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Add New Box Set', 'wp-movie-collector' ); ?></a>
			</div>
			
			<div class="wp-movie-collector-action-box">
				<h3><?php esc_html_e( 'Recent Movies', 'wp-movie-collector' ); ?></h3>
				<?php
				// Get recent movies
				$recent_movies = $wpdb->get_results( $wpdb->prepare( "SELECT id, title FROM {$db->get_movies_table()} ORDER BY created_at DESC LIMIT %d", 5 ), ARRAY_A );

				if ( $recent_movies ) :
					?>
				<ul>
					<?php foreach ( $recent_movies as $movie ) : ?>
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-edit-movie&id=' . intval( $movie['id'] ) ) ); ?>"><?php echo esc_html( $movie['title'] ); ?></a>
						<div class="row-actions">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-edit-movie&id=' . intval( $movie['id'] ) ) ); ?>"><?php esc_html_e( 'Edit', 'wp-movie-collector' ); ?></a> |
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
								<input type="hidden" name="action" value="wp_movie_collector_delete_movie">
								<input type="hidden" name="id" value="<?php echo intval( $movie['id'] ); ?>">
								<input type="hidden" name="redirect_to" value="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-dashboard' ) ); ?>">
								<?php wp_nonce_field( 'wp_movie_collector_delete_movie_' . intval( $movie['id'] ), 'wp_movie_collector_nonce' ); ?>
								<button type="submit"
										onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this movie? This action cannot be undone.', 'wp-movie-collector' ) ); ?>');"
										style="color:#b32d2e; background:none; border:none; padding:0; cursor:pointer; text-decoration:underline;"><?php esc_html_e( 'Delete', 'wp-movie-collector' ); ?></button>
							</form>
						</div>
					</li>
					<?php endforeach; ?>
				</ul>
				<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-movies' ) ); ?>"><?php esc_html_e( 'View All Movies', 'wp-movie-collector' ); ?></a></p>
				<?php else : ?>
				<p><?php esc_html_e( 'No movies yet. Why not add one?', 'wp-movie-collector' ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		
		<div class="wp-movie-collector-search">
			<h3><?php esc_html_e( 'Search Collection', 'wp-movie-collector' ); ?></h3>
			<form method="get">
				<input type="hidden" name="page" value="wp-movie-collector-dashboard">
				<input type="text" name="search" placeholder="<?php esc_attr_e( 'Search by title, director, actor...', 'wp-movie-collector' ); ?>" class="regular-text">
				<button type="submit" class="button"><?php esc_html_e( 'Search', 'wp-movie-collector' ); ?></button>
			</form>
		</div>
	</div>
</div>