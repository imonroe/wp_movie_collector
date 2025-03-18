<?php
/**
 * Template for displaying a single movie item in the grid.
 *
 * @package    WP_Movie_Collector
 */
?>
<div class="wp-movie-collector-item">
    <div class="wp-movie-collector-item-image">
        <?php if (!empty($movie['cover_image_url'])) : ?>
            <img src="<?php echo esc_url($movie['cover_image_url']); ?>" alt="<?php echo esc_attr($movie['title']); ?>">
        <?php else : ?>
            <div class="wp-movie-collector-no-image">
                <span><?php esc_html_e('No Image', 'wp-movie-collector'); ?></span>
            </div>
        <?php endif; ?>
        <div class="wp-movie-collector-item-format">
            <?php echo esc_html($movie['format']); ?>
        </div>
    </div>
    <div class="wp-movie-collector-item-content">
        <h3 class="wp-movie-collector-item-title">
            <a href="<?php echo esc_url(add_query_arg('movie_id', $movie['id'], get_permalink())); ?>">
                <?php echo esc_html($movie['title']); ?>
            </a>
        </h3>
        <div class="wp-movie-collector-item-meta">
            <span class="movie-year"><?php echo esc_html($movie['release_year']); ?></span>
            <?php if (!empty($movie['director'])) : ?>
                <span class="movie-director"><?php echo esc_html($movie['director']); ?></span>
            <?php endif; ?>
        </div>
    </div>
</div>