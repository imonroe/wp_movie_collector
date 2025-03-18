<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php
    // Show error message if there is one
    if (isset($_GET['error'])) {
        $error_type = sanitize_text_field($_GET['error']);
        $error_message = '';
        
        switch ($error_type) {
            case 'invalid_box_set':
                $error_message = __('Invalid box set ID.', 'wp-movie-collector');
                break;
            case 'db_error':
                $error_message = __('There was an error updating the box set in the database. Please try again.', 'wp-movie-collector');
                break;
            default:
                $error_message = __('An unknown error occurred. Please try again.', 'wp-movie-collector');
                break;
        }
        
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
    }
    
    // Get box set ID from URL
    $box_set_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    // Check if box set exists
    global $wpdb;
    $db = new WP_Movie_Collector_DB();
    $box_set = $db->get_box_set($box_set_id);
    
    if (!$box_set) {
        echo '<div class="notice notice-error"><p>' . __('Box set not found.', 'wp-movie-collector') . '</p></div>';
        echo '<p><a href="' . admin_url('admin.php?page=wp-movie-collector-box-sets') . '" class="button">' . __('Back to Box Sets', 'wp-movie-collector') . '</a></p>';
        return;
    }
    ?>
    
    <div class="wp-movie-collector-form">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="wp-movie-collector-edit-box-set-form">
            <input type="hidden" name="action" value="wp_movie_collector_update_box_set">
            <input type="hidden" name="box_set_id" value="<?php echo esc_attr($box_set_id); ?>">
            <?php wp_nonce_field('wp_movie_collector_update_box_set', 'wp_movie_collector_nonce'); ?>
            
            <div class="form-group">
                <label for="box-set-title"><?php _e('Title', 'wp-movie-collector'); ?></label>
                <input type="text" id="box-set-title" name="box_set[title]" value="<?php echo esc_attr($box_set['title']); ?>" class="regular-text" required>
            </div>
            
            <div class="form-group">
                <label for="box-set-release-year"><?php _e('Release Year', 'wp-movie-collector'); ?></label>
                <input type="number" id="box-set-release-year" name="box_set[release_year]" value="<?php echo esc_attr($box_set['release_year']); ?>" min="1900" max="<?php echo date('Y'); ?>" class="small-text" required>
            </div>
            
            <div class="form-group">
                <label for="box-set-format"><?php _e('Format', 'wp-movie-collector'); ?></label>
                <select id="box-set-format" name="box_set[format]" required>
                    <option value=""><?php _e('Select Format', 'wp-movie-collector'); ?></option>
                    <option value="DVD" <?php selected($box_set['format'], 'DVD'); ?>><?php _e('DVD', 'wp-movie-collector'); ?></option>
                    <option value="Blu-ray" <?php selected($box_set['format'], 'Blu-ray'); ?>><?php _e('Blu-ray', 'wp-movie-collector'); ?></option>
                    <option value="4K UHD" <?php selected($box_set['format'], '4K UHD'); ?>><?php _e('4K Ultra HD', 'wp-movie-collector'); ?></option>
                    <option value="VHS" <?php selected($box_set['format'], 'VHS'); ?>><?php _e('VHS', 'wp-movie-collector'); ?></option>
                    <option value="LaserDisc" <?php selected($box_set['format'], 'LaserDisc'); ?>><?php _e('LaserDisc', 'wp-movie-collector'); ?></option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="box-set-region-code"><?php _e('Region Code', 'wp-movie-collector'); ?></label>
                <select id="box-set-region-code" name="box_set[region_code]" required>
                    <option value=""><?php _e('Select Region', 'wp-movie-collector'); ?></option>
                    <option value="R1" <?php selected($box_set['region_code'], 'R1'); ?>>Region 1 (USA, Canada)</option>
                    <option value="R2" <?php selected($box_set['region_code'], 'R2'); ?>>Region 2 (Europe, Japan, Middle East)</option>
                    <option value="R3" <?php selected($box_set['region_code'], 'R3'); ?>>Region 3 (East Asia)</option>
                    <option value="R4" <?php selected($box_set['region_code'], 'R4'); ?>>Region 4 (Australia, New Zealand, Latin America)</option>
                    <option value="R5" <?php selected($box_set['region_code'], 'R5'); ?>>Region 5 (Africa, Asia, Russia)</option>
                    <option value="R6" <?php selected($box_set['region_code'], 'R6'); ?>>Region 6 (China)</option>
                    <option value="R0" <?php selected($box_set['region_code'], 'R0'); ?>>Region Free</option>
                    <option value="A" <?php selected($box_set['region_code'], 'A'); ?>>Region A (Blu-ray: Americas, East Asia)</option>
                    <option value="B" <?php selected($box_set['region_code'], 'B'); ?>>Region B (Blu-ray: Europe, Africa, Australia)</option>
                    <option value="C" <?php selected($box_set['region_code'], 'C'); ?>>Region C (Blu-ray: Central/South Asia, Russia, China)</option>
                    <option value="ABC" <?php selected($box_set['region_code'], 'ABC'); ?>>Region Free (Blu-ray)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="box-set-barcode"><?php _e('Barcode', 'wp-movie-collector'); ?></label>
                <input type="text" id="box-set-barcode" name="box_set[barcode]" value="<?php echo esc_attr($box_set['barcode']); ?>" class="regular-text">
            </div>
            
            <div class="form-group">
                <label for="box-set-cover-image-url"><?php _e('Cover Image URL', 'wp-movie-collector'); ?></label>
                <input type="url" id="box-set-cover-image-url" name="box_set[cover_image_url]" value="<?php echo esc_url($box_set['cover_image_url']); ?>" class="regular-text">
            </div>
            
            <div class="form-group">
                <label for="box-set-description"><?php _e('Description', 'wp-movie-collector'); ?></label>
                <textarea id="box-set-description" name="box_set[description]"><?php echo esc_textarea($box_set['description']); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="box-set-acquisition-date"><?php _e('Acquisition Date', 'wp-movie-collector'); ?></label>
                <input type="date" id="box-set-acquisition-date" name="box_set[acquisition_date]" value="<?php echo esc_attr($box_set['acquisition_date']); ?>" class="regular-text">
                <p class="description"><?php _e('When did you acquire this box set?', 'wp-movie-collector'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="box-set-special-features"><?php _e('Special Features', 'wp-movie-collector'); ?></label>
                <textarea id="box-set-special-features" name="box_set[special_features]"><?php echo esc_textarea($box_set['special_features']); ?></textarea>
                <p class="description"><?php _e('Enter special features included in this box set.', 'wp-movie-collector'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="box-set-custom-notes"><?php _e('Custom Notes', 'wp-movie-collector'); ?></label>
                <textarea id="box-set-custom-notes" name="box_set[custom_notes]"><?php echo esc_textarea($box_set['custom_notes']); ?></textarea>
                <p class="description"><?php _e('Any personal notes about this box set.', 'wp-movie-collector'); ?></p>
            </div>
            
            <input type="hidden" id="box-set-api-source" name="box_set[api_source]" value="<?php echo esc_attr($box_set['api_source']); ?>">
            
            <p class="submit">
                <button type="submit" class="button button-primary">
                    <?php _e('Update Box Set', 'wp-movie-collector'); ?>
                </button>
            </p>
        </form>
    </div>
    
    <p>
        <a href="<?php echo admin_url('admin.php?page=wp-movie-collector-box-sets'); ?>" class="button"><?php _e('Back to Box Sets', 'wp-movie-collector'); ?></a>
        <a href="<?php echo admin_url('admin.php?page=wp-movie-collector-manage-box-set&id=' . $box_set_id); ?>" class="button"><?php _e('Manage Movies in Box Set', 'wp-movie-collector'); ?></a>
    </p>
</div>