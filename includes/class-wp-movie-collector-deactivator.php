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

        // Flush rewrite rules so the plugin's custom post type / taxonomy
        // rewrite rules are removed once the plugin is no longer active.
        flush_rewrite_rules();
    }
}
