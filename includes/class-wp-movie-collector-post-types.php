<?php
/**
 * Register custom post types and taxonomies for the plugin.
 *
 * @since      1.0.0
 * @package    WP_Movie_Collector
 */

class WP_Movie_Collector_Post_Types {

    /**
     * Register custom post types.
     *
     * @since    1.0.0
     */
    public function register_post_types() {
        // Register the 'movie' post type
        register_post_type('movie', array(
            'labels' => array(
                'name'               => _x('Movies', 'post type general name', 'wp-movie-collector'),
                'singular_name'      => _x('Movie', 'post type singular name', 'wp-movie-collector'),
                'menu_name'          => _x('Movies', 'admin menu', 'wp-movie-collector'),
                'name_admin_bar'     => _x('Movie', 'add new on admin bar', 'wp-movie-collector'),
                'add_new'            => _x('Add New', 'movie', 'wp-movie-collector'),
                'add_new_item'       => __('Add New Movie', 'wp-movie-collector'),
                'new_item'           => __('New Movie', 'wp-movie-collector'),
                'edit_item'          => __('Edit Movie', 'wp-movie-collector'),
                'view_item'          => __('View Movie', 'wp-movie-collector'),
                'all_items'          => __('All Movies', 'wp-movie-collector'),
                'search_items'       => __('Search Movies', 'wp-movie-collector'),
                'parent_item_colon'  => __('Parent Movies:', 'wp-movie-collector'),
                'not_found'          => __('No movies found.', 'wp-movie-collector'),
                'not_found_in_trash' => __('No movies found in Trash.', 'wp-movie-collector')
            ),
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'movie'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => null,
            'supports'            => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest'        => true,
            'menu_icon'           => 'dashicons-video-alt2',
        ));

        // Register the 'box_set' post type
        register_post_type('box_set', array(
            'labels' => array(
                'name'               => _x('Box Sets', 'post type general name', 'wp-movie-collector'),
                'singular_name'      => _x('Box Set', 'post type singular name', 'wp-movie-collector'),
                'menu_name'          => _x('Box Sets', 'admin menu', 'wp-movie-collector'),
                'name_admin_bar'     => _x('Box Set', 'add new on admin bar', 'wp-movie-collector'),
                'add_new'            => _x('Add New', 'box set', 'wp-movie-collector'),
                'add_new_item'       => __('Add New Box Set', 'wp-movie-collector'),
                'new_item'           => __('New Box Set', 'wp-movie-collector'),
                'edit_item'          => __('Edit Box Set', 'wp-movie-collector'),
                'view_item'          => __('View Box Set', 'wp-movie-collector'),
                'all_items'          => __('All Box Sets', 'wp-movie-collector'),
                'search_items'       => __('Search Box Sets', 'wp-movie-collector'),
                'parent_item_colon'  => __('Parent Box Sets:', 'wp-movie-collector'),
                'not_found'          => __('No box sets found.', 'wp-movie-collector'),
                'not_found_in_trash' => __('No box sets found in Trash.', 'wp-movie-collector')
            ),
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'query_var'           => true,
            'rewrite'             => array('slug' => 'box-set'),
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => null,
            'supports'            => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields'),
            'show_in_rest'        => true,
            'menu_icon'           => 'dashicons-album',
        ));
    }

    /**
     * Register custom taxonomies.
     *
     * @since    1.0.0
     */
    public function register_taxonomies() {
        // Register the 'genre' taxonomy
        register_taxonomy('genre', array('movie', 'box_set'), array(
            'labels' => array(
                'name'              => _x('Genres', 'taxonomy general name', 'wp-movie-collector'),
                'singular_name'     => _x('Genre', 'taxonomy singular name', 'wp-movie-collector'),
                'search_items'      => __('Search Genres', 'wp-movie-collector'),
                'all_items'         => __('All Genres', 'wp-movie-collector'),
                'parent_item'       => __('Parent Genre', 'wp-movie-collector'),
                'parent_item_colon' => __('Parent Genre:', 'wp-movie-collector'),
                'edit_item'         => __('Edit Genre', 'wp-movie-collector'),
                'update_item'       => __('Update Genre', 'wp-movie-collector'),
                'add_new_item'      => __('Add New Genre', 'wp-movie-collector'),
                'new_item_name'     => __('New Genre Name', 'wp-movie-collector'),
                'menu_name'         => __('Genre', 'wp-movie-collector'),
            ),
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'genre'),
            'show_in_rest'      => true,
        ));

        // Register the 'director' taxonomy
        register_taxonomy('director', array('movie', 'box_set'), array(
            'labels' => array(
                'name'              => _x('Directors', 'taxonomy general name', 'wp-movie-collector'),
                'singular_name'     => _x('Director', 'taxonomy singular name', 'wp-movie-collector'),
                'search_items'      => __('Search Directors', 'wp-movie-collector'),
                'all_items'         => __('All Directors', 'wp-movie-collector'),
                'parent_item'       => __('Parent Director', 'wp-movie-collector'),
                'parent_item_colon' => __('Parent Director:', 'wp-movie-collector'),
                'edit_item'         => __('Edit Director', 'wp-movie-collector'),
                'update_item'       => __('Update Director', 'wp-movie-collector'),
                'add_new_item'      => __('Add New Director', 'wp-movie-collector'),
                'new_item_name'     => __('New Director Name', 'wp-movie-collector'),
                'menu_name'         => __('Director', 'wp-movie-collector'),
            ),
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'director'),
            'show_in_rest'      => true,
        ));

        // Register the 'studio' taxonomy
        register_taxonomy('studio', array('movie', 'box_set'), array(
            'labels' => array(
                'name'              => _x('Studios', 'taxonomy general name', 'wp-movie-collector'),
                'singular_name'     => _x('Studio', 'taxonomy singular name', 'wp-movie-collector'),
                'search_items'      => __('Search Studios', 'wp-movie-collector'),
                'all_items'         => __('All Studios', 'wp-movie-collector'),
                'parent_item'       => __('Parent Studio', 'wp-movie-collector'),
                'parent_item_colon' => __('Parent Studio:', 'wp-movie-collector'),
                'edit_item'         => __('Edit Studio', 'wp-movie-collector'),
                'update_item'       => __('Update Studio', 'wp-movie-collector'),
                'add_new_item'      => __('Add New Studio', 'wp-movie-collector'),
                'new_item_name'     => __('New Studio Name', 'wp-movie-collector'),
                'menu_name'         => __('Studio', 'wp-movie-collector'),
            ),
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'studio'),
            'show_in_rest'      => true,
        ));

        // Register the 'actor' taxonomy
        register_taxonomy('actor', array('movie', 'box_set'), array(
            'labels' => array(
                'name'              => _x('Actors', 'taxonomy general name', 'wp-movie-collector'),
                'singular_name'     => _x('Actor', 'taxonomy singular name', 'wp-movie-collector'),
                'search_items'      => __('Search Actors', 'wp-movie-collector'),
                'all_items'         => __('All Actors', 'wp-movie-collector'),
                'parent_item'       => __('Parent Actor', 'wp-movie-collector'),
                'parent_item_colon' => __('Parent Actor:', 'wp-movie-collector'),
                'edit_item'         => __('Edit Actor', 'wp-movie-collector'),
                'update_item'       => __('Update Actor', 'wp-movie-collector'),
                'add_new_item'      => __('Add New Actor', 'wp-movie-collector'),
                'new_item_name'     => __('New Actor Name', 'wp-movie-collector'),
                'menu_name'         => __('Actor', 'wp-movie-collector'),
            ),
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'actor'),
            'show_in_rest'      => true,
        ));
    }
}
