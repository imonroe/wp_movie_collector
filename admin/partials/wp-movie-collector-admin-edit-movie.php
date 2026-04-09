<?php
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
			$errors = get_transient( 'wp_movie_collector_form_errors' );
			if ( $errors && is_array( $errors ) ) {
				echo '<div class="notice notice-error is-dismissible">';
				echo '<p><strong>' . esc_html__( 'Please fix the following errors:', 'wp-movie-collector' ) . '</strong></p>';
				echo '<ul>';
				foreach ( $errors as $error ) {
					echo '<li>' . esc_html( $error ) . '</li>';
				}
				echo '</ul>';
				echo '</div>';
				delete_transient( 'wp_movie_collector_form_errors' );
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

			<div class="form-group">
				<label for="movie-title"><?php esc_html_e( 'Title', 'wp-movie-collector' ); ?></label>
				<input type="text" id="movie-title" name="movie[title]" class="regular-text" required value="<?php echo esc_attr( $movie['title'] ); ?>">
			</div>

			<div class="form-group">
				<label for="movie-release-year"><?php esc_html_e( 'Release Year', 'wp-movie-collector' ); ?></label>
				<input type="number" id="movie-release-year" name="movie[release_year]" min="1900" max="<?php echo esc_attr( date( 'Y' ) ); ?>" class="small-text" required value="<?php echo esc_attr( $movie['release_year'] ); ?>">
			</div>

			<div class="form-group">
				<label for="movie-format"><?php esc_html_e( 'Format', 'wp-movie-collector' ); ?></label>
				<select id="movie-format" name="movie[format]" required>
					<option value=""><?php esc_html_e( 'Select Format', 'wp-movie-collector' ); ?></option>
					<?php
					$formats = array(
						'DVD'       => __( 'DVD', 'wp-movie-collector' ),
						'Blu-ray'   => __( 'Blu-ray', 'wp-movie-collector' ),
						'4K UHD'    => __( '4K Ultra HD', 'wp-movie-collector' ),
						'VHS'       => __( 'VHS', 'wp-movie-collector' ),
						'LaserDisc' => __( 'LaserDisc', 'wp-movie-collector' ),
					);
					foreach ( $formats as $value => $label ) {
						printf(
							'<option value="%s"%s>%s</option>',
							esc_attr( $value ),
							selected( $movie['format'], $value, false ),
							esc_html( $label )
						);
					}
					?>
				</select>
			</div>

			<div class="form-group">
				<label for="movie-region-code"><?php esc_html_e( 'Region Code', 'wp-movie-collector' ); ?></label>
				<select id="movie-region-code" name="movie[region_code]" required>
					<option value=""><?php esc_html_e( 'Select Region', 'wp-movie-collector' ); ?></option>
					<?php
					$regions = array(
						'R1'  => __( 'Region 1 (USA, Canada)', 'wp-movie-collector' ),
						'R2'  => __( 'Region 2 (Europe, Japan, Middle East)', 'wp-movie-collector' ),
						'R3'  => __( 'Region 3 (East Asia)', 'wp-movie-collector' ),
						'R4'  => __( 'Region 4 (Australia, New Zealand, Latin America)', 'wp-movie-collector' ),
						'R5'  => __( 'Region 5 (Africa, Asia, Russia)', 'wp-movie-collector' ),
						'R6'  => __( 'Region 6 (China)', 'wp-movie-collector' ),
						'R0'  => __( 'Region Free', 'wp-movie-collector' ),
						'A'   => __( 'Region A (Blu-ray: Americas, East Asia)', 'wp-movie-collector' ),
						'B'   => __( 'Region B (Blu-ray: Europe, Africa, Australia)', 'wp-movie-collector' ),
						'C'   => __( 'Region C (Blu-ray: Central/South Asia, Russia, China)', 'wp-movie-collector' ),
						'ABC' => __( 'Region Free (Blu-ray)', 'wp-movie-collector' ),
					);
					foreach ( $regions as $value => $label ) {
						printf(
							'<option value="%s"%s>%s</option>',
							esc_attr( $value ),
							selected( $movie['region_code'], $value, false ),
							esc_html( $label )
						);
					}
					?>
				</select>
			</div>

			<div class="form-group">
				<label for="movie-barcode"><?php esc_html_e( 'Barcode', 'wp-movie-collector' ); ?></label>
				<input type="text" id="movie-barcode" name="movie[barcode]" class="regular-text" value="<?php echo esc_attr( $movie['barcode'] ); ?>">
			</div>

			<div class="form-group">
				<label for="movie-director"><?php esc_html_e( 'Director', 'wp-movie-collector' ); ?></label>
				<input type="text" id="movie-director" name="movie[director]" class="regular-text" value="<?php echo esc_attr( $movie['director'] ); ?>">
			</div>

			<div class="form-group">
				<label for="movie-studio"><?php esc_html_e( 'Studio', 'wp-movie-collector' ); ?></label>
				<input type="text" id="movie-studio" name="movie[studio]" class="regular-text" value="<?php echo esc_attr( $movie['studio'] ); ?>">
			</div>

			<div class="form-group">
				<label for="movie-actors"><?php esc_html_e( 'Actors', 'wp-movie-collector' ); ?></label>
				<textarea id="movie-actors" name="movie[actors]"><?php echo esc_textarea( $movie['actors'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Enter actors separated by commas.', 'wp-movie-collector' ); ?></p>
			</div>

			<div class="form-group">
				<label for="movie-genre"><?php esc_html_e( 'Genre', 'wp-movie-collector' ); ?></label>
				<input type="text" id="movie-genre" name="movie[genre]" class="regular-text" value="<?php echo esc_attr( $movie['genre'] ); ?>">
				<p class="description"><?php esc_html_e( 'Enter genres separated by commas.', 'wp-movie-collector' ); ?></p>
			</div>

			<div class="form-group">
				<label for="movie-special-features"><?php esc_html_e( 'Special Features', 'wp-movie-collector' ); ?></label>
				<textarea id="movie-special-features" name="movie[special_features]"><?php echo esc_textarea( $movie['special_features'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Enter special features like director\'s commentary, deleted scenes, etc.', 'wp-movie-collector' ); ?></p>
			</div>

			<div class="form-group">
				<label for="movie-cover-image"><?php esc_html_e( 'Cover Image', 'wp-movie-collector' ); ?></label>
				<div class="wp-movie-collector-image-upload-container">
					<div class="image-preview">
						<?php if ( ! empty( $movie['cover_image_url'] ) ) : ?>
							<img src="<?php echo esc_url( $movie['cover_image_url'] ); ?>" alt="" style="max-width:150px;max-height:150px;">
						<?php endif; ?>
					</div>
					<input type="hidden" id="movie-cover-image-id" name="movie[cover_image_id]" class="image-id-field" value="<?php echo ! empty( $movie['cover_image_id'] ) ? intval( $movie['cover_image_id'] ) : ''; ?>">
					<input type="url" id="movie-cover-image-url" name="movie[cover_image_url]" class="regular-text image-url-field" placeholder="<?php esc_attr_e( 'Image URL or upload', 'wp-movie-collector' ); ?>" value="<?php echo esc_attr( $movie['cover_image_url'] ); ?>">
					<button type="button" class="button wp-movie-collector-upload-image-button"><?php esc_html_e( 'Upload Image', 'wp-movie-collector' ); ?></button>
					<button type="button" class="button wp-movie-collector-remove-image-button" <?php echo empty( $movie['cover_image_url'] ) ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Remove Image', 'wp-movie-collector' ); ?></button>
					<p class="description"><?php esc_html_e( 'Upload an image or enter a URL for the movie cover.', 'wp-movie-collector' ); ?></p>
				</div>
			</div>

			<div class="form-group">
				<label for="movie-description"><?php esc_html_e( 'Description', 'wp-movie-collector' ); ?></label>
				<textarea id="movie-description" name="movie[description]"><?php echo esc_textarea( $movie['description'] ); ?></textarea>
			</div>

			<div class="form-group">
				<label for="movie-acquisition-date"><?php esc_html_e( 'Acquisition Date', 'wp-movie-collector' ); ?></label>
				<input type="date" id="movie-acquisition-date" name="movie[acquisition_date]" class="regular-text" value="<?php echo esc_attr( ( ! empty( $movie['acquisition_date'] ) && $movie['acquisition_date'] !== '0000-00-00' ) ? $movie['acquisition_date'] : '' ); ?>">
				<p class="description"><?php esc_html_e( 'When did you acquire this movie?', 'wp-movie-collector' ); ?></p>
			</div>

			<div class="form-group">
				<label for="movie-box-set"><?php esc_html_e( 'Part of Box Set?', 'wp-movie-collector' ); ?></label>
				<select id="movie-box-set" name="movie[box_set_id]">
					<option value=""><?php esc_html_e( 'Not part of a box set', 'wp-movie-collector' ); ?></option>
					<?php
					global $wpdb;
					$box_sets = $wpdb->get_results( "SELECT id, title FROM {$db->get_box_sets_table()} ORDER BY title ASC", ARRAY_A );

					if ( $box_sets ) {
						foreach ( $box_sets as $box_set ) {
							printf(
								'<option value="%s"%s>%s</option>',
								esc_attr( $box_set['id'] ),
								selected( $movie['box_set_id'], $box_set['id'], false ),
								esc_html( $box_set['title'] )
							);
						}
					}
					?>
				</select>
			</div>

			<div class="form-group">
				<label for="movie-custom-notes"><?php esc_html_e( 'Custom Notes', 'wp-movie-collector' ); ?></label>
				<textarea id="movie-custom-notes" name="movie[custom_notes]"><?php echo esc_textarea( $movie['custom_notes'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Any personal notes about this movie.', 'wp-movie-collector' ); ?></p>
			</div>

			<input type="hidden" id="movie-api-source" name="movie[api_source]" value="<?php echo esc_attr( $movie['api_source'] ); ?>">

			<p class="submit">
				<button type="submit" class="button button-primary" name="wp_movie_collector_edit_movie_submit">
					<?php esc_html_e( 'Update Movie', 'wp-movie-collector' ); ?>
				</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-movies' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'wp-movie-collector' ); ?></a>
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=wp-movie-collector-movies&action=wp_movie_collector_delete_movie&id=' . intval( $movie_id ) ), 'wp_movie_collector_delete_movie_' . intval( $movie_id ), 'wp_movie_collector_nonce' ) ); ?>"
					class="button button-link-delete"
					onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this movie? This action cannot be undone.', 'wp-movie-collector' ) ); ?>');"
					style="color:#b32d2e;">
					<?php esc_html_e( 'Delete Movie', 'wp-movie-collector' ); ?>
				</a>
			</p>
		</form>
	</div>
</div>
