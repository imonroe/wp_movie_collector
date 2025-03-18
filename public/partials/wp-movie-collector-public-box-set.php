<?php
/**
 * Template for displaying a single box set.
 *
 * @package    WP_Movie_Collector
 */
?>

<div class="wp-movie-collector-single wp-movie-collector-box-set">
    <div class="wp-movie-collector-single-header">
        <div class="wp-movie-collector-single-image">
            <?php if (!empty($box_set['cover_image_url'])) : ?>
                <img src="<?php echo esc_url($box_set['cover_image_url']); ?>" alt="<?php echo esc_attr($box_set['title']); ?>">
            <?php else : ?>
                <div class="wp-movie-collector-no-image">
                    <span><?php esc_html_e('No Image Available', 'wp-movie-collector'); ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="wp-movie-collector-single-details">
            <h1 class="wp-movie-collector-single-title"><?php echo esc_html($box_set['title']); ?></h1>
            
            <div class="wp-movie-collector-single-meta">
                <div class="wp-movie-collector-single-meta-item">
                    <span class="wp-movie-collector-single-meta-label"><?php esc_html_e('Release Year:', 'wp-movie-collector'); ?></span>
                    <span><?php echo esc_html($box_set['release_year']); ?></span>
                </div>
                
                <div class="wp-movie-collector-single-meta-item">
                    <span class="wp-movie-collector-single-meta-label"><?php esc_html_e('Format:', 'wp-movie-collector'); ?></span>
                    <span><?php echo esc_html($box_set['format']); ?></span>
                </div>
                
                <div class="wp-movie-collector-single-meta-item">
                    <span class="wp-movie-collector-single-meta-label"><?php esc_html_e('Region Code:', 'wp-movie-collector'); ?></span>
                    <span><?php echo esc_html($box_set['region_code']); ?></span>
                </div>
                
                <?php if (!empty($box_set['barcode'])) : ?>
                    <div class="wp-movie-collector-single-meta-item">
                        <span class="wp-movie-collector-single-meta-label"><?php esc_html_e('Barcode:', 'wp-movie-collector'); ?></span>
                        <span><?php echo esc_html($box_set['barcode']); ?></span>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($box_set['acquisition_date'])) : ?>
                    <div class="wp-movie-collector-single-meta-item">
                        <span class="wp-movie-collector-single-meta-label"><?php esc_html_e('Acquired On:', 'wp-movie-collector'); ?></span>
                        <span><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($box_set['acquisition_date']))); ?></span>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($box_set['special_features'])) : ?>
                <div class="wp-movie-collector-single-special-features">
                    <h3><?php esc_html_e('Special Features', 'wp-movie-collector'); ?></h3>
                    <p><?php echo nl2br(esc_html($box_set['special_features'])); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if (!empty($box_set['description'])) : ?>
        <div class="wp-movie-collector-single-description">
            <h3><?php esc_html_e('Description', 'wp-movie-collector'); ?></h3>
            <p><?php echo nl2br(esc_html($box_set['description'])); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($movies)) : ?>
        <div class="wp-movie-collector-box-set-movies">
            <h3><?php esc_html_e('Movies in this Box Set', 'wp-movie-collector'); ?></h3>
            
            <div class="wp-movie-collector-grid">
                <?php foreach ($movies as $movie) : ?>
                    <div class="wp-movie-collector-item">
                        <div class="wp-movie-collector-item-image">
                            <?php if (!empty($movie['cover_image_url'])) : ?>
                                <img src="<?php echo esc_url($movie['cover_image_url']); ?>" alt="<?php echo esc_attr($movie['title']); ?>">
                            <?php else : ?>
                                <div class="wp-movie-collector-no-image">
                                    <span><?php esc_html_e('No Image', 'wp-movie-collector'); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="wp-movie-collector-item-content">
                            <h3 class="wp-movie-collector-item-title">
                                <a href="<?php echo esc_url(add_query_arg('movie_id', $movie['id'], get_permalink())); ?>">
                                    <?php echo esc_html($movie['title']); ?>
                                </a>
                            </h3>
                            <div class="wp-movie-collector-item-meta">
                                <span class="movie-year"><?php echo esc_html($movie['release_year']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else : ?>
        <div class="wp-movie-collector-box-set-movies">
            <h3><?php esc_html_e('Movies in this Box Set', 'wp-movie-collector'); ?></h3>
            <p><?php esc_html_e('No movies have been added to this box set yet.', 'wp-movie-collector'); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($box_set['custom_notes'])) : ?>
        <div class="wp-movie-collector-single-notes">
            <h3><?php esc_html_e('Notes', 'wp-movie-collector'); ?></h3>
            <p><?php echo nl2br(esc_html($box_set['custom_notes'])); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="wp-movie-collector-single-back">
        <a href="<?php echo esc_url(remove_query_arg('box_set_id', get_permalink())); ?>">
            &laquo; <?php esc_html_e('Back to Collection', 'wp-movie-collector'); ?>
        </a>
    </div>
</div>
