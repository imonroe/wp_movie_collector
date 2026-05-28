<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    WP_Movie_Collector
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WP_Movie_Collector_Admin {

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		$css_url = WP_MOVIE_COLLECTOR_PLUGIN_URL . 'admin/css/wp-movie-collector-admin.css';
		if ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
			$built_path = WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'dist/admin/css/wp-movie-collector-admin.min.css';
			if ( file_exists( $built_path ) ) {
				$css_url = WP_MOVIE_COLLECTOR_PLUGIN_URL . 'dist/admin/css/wp-movie-collector-admin.min.css';
			}
		}
		wp_enqueue_style( 'wp-movie-collector-admin', $css_url, array(), WP_MOVIE_COLLECTOR_VERSION, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		// WordPress media uploader
		wp_enqueue_media();

		// jQuery UI for sortable
		wp_enqueue_script( 'jquery-ui-sortable' );

		// Custom admin script
		$js_url = WP_MOVIE_COLLECTOR_PLUGIN_URL . 'admin/js/wp-movie-collector-admin.js';
		if ( ! ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ) {
			$built_path = WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'dist/admin/js/wp-movie-collector-admin.min.js';
			if ( file_exists( $built_path ) ) {
				$js_url = WP_MOVIE_COLLECTOR_PLUGIN_URL . 'dist/admin/js/wp-movie-collector-admin.min.js';
			}
		}
		wp_enqueue_script( 'wp-movie-collector-admin', $js_url, array( 'jquery', 'jquery-ui-sortable' ), WP_MOVIE_COLLECTOR_VERSION, false );

		// Localize the script with new data
		$localize_data = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'wp_movie_collector_nonce' ),
			'messages' => array(
				'select_image'           => __( 'Select or Upload Cover Image', 'wp-movie-collector' ),
				'use_image'              => __( 'Use this image', 'wp-movie-collector' ),
				'required_fields'        => __( 'Please fill out all required fields.', 'wp-movie-collector' ),
				'confirm_delete_movie'   => __( 'Are you sure you want to delete this movie?', 'wp-movie-collector' ),
				'confirm_delete_box_set' => __( 'Are you sure you want to delete this box set?', 'wp-movie-collector' ),
				'no_file_selected'       => __( 'Please select a file to import.', 'wp-movie-collector' ),
				'invalid_file_type'      => __( 'Please select a CSV or JSON file.', 'wp-movie-collector' ),
			),
		);
		wp_localize_script( 'wp-movie-collector-admin', 'wp_movie_collector_admin', $localize_data );

		// Add shared escHtml helper for safe HTML insertion (text and attribute
		// contexts). Encodes &, <, >, " and ' so a value concatenated into a
		// quoted attribute can't break out. Fallback for the unbuilt source
		// path; the webpack bundle exposes the same implementation.
		wp_add_inline_script( 'wp-movie-collector-admin', 'function wpMovieCollectorEscHtml(s){return String(s).replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/"/g,"&quot;").replace(/\'/g,"&#039;");}', 'before' );

		// Add shared image-preview helper that builds the <img> via DOM APIs
		// and only accepts http(s) URLs, so untrusted cover_image_url values
		// (from external APIs / imports) can't inject markup or javascript: URIs.
		wp_add_inline_script( 'wp-movie-collector-admin', 'function wpMovieCollectorSetImagePreview($preview,url){$preview.empty();if(typeof url==="string"&&/^https?:\/\//i.test(url)){jQuery("<img>",{alt:""}).attr("src",url).css({"max-width":"150px","max-height":"150px"}).appendTo($preview);}}', 'before' );
	}

	/**
	 * Add plugin admin menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		// Main menu item (Dashboard)
		add_menu_page(
			__( 'Movie Collection', 'wp-movie-collector' ),
			__( 'Movies', 'wp-movie-collector' ),
			'manage_options',
			'wp-movie-collector-dashboard', // Change this to point to dashboard explicitly
			array( $this, 'display_plugin_admin_dashboard' ),
			'dashicons-video-alt2',
			20
		);

		// Rename default submenu item to "Dashboard"
		global $submenu;
		if ( isset( $submenu['wp-movie-collector-dashboard'] ) ) {
			$submenu['wp-movie-collector-dashboard'][0][0] = __( 'Dashboard', 'wp-movie-collector' );
		}

		// All Movies submenu
		add_submenu_page(
			'wp-movie-collector-dashboard',
			__( 'All Movies', 'wp-movie-collector' ),
			__( 'All Movies', 'wp-movie-collector' ),
			'manage_options',
			'wp-movie-collector-movies',
			array( $this, 'display_plugin_admin_movies' )
		);

		// Add New Movie submenu
		add_submenu_page(
			'wp-movie-collector-dashboard',
			__( 'Add New Movie', 'wp-movie-collector' ),
			__( 'Add New Movie', 'wp-movie-collector' ),
			'manage_options',
			'wp-movie-collector-add-movie',
			array( $this, 'display_plugin_admin_add_movie' )
		);

		// Add New Box Set submenu
		add_submenu_page(
			'wp-movie-collector-dashboard',
			__( 'Add New Box Set', 'wp-movie-collector' ),
			__( 'Add New Box Set', 'wp-movie-collector' ),
			'manage_options',
			'wp-movie-collector-add-box-set',
			array( $this, 'display_plugin_admin_add_box_set' )
		);

		// Import/Export submenu
		add_submenu_page(
			'wp-movie-collector-dashboard',
			__( 'Import/Export', 'wp-movie-collector' ),
			__( 'Import/Export', 'wp-movie-collector' ),
			'manage_options',
			'wp-movie-collector-import-export',
			array( $this, 'display_plugin_admin_import_export' )
		);

		// Settings submenu
		add_submenu_page(
			'wp-movie-collector-dashboard',
			__( 'Settings', 'wp-movie-collector' ),
			__( 'Settings', 'wp-movie-collector' ),
			'manage_options',
			'wp-movie-collector-settings',
			array( $this, 'display_plugin_admin_settings' )
		);

		// Hidden submenu page for editing a movie (accessible via URL but not shown in menu)
		add_submenu_page(
			null,
			__( 'Edit Movie', 'wp-movie-collector' ),
			__( 'Edit Movie', 'wp-movie-collector' ),
			'manage_options',
			'wp-movie-collector-edit-movie',
			array( $this, 'display_plugin_admin_edit_movie' )
		);

		// All Box Sets submenu
		add_submenu_page(
			'wp-movie-collector-dashboard',
			__( 'All Box Sets', 'wp-movie-collector' ),
			__( 'All Box Sets', 'wp-movie-collector' ),
			'manage_options',
			'wp-movie-collector-box-sets',
			array( $this, 'display_plugin_admin_box_sets' )
		);

		// Hidden submenu page for editing a box set
		add_submenu_page(
			null,
			__( 'Edit Box Set', 'wp-movie-collector' ),
			__( 'Edit Box Set', 'wp-movie-collector' ),
			'manage_options',
			'wp-movie-collector-edit-box-set',
			array( $this, 'display_plugin_admin_edit_box_set' )
		);

		// Hidden submenu page for managing movies in a box set
		add_submenu_page(
			null,
			__( 'Manage Box Set', 'wp-movie-collector' ),
			__( 'Manage Box Set', 'wp-movie-collector' ),
			'manage_options',
			'wp-movie-collector-manage-box-set',
			array( $this, 'display_plugin_admin_manage_box_set' )
		);

		// Remove "All Box Sets" if it still appears
		add_action(
			'admin_menu',
			function () {
				remove_submenu_page( 'wp-movie-collector-dashboard', 'edit.php?post_type=box_set' );
			},
			99
		);
	}



	/**
	 * Add contextual help tabs to the plugin's admin pages.
	 *
	 * Hooked to current_screen. Adds a help tab (and a shared sidebar with
	 * documentation links) to each WP Movie Collector admin screen.
	 *
	 * @since    1.3.0
	 * @param    WP_Screen $screen The current admin screen.
	 */
	public function add_help_tabs( $screen ) {
		if ( ! $screen instanceof WP_Screen || false === strpos( $screen->id, 'wp-movie-collector' ) ) {
			return;
		}

		$help = array();

		if ( false !== strpos( $screen->id, 'dashboard' ) ) {
			$help[] = array(
				'id'      => 'wpmc-dashboard-overview',
				'title'   => __( 'Overview', 'wp-movie-collector' ),
				'content' => '<p>' . esc_html__( 'The dashboard summarizes your collection: total movies and box sets, format breakdown, top genres, directors, and studios, and recent additions. Use the menu on the left to add movies, manage box sets, import/export your data, and configure API keys.', 'wp-movie-collector' ) . '</p>',
			);
		} elseif ( false !== strpos( $screen->id, 'add-movie' ) || false !== strpos( $screen->id, 'edit-movie' ) ) {
			$help[] = array(
				'id'      => 'wpmc-add-movie',
				'title'   => __( 'Adding a Movie', 'wp-movie-collector' ),
				'content' => '<p>' . esc_html__( 'Enter the movie details manually, or scan/enter a barcode and use the lookup button to fetch metadata automatically. A barcode scanner that behaves as a keyboard will type the barcode into the field. Title, year, format, and region are required.', 'wp-movie-collector' ) . '</p>',
			);
		} elseif ( false !== strpos( $screen->id, 'add-box-set' ) || false !== strpos( $screen->id, 'edit-box-set' ) || false !== strpos( $screen->id, 'manage-box-set' ) ) {
			$help[] = array(
				'id'      => 'wpmc-box-sets',
				'title'   => __( 'Box Sets', 'wp-movie-collector' ),
				'content' => '<p>' . esc_html__( 'Box sets group related movies. Create the box set first, then use "Manage" to add movies to it and drag to reorder them. Removing a movie from a set does not delete the movie.', 'wp-movie-collector' ) . '</p>',
			);
		} elseif ( false !== strpos( $screen->id, 'import-export' ) ) {
			$help[] = array(
				'id'      => 'wpmc-import-export',
				'title'   => __( 'Import / Export', 'wp-movie-collector' ),
				'content' => '<p>' . esc_html__( 'Export your collection as CSV or JSON for backup or migration. When importing, download the CSV template first to match the expected columns. Choose append to add to your collection or replace to overwrite it.', 'wp-movie-collector' ) . '</p>',
			);
		} elseif ( false !== strpos( $screen->id, 'settings' ) ) {
			$help[] = array(
				'id'      => 'wpmc-settings',
				'title'   => __( 'API Keys', 'wp-movie-collector' ),
				'content' => '<p>' . esc_html__( 'API keys are optional and enable automatic metadata and barcode lookups. Get a TMDb key from your TMDb account, an OMDb key from omdbapi.com, and a Barcode Lookup key from barcodelookup.com. Without keys you can still add movies manually.', 'wp-movie-collector' ) . '</p>',
			);
		} else {
			$help[] = array(
				'id'      => 'wpmc-collection',
				'title'   => __( 'Your Collection', 'wp-movie-collector' ),
				'content' => '<p>' . esc_html__( 'Browse, search, and filter your movies and box sets. Use the row actions to edit or delete an item.', 'wp-movie-collector' ) . '</p>',
			);
		}

		foreach ( $help as $tab ) {
			$screen->add_help_tab( $tab );
		}

		$screen->set_help_sidebar(
			'<p><strong>' . esc_html__( 'For more information:', 'wp-movie-collector' ) . '</strong></p>' .
			'<p><a href="https://github.com/imonroe/wp_movie_collector" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Documentation', 'wp-movie-collector' ) . '</a></p>' .
			'<p><a href="https://github.com/imonroe/wp_movie_collector/issues" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Support', 'wp-movie-collector' ) . '</a></p>'
		);
	}

	/**
	 * Display the admin dashboard.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_dashboard() {
		include_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'admin/partials/wp-movie-collector-admin-dashboard.php';
	}

	/**
	 * Display the all movies list page.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_movies() {
		include_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'admin/partials/wp-movie-collector-admin-movies.php';
	}

	/**
	 * Display the add movie page.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_add_movie() {
		include_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'admin/partials/wp-movie-collector-admin-add-movie.php';
	}

	/**
	 * Display the edit movie page.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_edit_movie() {
		include_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'admin/partials/wp-movie-collector-admin-edit-movie.php';
	}

	/**
	 * Display the box sets page.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_box_sets() {
		include_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'admin/partials/wp-movie-collector-admin-box-sets.php';
	}

	/**
	 * Display the add box set page.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_add_box_set() {
		include_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'admin/partials/wp-movie-collector-admin-add-box-set.php';
	}

	/**
	 * Display the manage box set page.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_manage_box_set() {
		include_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'admin/partials/wp-movie-collector-admin-manage-box-set.php';
	}

	/**
	 * Display the edit box set page.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_edit_box_set() {
		include_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'admin/partials/wp-movie-collector-admin-edit-box-set.php';
	}

	/**
	 * Display the import/export page.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_import_export() {
		include_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'admin/partials/wp-movie-collector-admin-import-export.php';
	}

	/**
	 * Display the settings page.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_settings() {
		include_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'admin/partials/wp-movie-collector-admin-settings.php';
	}

	/**
	 * Register admin settings.
	 *
	 * @since    1.0.0
	 */
	public function register_settings() {
		register_setting( 'wp_movie_collector_settings', 'wp_movie_collector_tmdb_api_key' );
		register_setting( 'wp_movie_collector_settings', 'wp_movie_collector_omdb_api_key' );
		register_setting( 'wp_movie_collector_settings', 'wp_movie_collector_barcode_api_key' );

		add_settings_section(
			'wp_movie_collector_api_settings',
			__( 'API Settings', 'wp-movie-collector' ),
			array( $this, 'settings_section_callback' ),
			'wp_movie_collector_settings'
		);

		add_settings_field(
			'wp_movie_collector_tmdb_api_key',
			__( 'TMDb API Key', 'wp-movie-collector' ),
			array( $this, 'tmdb_api_key_callback' ),
			'wp_movie_collector_settings',
			'wp_movie_collector_api_settings'
		);

		add_settings_field(
			'wp_movie_collector_omdb_api_key',
			__( 'OMDb API Key', 'wp-movie-collector' ),
			array( $this, 'omdb_api_key_callback' ),
			'wp_movie_collector_settings',
			'wp_movie_collector_api_settings'
		);

		add_settings_field(
			'wp_movie_collector_barcode_api_key',
			__( 'UPC Database API Key', 'wp-movie-collector' ),
			array( $this, 'barcode_api_key_callback' ),
			'wp_movie_collector_settings',
			'wp_movie_collector_api_settings'
		);
	}

	/**
	 * Process the add movie form submission.
	 *
	 * @since    1.0.0
	 */
	public function process_add_movie_form() {
		// Check if form is submitted
		if ( ! isset( $_POST['wp_movie_collector_add_movie_submit'] ) ) {
			return;
		}

		// Check nonce
		if ( ! isset( $_POST['wp_movie_collector_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wp_movie_collector_nonce'] ), 'wp_movie_collector_add_movie' ) ) {
			wp_die( __( 'Security check failed.', 'wp-movie-collector' ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to perform this action.', 'wp-movie-collector' ) );
		}

		// Ensure the movie payload exists and has the expected structure.
		if ( ! isset( $_POST['movie'] ) || ! is_array( $_POST['movie'] ) ) {
			wp_safe_redirect( add_query_arg( 'error', 'validation', admin_url( 'admin.php?page=wp-movie-collector-add-movie' ) ) );
			exit;
		}

		// Validate and sanitize movie data
		$movie = $this->validate_and_sanitize_movie_data( wp_unslash( $_POST['movie'] ) );

		// Insert the movie into the database
		$db       = new WP_Movie_Collector_DB();
		$movie_id = $db->insert_movie( $movie );

		if ( $movie_id ) {
			// If this movie is part of a box set, create the relationship
			if ( ! empty( $movie['box_set_id'] ) ) {
				$db->add_movie_to_box_set( $movie_id, $movie['box_set_id'] );
			}

			// Redirect to the movie list with success message
			wp_safe_redirect( add_query_arg( 'message', 'movie_added', admin_url( 'admin.php?page=wp-movie-collector-dashboard' ) ) );
			exit;
		} else {
			// Redirect back to form with error message
			wp_safe_redirect( add_query_arg( 'error', 'db_error', admin_url( 'admin.php?page=wp-movie-collector-add-movie' ) ) );
			exit;
		}
	}

	/**
	 * Process the add box set form submission.
	 *
	 * @since    1.0.0
	 */
	public function process_add_box_set_form() {
		// Check if form is submitted
		if ( ! isset( $_POST['wp_movie_collector_add_box_set_submit'] ) ) {
			return;
		}

		// Check nonce
		if ( ! isset( $_POST['wp_movie_collector_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wp_movie_collector_nonce'] ), 'wp_movie_collector_add_box_set' ) ) {
			wp_die( __( 'Security check failed.', 'wp-movie-collector' ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to perform this action.', 'wp-movie-collector' ) );
		}

		// Ensure the box set payload exists and has the expected structure.
		if ( ! isset( $_POST['box_set'] ) || ! is_array( $_POST['box_set'] ) ) {
			wp_safe_redirect( add_query_arg( 'error', 'validation', admin_url( 'admin.php?page=wp-movie-collector-add-box-set' ) ) );
			exit;
		}

		// Validate and sanitize box set data
		$box_set = $this->validate_and_sanitize_box_set_data( wp_unslash( $_POST['box_set'] ) );

		// Insert the box set into the database
		$db         = new WP_Movie_Collector_DB();
		$box_set_id = $db->insert_box_set( $box_set );

		if ( $box_set_id ) {
			// Redirect to the box set list with success message
			wp_safe_redirect( add_query_arg( 'message', 'box_set_added', admin_url( 'admin.php?page=wp-movie-collector-dashboard' ) ) );
			exit;
		} else {
			// Redirect back to form with error message
			wp_safe_redirect( add_query_arg( 'error', 'db_error', admin_url( 'admin.php?page=wp-movie-collector-add-box-set' ) ) );
			exit;
		}
	}

	/**
	 * Process the edit movie form submission.
	 *
	 * @since    1.0.0
	 */
	public function process_edit_movie_form() {
		// Check if form is submitted
		if ( ! isset( $_POST['wp_movie_collector_edit_movie_submit'] ) ) {
			return;
		}

		// Check nonce
		if ( ! isset( $_POST['wp_movie_collector_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wp_movie_collector_nonce'] ), 'wp_movie_collector_edit_movie' ) ) {
			wp_die( __( 'Security check failed.', 'wp-movie-collector' ), '', array( 'response' => 403 ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to perform this action.', 'wp-movie-collector' ), '', array( 'response' => 403 ) );
		}

		// Get movie ID
		$movie_id = isset( $_POST['movie_id'] ) ? intval( $_POST['movie_id'] ) : 0;
		if ( ! $movie_id ) {
			wp_safe_redirect( add_query_arg( 'error', 'invalid_movie', admin_url( 'admin.php?page=wp-movie-collector-movies' ) ) );
			exit;
		}

		// Verify movie exists
		$db             = new WP_Movie_Collector_DB();
		$existing_movie = $db->get_movie( $movie_id );
		if ( ! $existing_movie ) {
			wp_safe_redirect( add_query_arg( 'error', 'invalid_movie', admin_url( 'admin.php?page=wp-movie-collector-movies' ) ) );
			exit;
		}

		// Ensure the movie payload exists and has the expected structure.
		if ( ! isset( $_POST['movie'] ) || ! is_array( $_POST['movie'] ) ) {
			wp_safe_redirect( add_query_arg( 'error', 'validation', admin_url( 'admin.php?page=wp-movie-collector-edit-movie&id=' . $movie_id ) ) );
			exit;
		}

		// Validate and sanitize movie data
		$movie = $this->validate_and_sanitize_movie_data( wp_unslash( $_POST['movie'] ), $movie_id );

		// Ensure clearable optional fields are always explicitly persisted on edit.
		// The validator omits empty optional fields, but update needs explicit values
		// so previously stored data can be cleared.
		if ( ! isset( $movie['box_set_id'] ) ) {
			$movie['box_set_id'] = null;
		}
		if ( ! isset( $movie['cover_image_id'] ) ) {
			$movie['cover_image_id'] = 0;
		}
		if ( ! isset( $movie['acquisition_date'] ) ) {
			$movie['acquisition_date'] = null;
		}

		global $wpdb;

		// Wrap the movie update and relationship rewrite in a transaction
		// so they succeed or fail together.
		$transaction_started = false;
		if ( false !== $wpdb->query( 'START TRANSACTION' ) ) {
			$transaction_started = true;
		}

		// Update the movie in the database.
		$result = $db->update_movie( $movie_id, $movie );

		if ( $result ) {
			// Handle box set relationship changes.
			// A return value of 0 means no rows matched (not a failure).
			$delete_result         = $wpdb->delete( $db->get_relationships_table(), array( 'movie_id' => $movie_id ) );
			$relationships_updated = ( false !== $delete_result );

			// Add new relationship if a box set is selected.
			if ( $relationships_updated && ! empty( $movie['box_set_id'] ) ) {
				$relationships_updated = ( false !== $db->add_movie_to_box_set( $movie_id, $movie['box_set_id'] ) );
			}

			if ( $relationships_updated ) {
				if ( $transaction_started ) {
					$wpdb->query( 'COMMIT' );
				}

				wp_safe_redirect( add_query_arg( 'message', 'movie_updated', admin_url( 'admin.php?page=wp-movie-collector-movies' ) ) );
				exit;
			}
		}

		if ( $transaction_started ) {
			$wpdb->query( 'ROLLBACK' );
		}

		wp_safe_redirect( add_query_arg( 'error', 'db_error', admin_url( 'admin.php?page=wp-movie-collector-edit-movie&id=' . $movie_id ) ) );
		exit;
	}

	/**
	 * Process deleting a movie via admin-post.php.
	 *
	 * @since    1.0.0
	 */
	public function process_delete_movie() {
		// Get and validate movie ID first for a clearer error message.
		$movie_id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;

		if ( ! $movie_id ) {
			wp_safe_redirect( add_query_arg( 'error', 'invalid_movie', admin_url( 'admin.php?page=wp-movie-collector-movies' ) ) );
			exit;
		}

		// Check nonce
		if ( ! isset( $_POST['wp_movie_collector_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wp_movie_collector_nonce'] ), 'wp_movie_collector_delete_movie_' . $movie_id ) ) {
			wp_die( __( 'Security check failed.', 'wp-movie-collector' ), '', array( 'response' => 403 ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to perform this action.', 'wp-movie-collector' ), '', array( 'response' => 403 ) );
		}

		// Get database instance
		$db = new WP_Movie_Collector_DB();

		// Delete the movie (this also removes relationships)
		$result = $db->delete_movie( $movie_id );

		// Redirect back to the originating page when provided, falling back to All Movies.
		$default_redirect = admin_url( 'admin.php?page=wp-movie-collector-movies' );
		$redirect_to      = isset( $_POST['redirect_to'] ) ? wp_validate_redirect( sanitize_url( wp_unslash( $_POST['redirect_to'] ) ), $default_redirect ) : $default_redirect;

		if ( $result ) {
			wp_safe_redirect( add_query_arg( 'message', 'movie_deleted', $redirect_to ) );
		} else {
			wp_safe_redirect( add_query_arg( 'error', 'delete_failed', $redirect_to ) );
		}
		exit;
	}

	/**
	 * Validate and sanitize movie form data.
	 *
	 * @since    1.0.0
	 * @param    array $movie       The raw movie data from the form.
	 * @param    int   $movie_id    Optional movie ID for edit context.
	 * @return   array                 The validated and sanitized movie data.
	 */
	private function validate_and_sanitize_movie_data( $movie, $movie_id = 0 ) {
		$result = $this->validate_movie_fields( $movie, $movie_id );

		// Validation failure here redirects back to the form with the errors
		// stored in a transient (the form-handler contract). Reusable callers
		// should use validate_movie_fields() directly instead.
		if ( ! empty( $result['errors'] ) ) {
			set_transient( 'wp_movie_collector_form_errors_' . get_current_user_id(), $result['errors'], 60 * 5 );
			if ( $movie_id ) {
				wp_safe_redirect( add_query_arg( 'error', 'validation', admin_url( 'admin.php?page=wp-movie-collector-edit-movie&id=' . $movie_id ) ) );
			} else {
				wp_safe_redirect( add_query_arg( 'error', 'validation', admin_url( 'admin.php?page=wp-movie-collector-add-movie' ) ) );
			}
			exit;
		}

		return $result['data'];
	}

	/**
	 * Validate and sanitize movie fields without redirecting.
	 *
	 * Returns the sanitized data plus any validation errors so callers can
	 * decide how to handle failure (redirect, roll back, skip a row, etc.).
	 * This makes the routine reusable from non-redirecting contexts such as
	 * the CSV/JSON importer.
	 *
	 * @since 1.3.0
	 * @param array $movie    Raw movie data.
	 * @param int   $movie_id Optional movie ID for edit context (excludes the
	 *                        row from its own duplicate check).
	 * @return array{data: array, errors: array}
	 */
	private function validate_movie_fields( $movie, $movie_id = 0 ) {
		$sanitized = array();
		$errors    = array();

		// Validate required fields
		if ( empty( $movie['title'] ) ) {
			$errors[] = __( 'Title is required.', 'wp-movie-collector' );
		} else {
			$sanitized['title'] = sanitize_text_field( $movie['title'] );
		}

		if ( empty( $movie['release_year'] ) ) {
			$errors[] = __( 'Release year is required.', 'wp-movie-collector' );
		} else {
			$year = intval( $movie['release_year'] );
			if ( $year < 1900 || $year > intval( date( 'Y' ) ) ) {
				$errors[] = __( 'Release year must be between 1900 and the current year.', 'wp-movie-collector' );
			} else {
				$sanitized['release_year'] = $year;
			}
		}

		if ( empty( $movie['format'] ) ) {
			$errors[] = __( 'Format is required.', 'wp-movie-collector' );
		} else {
			$valid_formats = array( 'DVD', 'Blu-ray', '4K UHD', 'VHS', 'LaserDisc' );
			if ( ! in_array( $movie['format'], $valid_formats ) ) {
				$errors[] = __( 'Invalid format selected.', 'wp-movie-collector' );
			} else {
				$sanitized['format'] = sanitize_text_field( $movie['format'] );
			}
		}

		if ( empty( $movie['region_code'] ) ) {
			$errors[] = __( 'Region code is required.', 'wp-movie-collector' );
		} else {
			$valid_regions = array( 'R0', 'R1', 'R2', 'R3', 'R4', 'R5', 'R6', 'A', 'B', 'C', 'ABC' );
			if ( ! in_array( $movie['region_code'], $valid_regions ) ) {
				$errors[] = __( 'Invalid region code selected.', 'wp-movie-collector' );
			} else {
				$sanitized['region_code'] = sanitize_text_field( $movie['region_code'] );
			}
		}

		// Optional fields
		$sanitized['barcode']          = ! empty( $movie['barcode'] ) ? sanitize_text_field( $movie['barcode'] ) : '';
		$sanitized['director']         = ! empty( $movie['director'] ) ? sanitize_text_field( $movie['director'] ) : '';
		$sanitized['studio']           = ! empty( $movie['studio'] ) ? sanitize_text_field( $movie['studio'] ) : '';
		$sanitized['actors']           = ! empty( $movie['actors'] ) ? sanitize_textarea_field( $movie['actors'] ) : '';
		$sanitized['genre']            = ! empty( $movie['genre'] ) ? sanitize_text_field( $movie['genre'] ) : '';
		$sanitized['special_features'] = ! empty( $movie['special_features'] ) ? sanitize_textarea_field( $movie['special_features'] ) : '';

		// Handle cover image (either URL or attachment ID)
		if ( ! empty( $movie['cover_image_url'] ) ) {
			$url = esc_url_raw( $movie['cover_image_url'] );
			if ( $url === '' ) {
				$errors[] = __( 'Cover image URL is not valid.', 'wp-movie-collector' );
			} else {
				$sanitized['cover_image_url'] = $url;
			}
		} else {
			$sanitized['cover_image_url'] = '';
		}

		// Handle cover image attachment ID
		if ( ! empty( $movie['cover_image_id'] ) ) {
			$cover_image_id = intval( $movie['cover_image_id'] );
			// Verify attachment exists and is an image
			$attachment = get_post( $cover_image_id );
			if ( ! $attachment || $attachment->post_type !== 'attachment' || strpos( $attachment->post_mime_type ?? '', 'image' ) === false ) {
				$errors[] = __( 'Selected cover image is not valid.', 'wp-movie-collector' );
			} else {
				$sanitized['cover_image_id'] = $cover_image_id;

				// If cover_image_url is empty, set it to the attachment URL
				if ( empty( $sanitized['cover_image_url'] ) ) {
					$sanitized['cover_image_url'] = wp_get_attachment_url( $cover_image_id );
				}
			}
		}

		$sanitized['description']  = ! empty( $movie['description'] ) ? sanitize_textarea_field( $movie['description'] ) : '';
		$sanitized['custom_notes'] = ! empty( $movie['custom_notes'] ) ? sanitize_textarea_field( $movie['custom_notes'] ) : '';
		$sanitized['api_source']   = ! empty( $movie['api_source'] ) ? sanitize_text_field( $movie['api_source'] ) : '';

		// Handle acquisition date
		if ( ! empty( $movie['acquisition_date'] ) ) {
			// Validate date format (YYYY-MM-DD)
			if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $movie['acquisition_date'], $matches ) ) {
				if ( checkdate( $matches[2], $matches[3], $matches[1] ) ) {
					$sanitized['acquisition_date'] = sanitize_text_field( $movie['acquisition_date'] );
				} else {
					$errors[] = __( 'Acquisition date is not valid.', 'wp-movie-collector' );
				}
			} else {
				$errors[] = __( 'Acquisition date must be in YYYY-MM-DD format.', 'wp-movie-collector' );
			}
		}

		// Handle box set relationship
		if ( ! empty( $movie['box_set_id'] ) ) {
			$box_set_id = intval( $movie['box_set_id'] );
			// Verify box set exists
			global $wpdb;
			$db             = new WP_Movie_Collector_DB();
			$box_set_exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$db->get_box_sets_table()} WHERE id = %d",
					$box_set_id
				)
			);

			if ( ! $box_set_exists ) {
				$errors[] = __( 'Selected box set does not exist.', 'wp-movie-collector' );
			} else {
				$sanitized['box_set_id'] = $box_set_id;
			}
		}

		// Check for duplicates (only on add, and only when user hasn't overridden).
		$allow_duplicate = ! empty( $movie['allow_duplicate'] );
		if ( ! $movie_id && ! $allow_duplicate && empty( $errors ) ) {
			$dup_db = new WP_Movie_Collector_DB();
			$dupes  = $dup_db->find_duplicate_movies(
				isset( $sanitized['title'] ) ? $sanitized['title'] : '',
				isset( $sanitized['release_year'] ) ? $sanitized['release_year'] : 0,
				isset( $sanitized['barcode'] ) ? $sanitized['barcode'] : ''
			);

			if ( ! empty( $dupes['barcode_match'] ) ) {
				$errors[] = sprintf(
					/* translators: 1: movie title, 2: movie ID */
					__( 'A movie with this barcode already exists: "%1$s" (ID: %2$d). If you want to add it anyway, check "Allow duplicate".', 'wp-movie-collector' ),
					$dupes['barcode_match']['title'],
					$dupes['barcode_match']['id']
				);
			} elseif ( ! empty( $dupes['title_matches'] ) ) {
				$existing = $dupes['title_matches'][0];
				$errors[] = sprintf(
					/* translators: 1: movie title, 2: release year, 3: movie ID */
					__( 'A movie with the same title and year already exists: "%1$s" (%2$d), ID: %3$d. If you own multiple copies, check "Allow duplicate".', 'wp-movie-collector' ),
					$existing['title'],
					$existing['release_year'],
					$existing['id']
				);
			}

			// Cross-type barcode check: also look in box sets table.
			if ( empty( $errors ) && ! empty( $sanitized['barcode'] ) ) {
				$box_set_dupes = $dup_db->find_duplicate_box_sets( '', 0, $sanitized['barcode'] );
				if ( ! empty( $box_set_dupes['barcode_match'] ) ) {
					$errors[] = sprintf(
						/* translators: 1: box set title, 2: box set ID */
						__( 'A box set with this barcode already exists: "%1$s" (ID: %2$d). If you want to add it anyway, check "Allow duplicate".', 'wp-movie-collector' ),
						$box_set_dupes['barcode_match']['title'],
						$box_set_dupes['barcode_match']['id']
					);
				}
			}
		}

		return array(
			'data'   => $sanitized,
			'errors' => $errors,
		);
	}

	/**
	 * Validate and sanitize box set form data.
	 *
	 * @since    1.0.0
	 * @param    array $box_set       The raw box set data from the form.
	 * @param    int   $box_set_id    Optional box set ID for edit context.
	 * @return   array                   The validated and sanitized box set data.
	 */
	private function validate_and_sanitize_box_set_data( $box_set, $box_set_id = 0 ) {
		$result = $this->validate_box_set_fields( $box_set, $box_set_id );

		// Validation failure here redirects back to the form (the form-handler
		// contract). Reusable callers should use validate_box_set_fields().
		if ( ! empty( $result['errors'] ) ) {
			set_transient( 'wp_movie_collector_form_errors_' . get_current_user_id(), $result['errors'], 60 * 5 );
			if ( $box_set_id ) {
				wp_safe_redirect( add_query_arg( 'error', 'validation', admin_url( 'admin.php?page=wp-movie-collector-edit-box-set&id=' . $box_set_id ) ) );
			} else {
				wp_safe_redirect( add_query_arg( 'error', 'validation', admin_url( 'admin.php?page=wp-movie-collector-add-box-set' ) ) );
			}
			exit;
		}

		return $result['data'];
	}

	/**
	 * Validate and sanitize box-set fields without redirecting.
	 *
	 * @since 1.3.0
	 * @param array $box_set    Raw box-set data.
	 * @param int   $box_set_id Optional box-set ID for edit context.
	 * @return array{data: array, errors: array}
	 */
	private function validate_box_set_fields( $box_set, $box_set_id = 0 ) {
		$sanitized = array();
		$errors    = array();

		// Validate required fields
		if ( empty( $box_set['title'] ) ) {
			$errors[] = __( 'Title is required.', 'wp-movie-collector' );
		} else {
			$sanitized['title'] = sanitize_text_field( $box_set['title'] );
		}

		if ( empty( $box_set['release_year'] ) ) {
			$errors[] = __( 'Release year is required.', 'wp-movie-collector' );
		} else {
			$year = intval( $box_set['release_year'] );
			if ( $year < 1900 || $year > intval( date( 'Y' ) ) ) {
				$errors[] = __( 'Release year must be between 1900 and the current year.', 'wp-movie-collector' );
			} else {
				$sanitized['release_year'] = $year;
			}
		}

		if ( empty( $box_set['format'] ) ) {
			$errors[] = __( 'Format is required.', 'wp-movie-collector' );
		} else {
			$valid_formats = array( 'DVD', 'Blu-ray', '4K UHD', 'VHS', 'LaserDisc' );
			if ( ! in_array( $box_set['format'], $valid_formats ) ) {
				$errors[] = __( 'Invalid format selected.', 'wp-movie-collector' );
			} else {
				$sanitized['format'] = sanitize_text_field( $box_set['format'] );
			}
		}

		if ( empty( $box_set['region_code'] ) ) {
			$errors[] = __( 'Region code is required.', 'wp-movie-collector' );
		} else {
			$valid_regions = array( 'R0', 'R1', 'R2', 'R3', 'R4', 'R5', 'R6', 'A', 'B', 'C', 'ABC' );
			if ( ! in_array( $box_set['region_code'], $valid_regions ) ) {
				$errors[] = __( 'Invalid region code selected.', 'wp-movie-collector' );
			} else {
				$sanitized['region_code'] = sanitize_text_field( $box_set['region_code'] );
			}
		}

		// Optional fields
		$sanitized['barcode'] = ! empty( $box_set['barcode'] ) ? sanitize_text_field( $box_set['barcode'] ) : '';

		// Handle cover image (either URL or attachment ID)
		if ( ! empty( $box_set['cover_image_url'] ) ) {
			$url = esc_url_raw( $box_set['cover_image_url'] );
			if ( $url === '' ) {
				$errors[] = __( 'Cover image URL is not valid.', 'wp-movie-collector' );
			} else {
				$sanitized['cover_image_url'] = $url;
			}
		} else {
			$sanitized['cover_image_url'] = '';
		}

		// Handle cover image attachment ID
		if ( ! empty( $box_set['cover_image_id'] ) ) {
			$cover_image_id = intval( $box_set['cover_image_id'] );
			// Verify attachment exists and is an image
			$attachment = get_post( $cover_image_id );
			if ( ! $attachment || $attachment->post_type !== 'attachment' || strpos( $attachment->post_mime_type ?? '', 'image' ) === false ) {
				$errors[] = __( 'Selected cover image is not valid.', 'wp-movie-collector' );
			} else {
				$sanitized['cover_image_id'] = $cover_image_id;

				// If cover_image_url is empty, set it to the attachment URL
				if ( empty( $sanitized['cover_image_url'] ) ) {
					$sanitized['cover_image_url'] = wp_get_attachment_url( $cover_image_id );
				}
			}
		}

		$sanitized['description']      = ! empty( $box_set['description'] ) ? sanitize_textarea_field( $box_set['description'] ) : '';
		$sanitized['special_features'] = ! empty( $box_set['special_features'] ) ? sanitize_textarea_field( $box_set['special_features'] ) : '';
		$sanitized['custom_notes']     = ! empty( $box_set['custom_notes'] ) ? sanitize_textarea_field( $box_set['custom_notes'] ) : '';
		$sanitized['api_source']       = ! empty( $box_set['api_source'] ) ? sanitize_text_field( $box_set['api_source'] ) : '';

		// Handle acquisition date
		if ( ! empty( $box_set['acquisition_date'] ) ) {
			// Validate date format (YYYY-MM-DD)
			if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $box_set['acquisition_date'], $matches ) ) {
				if ( checkdate( $matches[2], $matches[3], $matches[1] ) ) {
					$sanitized['acquisition_date'] = sanitize_text_field( $box_set['acquisition_date'] );
				} else {
					$errors[] = __( 'Acquisition date is not valid.', 'wp-movie-collector' );
				}
			} else {
				$errors[] = __( 'Acquisition date must be in YYYY-MM-DD format.', 'wp-movie-collector' );
			}
		}

		// Check for duplicates (only on add, and only when user hasn't overridden).
		$allow_duplicate = ! empty( $box_set['allow_duplicate'] );
		if ( ! $box_set_id && ! $allow_duplicate && empty( $errors ) ) {
			$dup_db = new WP_Movie_Collector_DB();
			$dupes  = $dup_db->find_duplicate_box_sets(
				isset( $sanitized['title'] ) ? $sanitized['title'] : '',
				isset( $sanitized['release_year'] ) ? $sanitized['release_year'] : 0,
				isset( $sanitized['barcode'] ) ? $sanitized['barcode'] : ''
			);

			if ( ! empty( $dupes['barcode_match'] ) ) {
				$errors[] = sprintf(
					/* translators: 1: box set title, 2: box set ID */
					__( 'A box set with this barcode already exists: "%1$s" (ID: %2$d). If you want to add it anyway, check "Allow duplicate".', 'wp-movie-collector' ),
					$dupes['barcode_match']['title'],
					$dupes['barcode_match']['id']
				);
			} elseif ( ! empty( $dupes['title_matches'] ) ) {
				$existing = $dupes['title_matches'][0];
				$errors[] = sprintf(
					/* translators: 1: box set title, 2: release year, 3: box set ID */
					__( 'A box set with the same title and year already exists: "%1$s" (%2$d), ID: %3$d. If you own multiple copies, check "Allow duplicate".', 'wp-movie-collector' ),
					$existing['title'],
					$existing['release_year'],
					$existing['id']
				);
			}

			// Cross-type barcode check: also look in movies table.
			if ( empty( $errors ) && ! empty( $sanitized['barcode'] ) ) {
				$movie_dupes = $dup_db->find_duplicate_movies( '', 0, $sanitized['barcode'] );
				if ( ! empty( $movie_dupes['barcode_match'] ) ) {
					$errors[] = sprintf(
						/* translators: 1: movie title, 2: movie ID */
						__( 'A movie with this barcode already exists: "%1$s" (ID: %2$d). If you want to add it anyway, check "Allow duplicate".', 'wp-movie-collector' ),
						$movie_dupes['barcode_match']['title'],
						$movie_dupes['barcode_match']['id']
					);
				}
			}
		}

		return array(
			'data'   => $sanitized,
			'errors' => $errors,
		);
	}

	/**
	 * Settings section callback.
	 *
	 * @since    1.0.0
	 */
	public function settings_section_callback() {
		echo '<p>' . esc_html__( 'Enter your API keys for movie data services.', 'wp-movie-collector' ) . '</p>';
	}

	/**
	 * TMDb API key field callback.
	 *
	 * @since    1.0.0
	 */
	public function tmdb_api_key_callback() {
		$api_key = get_option( 'wp_movie_collector_tmdb_api_key' );
		echo '<input type="password" autocomplete="off" id="wp_movie_collector_tmdb_api_key" name="wp_movie_collector_tmdb_api_key" value="' . esc_attr( $api_key ) . '" class="regular-text" />';
		$link = '<a href="' . esc_url( 'https://www.themoviedb.org/settings/api' ) . '" target="_blank" rel="noopener noreferrer">TMDb</a>';
		/* translators: %s: link to the provider's API key page. */
		$text = sprintf( __( 'Get your API key from %s.', 'wp-movie-collector' ), $link );
		echo '<p class="description">' . wp_kses( $text, array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) ) . '</p>';
	}

	/**
	 * OMDb API key field callback.
	 *
	 * @since    1.0.0
	 */
	public function omdb_api_key_callback() {
		$api_key = get_option( 'wp_movie_collector_omdb_api_key' );
		echo '<input type="password" autocomplete="off" id="wp_movie_collector_omdb_api_key" name="wp_movie_collector_omdb_api_key" value="' . esc_attr( $api_key ) . '" class="regular-text" />';
		$link = '<a href="' . esc_url( 'https://www.omdbapi.com/apikey.aspx' ) . '" target="_blank" rel="noopener noreferrer">OMDb</a>';
		/* translators: %s: link to the provider's API key page. */
		$text = sprintf( __( 'Get your API key from %s.', 'wp-movie-collector' ), $link );
		echo '<p class="description">' . wp_kses( $text, array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) ) . '</p>';
	}

	/**
	 * BarcodeLookup API key field callback.
	 *
	 * @since    1.0.0
	 */
	public function barcode_api_key_callback() {
		$api_key = get_option( 'wp_movie_collector_barcode_api_key' );
		echo '<input type="password" autocomplete="off" id="wp_movie_collector_barcode_api_key" name="wp_movie_collector_barcode_api_key" value="' . esc_attr( $api_key ) . '" class="regular-text" />';
		$link = '<a href="' . esc_url( 'https://barcodelookup.com/api' ) . '" target="_blank" rel="noopener noreferrer">BarcodeLookup</a>';
		/* translators: %s: link to the provider's API key page. */
		$text = sprintf( __( 'Get your API key from %s.', 'wp-movie-collector' ), $link );
		echo '<p class="description">' . wp_kses( $text, array( 'a' => array( 'href' => array(), 'target' => array(), 'rel' => array() ) ) ) . '</p>';
	}

	/**
	 * Process the edit box set form via admin-post.php.
	 *
	 * @since    1.0.0
	 */
	public function process_edit_box_set_form() {
		// Check nonce.
		if ( ! isset( $_POST['wp_movie_collector_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wp_movie_collector_nonce'] ), 'wp_movie_collector_update_box_set' ) ) {
			wp_die( __( 'Security check failed.', 'wp-movie-collector' ), '', array( 'response' => 403 ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to perform this action.', 'wp-movie-collector' ), '', array( 'response' => 403 ) );
		}

		// Get box set ID.
		$box_set_id = isset( $_POST['box_set_id'] ) ? intval( $_POST['box_set_id'] ) : 0;

		if ( ! $box_set_id ) {
			wp_safe_redirect( add_query_arg( 'error', 'invalid_box_set', admin_url( 'admin.php?page=wp-movie-collector-box-sets' ) ) );
			exit;
		}

		// Verify box set exists.
		$db       = new WP_Movie_Collector_DB();
		$existing = $db->get_box_set( $box_set_id );

		if ( ! $existing ) {
			wp_safe_redirect( add_query_arg( 'error', 'invalid_box_set', admin_url( 'admin.php?page=wp-movie-collector-box-sets' ) ) );
			exit;
		}

		// Ensure the box set payload exists and has the expected structure.
		if ( ! isset( $_POST['box_set'] ) || ! is_array( $_POST['box_set'] ) ) {
			wp_safe_redirect( add_query_arg( 'error', 'validation', admin_url( 'admin.php?page=wp-movie-collector-edit-box-set&id=' . $box_set_id ) ) );
			exit;
		}

		// Validate and sanitize box set data.
		$box_set = $this->validate_and_sanitize_box_set_data( wp_unslash( $_POST['box_set'] ), $box_set_id );

		// Ensure clearable optional fields are explicitly persisted.
		if ( ! isset( $box_set['cover_image_id'] ) ) {
			$box_set['cover_image_id'] = 0;
		}
		if ( ! isset( $box_set['acquisition_date'] ) ) {
			$box_set['acquisition_date'] = null;
		}

		// Update the box set in the database.
		$result = $db->update_box_set( $box_set_id, $box_set );

		if ( $result ) {
			wp_safe_redirect( add_query_arg( 'message', 'box_set_updated', admin_url( 'admin.php?page=wp-movie-collector-box-sets' ) ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( 'error', 'db_error', admin_url( 'admin.php?page=wp-movie-collector-edit-box-set&id=' . $box_set_id ) ) );
		exit;
	}

	/**
	 * Process the add movies to box set form.
	 *
	 * @since    1.0.0
	 */
	public function process_add_movies_to_box_set() {
		// Check nonce
		if ( ! isset( $_POST['wp_movie_collector_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wp_movie_collector_nonce'] ), 'wp_movie_collector_add_movies' ) ) {
			wp_die( __( 'Security check failed.', 'wp-movie-collector' ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to perform this action.', 'wp-movie-collector' ) );
		}

		// Get box set ID
		if ( ! isset( $_POST['box_set_id'] ) || empty( $_POST['box_set_id'] ) ) {
			wp_safe_redirect( add_query_arg( 'error', 'invalid_box_set', admin_url( 'admin.php?page=wp-movie-collector-dashboard' ) ) );
			exit;
		}

		$box_set_id = intval( $_POST['box_set_id'] );

		// Check if movie IDs are provided
		if ( ! isset( $_POST['movie_ids'] ) || ! is_array( $_POST['movie_ids'] ) || empty( $_POST['movie_ids'] ) ) {
			wp_safe_redirect( add_query_arg( 'error', 'no_movies_selected', admin_url( 'admin.php?page=wp-movie-collector-manage-box-set&id=' . $box_set_id ) ) );
			exit;
		}

		// Get database instance
		$db = new WP_Movie_Collector_DB();

		// Check if box set exists
		$box_set = $db->get_box_set( $box_set_id );
		if ( ! $box_set ) {
			wp_safe_redirect( add_query_arg( 'error', 'invalid_box_set', admin_url( 'admin.php?page=wp-movie-collector-dashboard' ) ) );
			exit;
		}

		// Add each movie to the box set
		$count = 0;
		foreach ( $_POST['movie_ids'] as $movie_id ) {
			$movie_id = intval( $movie_id );

			// Check if movie exists
			$movie = $db->get_movie( $movie_id );
			if ( ! $movie ) {
				continue;
			}

			// Add movie to box set
			$result = $db->add_movie_to_box_set( $movie_id, $box_set_id );
			if ( $result ) {
				++$count;
			}
		}

		// Redirect back to manage box set page
		wp_safe_redirect(
			add_query_arg(
				array(
					'message' => 'movie_added',
					'count'   => $count,
				),
				admin_url( 'admin.php?page=wp-movie-collector-manage-box-set&id=' . $box_set_id )
			)
		);
		exit;
	}

	/**
	 * Process removing a movie from a box set via admin-post.php.
	 *
	 * @since    1.0.0
	 */
	public function process_remove_movie_from_box_set() {
		// Get and validate IDs first for clearer error messages.
		$movie_id   = isset( $_POST['movie_id'] ) ? intval( $_POST['movie_id'] ) : 0;
		$box_set_id = isset( $_POST['box_set_id'] ) ? intval( $_POST['box_set_id'] ) : 0;

		if ( ! $box_set_id ) {
			wp_safe_redirect( add_query_arg( 'error', 'invalid_box_set', admin_url( 'admin.php?page=wp-movie-collector-box-sets' ) ) );
			exit;
		}

		if ( ! $movie_id ) {
			wp_safe_redirect( add_query_arg( 'error', 'invalid_movie', admin_url( 'admin.php?page=wp-movie-collector-manage-box-set&id=' . $box_set_id ) ) );
			exit;
		}

		// Check nonce.
		if ( ! isset( $_POST['wp_movie_collector_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wp_movie_collector_nonce'] ), 'wp_movie_collector_remove_movie_' . $movie_id . '_' . $box_set_id ) ) {
			wp_die( __( 'Security check failed.', 'wp-movie-collector' ), '', array( 'response' => 403 ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to perform this action.', 'wp-movie-collector' ), '', array( 'response' => 403 ) );
		}

		// Get database instance.
		$db = new WP_Movie_Collector_DB();

		// Verify box set and movie exist before attempting removal.
		if ( ! $db->get_box_set( $box_set_id ) || ! $db->get_movie( $movie_id ) ) {
			wp_safe_redirect( add_query_arg( 'error', 'invalid_movie', admin_url( 'admin.php?page=wp-movie-collector-manage-box-set&id=' . $box_set_id ) ) );
			exit;
		}

		// Remove movie from box set.
		$result = $db->remove_movie_from_box_set( $movie_id, $box_set_id );

		if ( $result ) {
			wp_safe_redirect( add_query_arg( 'message', 'movie_removed', admin_url( 'admin.php?page=wp-movie-collector-manage-box-set&id=' . $box_set_id ) ) );
		} else {
			wp_safe_redirect( add_query_arg( 'error', 'remove_failed', admin_url( 'admin.php?page=wp-movie-collector-manage-box-set&id=' . $box_set_id ) ) );
		}
		exit;
	}

	/**
	 * Process deleting a box set via admin-post.php.
	 *
	 * @since    1.0.0
	 */
	public function process_delete_box_set() {
		// Get and validate box set ID first.
		$box_set_id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;

		if ( ! $box_set_id ) {
			wp_safe_redirect( add_query_arg( 'error', 'invalid_box_set', admin_url( 'admin.php?page=wp-movie-collector-box-sets' ) ) );
			exit;
		}

		// Check nonce.
		if ( ! isset( $_POST['wp_movie_collector_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wp_movie_collector_nonce'] ), 'wp_movie_collector_delete_box_set_' . $box_set_id ) ) {
			wp_die( __( 'Security check failed.', 'wp-movie-collector' ), '', array( 'response' => 403 ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to perform this action.', 'wp-movie-collector' ), '', array( 'response' => 403 ) );
		}

		// Get database instance.
		$db = new WP_Movie_Collector_DB();

		// Verify box set exists before attempting deletion.
		if ( ! $db->get_box_set( $box_set_id ) ) {
			wp_safe_redirect( add_query_arg( 'error', 'invalid_box_set', admin_url( 'admin.php?page=wp-movie-collector-box-sets' ) ) );
			exit;
		}

		// Delete the box set.
		$result = $db->delete_box_set( $box_set_id );

		// Redirect back to box sets page.
		$default_redirect = admin_url( 'admin.php?page=wp-movie-collector-box-sets' );
		$redirect_to      = isset( $_POST['redirect_to'] ) ? wp_validate_redirect( sanitize_url( wp_unslash( $_POST['redirect_to'] ) ), $default_redirect ) : $default_redirect;

		if ( $result ) {
			wp_safe_redirect( add_query_arg( 'message', 'box_set_deleted', $redirect_to ) );
		} else {
			wp_safe_redirect( add_query_arg( 'error', 'delete_failed', $redirect_to ) );
		}
		exit;
	}

	/**
	 * Process reordering movies in a box set.
	 *
	 * @since    1.0.0
	 */
	public function process_reorder_movies() {
		// Check nonce
		if ( ! isset( $_POST['wp_movie_collector_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wp_movie_collector_nonce'] ), 'wp_movie_collector_reorder_movies' ) ) {
			wp_die( __( 'Security check failed.', 'wp-movie-collector' ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to perform this action.', 'wp-movie-collector' ) );
		}

		// Get box set ID
		if ( ! isset( $_POST['box_set_id'] ) || empty( $_POST['box_set_id'] ) ) {
			wp_safe_redirect( add_query_arg( 'error', 'invalid_box_set', admin_url( 'admin.php?page=wp-movie-collector-dashboard' ) ) );
			exit;
		}

		$box_set_id = intval( $_POST['box_set_id'] );

		// Check if movie order is provided
		if ( ! isset( $_POST['movie_order'] ) || ! is_array( $_POST['movie_order'] ) || empty( $_POST['movie_order'] ) ) {
			wp_safe_redirect( add_query_arg( 'error', 'no_movie_order', admin_url( 'admin.php?page=wp-movie-collector-manage-box-set&id=' . $box_set_id ) ) );
			exit;
		}

		// Get database instance
		$db = new WP_Movie_Collector_DB();

		// Check if box set exists
		$box_set = $db->get_box_set( $box_set_id );
		if ( ! $box_set ) {
			wp_safe_redirect( add_query_arg( 'error', 'invalid_box_set', admin_url( 'admin.php?page=wp-movie-collector-dashboard' ) ) );
			exit;
		}

		// Update the order of movies
		global $wpdb;
		$relationship_table = $db->get_relationships_table();
		$success            = true;

		// Build the set of movie IDs that are actually members of this box set,
		// so a bogus or partially-bogus movie_order can't report success while
		// silently reordering nothing ($wpdb->update returns 0 — not false — for
		// rows that don't exist).
		$member_ids = array_map(
			'intval',
			(array) $wpdb->get_col(
				$wpdb->prepare( "SELECT movie_id FROM $relationship_table WHERE box_set_id = %d", $box_set_id )
			)
		);

		// Begin transaction
		$wpdb->query( 'START TRANSACTION' );

		try {
			// Update the display order for each movie.
			foreach ( $_POST['movie_order'] as $order => $movie_id ) {
				$movie_id = intval( $movie_id );
				$order    = intval( $order ) + 1; // Make order 1-based instead of 0-based

				// Reject IDs that aren't members of this box set.
				if ( ! in_array( $movie_id, $member_ids, true ) ) {
					$success = false;
					break;
				}

				$result = $wpdb->update(
					$relationship_table,
					array( 'display_order' => $order ),
					array(
						'movie_id'   => $movie_id,
						'box_set_id' => $box_set_id,
					)
				);

				if ( $result === false ) {
					$success = false;
					break;
				}
			}

			if ( $success ) {
				$wpdb->query( 'COMMIT' );
			} else {
				$wpdb->query( 'ROLLBACK' );
			}
		} catch ( Exception $e ) {
			$wpdb->query( 'ROLLBACK' );
			$success = false;
		}

		// Redirect back to manage box set page
		if ( $success ) {
			wp_safe_redirect( add_query_arg( 'message', 'movies_reordered', admin_url( 'admin.php?page=wp-movie-collector-manage-box-set&id=' . $box_set_id ) ) );
		} else {
			wp_safe_redirect( add_query_arg( 'error', 'reorder_failed', admin_url( 'admin.php?page=wp-movie-collector-manage-box-set&id=' . $box_set_id ) ) );
		}
		exit;
	}

	/**
	 * AJAX handler for searching available movies for a box set.
	 *
	 * @since    1.0.0
	 */
	public function ajax_search_available_movies() {
		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'wp_movie_collector_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 'wp-movie-collector' ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions.', 'wp-movie-collector' ) );
		}

		// Check if query is provided
		if ( ! isset( $_POST['query'] ) || empty( $_POST['query'] ) ) {
			wp_send_json_error( __( 'No search query provided.', 'wp-movie-collector' ) );
		}

		// Get box set ID
		$box_set_id = isset( $_POST['box_set_id'] ) ? intval( $_POST['box_set_id'] ) : 0;

		if ( ! $box_set_id ) {
			wp_send_json_error( __( 'Invalid box set ID.', 'wp-movie-collector' ) );
		}

		// Get search query
		$query = sanitize_text_field( wp_unslash( $_POST['query'] ) );

		// Search for movies
		global $wpdb;
		$db = new WP_Movie_Collector_DB();

		$movies = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$db->get_movies_table()}
                WHERE title LIKE %s AND id NOT IN (
                    SELECT movie_id FROM {$db->get_relationships_table()}
                    WHERE box_set_id = %d
                )
                ORDER BY title ASC
                LIMIT 50",
				'%' . $wpdb->esc_like( $query ) . '%',
				$box_set_id
			),
			ARRAY_A
		);

		if ( empty( $movies ) ) {
			wp_send_json_success( array() );
		} else {
			wp_send_json_success( $movies );
		}
	}

	/**
	 * Process the export movies request.
	 *
	 * @since    1.0.0
	 */
	public function process_export_movies() {
		// Check if this is an export request
		if ( ! isset( $_POST['action'] ) || sanitize_text_field( wp_unslash( $_POST['action'] ) ) !== 'wp_movie_collector_export_movies' ) {
			return;
		}

		// Check nonce
		if ( ! isset( $_POST['wp_movie_collector_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wp_movie_collector_nonce'] ), 'wp_movie_collector_export' ) ) {
			wp_die( __( 'Security check failed.', 'wp-movie-collector' ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to perform this action.', 'wp-movie-collector' ) );
		}

		// Get export type and format
		$export_type   = isset( $_POST['export_type'] ) ? sanitize_text_field( wp_unslash( $_POST['export_type'] ) ) : 'all';
		$export_format = isset( $_POST['export_format'] ) ? sanitize_text_field( wp_unslash( $_POST['export_format'] ) ) : 'csv';

		// Get data based on export type
		$data = $this->get_export_data( $export_type );

		// Generate the export file based on format
		if ( $export_format === 'csv' ) {
			$this->export_as_csv( $data, $export_type );
		} else {
			$this->export_as_json( $data, $export_type );
		}

		exit;
	}

	/**
	 * Process the import movies request.
	 *
	 * @since    1.0.0
	 */
	public function process_import_movies() {
		// Check if this is an import request
		if ( ! isset( $_POST['action'] ) || sanitize_text_field( wp_unslash( $_POST['action'] ) ) !== 'wp_movie_collector_import_movies' ) {
			return;
		}

		// Check nonce
		if ( ! isset( $_POST['wp_movie_collector_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wp_movie_collector_nonce'] ), 'wp_movie_collector_import' ) ) {
			wp_die( __( 'Security check failed.', 'wp-movie-collector' ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to perform this action.', 'wp-movie-collector' ) );
		}

		// Check if file was uploaded
		if ( ! isset( $_FILES['import_file'] ) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK ) {
			wp_safe_redirect( add_query_arg( 'error', 'file_upload', admin_url( 'admin.php?page=wp-movie-collector-import-export' ) ) );
			exit;
		}

		// Enforce maximum file size (use WordPress upload limit or 5MB, whichever is smaller)
		$max_size = min( wp_max_upload_size(), 5 * MB_IN_BYTES );
		if ( $_FILES['import_file']['size'] > $max_size ) {
			wp_safe_redirect( add_query_arg( 'error', 'file_too_large', admin_url( 'admin.php?page=wp-movie-collector-import-export' ) ) );
			exit;
		}

		// Get import type
		$import_type = isset( $_POST['import_type'] ) ? sanitize_text_field( wp_unslash( $_POST['import_type'] ) ) : 'append';

		// Get file information
		$file_info      = pathinfo( $_FILES['import_file']['name'] );
		$file_extension = strtolower( $file_info['extension'] ?? '' );

		// Check file extension
		if ( ! in_array( $file_extension, array( 'csv', 'json' ), true ) ) {
			wp_safe_redirect( add_query_arg( 'error', 'invalid_format', admin_url( 'admin.php?page=wp-movie-collector-import-export' ) ) );
			exit;
		}

		// Verify file extension and MIME type using the uploaded temporary file
		$allowed_mimes = array(
			'csv'  => array( 'text/csv', 'text/plain', 'application/csv' ),
			'json' => array( 'application/json', 'text/plain' ),
		);
		$file_type     = wp_check_filetype_and_ext(
			$_FILES['import_file']['tmp_name'],
			$_FILES['import_file']['name'],
			array(
				'csv'  => 'text/csv',
				'json' => 'application/json',
			)
		);
		if (
			empty( $file_type['ext'] ) ||
			$file_type['ext'] !== $file_extension ||
			( ! empty( $file_type['type'] ) && isset( $allowed_mimes[ $file_extension ] ) &&
			! in_array( $file_type['type'], $allowed_mimes[ $file_extension ], true ) )
		) {
			wp_safe_redirect( add_query_arg( 'error', 'invalid_format', admin_url( 'admin.php?page=wp-movie-collector-import-export' ) ) );
			exit;
		}

		// Process the import file
		if ( $file_extension === 'csv' ) {
			$result = $this->import_from_csv( $_FILES['import_file']['tmp_name'], $import_type );
		} else {
			$result = $this->import_from_json( $_FILES['import_file']['tmp_name'], $import_type );
		}

		// Redirect with result
		if ( is_wp_error( $result ) ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'error'        => 'import_failed',
						'error_detail' => rawurlencode( $result->get_error_message() ),
					),
					admin_url( 'admin.php?page=wp-movie-collector-import-export' )
				)
			);
		} else {
			wp_safe_redirect(
				add_query_arg(
					array(
						'message' => 'import_success',
						'count'   => $result,
					),
					admin_url( 'admin.php?page=wp-movie-collector-import-export' )
				)
			);
		}

		exit;
	}

	/**
	 * Generate and download a CSV template.
	 *
	 * @since    1.0.0
	 */
	public function download_csv_template() {
		// Check if this is a template download request
		if ( ! isset( $_GET['action'] ) || sanitize_text_field( wp_unslash( $_GET['action'] ) ) !== 'wp_movie_collector_download_csv_template' ) {
			return;
		}

		// Check nonce
		if ( ! isset( $_GET['wp_movie_collector_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_GET['wp_movie_collector_nonce'] ), 'wp_movie_collector_template' ) ) {
			wp_die( __( 'Security check failed.', 'wp-movie-collector' ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to perform this action.', 'wp-movie-collector' ) );
		}

		// Set headers for download
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="wp-movie-collector-template.csv"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Create CSV headers
		$output = fopen( 'php://output', 'w' );
		fputcsv(
			$output,
			array(
				'title',
				'release_year',
				'format',
				'region_code',
				'barcode',
				'director',
				'studio',
				'actors',
				'genre',
				'special_features',
				'cover_image_url',
				'description',
				'acquisition_date',
				'box_set_id',
				'custom_notes',
				'type',
			)
		);

		// Add a sample row
		fputcsv(
			$output,
			array(
				'The Matrix',
				'1999',
				'DVD',
				'R1',
				'085391832621',
				'Lana Wachowski, Lilly Wachowski',
				'Warner Bros.',
				'Keanu Reeves, Laurence Fishburne, Carrie-Anne Moss',
				'Science Fiction, Action',
				'Director\'s Commentary, Behind the Scenes',
				'https://example.com/images/the-matrix.jpg',
				'A computer hacker learns about the true nature of reality.',
				'2023-01-15',
				'',
				'Purchased at garage sale',
				'movie',
			)
		);

		// Add another sample row for box set
		fputcsv(
			$output,
			array(
				'The Lord of the Rings Trilogy',
				'2004',
				'Blu-ray',
				'ABC',
				'024543983477',
				'',
				'New Line Cinema',
				'',
				'Fantasy, Adventure',
				'Extended Editions, Documentaries',
				'https://example.com/images/lotr-trilogy.jpg',
				'The complete Lord of the Rings trilogy.',
				'2023-02-20',
				'',
				'',
				'box_set',
			)
		);

		fclose( $output );
		exit;
	}

	/**
	 * Bulk-sync all custom-table movies and box sets into CPT posts.
	 *
	 * Backfills `movie`/`box_set` posts for data that pre-dates the sync
	 * feature (or repairs posts that drifted). Triggered from the
	 * Import/Export admin page.
	 *
	 * @since    1.4.0
	 */
	public function process_sync_posts() {
		// Check nonce.
		if ( ! isset( $_POST['wp_movie_collector_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wp_movie_collector_nonce'] ), 'wp_movie_collector_sync_posts' ) ) {
			wp_die( __( 'Security check failed.', 'wp-movie-collector' ), '', array( 'response' => 403 ) );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to perform this action.', 'wp-movie-collector' ), '', array( 'response' => 403 ) );
		}

		$sync   = new WP_Movie_Collector_Sync();
		$counts = $sync->sync_all();

		$redirect = add_query_arg(
			array(
				'message'        => 'synced',
				'synced_movies'  => (int) $counts['movies'],
				'synced_box_sets' => (int) $counts['box_sets'],
			),
			admin_url( 'admin.php?page=wp-movie-collector-import-export' )
		);

		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Get export data based on type.
	 *
	 * @since    1.0.0
	 * @param    string $export_type    Type of data to export (all, movies_only, box_sets).
	 * @return   array                     The data to export.
	 */
	private function get_export_data( $export_type ) {
		global $wpdb;
		$db   = new WP_Movie_Collector_DB();
		$data = array();

		// Get movies if needed
		if ( $export_type === 'all' || $export_type === 'movies_only' ) {
			$movies = $wpdb->get_results( "SELECT * FROM {$db->get_movies_table()}", ARRAY_A );

			foreach ( $movies as &$movie ) {
				$movie['type'] = 'movie';
			}

			$data = array_merge( $data, $movies );
		}

		// Get box sets if needed
		if ( $export_type === 'all' || $export_type === 'box_sets' ) {
			$box_sets = $wpdb->get_results( "SELECT * FROM {$db->get_box_sets_table()}", ARRAY_A );

			foreach ( $box_sets as &$box_set ) {
				$box_set['type'] = 'box_set';
			}

			$data = array_merge( $data, $box_sets );
		}

		return $data;
	}

	/**
	 * Export data as CSV.
	 *
	 * @since    1.0.0
	 * @param    array  $data          The data to export.
	 * @param    string $export_type    Type of data exported (all, movies_only, box_sets).
	 */
	private function export_as_csv( $data, $export_type ) {
		// Set filename based on export type
		$filename = 'wp-movie-collector-export-';
		switch ( $export_type ) {
			case 'movies_only':
				$filename .= 'movies-';
				break;
			case 'box_sets':
				$filename .= 'box-sets-';
				break;
			default:
				$filename .= 'all-';
				break;
		}
		$filename .= date( 'Y-m-d' ) . '.csv';
		$filename  = sanitize_file_name( $filename );

		// Set headers for download
		header( 'Content-Type: text/csv' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Output the CSV
		$output = fopen( 'php://output', 'w' );

		// Write header row if there's data
		if ( ! empty( $data ) ) {
			// For an "all" export, $data merges movie and box-set rows from two
			// tables with different column sets. Build the union of all keys
			// (not just the first row's) and write every row in that fixed
			// order, filling missing keys with '', so box-set rows aren't
			// written positionally under movie headers.
			$headers = array();
			foreach ( $data as $row ) {
				$headers += $row;
			}
			$headers = array_keys( $headers );
			fputcsv( $output, $headers );

			// Write data rows in the unified column order.
			foreach ( $data as $row ) {
				$ordered = array();
				foreach ( $headers as $key ) {
					$ordered[] = isset( $row[ $key ] ) ? $row[ $key ] : '';
				}
				fputcsv( $output, $ordered );
			}
		} else {
			// Just write headers for an empty file
			fputcsv(
				$output,
				array(
					'id',
					'title',
					'release_year',
					'format',
					'region_code',
					'barcode',
					'director',
					'studio',
					'actors',
					'genre',
					'special_features',
					'cover_image_url',
					'description',
					'acquisition_date',
					'box_set_id',
					'api_source',
					'custom_notes',
					'created_at',
					'updated_at',
					'type',
				)
			);
		}

		fclose( $output );
	}

	/**
	 * Export data as JSON.
	 *
	 * @since    1.0.0
	 * @param    array  $data          The data to export.
	 * @param    string $export_type    Type of data exported (all, movies_only, box_sets).
	 */
	private function export_as_json( $data, $export_type ) {
		// Set filename based on export type
		$filename = 'wp-movie-collector-export-';
		switch ( $export_type ) {
			case 'movies_only':
				$filename .= 'movies-';
				break;
			case 'box_sets':
				$filename .= 'box-sets-';
				break;
			default:
				$filename .= 'all-';
				break;
		}
		$filename .= date( 'Y-m-d' ) . '.json';
		$filename  = sanitize_file_name( $filename );

		// Set headers for download
		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		// Output the JSON
		echo wp_json_encode( $data, JSON_PRETTY_PRINT );
	}

	/**
	 * Import data from a CSV file.
	 *
	 * @since    1.0.0
	 * @param    string $file_path      Path to the CSV file.
	 * @param    string $import_type    Type of import (append or replace).
	 * @return   int|WP_Error              Count of imported items or error.
	 */
	private function import_from_csv( $file_path, $import_type ) {
		// Open the file
		$handle = fopen( $file_path, 'r' );
		if ( ! $handle ) {
			return new WP_Error( 'file_error', __( 'Could not open file for import.', 'wp-movie-collector' ) );
		}

		// Read the header row
		$headers = fgetcsv( $handle );
		if ( ! $headers ) {
			fclose( $handle );
			return new WP_Error( 'file_error', __( 'Invalid CSV file format.', 'wp-movie-collector' ) );
		}

		// Clean empty cells from headers
		$headers = array_map( 'trim', $headers );

		// Stage every row before touching the database so that a parse error
		// or a failed insert in replace mode can't destroy the existing
		// collection (see persist_import()).
		$movies   = array();
		$box_sets = array();

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			// Skip empty rows
			if ( empty( $row[0] ) ) {
				continue;
			}

			// Create an associative array from the row. Skip blank header
			// columns (e.g. a trailing delimiter / empty column) so they don't
			// become an empty '' key that would fail $wpdb->insert() with an
			// "Unknown column ''" error.
			$item = array();
			foreach ( $headers as $i => $header ) {
				if ( '' === $header ) {
					continue;
				}
				$item[ $header ] = isset( $row[ $i ] ) ? $row[ $i ] : '';
			}

			// Set timestamps
			$item['created_at'] = current_time( 'mysql' );
			$item['updated_at'] = current_time( 'mysql' );

			// Determine if this is a movie or box set, then drop the
			// non-column id/type fields.
			$type      = isset( $item['type'] ) ? $item['type'] : 'movie';
			$source_id = isset( $item['id'] ) ? (int) $item['id'] : 0;
			unset( $item['id'], $item['type'] );

			if ( 'box_set' === $type ) {
				// Remember the exported id so movie box_set_id references can be
				// remapped to the ids assigned on insert (see persist_import()).
				$item['__source_id'] = $source_id;
				$box_sets[]          = $item;
			} else {
				$movies[] = $item;
			}
		}

		fclose( $handle );

		return $this->persist_import( $movies, $box_sets, $import_type );
	}

	/**
	 * Persist staged import rows, optionally replacing the existing collection.
	 *
	 * In replace mode the destructive delete and the inserts run inside a
	 * single transaction using DELETE (not TRUNCATE, which implicitly commits
	 * and cannot be rolled back); if any row fails to insert the whole import
	 * is rolled back so the user's collection is never left partially wiped.
	 *
	 * @since 1.3.0
	 * @param array  $movies      Staged movie rows.
	 * @param array  $box_sets    Staged box-set rows.
	 * @param string $import_type 'append' or 'replace'.
	 * @return int|WP_Error Count of imported items, or WP_Error on failure.
	 */
	private function persist_import( array $movies, array $box_sets, $import_type ) {
		global $wpdb;
		$db      = new WP_Movie_Collector_DB();
		$replace = ( 'replace' === $import_type );

		if ( $replace ) {
			// Never wipe the collection for an import that has nothing to insert
			// (e.g. an empty file, or a payload with no valid rows). Without this
			// guard replace mode would delete everything and commit an empty
			// import.
			if ( empty( $movies ) && empty( $box_sets ) ) {
				return new WP_Error(
					'import_empty',
					__( 'The import file contained no valid rows; the existing collection was left unchanged.', 'wp-movie-collector' )
				);
			}

			// Replace mode deletes the whole collection before re-inserting. That
			// is only safe if a failed import can be rolled back, which requires a
			// transactional engine — bail before deleting anything otherwise.
			if ( ! $this->tables_support_transactions( $db ) ) {
				return new WP_Error(
					'import_no_transaction',
					__( 'Replace-mode import requires transactional (InnoDB) database tables so a failed import can be rolled back. The plugin tables use a non-transactional engine; aborting to avoid data loss.', 'wp-movie-collector' )
				);
			}

			if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
				return new WP_Error(
					'import_failed',
					__( 'Could not start a database transaction for the import; no changes were made.', 'wp-movie-collector' )
				);
			}
			// Each DELETE must succeed; a failed clear (permissions, missing
			// table) would otherwise leave old rows in place while new rows are
			// appended and later committed. Roll back and bail on any failure.
			$delete_tables = array(
				$db->get_relationships_table(),
				$db->get_movies_table(),
				$db->get_box_sets_table(),
			);
			foreach ( $delete_tables as $delete_table ) {
				if ( false === $wpdb->query( "DELETE FROM {$delete_table}" ) ) {
					$wpdb->query( 'ROLLBACK' );
					return new WP_Error(
						'import_failed',
						__( 'Could not clear the existing collection for the import; no changes were made.', 'wp-movie-collector' )
					);
				}
			}
		}

		$count  = 0;
		$failed = 0;

		// Insert box sets first, building a map from each row's exported id to
		// the id assigned on insert. Movie rows carry a denormalized box_set_id
		// that must be translated to the new id: in replace mode the old ids were
		// just deleted, and even in append mode AUTO_INCREMENT assigns fresh ids.
		// Pass true to skip per-row cache invalidation during the bulk import.
		//
		// Each staged row is run through the shared field validator/sanitizer
		// (issue #65) so imported CSV/JSON content is sanitized (no stored XSS)
		// and integrity-checked (year range, format/region whitelist) exactly
		// like the add/edit forms. The validator's output contains only real
		// table columns, which also drops any movie-only fields a shared CSV
		// header carries into a box-set row (avoiding "Unknown column" on
		// insert). allow_duplicate skips the add-only duplicate check, which
		// doesn't apply to a bulk import. Rows that fail validation are skipped
		// (and counted as failures, which rolls back a replace-mode import).
		$id_map = array();
		foreach ( $box_sets as $box_set ) {
			$source_id = isset( $box_set['__source_id'] ) ? (int) $box_set['__source_id'] : 0;
			unset( $box_set['__source_id'] );

			$box_set['allow_duplicate'] = true;
			$validated                  = $this->validate_box_set_fields( $box_set );
			if ( ! empty( $validated['errors'] ) ) {
				++$failed;
				continue;
			}

			$new_id = $db->insert_box_set( $validated['data'], true );
			if ( $new_id ) {
				++$count;
				if ( $source_id > 0 ) {
					$id_map[ $source_id ] = (int) $new_id;
				}
			} else {
				++$failed;
			}
		}

		foreach ( $movies as $movie ) {
			// Resolve box-set membership separately from content validation: the
			// movie validator rejects a box_set_id that doesn't exist, but on
			// import the referenced set's id is remapped (replace mode just
			// deleted the old ids). Validate the content without box_set_id, then
			// re-apply the remapped id afterward.
			$old = ! empty( $movie['box_set_id'] ) ? (int) $movie['box_set_id'] : 0;
			unset( $movie['box_set_id'] );

			$movie['allow_duplicate'] = true;
			$validated                = $this->validate_movie_fields( $movie );
			if ( ! empty( $validated['errors'] ) ) {
				++$failed;
				continue;
			}
			$row = $validated['data'];

			// The relationships table (which most lookups use) is only repopulated
			// for box sets imported alongside this movie; replace mode cleared it.
			$linked_box_set_id = 0;
			if ( $old ) {
				if ( isset( $id_map[ $old ] ) ) {
					$row['box_set_id'] = $id_map[ $old ];
					$linked_box_set_id = $id_map[ $old ];
				} elseif ( ! $replace ) {
					// Append mode: keep the original reference (the set may exist).
					$row['box_set_id'] = $old;
				}
				// Replace mode + unmapped: leave box_set_id unset; the old ids were wiped.
			}

			$new_movie_id = $db->insert_movie( $row, true );
			if ( $new_movie_id ) {
				++$count;
				// Mirror the membership into the relationships table so box sets
				// list their movies after import (replace mode wiped it).
				if ( $linked_box_set_id && false === $db->add_movie_to_box_set( (int) $new_movie_id, $linked_box_set_id ) ) {
					++$failed;
				}
			} else {
				++$failed;
			}
		}

		if ( $replace ) {
			if ( $failed > 0 ) {
				// Don't leave the collection destroyed by a partial import.
				$wpdb->query( 'ROLLBACK' );
				return new WP_Error(
					'import_failed',
					sprintf(
						/* translators: %d: number of rows that failed to import */
						_n(
							'%d row failed to import; no changes were made.',
							'%d rows failed to import; no changes were made.',
							$failed,
							'wp-movie-collector'
						),
						$failed
					)
				);
			}
			if ( false === $wpdb->query( 'COMMIT' ) ) {
				// A failed COMMIT means the import isn't durable; roll back and
				// report failure rather than misreporting success.
				$wpdb->query( 'ROLLBACK' );
				return new WP_Error(
					'import_failed',
					__( 'Could not commit the import to the database; no changes were made.', 'wp-movie-collector' )
				);
			}
		}

		// Invalidate stats cache once after all imports complete.
		$db->invalidate_stats_cache();

		return $count;
	}

	/**
	 * Whether the plugin's tables use a transactional storage engine.
	 *
	 * Replace-mode import relies on transactions/ROLLBACK to avoid wiping the
	 * collection when an import fails. On a non-transactional engine (e.g.
	 * MyISAM) ROLLBACK is a silent no-op, so we must detect that and refuse to
	 * delete. An engine that can't be determined (null) is treated as
	 * acceptable so imports aren't blocked when the lookup is unavailable.
	 *
	 * @since 1.3.0
	 * @param WP_Movie_Collector_DB $db Database helper.
	 * @return bool True if all plugin tables are transactional (or unknown).
	 */
	private function tables_support_transactions( $db ) {
		global $wpdb;

		$tables = array(
			$db->get_movies_table(),
			$db->get_box_sets_table(),
			$db->get_relationships_table(),
		);

		foreach ( $tables as $table ) {
			$engine = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = %s',
					$table
				)
			);

			if ( null !== $engine && 'innodb' !== strtolower( (string) $engine ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Import data from a JSON file.
	 *
	 * @since    1.0.0
	 * @param    string $file_path      Path to the JSON file.
	 * @param    string $import_type    Type of import (append or replace).
	 * @return   int|WP_Error              Count of imported items or error.
	 */
	private function import_from_json( $file_path, $import_type ) {
		// Read the file content. Use a strict false check so a valid-but-falsy
		// payload (e.g. a file containing just "0") isn't treated as a read
		// failure — JSON validation below handles the contents.
		$json_data = file_get_contents( $file_path );
		if ( false === $json_data ) {
			return new WP_Error( 'file_error', __( 'Could not read file for import.', 'wp-movie-collector' ) );
		}

		// Decode the JSON
		$data = json_decode( $json_data, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 'json_error', __( 'Invalid JSON format.', 'wp-movie-collector' ) );
		}

		// A syntactically valid JSON scalar (e.g. "hello", 42, null) or object
		// (e.g. {"items":[]}) passes the error check above; require a JSON list
		// of items here, before any destructive operation, so a malformed file
		// can't wipe the collection in replace mode.
		if ( ! is_array( $data ) || ! array_is_list( $data ) ) {
			return new WP_Error( 'json_error', __( 'Invalid JSON format: expected an array of items.', 'wp-movie-collector' ) );
		}

		// Stage every row before touching the database (see persist_import()).
		$movies   = array();
		$box_sets = array();

		foreach ( $data as $item ) {
			// Skip empty or malformed items
			if ( ! is_array( $item ) || empty( $item['title'] ) ) {
				continue;
			}

			// Set timestamps
			$item['created_at'] = current_time( 'mysql' );
			$item['updated_at'] = current_time( 'mysql' );

			// Determine if this is a movie or box set, then drop the
			// non-column id/type fields.
			$type      = isset( $item['type'] ) ? $item['type'] : 'movie';
			$source_id = isset( $item['id'] ) ? (int) $item['id'] : 0;
			unset( $item['id'], $item['type'] );

			if ( 'box_set' === $type ) {
				// Remember the exported id so movie box_set_id references can be
				// remapped to the ids assigned on insert (see persist_import()).
				$item['__source_id'] = $source_id;
				$box_sets[]          = $item;
			} else {
				$movies[] = $item;
			}
		}

		return $this->persist_import( $movies, $box_sets, $import_type );
	}

	/**
	 * AJAX handler for barcode lookup.
	 *
	 * @since    1.0.0
	 */
	public function ajax_barcode_lookup() {
		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'wp_movie_collector_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 'wp-movie-collector' ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions.', 'wp-movie-collector' ) );
		}

		// Check if barcode is provided
		if ( empty( $_POST['barcode'] ) ) {
			wp_send_json_error( __( 'No barcode provided.', 'wp-movie-collector' ) );
		}

		$barcode       = sanitize_text_field( wp_unslash( $_POST['barcode'] ) );
		$valid_contexts = array( 'movie', 'box_set' );
		$context        = isset( $_POST['context'] ) ? sanitize_text_field( wp_unslash( $_POST['context'] ) ) : 'movie';
		if ( ! in_array( $context, $valid_contexts, true ) ) {
			$context = 'movie';
		}

		$db = new WP_Movie_Collector_DB();

		// Check if barcode already exists in the database (check both tables).
		$movie   = $db->get_movie_by_barcode( $barcode );
		$box_set = $db->get_box_set_by_barcode( $barcode );

		// Enrich whichever records exist so the right one can be returned.
		if ( $box_set ) {
			$box_set['existing_in_db'] = true;
			$box_set['existing_type']  = 'box_set';
			$box_set['edit_url']       = admin_url( 'admin.php?page=wp-movie-collector-edit-box-set&id=' . intval( $box_set['id'] ) );
		}
		if ( $movie ) {
			$movie['existing_in_db'] = true;
			$movie['existing_type']  = 'movie';
			$movie['edit_url']       = admin_url( 'admin.php?page=wp-movie-collector-edit-movie&id=' . intval( $movie['id'] ) );
		}

		// Prefer the record matching the requested context, then fall back to a
		// match in the other table, then fall through to the API lookup.
		$context_match    = ( 'box_set' === $context ) ? $box_set : $movie;
		$cross_type_match = ( 'box_set' === $context ) ? $movie : $box_set;

		if ( $context_match ) {
			wp_send_json_success( $context_match );
		}
		if ( $cross_type_match ) {
			wp_send_json_success( $cross_type_match );
		}

		// If not in our database, try to look it up via API.
		$api    = new WP_Movie_Collector_API();
		$result = $api->lookup_by_barcode( $barcode );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for movie search.
	 *
	 * @since    1.0.0
	 */
	public function ajax_movie_search() {
		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'wp_movie_collector_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 'wp-movie-collector' ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions.', 'wp-movie-collector' ) );
		}

		// Check if title is provided
		if ( empty( $_POST['title'] ) ) {
			wp_send_json_error( __( 'No title provided.', 'wp-movie-collector' ) );
		}

		$title = sanitize_text_field( wp_unslash( $_POST['title'] ) );
		$year  = isset( $_POST['year'] ) ? intval( $_POST['year'] ) : null;

		// Search for movie via API
		$api     = new WP_Movie_Collector_API();
		$results = $api->search_movie_by_title( $title, $year );

		if ( is_wp_error( $results ) ) {
			wp_send_json_error( $results->get_error_message() );
		}

		wp_send_json_success( $results );
	}

	/**
	 * AJAX handler for getting movie details.
	 *
	 * @since    1.0.0
	 */
	public function ajax_get_movie_details() {
		// Check nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'wp_movie_collector_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 'wp-movie-collector' ) );
		}

		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions.', 'wp-movie-collector' ) );
		}

		// Check if movie_id is provided
		if ( empty( $_POST['movie_id'] ) ) {
			wp_send_json_error( __( 'No movie ID provided.', 'wp-movie-collector' ) );
		}

		$movie_id = intval( $_POST['movie_id'] );

		// Get movie details via API
		$api   = new WP_Movie_Collector_API();
		$movie = $api->get_movie_details( $movie_id );

		if ( is_wp_error( $movie ) ) {
			wp_send_json_error( $movie->get_error_message() );
		}

		wp_send_json_success( $movie );
	}

	/**
	 * AJAX handler for checking duplicate movies.
	 *
	 * @since    1.0.0
	 */
	public function ajax_check_duplicate_movie() {
		// Check nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'wp_movie_collector_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 'wp-movie-collector' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions.', 'wp-movie-collector' ) );
		}

		$title      = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$year       = isset( $_POST['year'] ) ? intval( $_POST['year'] ) : 0;
		$barcode    = isset( $_POST['barcode'] ) ? sanitize_text_field( wp_unslash( $_POST['barcode'] ) ) : '';
		$exclude_id = isset( $_POST['exclude_id'] ) ? intval( $_POST['exclude_id'] ) : 0;

		if ( empty( $title ) && empty( $barcode ) ) {
			wp_send_json_success( array( 'has_duplicates' => false ) );
		}

		$db         = new WP_Movie_Collector_DB();
		$duplicates = $db->find_duplicate_movies( $title, $year, $barcode, $exclude_id );

		// Cross-type barcode check: also look in box sets table.
		$cross_type_match = null;
		if ( empty( $duplicates['barcode_match'] ) && ! empty( $barcode ) ) {
			$box_set_dupes = $db->find_duplicate_box_sets( '', 0, $barcode );
			if ( ! empty( $box_set_dupes['barcode_match'] ) ) {
				$cross_type_match       = $box_set_dupes['barcode_match'];
				$cross_type_match['type'] = 'box_set';
			}
		}

		$has_duplicates = ! empty( $duplicates['barcode_match'] ) || ! empty( $duplicates['title_matches'] ) || ! empty( $cross_type_match );

		wp_send_json_success(
			array(
				'has_duplicates'   => $has_duplicates,
				'barcode_match'    => $duplicates['barcode_match'],
				'title_matches'    => $duplicates['title_matches'],
				'cross_type_match' => $cross_type_match,
			)
		);
	}

	/**
	 * AJAX handler for checking duplicate box sets.
	 *
	 * @since    1.0.0
	 */
	public function ajax_check_duplicate_box_set() {
		// Check nonce.
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['nonce'] ), 'wp_movie_collector_nonce' ) ) {
			wp_send_json_error( __( 'Security check failed.', 'wp-movie-collector' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'You do not have sufficient permissions.', 'wp-movie-collector' ) );
		}

		$title      = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$year       = isset( $_POST['year'] ) ? intval( $_POST['year'] ) : 0;
		$barcode    = isset( $_POST['barcode'] ) ? sanitize_text_field( wp_unslash( $_POST['barcode'] ) ) : '';
		$exclude_id = isset( $_POST['exclude_id'] ) ? intval( $_POST['exclude_id'] ) : 0;

		if ( empty( $title ) && empty( $barcode ) ) {
			wp_send_json_success( array( 'has_duplicates' => false ) );
		}

		$db         = new WP_Movie_Collector_DB();
		$duplicates = $db->find_duplicate_box_sets( $title, $year, $barcode, $exclude_id );

		// Cross-type barcode check: also look in movies table.
		$cross_type_match = null;
		if ( empty( $duplicates['barcode_match'] ) && ! empty( $barcode ) ) {
			$movie_dupes = $db->find_duplicate_movies( '', 0, $barcode );
			if ( ! empty( $movie_dupes['barcode_match'] ) ) {
				$cross_type_match       = $movie_dupes['barcode_match'];
				$cross_type_match['type'] = 'movie';
			}
		}

		$has_duplicates = ! empty( $duplicates['barcode_match'] ) || ! empty( $duplicates['title_matches'] ) || ! empty( $cross_type_match );

		wp_send_json_success(
			array(
				'has_duplicates'   => $has_duplicates,
				'barcode_match'    => $duplicates['barcode_match'],
				'title_matches'    => $duplicates['title_matches'],
				'cross_type_match' => $cross_type_match,
			)
		);
	}

	/**
	 * admin-post handler for clearing the API cache.
	 *
	 * Handles the Clear API Cache form (admin-post.php) and redirects back
	 * to the settings screen with a cache_cleared / cache_error notice.
	 *
	 * @since    1.0.0
	 */
	public function handle_clear_api_cache() {
		$redirect_url = admin_url( 'admin.php?page=wp-movie-collector-settings' );

		if ( ! isset( $_POST['wp_movie_collector_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wp_movie_collector_nonce'] ), 'wp_movie_collector_clear_cache' ) ) {
			wp_safe_redirect( add_query_arg( 'cache_error', 'nonce', $redirect_url ) );
			exit;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_safe_redirect( add_query_arg( 'cache_error', 'permission', $redirect_url ) );
			exit;
		}

		WP_Movie_Collector_API::clear_api_cache();

		wp_safe_redirect( add_query_arg( 'cache_cleared', '1', $redirect_url ) );
		exit;
	}

	/**
	 * admin-post handler for repairing the database tables.
	 *
	 * Recreates any missing tables and applies pending schema updates, then
	 * redirects back to the settings screen with a db_repaired /
	 * db_repair_error notice. Runs as a POST action (not a GET link) so the
	 * state change cannot be re-triggered by prefetchers or history.
	 *
	 * @since    1.0.0
	 */
	public function handle_repair_database() {
		$redirect_url = admin_url( 'admin.php?page=wp-movie-collector-settings' );

		if ( ! isset( $_POST['wp_movie_collector_nonce'] ) || ! wp_verify_nonce( wp_unslash( $_POST['wp_movie_collector_nonce'] ), 'wp_movie_collector_repair_db' ) ) {
			wp_safe_redirect( add_query_arg( 'db_repair_error', 'nonce', $redirect_url ) );
			exit;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_safe_redirect( add_query_arg( 'db_repair_error', 'permission', $redirect_url ) );
			exit;
		}

		global $wpdb;

		$db = new WP_Movie_Collector_DB();

		// Capture the last DB error after each step (before the SHOW TABLES
		// checks below overwrite it). create_tables()/update_tables() run many
		// queries and return no status, so $wpdb->last_error only reflects the
		// final query of each — a best-effort signal of a failed CREATE/ALTER.
		$wpdb->last_error = '';
		$db->create_tables();
		$create_error = $wpdb->last_error;

		$wpdb->last_error = '';
		$db->update_tables();
		$update_error = $wpdb->last_error;

		// The authoritative success signal for a "repair" is that the expected
		// tables now exist; verify all three. Combined with the per-step error
		// capture above, a partial failure can't be reported as success.
		$missing_table = false;
		foreach ( array( $db->get_movies_table(), $db->get_box_sets_table(), $db->get_relationships_table() ) as $table ) {
			$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );
			if ( $found !== $table ) {
				$missing_table = true;
				break;
			}
		}

		if ( $missing_table || '' !== $create_error || '' !== $update_error ) {
			wp_safe_redirect( add_query_arg( 'db_repair_error', $missing_table ? 'missing_table' : 'query', $redirect_url ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( 'db_repaired', '1', $redirect_url ) );
		exit;
	}

	/**
	 * Display admin notices when API issues are detected.
	 *
	 * Shows a warning banner when one or more external API providers
	 * have tripped the circuit breaker due to repeated failures.
	 *
	 * @since    1.0.0
	 */
	public function display_api_issue_notices() {
		if ( ! class_exists( 'WP_Movie_Collector_API_Client' ) ) {
			return;
		}

		$issues = WP_Movie_Collector_API_Client::get_api_issues();

		if ( empty( $issues ) ) {
			return;
		}

		$providers = array_keys( $issues );
		$list      = implode( ', ', array_map( 'strtoupper', $providers ) );

		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			sprintf(
				/* translators: %s: comma-separated list of API provider names */
				esc_html__( 'WP Movie Collector: The following API services are experiencing issues and have been temporarily paused: %s. They will be retried automatically after a cooldown period.', 'wp-movie-collector' ),
				esc_html( $list )
			)
		);
	}
}
