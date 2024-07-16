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
        // For example, you might want to remove the database table if you don't want to persist the data
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'broken_links';
        $sql = "DROP TABLE IF EXISTS $table_name";
        $wpdb->query($sql);
        
        // Note: Uncomment the above lines if you want to remove the table on deactivation
        // Be cautious about removing data, as users might expect it to persist

        // Clear any scheduled cron jobs if you've set any
        wp_clear_scheduled_hook('broken_links_manager_cron');

        // Optionally, you can also clear the logs
        $upload_dir = wp_upload_dir();
        $log_file = $upload_dir['basedir'] . '/broken-links-manager-log.txt';
        if (file_exists($log_file)) {
            unlink($log_file);
        }
    }
}