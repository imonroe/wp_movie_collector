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

// Delete plugin options
delete_option('wp_movie_collector_version');
delete_option('wp_movie_collector_tmdb_api_key');
delete_option('wp_movie_collector_omdb_api_key');

// Delete custom post types and taxonomies data
$post_types = array('movie', 'box_set');
foreach ($post_types as $post_type) {
    $posts = get_posts(array(
        'post_type' => $post_type,
        'numberposts' => -1,
        'post_status' => 'any',
    ));
    
    foreach ($posts as $post) {
        wp_delete_post($post->ID, true);
    }
}

// Delete custom taxonomies data
$taxonomies = array('genre', 'director', 'studio', 'actor');
foreach ($taxonomies as $taxonomy) {
    $terms = get_terms(array(
        'taxonomy' => $taxonomy,
        'hide_empty' => false,
    ));
    
    foreach ($terms as $term) {
        wp_delete_term($term->term_id, $taxonomy);
    }
}

// Remove database tables
global $wpdb;
$tables = array(
    $wpdb->prefix . 'movie_collection',
    $wpdb->prefix . 'movie_box_sets',
    $wpdb->prefix . 'movie_box_set_relationships',
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}