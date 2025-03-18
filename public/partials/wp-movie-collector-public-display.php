<?php
/**
 * Public display of the movie collection.
 *
 * @package    WP_Movie_Collector
 */

// Get the current query parameters
$type = isset($atts['type']) ? $atts['type'] : 'all';
$per_page = isset($atts['per_page']) ? intval($atts['per_page']) : 12;
$paged = get_query_var('paged') ? get_query_var('paged') : 1;

// Get filter values from URL
$filter_format = isset($_GET['format']) ? sanitize_text_field($_GET['format']) : '';
$filter_genre = isset($_GET['genre']) ? sanitize_text_field($_GET['genre']) : '';
$filter_year = isset($_GET['year']) ? intval($_GET['year']) : 0;
$filter_director = isset($_GET['director']) ? sanitize_text_field($_GET['director']) : '';
$filter_studio = isset($_GET['studio']) ? sanitize_text_field($_GET['studio']) : '';
$search_term = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

// Initialize DB
$db = new WP_Movie_Collector_DB();

// Build search criteria
$criteria = array(
    'per_page' => $per_page,
    'page' => $paged,
    'orderby' => isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'title',
    'order' => isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'ASC',
);

if (!empty($filter_format)) {
    $criteria['format'] = $filter_format;
}

if (!empty($filter_genre)) {
    $criteria['genre'] = $filter_genre;
}

if (!empty($filter_year)) {
    $criteria['year'] = $filter_year;
}

if (!empty($filter_director)) {
    $criteria['director'] = $filter_director;
}

if (!empty($filter_studio)) {
    $criteria['studio'] = $filter_studio;
}

if (!empty($search_term)) {
    $criteria['title'] = $search_term;
}

// Get the results based on the type
$results = array();
$total_items = 0;

if ($type === 'movies' || $type === 'all') {
    $results['movies'] = $db->search_movies($criteria);
    $total_items += count($results['movies']);
}

if ($type === 'box_sets' || $type === 'all') {
    $results['box_sets'] = $db->search_box_sets($criteria);
    $total_items += count($results['box_sets']);
}

// Get filter options for dropdowns
$formats = array('DVD', 'Blu-ray', '4K UHD', 'VHS', 'LaserDisc');

// Get genres from taxonomy
$genres = get_terms(array(
    'taxonomy' => 'genre',
    'hide_empty' => true,
));

// Get years range
$current_year = date('Y');
$years = range($current_year, 1900);

// Get directors from taxonomy
$directors = get_terms(array(
    'taxonomy' => 'director',
    'hide_empty' => true,
));

// Get studios from taxonomy
$studios = get_terms(array(
    'taxonomy' => 'studio',
    'hide_empty' => true,
));
?>

<div class="wp-movie-collector-container">
    <!-- Search Bar -->
    <div class="wp-movie-collector-search">
        <form method="get" action="<?php echo esc_url(get_permalink()); ?>">
            <input type="text" name="search" placeholder="<?php esc_attr_e('Search your collection...', 'wp-movie-collector'); ?>" value="<?php echo esc_attr($search_term); ?>">
            <button type="submit" class="button"><?php esc_html_e('Search', 'wp-movie-collector'); ?></button>
        </form>
    </div>
    
    <!-- Filters -->
    <div class="wp-movie-collector-filters">
        <form method="get" action="<?php echo esc_url(get_permalink()); ?>">
            <?php if (!empty($search_term)) : ?>
                <input type="hidden" name="search" value="<?php echo esc_attr($search_term); ?>">
            <?php endif; ?>
            
            <div class="filter-group">
                <label for="format-filter"><?php esc_html_e('Format', 'wp-movie-collector'); ?></label>
                <select id="format-filter" name="format">
                    <option value=""><?php esc_html_e('All Formats', 'wp-movie-collector'); ?></option>
                    <?php foreach ($formats as $format) : ?>
                        <option value="<?php echo esc_attr($format); ?>" <?php selected($filter_format, $format); ?>>
                            <?php echo esc_html($format); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="genre-filter"><?php esc_html_e('Genre', 'wp-movie-collector'); ?></label>
                <select id="genre-filter" name="genre">
                    <option value=""><?php esc_html_e('All Genres', 'wp-movie-collector'); ?></option>
                    <?php if (!empty($genres) && !is_wp_error($genres)) : ?>
                        <?php foreach ($genres as $genre) : ?>
                            <option value="<?php echo esc_attr($genre->slug); ?>" <?php selected($filter_genre, $genre->slug); ?>>
                                <?php echo esc_html($genre->name); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="year-filter"><?php esc_html_e('Year', 'wp-movie-collector'); ?></label>
                <select id="year-filter" name="year">
                    <option value=""><?php esc_html_e('All Years', 'wp-movie-collector'); ?></option>
                    <?php foreach ($years as $year) : ?>
                        <option value="<?php echo esc_attr($year); ?>" <?php selected($filter_year, $year); ?>>
                            <?php echo esc_html($year); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="director-filter"><?php esc_html_e('Director', 'wp-movie-collector'); ?></label>
                <select id="director-filter" name="director">
                    <option value=""><?php esc_html_e('All Directors', 'wp-movie-collector'); ?></option>
                    <?php if (!empty($directors) && !is_wp_error($directors)) : ?>
                        <?php foreach ($directors as $director) : ?>
                            <option value="<?php echo esc_attr($director->slug); ?>" <?php selected($filter_director, $director->slug); ?>>
                                <?php echo esc_html($director->name); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            
            <div class="filter-actions">
                <button type="submit" class="button"><?php esc_html_e('Apply Filters', 'wp-movie-collector'); ?></button>
                <a href="<?php echo esc_url(get_permalink()); ?>" class="button wp-movie-collector-clear-filters" data-base-url="<?php echo esc_url(get_permalink()); ?>"><?php esc_html_e('Clear Filters', 'wp-movie-collector'); ?></a>
            </div>
        </form>
    </div>
    
    <?php if ($total_items > 0) : ?>
        <!-- Results Grid -->
        <div class="wp-movie-collector-grid">
            <?php if (isset($results['movies']) && !empty($results['movies'])) : ?>
                <?php foreach ($results['movies'] as $movie) : ?>
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
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (isset($results['box_sets']) && !empty($results['box_sets'])) : ?>
                <?php foreach ($results['box_sets'] as $box_set) : ?>
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
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <div class="wp-movie-collector-pagination">
            <?php
            // Simple pagination
            $total_pages = ceil($total_items / $per_page);
            
            if ($total_pages > 1) {
                $current_page = max(1, $paged);
                
                // Build pagination links
                echo '<div class="nav-links">';
                
                // Previous page
                if ($current_page > 1) {
                    echo '<a class="page-numbers prev" href="' . esc_url(add_query_arg('paged', $current_page - 1, get_pagenum_link($current_page - 1))) . '">&laquo; ' . esc_html__('Previous', 'wp-movie-collector') . '</a>';
                }
                
                // Page numbers
                for ($i = 1; $i <= $total_pages; $i++) {
                    if ($i === $current_page) {
                        echo '<span class="page-numbers current">' . $i . '</span>';
                    } else {
                        echo '<a class="page-numbers" href="' . esc_url(add_query_arg('paged', $i, get_pagenum_link($i))) . '">' . $i . '</a>';
                    }
                }
                
                // Next page
                if ($current_page < $total_pages) {
                    echo '<a class="page-numbers next" href="' . esc_url(add_query_arg('paged', $current_page + 1, get_pagenum_link($current_page + 1))) . '">' . esc_html__('Next', 'wp-movie-collector') . ' &raquo;</a>';
                }
                
                echo '</div>';
            }
            ?>
        </div>
    <?php else : ?>
        <div class="wp-movie-collector-no-results">
            <p><?php esc_html_e('No movies or box sets found matching your criteria.', 'wp-movie-collector'); ?></p>
        </div>
    <?php endif; ?>
</div>
