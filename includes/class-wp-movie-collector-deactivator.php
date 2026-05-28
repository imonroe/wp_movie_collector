<?php
/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 * @package    WP_Movie_Collector
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

class WP_Movie_Collector_Deactivator {

    /**
     * Clean up when the plugin is deactivated.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // We don't delete the database tables on deactivation to avoid data
        // loss; that only happens on uninstall.

        // The CPTs/taxonomies have already been registered on `init` by the
        // time deactivation runs, so flushing alone would persist their
        // rewrite rules. Unregister them first (guarded), then flush so the
        // plugin's rewrite rules are actually removed.
        foreach ( array( 'movie', 'box_set' ) as $post_type ) {
            if ( post_type_exists( $post_type ) ) {
                unregister_post_type( $post_type );
            }
        }

        foreach ( array( 'genre', 'director', 'studio', 'actor' ) as $taxonomy ) {
            if ( taxonomy_exists( $taxonomy ) ) {
                unregister_taxonomy( $taxonomy );
            }
        }

        flush_rewrite_rules();
    }
}
