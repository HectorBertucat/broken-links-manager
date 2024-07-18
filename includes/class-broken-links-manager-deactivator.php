<?php

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Broken_Links_Manager
 * @subpackage Broken_Links_Manager/includes
 */
class Broken_Links_Manager_Deactivator {

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function deactivate() {
        // Perform any necessary cleanup here
        self::maybe_delete_tables();
        self::clean_logs();
        
        // Clear any scheduled cron jobs if you've set any
        wp_clear_scheduled_hook('broken_links_manager_cron');

        // Remove any options we've set
        delete_option('blm_scan_status');
        delete_option('blm_db_version');
    }

    /**
     * Delete plugin tables if the user chooses to do so.
     * 
     * In a real-world scenario, you might want to add a setting in your plugin's
     * options page to allow users to choose whether they want to delete the data
     * upon deactivation. For now, we'll just delete the tables.
     */
    private static function maybe_delete_tables() {
        global $wpdb;
        $tables = array(
            $wpdb->prefix . 'blm_posts',
            $wpdb->prefix . 'blm_links'
        );

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }

    /**
     * Clean up the logs created by the plugin.
     */
    private static function clean_logs() {
        $logger = new Logger();
        $logger->clear_logs();
    }
}