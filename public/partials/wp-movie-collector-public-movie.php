<?php
/**
 * Template for displaying a single movie.
 *
 * @package    WP_Movie_Collector
 */
?>

<div class="wp-movie-collector-single">
    <div class="wp-movie-collector-single-header">
        <div class="wp-movie-collector-single-image">
            <?php if (!empty($movie['cover_image_url'])) : ?>
                <img src="<?php echo esc_url($movie['cover_image_url']); ?>" alt="<?php echo esc_attr($movie['title']); ?>">
            <?php else : ?>
                <div class="wp-movie-collector-no-image">
                    <span><?php esc_html_e('No Image Available', 'wp-movie-collector'); ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="wp-movie-collector-single-details">
            <h1 class="wp-movie-collector-single-title"><?php echo esc_html($movie['title']); ?></h1>
            
            <div class="wp-movie-collector-single-meta">
                <div class="wp-movie-collector-single-meta-item">
                    <span class="wp-movie-collector-single-meta-label"><?php esc_html_e('Release Year:', 'wp-movie-collector'); ?></span>
                    <span><?php echo esc_html($movie['release_year']); ?></span>
                </div>
                
                <div class="wp-movie-collector-single-meta-item">
                    <span class="wp-movie-collector-single-meta-label"><?php esc_html_e('Format:', 'wp-movie-collector'); ?></span>
                    <span><?php echo esc_html($movie['format']); ?></span>
                </div>
                
                <div class="wp-movie-collector-single-meta-item">
                    <span class="wp-movie-collector-single-meta-label"><?php esc_html_e('Region Code:', 'wp-movie-collector'); ?></span>
                    <span><?php echo esc_html($movie['region_code']); ?></span>
                </div>
                
                <?php if (!empty($movie['barcode'])) : ?>
                    <div class="wp-movie-collector-single-meta-item">
                        <span class="wp-movie-collector-single-meta-label"><?php esc_html_e('Barcode:', 'wp-movie-collector'); ?></span>
                        <span><?php echo esc_html($movie['barcode']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($movie['director'])) : ?>
                    <div class="wp-movie-collector-single-meta-item">
                        <span class="wp-movie-collector-single-meta-label"><?php esc_html_e('Director:', 'wp-movie-collector'); ?></span>
                        <span><?php echo esc_html($movie['director']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($movie['studio'])) : ?>
                    <div class="wp-movie-collector-single-meta-item">
                        <span class="wp-movie-collector-single-meta-label"><?php esc_html_e('Studio:', 'wp-movie-collector'); ?></span>
                        <span><?php echo esc_html($movie['studio']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($movie['genre'])) : ?>
                    <div class="wp-movie-collector-single-meta-item">
                        <span class="wp-movie-collector-single-meta-label"><?php esc_html_e('Genre:', 'wp-movie-collector'); ?></span>
                        <span><?php echo esc_html($movie['genre']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($movie['actors'])) : ?>
                    <div class="wp-movie-collector-single-meta-item">
                        <span class="wp-movie-collector-single-meta-label"><?php esc_html_e('Actors:', 'wp-movie-collector'); ?></span>
                        <span><?php echo esc_html($movie['actors']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($movie['acquisition_date'])) : ?>
                    <div class="wp-movie-collector-single-meta-item">
                        <span class="wp-movie-collector-single-meta-label"><?php esc_html_e('Acquired On:', 'wp-movie-collector'); ?></span>
                        <span><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($movie['acquisition_date']))); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($movie['special_features'])) : ?>
                <div class="wp-movie-collector-single-special-features">
                    <h3><?php esc_html_e('Special Features', 'wp-movie-collector'); ?></h3>
                    <p><?php echo nl2br(esc_html($movie['special_features'])); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!empty($movie['description'])) : ?>
        <div class="wp-movie-collector-single-description">
            <h3><?php esc_html_e('Description', 'wp-movie-collector'); ?></h3>
            <p><?php echo nl2br(esc_html($movie['description'])); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($movie['custom_notes'])) : ?>
        <div class="wp-movie-collector-single-notes">
            <h3><?php esc_html_e('Notes', 'wp-movie-collector'); ?></h3>
            <p><?php echo nl2br(esc_html($movie['custom_notes'])); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($box_sets)) : ?>
        <div class="wp-movie-collector-single-box-set">
            <h3><?php esc_html_e('Part of Box Set', 'wp-movie-collector'); ?></h3>
            <ul>
                <?php foreach ($box_sets as $box_set) : ?>
                    <li>
                        <a href="<?php echo esc_url(add_query_arg('box_set_id', $box_set['id'], get_permalink())); ?>">
                            <?php echo esc_html($box_set['title']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="wp-movie-collector-single-back">
        <a href="<?php echo esc_url(remove_query_arg('movie_id', get_permalink())); ?>">
            &laquo; <?php esc_html_e('Back to Collection', 'wp-movie-collector'); ?>
        </a>
    </div>
</div>
