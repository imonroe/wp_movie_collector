<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="wp-movie-collector-dashboard">
        <div class="wp-movie-collector-stats">
            <?php
            global $wpdb;
            $db = new WP_Movie_Collector_DB();
            
            // Get count of movies
            $movies_count = $wpdb->get_var("SELECT COUNT(*) FROM {$db->get_movies_table()}");
            $movies_count = $movies_count ? $movies_count : 0;
            
            // Get count of box sets
            $box_sets_count = $wpdb->get_var("SELECT COUNT(*) FROM {$db->get_box_sets_table()}");
            $box_sets_count = $box_sets_count ? $box_sets_count : 0;
            ?>
            
            <div class="wp-movie-collector-stat-box">
                <h2><?php echo $movies_count; ?></h2>
                <p><?php _e('Movies', 'wp-movie-collector'); ?></p>
            </div>
            
            <div class="wp-movie-collector-stat-box">
                <h2><?php echo $box_sets_count; ?></h2>
                <p><?php _e('Box Sets', 'wp-movie-collector'); ?></p>
            </div>
        </div>
        
        <div class="wp-movie-collector-actions">
            <div class="wp-movie-collector-action-box">
                <h3><?php _e('Quick Actions', 'wp-movie-collector'); ?></h3>
                <a href="<?php echo admin_url('admin.php?page=wp-movie-collector-add-movie'); ?>" class="button button-primary"><?php _e('Add New Movie', 'wp-movie-collector'); ?></a>
                <a href="<?php echo admin_url('admin.php?page=wp-movie-collector-add-box-set'); ?>" class="button button-primary"><?php _e('Add New Box Set', 'wp-movie-collector'); ?></a>
            </div>
            
            <div class="wp-movie-collector-action-box">
                <h3><?php _e('Recent Movies', 'wp-movie-collector'); ?></h3>
                <?php
                // Get recent movies
                $recent_movies = $wpdb->get_results("SELECT id, title FROM {$db->get_movies_table()} ORDER BY created_at DESC LIMIT 5", ARRAY_A);
                
                if ($recent_movies) :
                ?>
                <ul>
                    <?php foreach ($recent_movies as $movie) : ?>
                    <li><a href="#"><?php echo esc_html($movie['title']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
                <?php else : ?>
                <p><?php _e('No movies yet. Why not add one?', 'wp-movie-collector'); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="wp-movie-collector-search">
            <h3><?php _e('Search Collection', 'wp-movie-collector'); ?></h3>
            <form method="get">
                <input type="hidden" name="page" value="wp-movie-collector">
                <input type="text" name="search" placeholder="<?php _e('Search by title, director, actor...', 'wp-movie-collector'); ?>" class="regular-text">
                <button type="submit" class="button"><?php _e('Search', 'wp-movie-collector'); ?></button>
            </form>
        </div>
    </div>
</div>