<?php
/**
 * Template for displaying a single box set item in the grid.
 *
 * @package    WP_Movie_Collector
 */
?>
<div class="wp-movie-collector-item wp-movie-collector-box-set-item">
    <div class="wp-movie-collector-item-image">
        <?php if (!empty($box_set['cover_image_url'])) : ?>
            <img src="<?php echo esc_url($box_set['cover_image_url']); ?>" alt="<?php echo esc_attr($box_set['title']); ?>">
        <?php else : ?>
            <div class="wp-movie-collector-no-image">
                <span><?php esc_html_e('No Image', 'wp-movie-collector'); ?></span>
            </div>
        <?php endif; ?>
        <div class="wp-movie-collector-item-format">
            <?php echo esc_html($box_set['format']); ?>
        </div>
    </div>
    <div class="wp-movie-collector-item-content">
        <h3 class="wp-movie-collector-item-title">
            <a href="<?php echo esc_url(add_query_arg('box_set_id', $box_set['id'], get_permalink())); ?>">
                <?php echo esc_html($box_set['title']); ?>
            </a>
        </h3>
        <div class="wp-movie-collector-item-meta">
            <span class="box-set-year"><?php echo esc_html($box_set['release_year']); ?></span>
            <span class="box-set-type"><?php esc_html_e('Box Set', 'wp-movie-collector'); ?></span>
        </div>
    </div>
</div>