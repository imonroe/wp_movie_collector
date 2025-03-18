<?php
/**
 * Fired during plugin deactivation.
 *
 * @since      1.0.0
 * @package    WP_Movie_Collector
 */

class WP_Movie_Collector_Deactivator {

    /**
     * Clean up when the plugin is deactivated.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Nothing to do here for now
        // We don't want to delete the database tables on deactivation
        // to avoid data loss. We'll only do that on uninstall if needed.
    }
}
