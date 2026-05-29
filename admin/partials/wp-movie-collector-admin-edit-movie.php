<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'wp-movie-collector' ), '', array( 'response' => 403 ) );
}

// Get movie ID from URL
$movie_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

if ( ! $movie_id ) {
	wp_die( esc_html__( 'Invalid movie ID.', 'wp-movie-collector' ), '', array( 'response' => 400 ) );
}

// Get movie data
$db    = new WP_Movie_Collector_DB();
$movie = $db->get_movie( $movie_id );

if ( ! $movie ) {
	wp_die( esc_html__( 'Movie not found.', 'wp-movie-collector' ), '', array( 'response' => 404 ) );
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Edit Movie', 'wp-movie-collector' ); ?></h1>

	<?php
	// Show error message if there is one
	if ( isset( $_GET['error'] ) ) {
		$error_type = sanitize_text_field( wp_unslash( $_GET['error'] ) );

		if ( $error_type === 'validation' ) {
			$errors = get_transient( 'wp_movie_collector_form_errors_' . get_current_user_id() );
			if ( $errors && is_array( $errors ) ) {
				echo '<div class="notice notice-error is-dismissible">';
				echo '<p><strong>' . esc_html__( 'Please fix the following errors:', 'wp-movie-collector' ) . '</strong></p>';
				echo '<ul>';
				foreach ( $errors as $error ) {
					echo '<li>' . esc_html( $error ) . '</li>';
				}
				echo '</ul>';
				echo '</div>';
				delete_transient( 'wp_movie_collector_form_errors_' . get_current_user_id() );
			}
		} else {
			$error_message = '';

			switch ( $error_type ) {
				case 'db_error':
					$error_message = __( 'There was an error updating the movie. Please try again.', 'wp-movie-collector' );
					break;
				default:
					$error_message = __( 'An unknown error occurred. Please try again.', 'wp-movie-collector' );
					break;
			}

			echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error_message ) . '</p></div>';
		}
	}
	?>

	<div class="wp-movie-collector-form">
		<h3><?php esc_html_e( 'Movie Details', 'wp-movie-collector' ); ?></h3>
		<form method="post" id="wp-movie-collector-edit-movie-form">
			<?php wp_nonce_field( 'wp_movie_collector_edit_movie', 'wp_movie_collector_nonce' ); ?>
			<input type="hidden" name="movie_id" value="<?php echo intval( $movie_id ); ?>">

			<?php
			// Movie detail fields, shared with the add-movie screen. $movie and
			// $db are already in scope above, so the partial renders pre-filled.
			include WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'admin/partials/movie-fields.php';
			?>

			<p class="submit">
				<button type="submit" class="button button-primary" name="wp_movie_collector_edit_movie_submit">
					<?php esc_html_e( 'Update Movie', 'wp-movie-collector' ); ?>
				</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-movies' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'wp-movie-collector' ); ?></a>
			</p>
		</form>
		<form method="post"
			action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
			style="display:inline;"
			onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this movie? This action cannot be undone.', 'wp-movie-collector' ) ); ?>');">
			<input type="hidden" name="action" value="wp_movie_collector_delete_movie">
			<input type="hidden" name="id" value="<?php echo intval( $movie_id ); ?>">
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-movies' ) ); ?>">
			<?php wp_nonce_field( 'wp_movie_collector_delete_movie_' . intval( $movie_id ), 'wp_movie_collector_nonce' ); ?>
			<button type="submit" class="button button-link-delete" style="color:#b32d2e;">
				<?php esc_html_e( 'Delete Movie', 'wp-movie-collector' ); ?>
			</button>
		</form>
	</div>
</div>
