<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <?php
    // Show success message if there is one
    if (isset($_GET['message'])) {
        $message_type = sanitize_text_field($_GET['message']);
        $message = '';
        
        switch ($message_type) {
            case 'box_set_added':
                $message = __('Box set added successfully!', 'wp-movie-collector');
                break;
            default:
                $message = __('Operation completed successfully.', 'wp-movie-collector');
                break;
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($message) . '</p></div>';
    }
    ?>
    
    <div class="wp-movie-collector-box-sets">
        <div class="wp-movie-collector-actions">
            <a href="<?php echo admin_url('admin.php?page=wp-movie-collector-add-box-set'); ?>" class="button button-primary"><?php _e('Add New Box Set', 'wp-movie-collector'); ?></a>
        </div>
        
        <?php
        global $wpdb;
        $db = new WP_Movie_Collector_DB();
        
        // Get box sets
        $box_sets = $wpdb->get_results("SELECT * FROM {$db->get_box_sets_table()} ORDER BY title ASC", ARRAY_A);
        
        if ($box_sets) :
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Title', 'wp-movie-collector'); ?></th>
                    <th><?php _e('Release Year', 'wp-movie-collector'); ?></th>
                    <th><?php _e('Format', 'wp-movie-collector'); ?></th>
                    <th><?php _e('Region', 'wp-movie-collector'); ?></th>
                    <th><?php _e('Movies', 'wp-movie-collector'); ?></th>
                    <th><?php _e('Actions', 'wp-movie-collector'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($box_sets as $box_set) : 
                    // Get movie count
                    $movie_count = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT COUNT(*) FROM {$db->get_relationships_table()} WHERE box_set_id = %d",
                            $box_set['id']
                        )
                    );
                ?>
                <tr>
                    <td><?php echo esc_html($box_set['title']); ?></td>
                    <td><?php echo esc_html($box_set['release_year']); ?></td>
                    <td><?php echo esc_html($box_set['format']); ?></td>
                    <td><?php echo esc_html($box_set['region_code']); ?></td>
                    <td><?php echo intval($movie_count); ?></td>
                    <td>
                        <a href="<?php echo admin_url('admin.php?page=wp-movie-collector-edit-box-set&id=' . $box_set['id']); ?>" class="button button-small"><?php _e('Edit', 'wp-movie-collector'); ?></a>
                        <a href="<?php echo wp_nonce_url(admin_url('admin-post.php?action=wp_movie_collector_delete_box_set&id=' . $box_set['id']), 'wp_movie_collector_delete_box_set_' . $box_set['id'], 'wp_movie_collector_nonce'); ?>" class="button button-small button-link-delete" onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this box set?', 'wp-movie-collector'); ?>')"><?php _e('Delete', 'wp-movie-collector'); ?></a>
                        <a href="<?php echo admin_url('admin.php?page=wp-movie-collector-manage-box-set&id=' . $box_set['id']); ?>" class="button button-small"><?php _e('Manage Movies', 'wp-movie-collector'); ?></a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <div class="wp-movie-collector-no-items">
            <p><?php _e('No box sets found. Why not add one?', 'wp-movie-collector'); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>