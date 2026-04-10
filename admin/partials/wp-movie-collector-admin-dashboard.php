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
		<?php
		global $wpdb;
		$db    = new WP_Movie_Collector_DB();
		$stats = $db->get_collection_stats();
		?>

		<div class="wp-movie-collector-stats">
			<div class="wp-movie-collector-stat-box">
				<h2><?php echo intval( $stats['total_movies'] ); ?></h2>
				<p><?php esc_html_e( 'Movies', 'wp-movie-collector' ); ?></p>
			</div>

			<div class="wp-movie-collector-stat-box">
				<h2><?php echo intval( $stats['total_box_sets'] ); ?></h2>
				<p><?php esc_html_e( 'Box Sets', 'wp-movie-collector' ); ?></p>
			</div>

			<div class="wp-movie-collector-stat-box">
				<h2><?php echo intval( $stats['unique_directors'] ); ?></h2>
				<p><?php esc_html_e( 'Directors', 'wp-movie-collector' ); ?></p>
			</div>

			<div class="wp-movie-collector-stat-box">
				<h2><?php echo intval( $stats['unique_studios'] ); ?></h2>
				<p><?php esc_html_e( 'Studios', 'wp-movie-collector' ); ?></p>
			</div>

			<?php if ( $stats['recent_count'] > 0 ) : ?>
			<div class="wp-movie-collector-stat-box wp-movie-collector-stat-highlight">
				<h2><?php echo intval( $stats['recent_count'] ); ?></h2>
				<p><?php esc_html_e( 'Added (30 days)', 'wp-movie-collector' ); ?></p>
			</div>
			<?php endif; ?>
		</div>

		<div class="wp-movie-collector-dashboard-row">
			<div class="wp-movie-collector-widget">
				<h3><?php esc_html_e( 'Format Breakdown', 'wp-movie-collector' ); ?></h3>
				<?php if ( ! empty( $stats['format_breakdown'] ) ) : ?>
				<table class="wp-movie-collector-stats-table">
					<tbody>
						<?php foreach ( $stats['format_breakdown'] as $format => $count ) : ?>
						<tr>
							<td><?php echo esc_html( $format ); ?></td>
							<td class="wp-movie-collector-stats-count"><?php echo intval( $count ); ?></td>
							<td class="wp-movie-collector-stats-bar-cell">
								<div class="wp-movie-collector-stats-bar" style="width: <?php echo esc_attr( $stats['total_movies'] > 0 ? round( ( $count / $stats['total_movies'] ) * 100 ) : 0 ); ?>%;"></div>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php else : ?>
				<p class="wp-movie-collector-empty-state"><?php esc_html_e( 'No format data available yet.', 'wp-movie-collector' ); ?></p>
				<?php endif; ?>
			</div>

			<div class="wp-movie-collector-widget">
				<h3><?php esc_html_e( 'Top Genres', 'wp-movie-collector' ); ?></h3>
				<?php if ( ! empty( $stats['top_genres'] ) ) : ?>
				<table class="wp-movie-collector-stats-table">
					<tbody>
						<?php
						$max_genre_count = max( $stats['top_genres'] );
						foreach ( $stats['top_genres'] as $genre => $count ) :
							?>
						<tr>
							<td><?php echo esc_html( $genre ); ?></td>
							<td class="wp-movie-collector-stats-count"><?php echo intval( $count ); ?></td>
							<td class="wp-movie-collector-stats-bar-cell">
								<div class="wp-movie-collector-stats-bar" style="width: <?php echo esc_attr( round( ( $count / $max_genre_count ) * 100 ) ); ?>%;"></div>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php else : ?>
				<p class="wp-movie-collector-empty-state"><?php esc_html_e( 'No genre data available.', 'wp-movie-collector' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<div class="wp-movie-collector-dashboard-row">
			<div class="wp-movie-collector-widget">
				<h3><?php esc_html_e( 'Top Directors', 'wp-movie-collector' ); ?></h3>
				<?php if ( ! empty( $stats['top_directors'] ) ) : ?>
				<table class="wp-movie-collector-stats-table">
					<tbody>
						<?php
						$max_director_count = max( $stats['top_directors'] );
						foreach ( $stats['top_directors'] as $director => $count ) :
							?>
						<tr>
							<td><?php echo esc_html( $director ); ?></td>
							<td class="wp-movie-collector-stats-count"><?php echo intval( $count ); ?></td>
							<td class="wp-movie-collector-stats-bar-cell">
								<div class="wp-movie-collector-stats-bar" style="width: <?php echo esc_attr( round( ( $count / $max_director_count ) * 100 ) ); ?>%;"></div>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php else : ?>
				<p class="wp-movie-collector-empty-state"><?php esc_html_e( 'No director data available.', 'wp-movie-collector' ); ?></p>
				<?php endif; ?>
			</div>

			<div class="wp-movie-collector-widget">
				<h3><?php esc_html_e( 'Top Studios', 'wp-movie-collector' ); ?></h3>
				<?php if ( ! empty( $stats['top_studios'] ) ) : ?>
				<table class="wp-movie-collector-stats-table">
					<tbody>
						<?php
						$max_studio_count = max( $stats['top_studios'] );
						foreach ( $stats['top_studios'] as $studio => $count ) :
							?>
						<tr>
							<td><?php echo esc_html( $studio ); ?></td>
							<td class="wp-movie-collector-stats-count"><?php echo intval( $count ); ?></td>
							<td class="wp-movie-collector-stats-bar-cell">
								<div class="wp-movie-collector-stats-bar" style="width: <?php echo esc_attr( round( ( $count / $max_studio_count ) * 100 ) ); ?>%;"></div>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php else : ?>
				<p class="wp-movie-collector-empty-state"><?php esc_html_e( 'No studio data available.', 'wp-movie-collector' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<div class="wp-movie-collector-dashboard-row">
			<div class="wp-movie-collector-widget">
				<h3><?php esc_html_e( 'Quick Actions', 'wp-movie-collector' ); ?></h3>
				<div class="wp-movie-collector-quick-actions">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-add-movie' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Add New Movie', 'wp-movie-collector' ); ?></a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-add-box-set' ) ); ?>" class="button button-primary"><?php esc_html_e( 'Add New Box Set', 'wp-movie-collector' ); ?></a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-movies' ) ); ?>" class="button"><?php esc_html_e( 'View All Movies', 'wp-movie-collector' ); ?></a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-import-export' ) ); ?>" class="button"><?php esc_html_e( 'Import / Export', 'wp-movie-collector' ); ?></a>
				</div>

				<h4><?php esc_html_e( 'Quick Scan', 'wp-movie-collector' ); ?></h4>
				<div class="wp-movie-collector-barcode-input">
					<label for="wp-movie-collector-dashboard-barcode" class="screen-reader-text"><?php esc_html_e( 'Barcode', 'wp-movie-collector' ); ?></label>
					<input type="text" id="wp-movie-collector-dashboard-barcode" class="regular-text" placeholder="<?php esc_attr_e( 'Scan or enter barcode...', 'wp-movie-collector' ); ?>">
					<button type="button" id="wp-movie-collector-dashboard-lookup" class="button"><?php esc_html_e( 'Lookup', 'wp-movie-collector' ); ?></button>
				</div>
				<div id="wp-movie-collector-dashboard-barcode-result" role="status" aria-live="polite" aria-atomic="true"></div>
			</div>

			<div class="wp-movie-collector-widget">
				<h3><?php esc_html_e( 'Recently Added', 'wp-movie-collector' ); ?></h3>
				<?php
				$recent_movies = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT id, title, format, release_year FROM {$db->get_movies_table()} ORDER BY created_at DESC LIMIT %d",
						5
					),
					ARRAY_A
				);

				if ( $recent_movies ) :
					?>
				<ul class="wp-movie-collector-recent-list">
					<?php foreach ( $recent_movies as $movie ) : ?>
					<li>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-edit-movie&id=' . intval( $movie['id'] ) ) ); ?>">
							<?php echo esc_html( $movie['title'] ); ?>
							<span class="wp-movie-collector-meta">(<?php echo esc_html( $movie['release_year'] ); ?>) &mdash; <?php echo esc_html( $movie['format'] ); ?></span>
						</a>
						<div class="row-actions">
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-edit-movie&id=' . intval( $movie['id'] ) ) ); ?>"><?php esc_html_e( 'Edit', 'wp-movie-collector' ); ?></a> |
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
								<input type="hidden" name="action" value="wp_movie_collector_delete_movie">
								<input type="hidden" name="id" value="<?php echo intval( $movie['id'] ); ?>">
								<input type="hidden" name="redirect_to" value="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-dashboard' ) ); ?>">
								<?php wp_nonce_field( 'wp_movie_collector_delete_movie_' . intval( $movie['id'] ), 'wp_movie_collector_nonce' ); ?>
								<button type="submit"
										onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this movie? This action cannot be undone.', 'wp-movie-collector' ) ); ?>');"
										class="wp-movie-collector-delete-btn"><?php esc_html_e( 'Delete', 'wp-movie-collector' ); ?></button>
							</form>
						</div>
					</li>
					<?php endforeach; ?>
				</ul>
				<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-movies' ) ); ?>"><?php esc_html_e( 'View All Movies', 'wp-movie-collector' ); ?> &rarr;</a></p>
				<?php else : ?>
				<p class="wp-movie-collector-empty-state"><?php esc_html_e( 'No movies yet. Why not add one?', 'wp-movie-collector' ); ?></p>
				<?php endif; ?>
			</div>
		</div>

		<div class="wp-movie-collector-dashboard-row">
			<div class="wp-movie-collector-widget">
				<h3><?php esc_html_e( 'Search Collection', 'wp-movie-collector' ); ?></h3>
				<form method="get">
					<input type="hidden" name="page" value="wp-movie-collector-movies">
					<div class="wp-movie-collector-search-input">
						<input type="text" name="s" placeholder="<?php esc_attr_e( 'Search by title, director, actor...', 'wp-movie-collector' ); ?>" class="regular-text">
						<button type="submit" class="button"><?php esc_html_e( 'Search', 'wp-movie-collector' ); ?></button>
					</div>
				</form>
			</div>

			<div class="wp-movie-collector-widget">
				<h3><?php esc_html_e( 'API Configuration', 'wp-movie-collector' ); ?></h3>
				<?php
				$tmdb_key    = get_option( 'wp_movie_collector_tmdb_api_key' );
				$omdb_key    = get_option( 'wp_movie_collector_omdb_api_key' );
				$barcode_key = get_option( 'wp_movie_collector_barcode_api_key' );
				?>
				<table class="wp-movie-collector-api-status">
					<tbody>
						<tr>
							<td><?php esc_html_e( 'TMDb API', 'wp-movie-collector' ); ?></td>
							<td>
								<?php if ( $tmdb_key ) : ?>
									<span class="wp-movie-collector-status-configured"><?php esc_html_e( 'Configured', 'wp-movie-collector' ); ?></span>
								<?php else : ?>
									<span class="wp-movie-collector-status-missing"><?php esc_html_e( 'Not configured', 'wp-movie-collector' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'OMDb API', 'wp-movie-collector' ); ?></td>
							<td>
								<?php if ( $omdb_key ) : ?>
									<span class="wp-movie-collector-status-configured"><?php esc_html_e( 'Configured', 'wp-movie-collector' ); ?></span>
								<?php else : ?>
									<span class="wp-movie-collector-status-missing"><?php esc_html_e( 'Not configured', 'wp-movie-collector' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Barcode Lookup', 'wp-movie-collector' ); ?></td>
							<td>
								<?php if ( $barcode_key ) : ?>
									<span class="wp-movie-collector-status-configured"><?php esc_html_e( 'Configured', 'wp-movie-collector' ); ?></span>
								<?php else : ?>
									<span class="wp-movie-collector-status-missing"><?php esc_html_e( 'Not configured', 'wp-movie-collector' ); ?></span>
								<?php endif; ?>
							</td>
						</tr>
					</tbody>
				</table>
				<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-settings' ) ); ?>"><?php esc_html_e( 'Manage API Settings', 'wp-movie-collector' ); ?> &rarr;</a></p>
			</div>
		</div>

		<?php if ( $stats['earliest_year'] && ( $stats['total_movies'] > 0 || $stats['total_box_sets'] > 0 ) ) : ?>
		<div class="wp-movie-collector-dashboard-row">
			<div class="wp-movie-collector-widget wp-movie-collector-widget-full">
				<h3><?php esc_html_e( 'Collection Summary', 'wp-movie-collector' ); ?></h3>
				<p>
				<?php
				printf(
					/* translators: 1: total movies, 2: total box sets, 3: earliest year, 4: latest year */
					esc_html__( 'Your collection contains %1$d movies and %2$d box sets, spanning release years %3$s to %4$s.', 'wp-movie-collector' ),
					intval( $stats['total_movies'] ),
					intval( $stats['total_box_sets'] ),
					esc_html( $stats['earliest_year'] ),
					esc_html( $stats['latest_year'] )
				);
				?>
				</p>
			</div>
		</div>
		<?php endif; ?>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	$('#wp-movie-collector-dashboard-lookup').on('click', function() {
		var barcode = $('#wp-movie-collector-dashboard-barcode').val();
		if (!barcode) {
			return;
		}

		$('#wp-movie-collector-dashboard-barcode-result').html(<?php echo wp_json_encode( '<p>' . esc_html__( 'Looking up barcode...', 'wp-movie-collector' ) . '</p>' ); ?>);

		$.ajax({
			url: wp_movie_collector_admin.ajax_url,
			type: 'POST',
			data: {
				action: 'wp_movie_collector_barcode_lookup',
				barcode: barcode,
				context: 'movie',
				nonce: wp_movie_collector_admin.nonce
			},
			success: function(response) {
				if (response.success) {
					if (response.data.existing_in_db) {
						var editUrl = response.data.edit_url;
						var label = (response.data.existing_type === 'box_set')
							? <?php echo wp_json_encode( esc_html__( 'Edit Existing Box Set', 'wp-movie-collector' ) ); ?>
							: <?php echo wp_json_encode( esc_html__( 'Edit Existing Movie', 'wp-movie-collector' ) ); ?>;
						$('#wp-movie-collector-dashboard-barcode-result').html(
							'<div class="notice notice-info inline"><p>' +
							<?php echo wp_json_encode( esc_html__( 'Found in your collection!', 'wp-movie-collector' ) ); ?> +
							' <a href="' + wpMovieCollectorEscHtml(editUrl) + '" class="button button-small">' +
							label + '</a></p></div>'
						);
					} else {
						var addUrl = <?php echo wp_json_encode( admin_url( 'admin.php?page=wp-movie-collector-add-movie&barcode=' ) ); ?> + encodeURIComponent(barcode);
						$('#wp-movie-collector-dashboard-barcode-result').html(
							'<div class="notice notice-success inline"><p>' +
							<?php echo wp_json_encode( esc_html__( 'Barcode found! ', 'wp-movie-collector' ) ); ?> +
							'<a href="' + wpMovieCollectorEscHtml(addUrl) + '" class="button button-small button-primary">' +
							<?php echo wp_json_encode( esc_html__( 'Add as New Movie', 'wp-movie-collector' ) ); ?> +
							'</a></p></div>'
						);
					}
				} else {
					$('#wp-movie-collector-dashboard-barcode-result').html(
						'<div class="notice notice-warning inline"><p></p></div>'
					).find('p').text(response.data);
				}
			},
			error: function() {
				$('#wp-movie-collector-dashboard-barcode-result').html(<?php echo wp_json_encode( '<div class="notice notice-error inline"><p>' . esc_html__( 'Error looking up barcode. Please try again.', 'wp-movie-collector' ) . '</p></div>' ); ?>);
			}
		});
	});

	$('#wp-movie-collector-dashboard-barcode').on('keypress', function(e) {
		if (e.which === 13) {
			e.preventDefault();
			$('#wp-movie-collector-dashboard-lookup').click();
		}
	});
});
</script>
