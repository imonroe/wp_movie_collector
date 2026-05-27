<?php
/**
 * Public display of the movie collection.
 *
 * @package    WP_Movie_Collector
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Get the current query parameters
$type = isset($atts['type']) ? $atts['type'] : 'all';
$per_page = isset($atts['per_page']) ? intval($atts['per_page']) : 12;
$paged = get_query_var('paged') ? get_query_var('paged') : 1;

// Get filter values from URL
$filter_format = isset($_GET['format']) ? sanitize_text_field(wp_unslash($_GET['format'])) : '';
$filter_genre = isset($_GET['genre']) ? sanitize_text_field(wp_unslash($_GET['genre'])) : '';
$raw_year = (isset($_GET['year']) && is_scalar($_GET['year'])) ? absint(wp_unslash($_GET['year'])) : 0;
$filter_year = ($raw_year >= 1900 && $raw_year <= intval(date('Y')) + 1) ? $raw_year : 0;
$filter_director = isset($_GET['director']) ? sanitize_text_field(wp_unslash($_GET['director'])) : '';
$filter_studio = isset($_GET['studio']) ? sanitize_text_field(wp_unslash($_GET['studio'])) : '';
$search_term = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';

// Initialize DB
$db = new WP_Movie_Collector_DB();

// Build search criteria
// Whitelist orderby/order to prevent SQL injection. Director only applies to movies.
// 'id' is allowed for backward compatibility (URL/shortcode) but not shown in the dropdown.
$allowed_orderby = array('title', 'release_year', 'id', 'created_at', 'acquisition_date', 'format');
if ( $type !== 'box_sets' ) {
    $allowed_orderby[] = 'director';
}
$allowed_order = array('ASC', 'DESC');

// Shortcode attributes provide defaults; normalize and validate, then fall back to title/ASC.
$att_orderby     = ! empty( $atts['orderby'] ) ? sanitize_key( trim( $atts['orderby'] ) ) : '';
$att_order       = ! empty( $atts['order'] ) ? strtoupper( trim( $atts['order'] ) ) : '';
$default_orderby = in_array( $att_orderby, $allowed_orderby, true ) ? $att_orderby : 'title';
$default_order   = in_array( $att_order, $allowed_order, true ) ? $att_order : 'ASC';

// Parse sort from combined "sort" param (e.g. "title-ASC") or separate orderby/order params.
if ( isset( $_GET['sort'] ) ) {
    $sort_parts  = explode( '-', sanitize_text_field( wp_unslash( $_GET['sort'] ) ), 2 );
    $raw_orderby = isset( $sort_parts[0] ) ? sanitize_key( trim( $sort_parts[0] ) ) : $default_orderby;
    $raw_order   = isset( $sort_parts[1] ) ? strtoupper( trim( $sort_parts[1] ) ) : $default_order;
} else {
    $raw_orderby = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : $default_orderby;
    $raw_order   = isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : $default_order;
}

// Validate URL values; fall back to shortcode defaults (not hardcoded title/ASC).
$current_orderby = in_array( $raw_orderby, $allowed_orderby, true ) ? $raw_orderby : $default_orderby;
$current_order   = in_array( strtoupper( $raw_order ), $allowed_order, true ) ? strtoupper( $raw_order ) : $default_order;

// Build combined sort keys for the dropdown and for determining when to persist sort in URLs.
$current_sort = $current_orderby . '-' . $current_order;
$default_sort = $default_orderby . '-' . $default_order;

$criteria = array(
    'per_page' => $per_page,
    'page' => $paged,
    'orderby' => $current_orderby,
    'order' => $current_order,
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

// Get the results and total counts for pagination.
// When type=all, each type is paginated independently (separate LIMIT/OFFSET queries),
// so total_pages should be the max of per-type page counts, not the sum.
$results       = array();
$total_movies  = 0;
$total_box_sets_count = 0;

if ($type === 'movies' || $type === 'all') {
    $results['movies'] = $db->search_movies($criteria);
    $total_movies      = $db->count_movies($criteria);
}

if ($type === 'box_sets' || $type === 'all') {
    $results['box_sets']  = $db->search_box_sets($criteria);
    $total_box_sets_count = $db->count_box_sets($criteria);
}

$total_items = $total_movies + $total_box_sets_count;

// Compute total pages: use max of per-type page counts when showing both types.
if ( $type === 'all' ) {
    $total_pages = max(
        (int) ceil( $total_movies / $per_page ),
        (int) ceil( $total_box_sets_count / $per_page )
    );
} else {
    $total_pages = (int) ceil( $total_items / $per_page );
}

// Count items on the current page for the "has results" check.
$current_page_items = 0;
if ( isset( $results['movies'] ) ) {
    $current_page_items += count( $results['movies'] );
}
if ( isset( $results['box_sets'] ) ) {
    $current_page_items += count( $results['box_sets'] );
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
        <form method="get" action="<?php echo esc_url(get_permalink()); ?>" role="search" aria-label="<?php esc_attr_e('Search the movie collection', 'wp-movie-collector'); ?>">
            <?php if ( $current_sort !== $default_sort ) : ?>
                <input type="hidden" name="sort" value="<?php echo esc_attr( $current_sort ); ?>">
            <?php endif; ?>
            <?php $search_field_id = function_exists( 'wp_unique_id' ) ? wp_unique_id( 'wp-movie-collector-search-' ) : 'wp-movie-collector-search-field'; ?>
            <label for="<?php echo esc_attr( $search_field_id ); ?>" class="screen-reader-text"><?php esc_html_e('Search your collection', 'wp-movie-collector'); ?></label>
            <input type="search" id="<?php echo esc_attr( $search_field_id ); ?>" name="search" placeholder="<?php esc_attr_e('Search your collection...', 'wp-movie-collector'); ?>" value="<?php echo esc_attr($search_term); ?>">
            <button type="submit" class="button"><?php esc_html_e('Search', 'wp-movie-collector'); ?></button>
        </form>
    </div>

    <!-- Filters -->
    <div class="wp-movie-collector-filters">
        <form method="get" action="<?php echo esc_url(get_permalink()); ?>" aria-label="<?php esc_attr_e('Filter and sort the collection', 'wp-movie-collector'); ?>">
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

            <div class="filter-group">
                <label for="sort-filter"><?php esc_html_e('Sort By', 'wp-movie-collector'); ?></label>
                <?php
                $sort_options = array(
                    'title-ASC'              => __('Title (A-Z)', 'wp-movie-collector'),
                    'title-DESC'             => __('Title (Z-A)', 'wp-movie-collector'),
                    'release_year-DESC'      => __('Year (Newest)', 'wp-movie-collector'),
                    'release_year-ASC'       => __('Year (Oldest)', 'wp-movie-collector'),
                    'created_at-DESC'        => __('Date Added (Newest)', 'wp-movie-collector'),
                    'created_at-ASC'         => __('Date Added (Oldest)', 'wp-movie-collector'),
                    'acquisition_date-DESC'  => __('Acquired (Newest)', 'wp-movie-collector'),
                    'acquisition_date-ASC'   => __('Acquired (Oldest)', 'wp-movie-collector'),
                    'format-ASC'             => __('Format', 'wp-movie-collector'),
                );
                // Director sort only applies to movies; hide when showing box sets only.
                if ( $type !== 'box_sets' ) {
                    $sort_options['director-ASC'] = __( 'Director', 'wp-movie-collector' );
                }
                ?>
                <select id="sort-filter" name="sort" class="wp-movie-collector-sort-select">
                    <?php foreach ($sort_options as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($current_sort, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="button"><?php esc_html_e('Apply Filters', 'wp-movie-collector'); ?></button>
                <a href="<?php echo esc_url(get_permalink()); ?>" class="button wp-movie-collector-clear-filters" data-base-url="<?php echo esc_url(get_permalink()); ?>"><?php esc_html_e('Clear Filters', 'wp-movie-collector'); ?></a>
            </div>
        </form>
    </div>
    
    <?php if ($current_page_items > 0) : ?>
        <!-- Results Grid -->
        <div class="wp-movie-collector-grid" role="list" aria-label="<?php esc_attr_e('Collection items', 'wp-movie-collector'); ?>">
            <?php if (isset($results['movies']) && !empty($results['movies'])) : ?>
                <?php foreach ($results['movies'] as $movie) : ?>
                    <div class="wp-movie-collector-item" role="listitem">
                        <div class="wp-movie-collector-item-image">
                            <?php if (!empty($movie['cover_image_url'])) : ?>
                                <img src="<?php echo esc_url($movie['cover_image_url']); ?>" alt="<?php echo esc_attr( ! empty( $movie['release_year'] ) ? sprintf( /* translators: 1: movie title, 2: release year */ __( '%1$s (%2$s)', 'wp-movie-collector' ), $movie['title'], $movie['release_year'] ) : $movie['title'] ); ?>">
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
                    <div class="wp-movie-collector-item wp-movie-collector-box-set-item" role="listitem">
                        <div class="wp-movie-collector-item-image">
                            <?php if (!empty($box_set['cover_image_url'])) : ?>
                                <img src="<?php echo esc_url($box_set['cover_image_url']); ?>" alt="<?php echo esc_attr( ! empty( $box_set['release_year'] ) ? sprintf( /* translators: 1: box set title, 2: release year, 3: "Box Set" label */ __( '%1$s (%2$s) — %3$s', 'wp-movie-collector' ), $box_set['title'], $box_set['release_year'], __( 'Box Set', 'wp-movie-collector' ) ) : sprintf( /* translators: 1: box set title, 2: "Box Set" label */ __( '%1$s — %2$s', 'wp-movie-collector' ), $box_set['title'], __( 'Box Set', 'wp-movie-collector' ) ) ); ?>">
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
        <?php
        // Only render the <nav> landmark when there is more than one page,
        // so we don't leave an empty navigation landmark in the DOM.
        if ($total_pages > 1) :
        ?>
        <nav class="wp-movie-collector-pagination" aria-label="<?php esc_attr_e('Collection pagination', 'wp-movie-collector'); ?>">
            <?php
            // Pagination — $total_pages was computed above; preserve filters and sort in links.
            $current_page = max(1, $paged);

                // Collect active query args so pagination links preserve them.
                $query_args = array();
                if ( ! empty( $search_term ) ) {
                    $query_args['search'] = $search_term;
                }
                if ( ! empty( $filter_format ) ) {
                    $query_args['format'] = $filter_format;
                }
                if ( ! empty( $filter_genre ) ) {
                    $query_args['genre'] = $filter_genre;
                }
                if ( ! empty( $filter_year ) ) {
                    $query_args['year'] = $filter_year;
                }
                if ( ! empty( $filter_director ) ) {
                    $query_args['director'] = $filter_director;
                }
                if ( ! empty( $filter_studio ) ) {
                    $query_args['studio'] = $filter_studio;
                }
                if ( $current_sort !== $default_sort ) {
                    $query_args['sort'] = $current_sort;
                }

                echo '<div class="nav-links">';

                // Previous page
                if ($current_page > 1) {
                    $prev_url = add_query_arg( $query_args, get_pagenum_link( $current_page - 1 ) );
                    echo '<a class="page-numbers prev" href="' . esc_url( $prev_url ) . '">&laquo; ' . esc_html__('Previous', 'wp-movie-collector') . '</a>';
                }

                // Page numbers
                for ($i = 1; $i <= $total_pages; $i++) {
                    if ($i === $current_page) {
                        echo '<span class="page-numbers current" aria-current="page">' . $i . '</span>';
                    } else {
                        $page_url = add_query_arg( $query_args, get_pagenum_link( $i ) );
                        echo '<a class="page-numbers" href="' . esc_url( $page_url ) . '">' . $i . '</a>';
                    }
                }

                // Next page
                if ($current_page < $total_pages) {
                    $next_url = add_query_arg( $query_args, get_pagenum_link( $current_page + 1 ) );
                    echo '<a class="page-numbers next" href="' . esc_url( $next_url ) . '">' . esc_html__('Next', 'wp-movie-collector') . ' &raquo;</a>';
                }

                echo '</div>';
            ?>
        </nav>
        <?php endif; ?>
    <?php else : ?>
        <div class="wp-movie-collector-no-results" role="status" aria-live="polite">
            <p><?php esc_html_e('No movies or box sets found matching your criteria.', 'wp-movie-collector'); ?></p>
        </div>
    <?php endif; ?>
</div>
