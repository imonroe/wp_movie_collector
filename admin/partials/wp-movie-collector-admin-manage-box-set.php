<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
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
				$message = __( 'Movie added to box set successfully!', 'wp-movie-collector' );
				break;
			case 'movie_removed':
				$message = __( 'Movie removed from box set successfully!', 'wp-movie-collector' );
				break;
			case 'movies_reordered':
				$message = __( 'Movies reordered successfully!', 'wp-movie-collector' );
				break;
			default:
				$message = __( 'Operation completed successfully.', 'wp-movie-collector' );
				break;
		}

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}

	// Show error message if there is one
	if ( isset( $_GET['error'] ) ) {
		$error_type    = sanitize_text_field( wp_unslash( $_GET['error'] ) );
		$error_message = '';

		switch ( $error_type ) {
			case 'invalid_box_set':
				$error_message = __( 'Invalid box set ID.', 'wp-movie-collector' );
				break;
			case 'invalid_movie':
				$error_message = __( 'Invalid movie ID.', 'wp-movie-collector' );
				break;
			case 'remove_failed':
				$error_message = __( 'Failed to remove movie from box set.', 'wp-movie-collector' );
				break;
			case 'reorder_failed':
				$error_message = __( 'Failed to reorder movies.', 'wp-movie-collector' );
				break;
			case 'no_movies_selected':
				$error_message = __( 'No movies were selected to add.', 'wp-movie-collector' );
				break;
			case 'no_movie_order':
				$error_message = __( 'No movie order data was provided.', 'wp-movie-collector' );
				break;
			default:
				$error_message = __( 'An unknown error occurred. Please try again.', 'wp-movie-collector' );
				break;
		}

		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $error_message ) . '</p></div>';
	}

	// Get box set ID from URL
	$box_set_id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;

	// Check if box set exists
	global $wpdb;
	$db      = new WP_Movie_Collector_DB();
	$box_set = $db->get_box_set( $box_set_id );

	if ( ! $box_set ) {
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Box set not found.', 'wp-movie-collector' ) . '</p></div>';
		echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=wp-movie-collector-box-sets' ) ) . '" class="button">' . esc_html__( 'Back to Box Sets', 'wp-movie-collector' ) . '</a></p>';
		return;
	}
	?>
	
	<div class="wp-movie-collector-box-set-details">
		<h2><?php echo esc_html( $box_set['title'] ); ?> (<?php echo esc_html( $box_set['release_year'] ); ?>)</h2>
		<p>
			<strong><?php esc_html_e( 'Format:', 'wp-movie-collector' ); ?></strong> <?php echo esc_html( $box_set['format'] ); ?> |
			<strong><?php esc_html_e( 'Region:', 'wp-movie-collector' ); ?></strong> <?php echo esc_html( $box_set['region_code'] ); ?>
		</p>
		
		<?php if ( ! empty( $box_set['description'] ) ) : ?>
		<div class="wp-movie-collector-description">
			<h3><?php esc_html_e( 'Description', 'wp-movie-collector' ); ?></h3>
			<p><?php echo esc_html( $box_set['description'] ); ?></p>
		</div>
		<?php endif; ?>
	</div>
	
	<div class="wp-movie-collector-tabs">
		<ul class="wp-movie-collector-tabs-nav" role="tablist">
			<li class="active" role="presentation"><a href="#current-movies" id="tab-current-movies" role="tab" aria-controls="current-movies" aria-selected="true"><?php esc_html_e( 'Current Movies', 'wp-movie-collector' ); ?></a></li>
			<li role="presentation"><a href="#add-movies" id="tab-add-movies" role="tab" aria-controls="add-movies" aria-selected="false" tabindex="-1"><?php esc_html_e( 'Add Movies', 'wp-movie-collector' ); ?></a></li>
		</ul>
		
		<div class="wp-movie-collector-tab-content">
			<!-- Current Movies Tab -->
			<div id="current-movies" class="wp-movie-collector-tab-pane active" role="tabpanel" aria-labelledby="tab-current-movies" tabindex="0">
				<h3><?php esc_html_e( 'Movies in this Box Set', 'wp-movie-collector' ); ?></h3>
				
				<?php
				// Get movies in this box set
				$movies = $db->get_movies_in_box_set( $box_set_id );

				if ( empty( $movies ) ) {
					echo '<p>' . esc_html__( 'No movies in this box set yet.', 'wp-movie-collector' ) . '</p>';
				} else {
					?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th scope="col" class="column-order"><?php esc_html_e( 'Order', 'wp-movie-collector' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Title', 'wp-movie-collector' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Release Year', 'wp-movie-collector' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Format', 'wp-movie-collector' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Actions', 'wp-movie-collector' ); ?></th>
						</tr>
					</thead>
					<tbody id="sortable-movies">
						<?php foreach ( $movies as $index => $movie ) : ?>
						<tr class="movie-item" data-movie-id="<?php echo esc_attr( $movie['id'] ); ?>">
							<td>
								<span class="dashicons dashicons-move" aria-hidden="true"></span>
								<span class="screen-reader-text">
									<?php
									/* translators: %s: movie title */
									printf( esc_html__( 'Drag to reorder %s', 'wp-movie-collector' ), esc_html( $movie['title'] ) );
									?>
								</span>
								<span class="wp-movie-collector-reorder-buttons">
									<button type="button" class="button button-small wp-movie-collector-move-up" aria-label="
										<?php
										/* translators: %s: movie title */
										printf( esc_attr__( 'Move %s up', 'wp-movie-collector' ), esc_attr( $movie['title'] ) );
										?>
									">&uarr;</button>
									<button type="button" class="button button-small wp-movie-collector-move-down" aria-label="
										<?php
										/* translators: %s: movie title */
										printf( esc_attr__( 'Move %s down', 'wp-movie-collector' ), esc_attr( $movie['title'] ) );
										?>
									">&darr;</button>
								</span>
							</td>
							<td><?php echo esc_html( $movie['title'] ); ?></td>
							<td><?php echo esc_html( $movie['release_year'] ); ?></td>
							<td><?php echo esc_html( $movie['format'] ); ?></td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
									<input type="hidden" name="action" value="wp_movie_collector_remove_movie">
									<input type="hidden" name="movie_id" value="<?php echo intval( $movie['id'] ); ?>">
									<input type="hidden" name="box_set_id" value="<?php echo intval( $box_set_id ); ?>">
									<?php wp_nonce_field( 'wp_movie_collector_remove_movie_' . intval( $movie['id'] ) . '_' . intval( $box_set_id ), 'wp_movie_collector_nonce' ); ?>
									<button type="submit"
										class="button button-small button-link-delete"
										onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to remove this movie from the box set?', 'wp-movie-collector' ) ); ?>');">
										<?php esc_html_e( 'Remove', 'wp-movie-collector' ); ?>
									</button>
								</form>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="wp-movie-collector-reorder-movies-form">
					<input type="hidden" name="action" value="wp_movie_collector_reorder_movies">
					<input type="hidden" name="box_set_id" value="<?php echo esc_attr( $box_set_id ); ?>">
					<?php wp_nonce_field( 'wp_movie_collector_reorder_movies', 'wp_movie_collector_nonce' ); ?>
					<div id="wp-movie-collector-reorder-inputs">
						<?php foreach ( $movies as $movie ) : ?>
						<input type="hidden" name="movie_order[]" value="<?php echo esc_attr( $movie['id'] ); ?>">
						<?php endforeach; ?>
					</div>
					<p class="submit">
						<button type="submit" class="button button-primary">
							<?php esc_html_e( 'Save Order', 'wp-movie-collector' ); ?>
						</button>
					</p>
				</form>
					<?php
				}
				?>
			</div>
			
			<!-- Add Movies Tab -->
			<div id="add-movies" class="wp-movie-collector-tab-pane" role="tabpanel" aria-labelledby="tab-add-movies" tabindex="0">
				<h3><?php esc_html_e( 'Add Movies to Box Set', 'wp-movie-collector' ); ?></h3>
				
				<div class="wp-movie-collector-search">
					<input type="text" id="wp-movie-collector-movie-search" class="regular-text" placeholder="<?php esc_attr_e( 'Search movies by title...', 'wp-movie-collector' ); ?>">
					<button type="button" id="wp-movie-collector-search-movies" class="button"><?php esc_html_e( 'Search', 'wp-movie-collector' ); ?></button>
				</div>
				
				<div id="wp-movie-collector-search-status" class="wp-movie-collector-search-status" role="status" aria-live="polite" aria-atomic="true"></div>
				<div id="wp-movie-collector-search-results">
					<!-- Search results will be displayed here -->
				</div>
				
				<div class="wp-movie-collector-movies-list">
					<h4><?php esc_html_e( 'Available Movies', 'wp-movie-collector' ); ?></h4>
					
					<?php
					$available_movies = $wpdb->get_results(
						$wpdb->prepare(
							"SELECT * FROM {$db->get_movies_table()} 
                            WHERE id NOT IN (
                                SELECT movie_id FROM {$db->get_relationships_table()} 
                                WHERE box_set_id = %d
                            )
                            ORDER BY title ASC
                            LIMIT 50",
							$box_set_id
						),
						ARRAY_A
					);

					if ( empty( $available_movies ) ) {
						echo '<p>' . esc_html__( 'No available movies found. Add some movies first!', 'wp-movie-collector' ) . '</p>';
					} else {
						?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="wp_movie_collector_add_movies_to_box_set">
						<input type="hidden" name="box_set_id" value="<?php echo esc_attr( $box_set_id ); ?>">
						<?php wp_nonce_field( 'wp_movie_collector_add_movies', 'wp_movie_collector_nonce' ); ?>
						
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th scope="col" class="column-select"><input type="checkbox" id="select-all-movies" aria-label="<?php esc_attr_e( 'Select all movies', 'wp-movie-collector' ); ?>"></th>
									<th scope="col"><?php esc_html_e( 'Title', 'wp-movie-collector' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Release Year', 'wp-movie-collector' ); ?></th>
									<th scope="col"><?php esc_html_e( 'Format', 'wp-movie-collector' ); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $available_movies as $movie ) : ?>
								<tr>
									<td><input type="checkbox" name="movie_ids[]" value="<?php echo esc_attr( $movie['id'] ); ?>"></td>
									<td><?php echo esc_html( $movie['title'] ); ?></td>
									<td><?php echo esc_html( $movie['release_year'] ); ?></td>
									<td><?php echo esc_html( $movie['format'] ); ?></td>
								</tr>
								<?php endforeach; ?>
							</tbody>
						</table>
						
						<p class="submit">
							<button type="submit" class="button button-primary">
								<?php esc_html_e( 'Add Selected Movies to Box Set', 'wp-movie-collector' ); ?>
							</button>
						</p>
					</form>
						<?php
					}
					?>
				</div>
			</div>
		</div>
	</div>
	
	<p>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-box-sets' ) ); ?>" class="button"><?php esc_html_e( 'Back to Box Sets', 'wp-movie-collector' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-movie-collector-edit-box-set&id=' . intval( $box_set_id ) ) ); ?>" class="button"><?php esc_html_e( 'Edit Box Set', 'wp-movie-collector' ); ?></a>
	</p>
</div>

<style>
.wp-movie-collector-tabs-nav {
	display: flex;
	margin: 20px 0 0 0;
	padding: 0;
	border-bottom: 1px solid #ccc;
}

.wp-movie-collector-tabs-nav li {
	margin: 0;
	padding: 0;
	list-style: none;
}

.wp-movie-collector-tabs-nav a {
	display: block;
	padding: 10px 15px;
	text-decoration: none;
	background: #f1f1f1;
	color: #444;
	margin-right: 5px;
	border: 1px solid #ccc;
	border-bottom: none;
}

.wp-movie-collector-tabs-nav li.active a {
	background: #fff;
	position: relative;
	border-bottom: 1px solid #fff;
	margin-bottom: -1px;
	font-weight: bold;
}

.wp-movie-collector-tab-content {
	background: #fff;
	border: 1px solid #ccc;
	border-top: none;
	padding: 20px;
	margin-bottom: 20px;
}

.wp-movie-collector-tab-pane {
	display: none;
}

.wp-movie-collector-tab-pane.active {
	display: block;
}

#sortable-movies .dashicons-move {
	cursor: move;
	color: #999;
}

.wp-movie-collector-search {
	margin-bottom: 20px;
}

#wp-movie-collector-search-results {
	margin-bottom: 20px;
}

.wp-movie-collector-search-status:not(:empty) {
	margin-bottom: 10px;
}

.wp-movie-collector-search-status .error {
	color: #b32d2e;
}
</style>

<script>
jQuery(document).ready(function($) {
	// Tab functionality (WAI-ARIA tabs pattern).
	var $tabs = $('.wp-movie-collector-tabs-nav a[role="tab"]');

	function activateTab($tab, setFocus) {
		// Reset all tabs/panes.
		$('.wp-movie-collector-tabs-nav li').removeClass('active');
		$('.wp-movie-collector-tab-pane').removeClass('active');
		$tabs.attr('aria-selected', 'false').attr('tabindex', '-1');

		// Activate the chosen tab and its pane.
		$tab.parent().addClass('active');
		$tab.attr('aria-selected', 'true').attr('tabindex', '0');
		$($tab.attr('href')).addClass('active');

		if (setFocus) {
			$tab.trigger('focus');
		}
	}

	$tabs.on('click', function(e) {
		e.preventDefault();
		activateTab($(this), false);
	});

	// Left/right (and home/end) arrow-key navigation between tabs.
	$tabs.on('keydown', function(e) {
		var index = $tabs.index(this);
		var newIndex = null;

		if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
			newIndex = (index + 1) % $tabs.length;
		} else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
			newIndex = (index - 1 + $tabs.length) % $tabs.length;
		} else if (e.key === 'Home') {
			newIndex = 0;
		} else if (e.key === 'End') {
			newIndex = $tabs.length - 1;
		}

		if (newIndex !== null) {
			e.preventDefault();
			activateTab($tabs.eq(newIndex), true);
		}
	});
	
	// Rebuild the hidden reorder inputs to match the current row order. Shared
	// by the mouse (jQuery UI sortable) and keyboard (up/down button) paths.
	function syncReorderInputs() {
		var $container = $('#wp-movie-collector-reorder-inputs');
		$container.empty();
		$('#sortable-movies tr').each(function() {
			$container.append('<input type="hidden" name="movie_order[]" value="' + parseInt($(this).data('movie-id'), 10) + '">');
		});
	}

	// Sortable functionality for reordering movies (mouse/pointer).
	if ($('#sortable-movies').length) {
		$('#sortable-movies').sortable({
			handle: '.dashicons-move',
			update: function() {
				syncReorderInputs();
			}
		});
	}

	// Keyboard-accessible reordering: Up/Down buttons move a row and keep the
	// hidden inputs in sync, so reordering works without a mouse.
	$('#sortable-movies').on('click', '.wp-movie-collector-move-up', function() {
		var $row  = $(this).closest('tr');
		var $prev = $row.prev('tr');
		if ($prev.length) {
			$row.insertBefore($prev);
			syncReorderInputs();
			$(this).trigger('focus');
		}
	});
	$('#sortable-movies').on('click', '.wp-movie-collector-move-down', function() {
		var $row  = $(this).closest('tr');
		var $next = $row.next('tr');
		if ($next.length) {
			$row.insertAfter($next);
			syncReorderInputs();
			$(this).trigger('focus');
		}
	});
	
	// Select all movies checkbox
	$('#select-all-movies').on('change', function() {
		$('input[name="movie_ids[]"]').prop('checked', $(this).prop('checked'));
	});
	
	// Movie search functionality
	$('#wp-movie-collector-search-movies').on('click', function() {
		var searchQuery = $('#wp-movie-collector-movie-search').val();
		if (!searchQuery) {
			return;
		}
		
		var boxSetId = <?php echo esc_js( $box_set_id ); ?>;
		
		$('#wp-movie-collector-search-status').text(<?php echo wp_json_encode( esc_html__( 'Searching...', 'wp-movie-collector' ) ); ?>);
		$('#wp-movie-collector-search-results').empty();

		$.ajax({
			url: wp_movie_collector_admin.ajax_url,
			type: 'POST',
			data: {
				action: 'wp_movie_collector_search_available_movies',
				box_set_id: boxSetId,
				query: searchQuery,
				nonce: wp_movie_collector_admin.nonce
			},
			success: function(response) {
				if (response.success && response.data.length > 0) {
					$('#wp-movie-collector-search-status').empty();
					var resultsHtml = '<h4>' + <?php echo wp_json_encode( esc_html__( 'Search Results', 'wp-movie-collector' ) ); ?> + '</h4>';
					resultsHtml += '<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">';
					resultsHtml += '<input type="hidden" name="action" value="wp_movie_collector_add_movies_to_box_set">';
					resultsHtml += '<input type="hidden" name="box_set_id" value="' + boxSetId + '">';
					resultsHtml += <?php echo wp_json_encode( wp_nonce_field( 'wp_movie_collector_add_movies', 'wp_movie_collector_nonce', true, false ) ); ?>;
					
					resultsHtml += '<table class="wp-list-table widefat fixed striped">';
					resultsHtml += '<thead><tr>';
					resultsHtml += '<th scope="col" class="column-select"><input type="checkbox" id="select-all-search-results" aria-label="' + <?php echo wp_json_encode( esc_attr__( 'Select all search results', 'wp-movie-collector' ) ); ?> + '"></th>';
					resultsHtml += '<th scope="col">' + <?php echo wp_json_encode( esc_html__( 'Title', 'wp-movie-collector' ) ); ?> + '</th>';
					resultsHtml += '<th scope="col">' + <?php echo wp_json_encode( esc_html__( 'Release Year', 'wp-movie-collector' ) ); ?> + '</th>';
					resultsHtml += '<th scope="col">' + <?php echo wp_json_encode( esc_html__( 'Format', 'wp-movie-collector' ) ); ?> + '</th>';
					resultsHtml += '</tr></thead><tbody>';
					
					$.each(response.data, function(index, movie) {
						resultsHtml += '<tr>';
						resultsHtml += '<td><input type="checkbox" name="movie_ids[]" value="' + parseInt(movie.id, 10) + '"></td>';
						resultsHtml += '<td>' + wpMovieCollectorEscHtml(movie.title) + '</td>';
						resultsHtml += '<td>' + wpMovieCollectorEscHtml(movie.release_year) + '</td>';
						resultsHtml += '<td>' + wpMovieCollectorEscHtml(movie.format) + '</td>';
						resultsHtml += '</tr>';
					});
					
					resultsHtml += '</tbody></table>';
					resultsHtml += '<p class="submit"><button type="submit" class="button button-primary">' + <?php echo wp_json_encode( esc_html__( 'Add Selected Movies to Box Set', 'wp-movie-collector' ) ); ?> + '</button></p>';
					resultsHtml += '</form>';
					
					$('#wp-movie-collector-search-results').html(resultsHtml);
					
					// Select all search results checkbox
					$('#select-all-search-results').on('change', function() {
						$(this).closest('form').find('input[name="movie_ids[]"]').prop('checked', $(this).prop('checked'));
					});
				} else if (response.success && response.data.length === 0) {
					$('#wp-movie-collector-search-results').empty();
					$('#wp-movie-collector-search-status').text(<?php echo wp_json_encode( esc_html__( 'No movies found matching your search.', 'wp-movie-collector' ) ); ?>);
				} else {
					var errorMsg = (response.data && typeof response.data === 'string') ? response.data : <?php echo wp_json_encode( esc_html__( 'An error occurred. Please try again.', 'wp-movie-collector' ) ); ?>;
					$('#wp-movie-collector-search-results').empty();
					$('#wp-movie-collector-search-status').html('<span class="error">' + $('<span>').text(errorMsg).html() + '</span>');
				}
			},
			error: function() {
				$('#wp-movie-collector-search-results').empty();
				$('#wp-movie-collector-search-status').html(<?php echo wp_json_encode( '<span class="error">' . esc_html__( 'Error searching for movies. Please try again.', 'wp-movie-collector' ) . '</span>' ); ?>);
			}
		});
	});
});
</script>