<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php
    // Show error message if there is one
    if (isset($_GET['error'])) {
        $error_type = sanitize_text_field($_GET['error']);
        
        if ($error_type === 'validation') {
            // Get validation errors from transient
            $errors = get_transient('wp_movie_collector_form_errors');
            if ($errors && is_array($errors)) {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p><strong>' . __('Please fix the following errors:', 'wp-movie-collector') . '</strong></p>';
                echo '<ul>';
                foreach ($errors as $error) {
                    echo '<li>' . esc_html($error) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
                
                // Delete the transient
                delete_transient('wp_movie_collector_form_errors');
            }
        } else {
            $error_message = '';
            
            switch ($error_type) {
                case 'db_error':
                    $error_message = __('There was an error saving the box set to the database. Please try again.', 'wp-movie-collector');
                    break;
                default:
                    $error_message = __('An unknown error occurred. Please try again.', 'wp-movie-collector');
                    break;
            }
            
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($error_message) . '</p></div>';
        }
    }
    ?>
    
    <div class="wp-movie-collector-form">
        <div class="wp-movie-collector-barcode-scanner">
            <h3><?php _e('Scan Barcode', 'wp-movie-collector'); ?></h3>
            <p><?php _e('Use a barcode scanner or enter a barcode manually to quickly add a box set.', 'wp-movie-collector'); ?></p>
            <div class="wp-movie-collector-barcode-input">
                <input type="text" id="wp-movie-collector-barcode" class="regular-text" placeholder="<?php _e('Scan or enter barcode...', 'wp-movie-collector'); ?>">
                <button type="button" id="wp-movie-collector-lookup-barcode" class="button"><?php _e('Lookup', 'wp-movie-collector'); ?></button>
            </div>
            <div id="wp-movie-collector-barcode-result"></div>
        </div>
        
        <h3><?php _e('Box Set Details', 'wp-movie-collector'); ?></h3>
        <form method="post" id="wp-movie-collector-add-box-set-form">
            <?php wp_nonce_field('wp_movie_collector_add_box_set', 'wp_movie_collector_nonce'); ?>
            
            <div class="form-group">
                <label for="box-set-title"><?php _e('Title', 'wp-movie-collector'); ?></label>
                <input type="text" id="box-set-title" name="box_set[title]" class="regular-text" required>
            </div>
            
            <div class="form-group">
                <label for="box-set-release-year"><?php _e('Release Year', 'wp-movie-collector'); ?></label>
                <input type="number" id="box-set-release-year" name="box_set[release_year]" min="1900" max="<?php echo date('Y'); ?>" class="small-text" required>
            </div>
            
            <div class="form-group">
                <label for="box-set-format"><?php _e('Format', 'wp-movie-collector'); ?></label>
                <select id="box-set-format" name="box_set[format]" required>
                    <option value=""><?php _e('Select Format', 'wp-movie-collector'); ?></option>
                    <option value="DVD"><?php _e('DVD', 'wp-movie-collector'); ?></option>
                    <option value="Blu-ray"><?php _e('Blu-ray', 'wp-movie-collector'); ?></option>
                    <option value="4K UHD"><?php _e('4K Ultra HD', 'wp-movie-collector'); ?></option>
                    <option value="VHS"><?php _e('VHS', 'wp-movie-collector'); ?></option>
                    <option value="LaserDisc"><?php _e('LaserDisc', 'wp-movie-collector'); ?></option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="box-set-region-code"><?php _e('Region Code', 'wp-movie-collector'); ?></label>
                <select id="box-set-region-code" name="box_set[region_code]" required>
                    <option value=""><?php _e('Select Region', 'wp-movie-collector'); ?></option>
                    <option value="R1">Region 1 (USA, Canada)</option>
                    <option value="R2">Region 2 (Europe, Japan, Middle East)</option>
                    <option value="R3">Region 3 (East Asia)</option>
                    <option value="R4">Region 4 (Australia, New Zealand, Latin America)</option>
                    <option value="R5">Region 5 (Africa, Asia, Russia)</option>
                    <option value="R6">Region 6 (China)</option>
                    <option value="R0">Region Free</option>
                    <option value="A">Region A (Blu-ray: Americas, East Asia)</option>
                    <option value="B">Region B (Blu-ray: Europe, Africa, Australia)</option>
                    <option value="C">Region C (Blu-ray: Central/South Asia, Russia, China)</option>
                    <option value="ABC">Region Free (Blu-ray)</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="box-set-barcode"><?php _e('Barcode', 'wp-movie-collector'); ?></label>
                <input type="text" id="box-set-barcode" name="box_set[barcode]" class="regular-text">
            </div>
            
            <div class="form-group">
                <label for="box-set-cover-image"><?php _e('Cover Image', 'wp-movie-collector'); ?></label>
                <div class="wp-movie-collector-image-upload-container">
                    <div class="image-preview"></div>
                    <input type="hidden" id="box-set-cover-image-id" name="box_set[cover_image_id]" class="image-id-field">
                    <input type="url" id="box-set-cover-image-url" name="box_set[cover_image_url]" class="regular-text image-url-field" placeholder="<?php _e('Image URL or upload', 'wp-movie-collector'); ?>">
                    <button type="button" class="button wp-movie-collector-upload-image-button"><?php _e('Upload Image', 'wp-movie-collector'); ?></button>
                    <button type="button" class="button wp-movie-collector-remove-image-button" style="display:none;"><?php _e('Remove Image', 'wp-movie-collector'); ?></button>
                    <p class="description"><?php _e('Upload an image or enter a URL for the box set cover.', 'wp-movie-collector'); ?></p>
                </div>
            </div>
            
            <div class="form-group">
                <label for="box-set-description"><?php _e('Description', 'wp-movie-collector'); ?></label>
                <textarea id="box-set-description" name="box_set[description]"></textarea>
            </div>
            
            <div class="form-group">
                <label for="box-set-acquisition-date"><?php _e('Acquisition Date', 'wp-movie-collector'); ?></label>
                <input type="date" id="box-set-acquisition-date" name="box_set[acquisition_date]" class="regular-text">
                <p class="description"><?php _e('When did you acquire this box set?', 'wp-movie-collector'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="box-set-special-features"><?php _e('Special Features', 'wp-movie-collector'); ?></label>
                <textarea id="box-set-special-features" name="box_set[special_features]"></textarea>
                <p class="description"><?php _e('Enter special features included in this box set.', 'wp-movie-collector'); ?></p>
            </div>
            
            <div class="form-group">
                <label for="box-set-custom-notes"><?php _e('Custom Notes', 'wp-movie-collector'); ?></label>
                <textarea id="box-set-custom-notes" name="box_set[custom_notes]"></textarea>
                <p class="description"><?php _e('Any personal notes about this box set.', 'wp-movie-collector'); ?></p>
            </div>
            
            <input type="hidden" id="box-set-api-source" name="box_set[api_source]" value="">
            
            <p class="submit">
                <button type="submit" class="button button-primary" name="wp_movie_collector_add_box_set_submit">
                    <?php _e('Add Box Set', 'wp-movie-collector'); ?>
                </button>
            </p>
        </form>
        
        <div id="wp-movie-collector-box-set-movies">
            <h3><?php _e('Movies in this Box Set', 'wp-movie-collector'); ?></h3>
            <p><?php _e('After saving the box set, you can add movies to it.', 'wp-movie-collector'); ?></p>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Barcode lookup
    $('#wp-movie-collector-lookup-barcode').on('click', function() {
        var barcode = $('#wp-movie-collector-barcode').val();
        if (!barcode) {
            return;
        }
        
        $('#wp-movie-collector-barcode-result').html('<p><?php _e('Looking up barcode...', 'wp-movie-collector'); ?></p>');
        
        $.ajax({
            url: wp_movie_collector_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'wp_movie_collector_barcode_lookup',
                barcode: barcode,
                nonce: wp_movie_collector_admin.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#wp-movie-collector-barcode-result').html('<p class="success"><?php _e('Box set found! Filling in details...', 'wp-movie-collector'); ?></p>');
                    // Fill in form with box set details
                    fillBoxSetForm(response.data);
                } else {
                    $('#wp-movie-collector-barcode-result').html('<p class="error">' + response.data + '</p>');
                }
            },
            error: function() {
                $('#wp-movie-collector-barcode-result').html('<p class="error"><?php _e('Error looking up barcode. Please try again.', 'wp-movie-collector'); ?></p>');
            }
        });
    });
    
    // Fill box set form with data
    function fillBoxSetForm(box_set) {
        $('#box-set-title').val(box_set.title || '');
        $('#box-set-release-year').val(box_set.release_year || '');
        $('#box-set-barcode').val(box_set.barcode || '');
        $('#box-set-description').val(box_set.description || '');
        
        // Handle cover image
        $('#box-set-cover-image-url').val(box_set.cover_image_url || '');
        if (box_set.cover_image_id) {
            $('#box-set-cover-image-id').val(box_set.cover_image_id);
        }
        
        // Display cover image preview if URL exists
        if (box_set.cover_image_url) {
            $('#box-set-cover-image-url').siblings('.image-preview').html('<img src="' + box_set.cover_image_url + '" alt="" style="max-width:150px;max-height:150px;" />');
            $('#box-set-cover-image-url').siblings('.wp-movie-collector-remove-image-button').show();
        }
        
        $('#box-set-api-source').val(box_set.api_source || '');
        
        // Set select dropdowns if values exist
        if (box_set.format) {
            $('#box-set-format').val(box_set.format);
        }
        
        if (box_set.region_code) {
            $('#box-set-region-code').val(box_set.region_code);
        }
    }
});
</script>