<?php
if ( ! current_user_can( 'manage_options' ) ) {
	wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'wp-movie-collector' ), '', array( 'response' => 403 ) );
}

// Get box set ID from URL.
$box_set_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

if ( ! $box_set_id ) {
	wp_die( esc_html__( 'Invalid box set ID.', 'wp-movie-collector' ), '', array( 'response' => 400 ) );
}

// Get box set data.
$db      = new WP_Movie_Collector_DB();
$box_set = $db->get_box_set( $box_set_id );

if ( ! $box_set ) {
	wp_die( esc_html__( 'Box set not found.', 'wp-movie-collector' ), '', array( 'response' => 404 ) );
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Edit Box Set', 'wp-movie-collector' ); ?></h1>

	<?php
	// Show error message if there is one.
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
					$error_message = __( 'There was an error updating the box set. Please try again.', 'wp-movie-collector' );
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
		<h3><?php esc_html_e( 'Box Set Details', 'wp-movie-collector' ); ?></h3>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="wp-movie-collector-edit-box-set-form">
			<input type="hidden" name="action" value="wp_movie_collector_update_box_set">
			<input type="hidden" name="box_set_id" value="<?php echo intval( $box_set_id ); ?>">
			<?php wp_nonce_field( 'wp_movie_collector_update_box_set', 'wp_movie_collector_nonce' ); ?>

			<div class="form-group">
				<label for="box-set-title"><?php esc_html_e( 'Title', 'wp-movie-collector' ); ?></label>
				<input type="text" id="box-set-title" name="box_set[title]" class="regular-text" required value="<?php echo esc_attr( $box_set['title'] ); ?>">
			</div>

			<div class="form-group">
				<label for="box-set-release-year"><?php esc_html_e( 'Release Year', 'wp-movie-collector' ); ?></label>
				<input type="number" id="box-set-release-year" name="box_set[release_year]" min="1900" max="<?php echo esc_attr( date( 'Y' ) ); ?>" class="small-text" required value="<?php echo esc_attr( $box_set['release_year'] ); ?>">
			</div>

			<div class="form-group">
				<label for="box-set-format"><?php esc_html_e( 'Format', 'wp-movie-collector' ); ?></label>
				<select id="box-set-format" name="box_set[format]" required>
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
							selected( $box_set['format'], $value, false ),
							esc_html( $label )
						);
					}
					?>
				</select>
			</div>

			<div class="form-group">
				<label for="box-set-region-code"><?php esc_html_e( 'Region Code', 'wp-movie-collector' ); ?></label>
				<select id="box-set-region-code" name="box_set[region_code]" required>
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
							selected( $box_set['region_code'], $value, false ),
							esc_html( $label )
						);
					}
					?>
				</select>
			</div>

			<div class="form-group">
				<label for="box-set-barcode"><?php esc_html_e( 'Barcode', 'wp-movie-collector' ); ?></label>
				<input type="text" id="box-set-barcode" name="box_set[barcode]" class="regular-text" value="<?php echo esc_attr( $box_set['barcode'] ); ?>">
			</div>

			<div class="form-group">
				<label for="box-set-cover-image"><?php esc_html_e( 'Cover Image', 'wp-movie-collector' ); ?></label>
				<div class="wp-movie-collector-image-upload-container">
					<div class="image-preview">
						<?php if ( ! empty( $box_set['cover_image_url'] ) ) : ?>
							<img src="<?php echo esc_url( $box_set['cover_image_url'] ); ?>" alt="" style="max-width:150px;max-height:150px;">
						<?php endif; ?>
					</div>
					<input type="hidden" id="box-set-cover-image-id" name="box_set[cover_image_id]" class="image-id-field" value="<?php echo ! empty( $box_set['cover_image_id'] ) ? intval( $box_set['cover_image_id'] ) : ''; ?>">
					<input type="url" id="box-set-cover-image-url" name="box_set[cover_image_url]" class="regular-text image-url-field" placeholder="<?php esc_attr_e( 'Image URL or upload', 'wp-movie-collector' ); ?>" value="<?php echo esc_attr( $box_set['cover_image_url'] ); ?>">
					<button type="button" class="button wp-movie-collector-upload-image-button"><?php esc_html_e( 'Upload Image', 'wp-movie-collector' ); ?></button>
					<button type="button" class="button wp-movie-collector-remove-image-button" <?php echo empty( $box_set['cover_image_url'] ) ? 'style="display:none;"' : ''; ?>><?php esc_html_e( 'Remove Image', 'wp-movie-collector' ); ?></button>
					<p class="description"><?php esc_html_e( 'Upload an image or enter a URL for the box set cover.', 'wp-movie-collector' ); ?></p>
				</div>
			</div>

			<div class="form-group">
				<label for="box-set-description"><?php esc_html_e( 'Description', 'wp-movie-collector' ); ?></label>
				<textarea id="box-set-description" name="box_set[description]"><?php echo esc_textarea( $box_set['description'] ); ?></textarea>
			</div>

			<div class="form-group">
				<label for="box-set-acquisition-date"><?php esc_html_e( 'Acquisition Date', 'wp-movie-collector' ); ?></label>
				<input type="date" id="box-set-acquisition-date" name="box_set[acquisition_date]" class="regular-text" value="<?php echo esc_attr( ( ! empty( $box_set['acquisition_date'] ) && $box_set['acquisition_date'] !== '0000-00-00' ) ? $box_set['acquisition_date'] : '' ); ?>">
				<p class="description"><?php esc_html_e( 'When did you acquire this box set?', 'wp-movie-collector' ); ?></p>
			</div>

			<div class="form-group">
				<label for="box-set-special-features"><?php esc_html_e( 'Special Features', 'wp-movie-collector' ); ?></label>
				<textarea id="box-set-special-features" name="box_set[special_features]"><?php echo esc_textarea( $box_set['special_features'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Enter special features included in this box set.', 'wp-movie-collector' ); ?></p>
			</div>

			<div class="form-group">
				<label for="box-set-custom-notes"><?php esc_html_e( 'Custom Notes', 'wp-movie-collector' ); ?></label>
				<textarea id="box-set-custom-notes" name="box_set[custom_notes]"><?php echo esc_textarea( $box_set['custom_notes'] ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Any personal notes about this box set.', 'wp-movie-collector' ); ?></p>
			</div>

			<input type="hidden" id="box-set-api-source" name="box_set[api_source]" value="<?php echo esc_attr( $box_set['api_source'] ); ?>">

			<p class="submit">
				<button type="submit" class="button button-primary">
					<?php esc_html_e( 'Update Box Set', 'wp-movie-collector' ); ?>
				</button>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-box-sets' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'wp-movie-collector' ); ?></a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-manage-box-set&id=' . intval( $box_set_id ) ) ); ?>" class="button"><?php esc_html_e( 'Manage Movies', 'wp-movie-collector' ); ?></a>
			</p>
		</form>
		<form method="post"
			action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
			style="display:inline;"
			onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this box set? This action cannot be undone.', 'wp-movie-collector' ) ); ?>');">
			<input type="hidden" name="action" value="wp_movie_collector_delete_box_set">
			<input type="hidden" name="id" value="<?php echo intval( $box_set_id ); ?>">
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-box-sets' ) ); ?>">
			<?php wp_nonce_field( 'wp_movie_collector_delete_box_set_' . intval( $box_set_id ), 'wp_movie_collector_nonce' ); ?>
			<button type="submit" class="button button-link-delete" style="color:#b32d2e;">
				<?php esc_html_e( 'Delete Box Set', 'wp-movie-collector' ); ?>
			</button>
		</form>
	</div>
</div>
