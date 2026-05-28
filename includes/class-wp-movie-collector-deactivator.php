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
        // rewrite rules. Unregister them first, then flush so the plugin's
        // rewrite rules are actually removed.
        //
        // The slugs (movie, genre, actor, ...) are generic, so we only
        // unregister objects that are actually ours: our CPTs are registered
        // under this plugin's admin menu, and our taxonomies are attached to
        // our CPTs. This avoids clobbering a same-named type registered by
        // another plugin on the same request.
        foreach ( array( 'movie', 'box_set' ) as $post_type ) {
            $object = get_post_type_object( $post_type );
            if ( $object && 'wp-movie-collector' === $object->show_in_menu ) {
                unregister_post_type( $post_type );
            }
        }

        foreach ( array( 'genre', 'director', 'studio', 'actor' ) as $taxonomy ) {
            $object = get_taxonomy( $taxonomy );
            if ( $object && array_intersect( array( 'movie', 'box_set' ), (array) $object->object_type ) ) {
                unregister_taxonomy( $taxonomy );
            }
        }

        flush_rewrite_rules();
    }
}
