<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    WP_Movie_Collector
 */

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
        wp_enqueue_style('wp-movie-collector-admin', WP_MOVIE_COLLECTOR_PLUGIN_URL . 'admin/css/wp-movie-collector-admin.css', array(), WP_MOVIE_COLLECTOR_VERSION, 'all');
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
        wp_enqueue_script('jquery-ui-sortable');

        // Custom admin script
        wp_enqueue_script('wp-movie-collector-admin', WP_MOVIE_COLLECTOR_PLUGIN_URL . 'admin/js/wp-movie-collector-admin.js', array('jquery', 'jquery-ui-sortable'), WP_MOVIE_COLLECTOR_VERSION, false);

        // Localize the script with new data
        $localize_data = array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_movie_collector_nonce'),
            'messages' => array(
                'select_image' => __('Select or Upload Cover Image', 'wp-movie-collector'),
                'use_image' => __('Use this image', 'wp-movie-collector'),
                'required_fields' => __('Please fill out all required fields.', 'wp-movie-collector'),
                'confirm_delete_movie' => __('Are you sure you want to delete this movie?', 'wp-movie-collector'),
                'confirm_delete_box_set' => __('Are you sure you want to delete this box set?', 'wp-movie-collector'),
                'no_file_selected' => __('Please select a file to import.', 'wp-movie-collector'),
                'invalid_file_type' => __('Please select a CSV or JSON file.', 'wp-movie-collector')
            )
        );
        wp_localize_script('wp-movie-collector-admin', 'wp_movie_collector_admin', $localize_data);
    }

/**
 * Add plugin admin menu.
 *
 * @since    1.0.0
 */
public function add_plugin_admin_menu() {
    // Main menu item (Dashboard)
    add_menu_page(
        __('Movie Collection', 'wp-movie-collector'),
        __('Movies', 'wp-movie-collector'),
        'manage_options',
        'wp-movie-collector-dashboard', // Change this to point to dashboard explicitly
        array($this, 'display_plugin_admin_dashboard'),
        'dashicons-video-alt2',
        20
    );

    // Rename default submenu item to "Dashboard"
    global $submenu;
    if (isset($submenu['wp-movie-collector-dashboard'])) {
        $submenu['wp-movie-collector-dashboard'][0][0] = __('Dashboard', 'wp-movie-collector');
    }

    // Add New Movie submenu
    add_submenu_page(
        'wp-movie-collector-dashboard',
        __('Add New Movie', 'wp-movie-collector'),
        __('Add New Movie', 'wp-movie-collector'),
        'manage_options',
        'wp-movie-collector-add-movie',
        array($this, 'display_plugin_admin_add_movie')
    );

    // Add New Box Set submenu
    add_submenu_page(
        'wp-movie-collector-dashboard',
        __('Add New Box Set', 'wp-movie-collector'),
        __('Add New Box Set', 'wp-movie-collector'),
        'manage_options',
        'wp-movie-collector-add-box-set',
        array($this, 'display_plugin_admin_add_box_set')
    );

    // Import/Export submenu
    add_submenu_page(
        'wp-movie-collector-dashboard',
        __('Import/Export', 'wp-movie-collector'),
        __('Import/Export', 'wp-movie-collector'),
        'manage_options',
        'wp-movie-collector-import-export',
        array($this, 'display_plugin_admin_import_export')
    );

    // Settings submenu
    add_submenu_page(
        'wp-movie-collector-dashboard',
        __('Settings', 'wp-movie-collector'),
        __('Settings', 'wp-movie-collector'),
        'manage_options',
        'wp-movie-collector-settings',
        array($this, 'display_plugin_admin_settings')
    );

    // Remove "All Box Sets" if it still appears
    add_action('admin_menu', function() {
        remove_submenu_page('wp-movie-collector-dashboard', 'edit.php?post_type=box_set');
    }, 99);
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
     * Display the add movie page.
     *
     * @since    1.0.0
     */
    public function display_plugin_admin_add_movie() {
        include_once WP_MOVIE_COLLECTOR_PLUGIN_DIR . 'admin/partials/wp-movie-collector-admin-add-movie.php';
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
        register_setting('wp_movie_collector_settings', 'wp_movie_collector_tmdb_api_key');
        register_setting('wp_movie_collector_settings', 'wp_movie_collector_omdb_api_key');
        register_setting('wp_movie_collector_settings', 'wp_movie_collector_barcode_api_key');

        add_settings_section(
            'wp_movie_collector_api_settings',
            __('API Settings', 'wp-movie-collector'),
            array($this, 'settings_section_callback'),
            'wp_movie_collector_settings'
        );

        add_settings_field(
            'wp_movie_collector_tmdb_api_key',
            __('TMDb API Key', 'wp-movie-collector'),
            array($this, 'tmdb_api_key_callback'),
            'wp_movie_collector_settings',
            'wp_movie_collector_api_settings'
        );

        add_settings_field(
            'wp_movie_collector_omdb_api_key',
            __('OMDb API Key', 'wp-movie-collector'),
            array($this, 'omdb_api_key_callback'),
            'wp_movie_collector_settings',
            'wp_movie_collector_api_settings'
        );

        add_settings_field(
            'wp_movie_collector_upc_api_key',
            __('UPC Database API Key', 'wp-movie-collector'),
            array($this, 'upc_api_key_callback'),
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
        if (!isset($_POST['wp_movie_collector_add_movie_submit'])) {
            return;
        }

        // Check nonce
        if (!isset($_POST['wp_movie_collector_nonce']) || !wp_verify_nonce($_POST['wp_movie_collector_nonce'], 'wp_movie_collector_add_movie')) {
            wp_die(__('Security check failed.', 'wp-movie-collector'));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'wp-movie-collector'));
        }

        // Validate and sanitize movie data
        $movie = $this->validate_and_sanitize_movie_data($_POST['movie']);

        // Insert the movie into the database
        $db = new WP_Movie_Collector_DB();
        $movie_id = $db->insert_movie($movie);

        if ($movie_id) {
            // If this movie is part of a box set, create the relationship
            if (!empty($movie['box_set_id'])) {
                $db->add_movie_to_box_set($movie_id, $movie['box_set_id']);
            }

            // Redirect to the movie list with success message
            wp_redirect(add_query_arg('message', 'movie_added', admin_url('admin.php?page=wp-movie-collector')));
            exit;
        } else {
            // Redirect back to form with error message
            wp_redirect(add_query_arg('error', 'db_error', admin_url('admin.php?page=wp-movie-collector-add-movie')));
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
        if (!isset($_POST['wp_movie_collector_add_box_set_submit'])) {
            return;
        }

        // Check nonce
        if (!isset($_POST['wp_movie_collector_nonce']) || !wp_verify_nonce($_POST['wp_movie_collector_nonce'], 'wp_movie_collector_add_box_set')) {
            wp_die(__('Security check failed.', 'wp-movie-collector'));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'wp-movie-collector'));
        }

        // Validate and sanitize box set data
        $box_set = $this->validate_and_sanitize_box_set_data($_POST['box_set']);

        // Insert the box set into the database
        $db = new WP_Movie_Collector_DB();
        $box_set_id = $db->insert_box_set($box_set);

        if ($box_set_id) {
            // Redirect to the box set list with success message
            wp_redirect(add_query_arg('message', 'box_set_added', admin_url('admin.php?page=wp-movie-collector-box-sets')));
            exit;
        } else {
            // Redirect back to form with error message
            wp_redirect(add_query_arg('error', 'db_error', admin_url('admin.php?page=wp-movie-collector-add-box-set')));
            exit;
        }
    }

    /**
     * Validate and sanitize movie form data.
     *
     * @since    1.0.0
     * @param    array    $movie    The raw movie data from the form.
     * @return   array              The validated and sanitized movie data.
     */
    private function validate_and_sanitize_movie_data($movie) {
        $sanitized = array();
        $errors = array();

        // Validate required fields
        if (empty($movie['title'])) {
            $errors[] = __('Title is required.', 'wp-movie-collector');
        } else {
            $sanitized['title'] = sanitize_text_field($movie['title']);
        }

        if (empty($movie['release_year'])) {
            $errors[] = __('Release year is required.', 'wp-movie-collector');
        } else {
            $year = intval($movie['release_year']);
            if ($year < 1900 || $year > intval(date('Y'))) {
                $errors[] = __('Release year must be between 1900 and the current year.', 'wp-movie-collector');
            } else {
                $sanitized['release_year'] = $year;
            }
        }

        if (empty($movie['format'])) {
            $errors[] = __('Format is required.', 'wp-movie-collector');
        } else {
            $valid_formats = array('DVD', 'Blu-ray', '4K UHD', 'VHS', 'LaserDisc');
            if (!in_array($movie['format'], $valid_formats)) {
                $errors[] = __('Invalid format selected.', 'wp-movie-collector');
            } else {
                $sanitized['format'] = sanitize_text_field($movie['format']);
            }
        }

        if (empty($movie['region_code'])) {
            $errors[] = __('Region code is required.', 'wp-movie-collector');
        } else {
            $valid_regions = array('R0', 'R1', 'R2', 'R3', 'R4', 'R5', 'R6', 'A', 'B', 'C', 'ABC');
            if (!in_array($movie['region_code'], $valid_regions)) {
                $errors[] = __('Invalid region code selected.', 'wp-movie-collector');
            } else {
                $sanitized['region_code'] = sanitize_text_field($movie['region_code']);
            }
        }

        // Optional fields
        $sanitized['barcode'] = !empty($movie['barcode']) ? sanitize_text_field($movie['barcode']) : '';
        $sanitized['director'] = !empty($movie['director']) ? sanitize_text_field($movie['director']) : '';
        $sanitized['studio'] = !empty($movie['studio']) ? sanitize_text_field($movie['studio']) : '';
        $sanitized['actors'] = !empty($movie['actors']) ? sanitize_textarea_field($movie['actors']) : '';
        $sanitized['genre'] = !empty($movie['genre']) ? sanitize_text_field($movie['genre']) : '';
        $sanitized['special_features'] = !empty($movie['special_features']) ? sanitize_textarea_field($movie['special_features']) : '';

        // Handle cover image (either URL or attachment ID)
        if (!empty($movie['cover_image_url'])) {
            $url = esc_url_raw($movie['cover_image_url']);
            if ($url === '') {
                $errors[] = __('Cover image URL is not valid.', 'wp-movie-collector');
            } else {
                $sanitized['cover_image_url'] = $url;
            }
        } else {
            $sanitized['cover_image_url'] = '';
        }

        // Handle cover image attachment ID
        if (!empty($movie['cover_image_id'])) {
            $cover_image_id = intval($movie['cover_image_id']);
            // Verify attachment exists and is an image
            $attachment = get_post($cover_image_id);
            if (!$attachment || $attachment->post_type !== 'attachment' || strpos($attachment->post_mime_type, 'image') === false) {
                $errors[] = __('Selected cover image is not valid.', 'wp-movie-collector');
            } else {
                $sanitized['cover_image_id'] = $cover_image_id;

                // If cover_image_url is empty, set it to the attachment URL
                if (empty($sanitized['cover_image_url'])) {
                    $sanitized['cover_image_url'] = wp_get_attachment_url($cover_image_id);
                }
            }
        }

        $sanitized['description'] = !empty($movie['description']) ? sanitize_textarea_field($movie['description']) : '';
        $sanitized['custom_notes'] = !empty($movie['custom_notes']) ? sanitize_textarea_field($movie['custom_notes']) : '';
        $sanitized['api_source'] = !empty($movie['api_source']) ? sanitize_text_field($movie['api_source']) : '';

        // Handle acquisition date
        if (!empty($movie['acquisition_date'])) {
            // Validate date format (YYYY-MM-DD)
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $movie['acquisition_date'], $matches)) {
                if (checkdate($matches[2], $matches[3], $matches[1])) {
                    $sanitized['acquisition_date'] = sanitize_text_field($movie['acquisition_date']);
                } else {
                    $errors[] = __('Acquisition date is not valid.', 'wp-movie-collector');
                }
            } else {
                $errors[] = __('Acquisition date must be in YYYY-MM-DD format.', 'wp-movie-collector');
            }
        }

        // Handle box set relationship
        if (!empty($movie['box_set_id'])) {
            $box_set_id = intval($movie['box_set_id']);
            // Verify box set exists
            global $wpdb;
            $db = new WP_Movie_Collector_DB();
            $box_set_exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$db->get_box_sets_table()} WHERE id = %d",
                    $box_set_id
                )
            );

            if (!$box_set_exists) {
                $errors[] = __('Selected box set does not exist.', 'wp-movie-collector');
            } else {
                $sanitized['box_set_id'] = $box_set_id;
            }
        }

        // If there are validation errors, redirect back with error message
        if (!empty($errors)) {
            // Store errors in transient
            set_transient('wp_movie_collector_form_errors', $errors, 60 * 5); // 5 minutes expiration
            wp_redirect(add_query_arg('error', 'validation', admin_url('admin.php?page=wp-movie-collector-add-movie')));
            exit;
        }

        return $sanitized;
    }

    /**
     * Validate and sanitize box set form data.
     *
     * @since    1.0.0
     * @param    array    $box_set    The raw box set data from the form.
     * @return   array                The validated and sanitized box set data.
     */
    private function validate_and_sanitize_box_set_data($box_set) {
        $sanitized = array();
        $errors = array();

        // Validate required fields
        if (empty($box_set['title'])) {
            $errors[] = __('Title is required.', 'wp-movie-collector');
        } else {
            $sanitized['title'] = sanitize_text_field($box_set['title']);
        }

        if (empty($box_set['release_year'])) {
            $errors[] = __('Release year is required.', 'wp-movie-collector');
        } else {
            $year = intval($box_set['release_year']);
            if ($year < 1900 || $year > intval(date('Y'))) {
                $errors[] = __('Release year must be between 1900 and the current year.', 'wp-movie-collector');
            } else {
                $sanitized['release_year'] = $year;
            }
        }

        if (empty($box_set['format'])) {
            $errors[] = __('Format is required.', 'wp-movie-collector');
        } else {
            $valid_formats = array('DVD', 'Blu-ray', '4K UHD', 'VHS', 'LaserDisc');
            if (!in_array($box_set['format'], $valid_formats)) {
                $errors[] = __('Invalid format selected.', 'wp-movie-collector');
            } else {
                $sanitized['format'] = sanitize_text_field($box_set['format']);
            }
        }

        if (empty($box_set['region_code'])) {
            $errors[] = __('Region code is required.', 'wp-movie-collector');
        } else {
            $valid_regions = array('R0', 'R1', 'R2', 'R3', 'R4', 'R5', 'R6', 'A', 'B', 'C', 'ABC');
            if (!in_array($box_set['region_code'], $valid_regions)) {
                $errors[] = __('Invalid region code selected.', 'wp-movie-collector');
            } else {
                $sanitized['region_code'] = sanitize_text_field($box_set['region_code']);
            }
        }

        // Optional fields
        $sanitized['barcode'] = !empty($box_set['barcode']) ? sanitize_text_field($box_set['barcode']) : '';

        // Handle cover image (either URL or attachment ID)
        if (!empty($box_set['cover_image_url'])) {
            $url = esc_url_raw($box_set['cover_image_url']);
            if ($url === '') {
                $errors[] = __('Cover image URL is not valid.', 'wp-movie-collector');
            } else {
                $sanitized['cover_image_url'] = $url;
            }
        } else {
            $sanitized['cover_image_url'] = '';
        }

        // Handle cover image attachment ID
        if (!empty($box_set['cover_image_id'])) {
            $cover_image_id = intval($box_set['cover_image_id']);
            // Verify attachment exists and is an image
            $attachment = get_post($cover_image_id);
            if (!$attachment || $attachment->post_type !== 'attachment' || strpos($attachment->post_mime_type, 'image') === false) {
                $errors[] = __('Selected cover image is not valid.', 'wp-movie-collector');
            } else {
                $sanitized['cover_image_id'] = $cover_image_id;

                // If cover_image_url is empty, set it to the attachment URL
                if (empty($sanitized['cover_image_url'])) {
                    $sanitized['cover_image_url'] = wp_get_attachment_url($cover_image_id);
                }
            }
        }

        $sanitized['description'] = !empty($box_set['description']) ? sanitize_textarea_field($box_set['description']) : '';
        $sanitized['special_features'] = !empty($box_set['special_features']) ? sanitize_textarea_field($box_set['special_features']) : '';
        $sanitized['custom_notes'] = !empty($box_set['custom_notes']) ? sanitize_textarea_field($box_set['custom_notes']) : '';
        $sanitized['api_source'] = !empty($box_set['api_source']) ? sanitize_text_field($box_set['api_source']) : '';

        // Handle acquisition date
        if (!empty($box_set['acquisition_date'])) {
            // Validate date format (YYYY-MM-DD)
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $box_set['acquisition_date'], $matches)) {
                if (checkdate($matches[2], $matches[3], $matches[1])) {
                    $sanitized['acquisition_date'] = sanitize_text_field($box_set['acquisition_date']);
                } else {
                    $errors[] = __('Acquisition date is not valid.', 'wp-movie-collector');
                }
            } else {
                $errors[] = __('Acquisition date must be in YYYY-MM-DD format.', 'wp-movie-collector');
            }
        }

        // If there are validation errors, redirect back with error message
        if (!empty($errors)) {
            // Store errors in transient
            set_transient('wp_movie_collector_form_errors', $errors, 60 * 5); // 5 minutes expiration
            wp_redirect(add_query_arg('error', 'validation', admin_url('admin.php?page=wp-movie-collector-add-box-set')));
            exit;
        }

        return $sanitized;
    }

    /**
     * Settings section callback.
     *
     * @since    1.0.0
     */
    public function settings_section_callback() {
        echo '<p>' . __('Enter your API keys for movie data services.', 'wp-movie-collector') . '</p>';
    }

    /**
     * TMDb API key field callback.
     *
     * @since    1.0.0
     */
    public function tmdb_api_key_callback() {
        $api_key = get_option('wp_movie_collector_tmdb_api_key');
        echo '<input type="text" name="wp_movie_collector_tmdb_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">' . __('Get your API key from <a href="https://www.themoviedb.org/settings/api" target="_blank">TMDb</a>.', 'wp-movie-collector') . '</p>';
    }

    /**
     * OMDb API key field callback.
     *
     * @since    1.0.0
     */
    public function omdb_api_key_callback() {
        $api_key = get_option('wp_movie_collector_omdb_api_key');
        echo '<input type="text" name="wp_movie_collector_omdb_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">' . __('Get your API key from <a href="https://www.omdbapi.com/apikey.aspx" target="_blank">OMDb</a>.', 'wp-movie-collector') . '</p>';
    }

    /**
     * BarcodeLookup API key field callback.
     *
     * @since    1.0.0
     */
    public function upc_api_key_callback() {
        $api_key = get_option('wp_movie_collector_barcode_api_key');
        echo '<input type="text" name="wp_movie_collector_barcode_api_key" value="' . esc_attr($api_key) . '" class="regular-text" />';
        echo '<p class="description">' . __('Get your API key from <a href="https://barcodelookup.com/api" target="_blank">BarcodeLookup</a>.', 'wp-movie-collector') . '</p>';
    }

    /**
     * Process the add movies to box set form.
     *
     * @since    1.0.0
     */
    public function process_add_movies_to_box_set() {
        // Check if this is an add movies request
        if (!isset($_POST['action']) || $_POST['action'] != 'wp_movie_collector_add_movies_to_box_set') {
            return;
        }

        // Check nonce
        if (!isset($_POST['wp_movie_collector_nonce']) || !wp_verify_nonce($_POST['wp_movie_collector_nonce'], 'wp_movie_collector_add_movies')) {
            wp_die(__('Security check failed.', 'wp-movie-collector'));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'wp-movie-collector'));
        }

        // Get box set ID
        if (!isset($_POST['box_set_id']) || empty($_POST['box_set_id'])) {
            wp_redirect(add_query_arg('error', 'invalid_box_set', admin_url('admin.php?page=wp-movie-collector-box-sets')));
            exit;
        }

        $box_set_id = intval($_POST['box_set_id']);

        // Check if movie IDs are provided
        if (!isset($_POST['movie_ids']) || !is_array($_POST['movie_ids']) || empty($_POST['movie_ids'])) {
            wp_redirect(add_query_arg('error', 'no_movies_selected', admin_url('admin.php?page=wp-movie-collector-manage-box-set&id=' . $box_set_id)));
            exit;
        }

        // Get database instance
        $db = new WP_Movie_Collector_DB();

        // Check if box set exists
        $box_set = $db->get_box_set($box_set_id);
        if (!$box_set) {
            wp_redirect(add_query_arg('error', 'invalid_box_set', admin_url('admin.php?page=wp-movie-collector-box-sets')));
            exit;
        }

        // Add each movie to the box set
        $count = 0;
        foreach ($_POST['movie_ids'] as $movie_id) {
            $movie_id = intval($movie_id);

            // Check if movie exists
            $movie = $db->get_movie($movie_id);
            if (!$movie) {
                continue;
            }

            // Add movie to box set
            $result = $db->add_movie_to_box_set($movie_id, $box_set_id);
            if ($result) {
                $count++;
            }
        }

        // Redirect back to manage box set page
        wp_redirect(add_query_arg(
            array(
                'message' => 'movie_added',
                'count' => $count
            ),
            admin_url('admin.php?page=wp-movie-collector-manage-box-set&id=' . $box_set_id)
        ));
        exit;
    }

    /**
     * Process removing a movie from a box set.
     *
     * @since    1.0.0
     */
    public function process_remove_movie_from_box_set() {
        // Check if this is a remove movie request
        if (!isset($_GET['action']) || $_GET['action'] != 'wp_movie_collector_remove_movie') {
            return;
        }

        // Get movie ID and box set ID
        $movie_id = isset($_GET['movie_id']) ? intval($_GET['movie_id']) : 0;
        $box_set_id = isset($_GET['box_set_id']) ? intval($_GET['box_set_id']) : 0;

        // Check nonce
        if (!isset($_GET['wp_movie_collector_nonce']) || !wp_verify_nonce($_GET['wp_movie_collector_nonce'], 'wp_movie_collector_remove_movie_' . $movie_id)) {
            wp_die(__('Security check failed.', 'wp-movie-collector'));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'wp-movie-collector'));
        }

        // Get database instance
        $db = new WP_Movie_Collector_DB();

        // Check if box set exists
        $box_set = $db->get_box_set($box_set_id);
        if (!$box_set) {
            wp_redirect(add_query_arg('error', 'invalid_box_set', admin_url('admin.php?page=wp-movie-collector-box-sets')));
            exit;
        }

        // Check if movie exists
        $movie = $db->get_movie($movie_id);
        if (!$movie) {
            wp_redirect(add_query_arg('error', 'invalid_movie', admin_url('admin.php?page=wp-movie-collector-manage-box-set&id=' . $box_set_id)));
            exit;
        }

        // Remove movie from box set
        $result = $db->remove_movie_from_box_set($movie_id, $box_set_id);

        // Redirect back to manage box set page
        if ($result) {
            wp_redirect(add_query_arg('message', 'movie_removed', admin_url('admin.php?page=wp-movie-collector-manage-box-set&id=' . $box_set_id)));
        } else {
            wp_redirect(add_query_arg('error', 'remove_failed', admin_url('admin.php?page=wp-movie-collector-manage-box-set&id=' . $box_set_id)));
        }
        exit;
    }

    /**
     * Process deleting a box set.
     *
     * @since    1.0.0
     */
    public function process_delete_box_set() {
        // Check if this is a delete box set request
        if (!isset($_GET['action']) || $_GET['action'] != 'wp_movie_collector_delete_box_set') {
            return;
        }

        // Get box set ID
        $box_set_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        // Check nonce
        if (!isset($_GET['wp_movie_collector_nonce']) || !wp_verify_nonce($_GET['wp_movie_collector_nonce'], 'wp_movie_collector_delete_box_set_' . $box_set_id)) {
            wp_die(__('Security check failed.', 'wp-movie-collector'));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'wp-movie-collector'));
        }

        // Get database instance
        $db = new WP_Movie_Collector_DB();

        // Delete the box set
        $result = $db->delete_box_set($box_set_id);

        // Redirect back to box sets page
        if ($result) {
            wp_redirect(add_query_arg('message', 'box_set_deleted', admin_url('admin.php?page=wp-movie-collector-box-sets')));
        } else {
            wp_redirect(add_query_arg('error', 'delete_failed', admin_url('admin.php?page=wp-movie-collector-box-sets')));
        }
        exit;
    }

    /**
     * Process reordering movies in a box set.
     *
     * @since    1.0.0
     */
    public function process_reorder_movies() {
        // Check if this is a reorder movies request
        if (!isset($_POST['action']) || $_POST['action'] != 'wp_movie_collector_reorder_movies') {
            return;
        }

        // Check nonce
        if (!isset($_POST['wp_movie_collector_nonce']) || !wp_verify_nonce($_POST['wp_movie_collector_nonce'], 'wp_movie_collector_reorder_movies')) {
            wp_die(__('Security check failed.', 'wp-movie-collector'));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'wp-movie-collector'));
        }

        // Get box set ID
        if (!isset($_POST['box_set_id']) || empty($_POST['box_set_id'])) {
            wp_redirect(add_query_arg('error', 'invalid_box_set', admin_url('admin.php?page=wp-movie-collector-box-sets')));
            exit;
        }

        $box_set_id = intval($_POST['box_set_id']);

        // Check if movie order is provided
        if (!isset($_POST['movie_order']) || !is_array($_POST['movie_order']) || empty($_POST['movie_order'])) {
            wp_redirect(add_query_arg('error', 'no_movie_order', admin_url('admin.php?page=wp-movie-collector-manage-box-set&id=' . $box_set_id)));
            exit;
        }

        // Get database instance
        $db = new WP_Movie_Collector_DB();

        // Check if box set exists
        $box_set = $db->get_box_set($box_set_id);
        if (!$box_set) {
            wp_redirect(add_query_arg('error', 'invalid_box_set', admin_url('admin.php?page=wp-movie-collector-box-sets')));
            exit;
        }

        // Update the order of movies
        global $wpdb;
        $relationship_table = $db->get_relationships_table();
        $success = true;

        // We need to modify the relationships table to add a display_order column if it doesn't exist
        $wpdb->query("ALTER TABLE {$relationship_table} ADD COLUMN display_order INT DEFAULT 0");

        // Begin transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Update the display order for each movie
            foreach ($_POST['movie_order'] as $order => $movie_id) {
                $movie_id = intval($movie_id);
                $order = intval($order) + 1; // Make order 1-based instead of 0-based

                $result = $wpdb->update(
                    $relationship_table,
                    array('display_order' => $order),
                    array('movie_id' => $movie_id, 'box_set_id' => $box_set_id)
                );

                if ($result === false) {
                    $success = false;
                    break;
                }
            }

            if ($success) {
                $wpdb->query('COMMIT');
            } else {
                $wpdb->query('ROLLBACK');
            }
        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            $success = false;
        }

        // Redirect back to manage box set page
        if ($success) {
            wp_redirect(add_query_arg('message', 'movies_reordered', admin_url('admin.php?page=wp-movie-collector-manage-box-set&id=' . $box_set_id)));
        } else {
            wp_redirect(add_query_arg('error', 'reorder_failed', admin_url('admin.php?page=wp-movie-collector-manage-box-set&id=' . $box_set_id)));
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
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_movie_collector_nonce')) {
            wp_send_json_error(__('Security check failed.', 'wp-movie-collector'));
        }

        // Check if query is provided
        if (!isset($_POST['query']) || empty($_POST['query'])) {
            wp_send_json_error(__('No search query provided.', 'wp-movie-collector'));
        }

        // Get box set ID
        $box_set_id = isset($_POST['box_set_id']) ? intval($_POST['box_set_id']) : 0;

        if (!$box_set_id) {
            wp_send_json_error(__('Invalid box set ID.', 'wp-movie-collector'));
        }

        // Get search query
        $query = sanitize_text_field($_POST['query']);

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
                '%' . $wpdb->esc_like($query) . '%',
                $box_set_id
            ),
            ARRAY_A
        );

        if (empty($movies)) {
            wp_send_json_error(__('No movies found matching your search.', 'wp-movie-collector'));
        }

        wp_send_json_success($movies);
    }

    /**
     * Process the export movies request.
     *
     * @since    1.0.0
     */
    public function process_export_movies() {
        // Check if this is an export request
        if (!isset($_POST['action']) || $_POST['action'] != 'wp_movie_collector_export_movies') {
            return;
        }

        // Check nonce
        if (!isset($_POST['wp_movie_collector_nonce']) || !wp_verify_nonce($_POST['wp_movie_collector_nonce'], 'wp_movie_collector_export')) {
            wp_die(__('Security check failed.', 'wp-movie-collector'));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'wp-movie-collector'));
        }

        // Get export type and format
        $export_type = isset($_POST['export_type']) ? sanitize_text_field($_POST['export_type']) : 'all';
        $export_format = isset($_POST['export_format']) ? sanitize_text_field($_POST['export_format']) : 'csv';

        // Get data based on export type
        $data = $this->get_export_data($export_type);

        // Generate the export file based on format
        if ($export_format === 'csv') {
            $this->export_as_csv($data, $export_type);
        } else {
            $this->export_as_json($data, $export_type);
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
        if (!isset($_POST['action']) || $_POST['action'] != 'wp_movie_collector_import_movies') {
            return;
        }

        // Check nonce
        if (!isset($_POST['wp_movie_collector_nonce']) || !wp_verify_nonce($_POST['wp_movie_collector_nonce'], 'wp_movie_collector_import')) {
            wp_die(__('Security check failed.', 'wp-movie-collector'));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'wp-movie-collector'));
        }

        // Check if file was uploaded
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            wp_redirect(add_query_arg('error', 'file_upload', admin_url('admin.php?page=wp-movie-collector-import-export')));
            exit;
        }

        // Get import type
        $import_type = isset($_POST['import_type']) ? sanitize_text_field($_POST['import_type']) : 'append';

        // Get file information
        $file_info = pathinfo($_FILES['import_file']['name']);
        $file_extension = strtolower($file_info['extension']);

        // Check file extension
        if (!in_array($file_extension, array('csv', 'json'))) {
            wp_redirect(add_query_arg('error', 'invalid_format', admin_url('admin.php?page=wp-movie-collector-import-export')));
            exit;
        }

        // Process the import file
        if ($file_extension === 'csv') {
            $result = $this->import_from_csv($_FILES['import_file']['tmp_name'], $import_type);
        } else {
            $result = $this->import_from_json($_FILES['import_file']['tmp_name'], $import_type);
        }

        // Redirect with result
        if (is_wp_error($result)) {
            wp_redirect(add_query_arg(
                array(
                    'error' => 'import_failed',
                    'message' => urlencode($result->get_error_message())
                ),
                admin_url('admin.php?page=wp-movie-collector-import-export')
            ));
        } else {
            wp_redirect(add_query_arg(
                array(
                    'message' => 'import_success',
                    'count' => $result
                ),
                admin_url('admin.php?page=wp-movie-collector-import-export')
            ));
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
        if (!isset($_GET['action']) || $_GET['action'] != 'wp_movie_collector_download_csv_template') {
            return;
        }

        // Check nonce
        if (!isset($_GET['wp_movie_collector_nonce']) || !wp_verify_nonce($_GET['wp_movie_collector_nonce'], 'wp_movie_collector_template')) {
            wp_die(__('Security check failed.', 'wp-movie-collector'));
        }

        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'wp-movie-collector'));
        }

        // Set headers for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="wp-movie-collector-template.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Create CSV headers
        $output = fopen('php://output', 'w');
        fputcsv($output, array(
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
            'type'
        ));

        // Add a sample row
        fputcsv($output, array(
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
            'movie'
        ));

        // Add another sample row for box set
        fputcsv($output, array(
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
            'box_set'
        ));

        fclose($output);
        exit;
    }

    /**
     * Get export data based on type.
     *
     * @since    1.0.0
     * @param    string    $export_type    Type of data to export (all, movies_only, box_sets).
     * @return   array                     The data to export.
     */
    private function get_export_data($export_type) {
        global $wpdb;
        $db = new WP_Movie_Collector_DB();
        $data = array();

        // Get movies if needed
        if ($export_type === 'all' || $export_type === 'movies_only') {
            $movies = $wpdb->get_results("SELECT * FROM {$db->get_movies_table()}", ARRAY_A);

            foreach ($movies as &$movie) {
                $movie['type'] = 'movie';
            }

            $data = array_merge($data, $movies);
        }

        // Get box sets if needed
        if ($export_type === 'all' || $export_type === 'box_sets') {
            $box_sets = $wpdb->get_results("SELECT * FROM {$db->get_box_sets_table()}", ARRAY_A);

            foreach ($box_sets as &$box_set) {
                $box_set['type'] = 'box_set';
            }

            $data = array_merge($data, $box_sets);
        }

        return $data;
    }

    /**
     * Export data as CSV.
     *
     * @since    1.0.0
     * @param    array     $data          The data to export.
     * @param    string    $export_type    Type of data exported (all, movies_only, box_sets).
     */
    private function export_as_csv($data, $export_type) {
        // Set filename based on export type
        $filename = 'wp-movie-collector-export-';
        switch ($export_type) {
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
        $filename .= date('Y-m-d') . '.csv';

        // Set headers for download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output the CSV
        $output = fopen('php://output', 'w');

        // Write header row if there's data
        if (!empty($data)) {
            $headers = array_keys($data[0]);
            fputcsv($output, $headers);

            // Write data rows
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        } else {
            // Just write headers for an empty file
            fputcsv($output, array(
                'id', 'title', 'release_year', 'format', 'region_code', 'barcode',
                'director', 'studio', 'actors', 'genre', 'special_features',
                'cover_image_url', 'description', 'acquisition_date', 'box_set_id',
                'api_source', 'custom_notes', 'created_at', 'updated_at', 'type'
            ));
        }

        fclose($output);
    }

    /**
     * Export data as JSON.
     *
     * @since    1.0.0
     * @param    array     $data          The data to export.
     * @param    string    $export_type    Type of data exported (all, movies_only, box_sets).
     */
    private function export_as_json($data, $export_type) {
        // Set filename based on export type
        $filename = 'wp-movie-collector-export-';
        switch ($export_type) {
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
        $filename .= date('Y-m-d') . '.json';

        // Set headers for download
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Output the JSON
        echo json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Import data from a CSV file.
     *
     * @since    1.0.0
     * @param    string    $file_path      Path to the CSV file.
     * @param    string    $import_type    Type of import (append or replace).
     * @return   int|WP_Error              Count of imported items or error.
     */
    private function import_from_csv($file_path, $import_type) {
        $db = new WP_Movie_Collector_DB();

        // Open the file
        $handle = fopen($file_path, 'r');
        if (!$handle) {
            return new WP_Error('file_error', __('Could not open file for import.', 'wp-movie-collector'));
        }

        // Read the header row
        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return new WP_Error('file_error', __('Invalid CSV file format.', 'wp-movie-collector'));
        }

        // Clean empty cells from headers
        $headers = array_map('trim', $headers);

        // If replacing, truncate tables first
        if ($import_type === 'replace') {
            global $wpdb;
            $wpdb->query("TRUNCATE TABLE {$db->get_relationships_table()}");
            $wpdb->query("TRUNCATE TABLE {$db->get_movies_table()}");
            $wpdb->query("TRUNCATE TABLE {$db->get_box_sets_table()}");
        }

        // Process rows
        $count = 0;
        $movies = array();
        $box_sets = array();

        while (($row = fgetcsv($handle)) !== false) {
            // Skip empty rows
            if (empty($row[0])) {
                continue;
            }

            // Create an associative array from the row
            $item = array();
            foreach ($headers as $i => $header) {
                if (isset($row[$i])) {
                    $item[$header] = $row[$i];
                } else {
                    $item[$header] = '';
                }
            }

            // Set timestamps
            $item['created_at'] = current_time('mysql');
            $item['updated_at'] = current_time('mysql');

            // Determine if this is a movie or box set
            $type = isset($item['type']) ? $item['type'] : 'movie';

            // Remove ID and type fields if present
            unset($item['id']);
            unset($item['type']);

            // Import the item
            if ($type === 'box_set') {
                // Store box sets to process after movies
                $box_sets[] = $item;
            } else {
                // Assume it's a movie
                $movie_id = $db->insert_movie($item);
                if ($movie_id) {
                    $count++;
                    $movies[$item['title']] = $movie_id;
                }
            }
        }

        // Process box sets after movies (to handle relationships)
        foreach ($box_sets as $box_set) {
            $box_set_id = $db->insert_box_set($box_set);
            if ($box_set_id) {
                $count++;
            }
        }

        fclose($handle);
        return $count;
    }

    /**
     * Import data from a JSON file.
     *
     * @since    1.0.0
     * @param    string    $file_path      Path to the JSON file.
     * @param    string    $import_type    Type of import (append or replace).
     * @return   int|WP_Error              Count of imported items or error.
     */
    private function import_from_json($file_path, $import_type) {
        $db = new WP_Movie_Collector_DB();

        // Read the file content
        $json_data = file_get_contents($file_path);
        if (!$json_data) {
            return new WP_Error('file_error', __('Could not read file for import.', 'wp-movie-collector'));
        }

        // Decode the JSON
        $data = json_decode($json_data, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('json_error', __('Invalid JSON format.', 'wp-movie-collector'));
        }

        // If replacing, truncate tables first
        if ($import_type === 'replace') {
            global $wpdb;
            $wpdb->query("TRUNCATE TABLE {$db->get_relationships_table()}");
            $wpdb->query("TRUNCATE TABLE {$db->get_movies_table()}");
            $wpdb->query("TRUNCATE TABLE {$db->get_box_sets_table()}");
        }

        // Process items
        $count = 0;
        $movies = array();
        $box_sets = array();

        foreach ($data as $item) {
            // Skip empty items
            if (empty($item['title'])) {
                continue;
            }

            // Set timestamps
            $item['created_at'] = current_time('mysql');
            $item['updated_at'] = current_time('mysql');

            // Determine if this is a movie or box set
            $type = isset($item['type']) ? $item['type'] : 'movie';

            // Remove ID and type fields if present
            unset($item['id']);
            unset($item['type']);

            // Import the item
            if ($type === 'box_set') {
                // Store box sets to process after movies
                $box_sets[] = $item;
            } else {
                // Assume it's a movie
                $movie_id = $db->insert_movie($item);
                if ($movie_id) {
                    $count++;
                    $movies[$item['title']] = $movie_id;
                }
            }
        }

        // Process box sets after movies (to handle relationships)
        foreach ($box_sets as $box_set) {
            $box_set_id = $db->insert_box_set($box_set);
            if ($box_set_id) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * AJAX handler for barcode lookup.
     *
     * @since    1.0.0
     */
    public function ajax_barcode_lookup() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_movie_collector_nonce')) {
            wp_send_json_error(__('Security check failed.', 'wp-movie-collector'));
        }

        // Check if barcode is provided
        if (empty($_POST['barcode'])) {
            wp_send_json_error(__('No barcode provided.', 'wp-movie-collector'));
        }

        $barcode = sanitize_text_field($_POST['barcode']);

        // First check if we already have this barcode in our database
        $db = new WP_Movie_Collector_DB();
        $movie = $db->get_movie_by_barcode($barcode);

        if ($movie) {
            wp_send_json_success($movie);
        }

        // If not in our database, try to look it up via API
        $api = new WP_Movie_Collector_API();
        $result = $api->lookup_by_barcode($barcode);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX handler for movie search.
     *
     * @since    1.0.0
     */
    public function ajax_movie_search() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_movie_collector_nonce')) {
            wp_send_json_error(__('Security check failed.', 'wp-movie-collector'));
        }

        // Check if title is provided
        if (empty($_POST['title'])) {
            wp_send_json_error(__('No title provided.', 'wp-movie-collector'));
        }

        $title = sanitize_text_field($_POST['title']);
        $year = isset($_POST['year']) ? intval($_POST['year']) : null;

        // Search for movie via API
        $api = new WP_Movie_Collector_API();
        $results = $api->search_movie_by_title($title, $year);

        if (is_wp_error($results)) {
            wp_send_json_error($results->get_error_message());
        }

        wp_send_json_success($results);
    }

    /**
     * AJAX handler for getting movie details.
     *
     * @since    1.0.0
     */
    public function ajax_get_movie_details() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_movie_collector_nonce')) {
            wp_send_json_error(__('Security check failed.', 'wp-movie-collector'));
        }

        // Check if movie_id is provided
        if (empty($_POST['movie_id'])) {
            wp_send_json_error(__('No movie ID provided.', 'wp-movie-collector'));
        }

        $movie_id = intval($_POST['movie_id']);

        // Get movie details via API
        $api = new WP_Movie_Collector_API();
        $movie = $api->get_movie_details($movie_id);

        if (is_wp_error($movie)) {
            wp_send_json_error($movie->get_error_message());
        }

        wp_send_json_success($movie);
    }
}
