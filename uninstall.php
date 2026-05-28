<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @since      1.0.0
 * @package    WP_Movie_Collector
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Remove all plugin data for the current site.
 *
 * Deletes plugin options (by explicit name and by prefix), all plugin
 * transients (caches, circuit-breaker state, stats, per-user form errors),
 * the custom post types/taxonomies data, and the custom database tables.
 */
function wp_movie_collector_uninstall_site() {
    global $wpdb;

    // Delete known plugin options explicitly, then sweep any remaining
    // wp_movie_collector_* options by prefix so nothing is left behind.
    delete_option('wp_movie_collector_version');
    delete_option('wp_movie_collector_tmdb_api_key');
    delete_option('wp_movie_collector_omdb_api_key');
    delete_option('wp_movie_collector_barcode_api_key');
    delete_option('wp_movie_collector_cache_version');

    $option_like = $wpdb->esc_like('wp_movie_collector_') . '%';
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $option_like
        )
    );

    // Delete all plugin transients (both the value and timeout rows).
    $transient_like = $wpdb->esc_like('_transient_wp_movie') . '%';
    $transient_timeout_like = $wpdb->esc_like('_transient_timeout_wp_movie') . '%';
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $transient_like,
            $transient_timeout_like
        )
    );

    // Delete custom post types data.
    $post_types = array('movie', 'box_set');
    foreach ($post_types as $post_type) {
        $posts = get_posts(array(
            'post_type' => $post_type,
            'numberposts' => -1,
            'post_status' => 'any',
            'fields' => 'ids',
        ));

        foreach ($posts as $post_id) {
            wp_delete_post($post_id, true);
        }
    }

    // Delete custom taxonomies data.
    $taxonomies = array('genre', 'director', 'studio', 'actor');
    foreach ($taxonomies as $taxonomy) {
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
        ));

        if (is_wp_error($terms)) {
            continue;
        }

        foreach ($terms as $term) {
            wp_delete_term($term->term_id, $taxonomy);
        }
    }

    // Remove database tables.
    $tables = array(
        $wpdb->prefix . 'movie_collection',
        $wpdb->prefix . 'movie_box_sets',
        $wpdb->prefix . 'movie_box_set_relationships',
    );

    foreach ($tables as $table) {
        $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
    }
}

if (is_multisite()) {
    $site_ids = get_sites(array('fields' => 'ids'));
    foreach ($site_ids as $site_id) {
        switch_to_blog($site_id);
        wp_movie_collector_uninstall_site();
        restore_current_blog();
    }
} else {
    wp_movie_collector_uninstall_site();
}
