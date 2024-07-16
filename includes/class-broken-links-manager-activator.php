<?php

class Broken_Links_Manager_Activator {

    public static function activate() {
        self::create_broken_links_table();
        self::set_default_options();
    }

    private static function create_broken_links_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'broken_links';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            url varchar(255) NOT NULL,
            status_code smallint(4) NOT NULL,
            found_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            KEY post_id (post_id),
            KEY status_code (status_code)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    private static function set_default_options() {
        $default_options = array(
            'scan_frequency' => 'weekly',
            'email_notifications' => true,
            'notification_email' => get_option('admin_email')
        );

        foreach ($default_options as $option_name => $option_value) {
            if (get_option($option_name) === false) {
                add_option($option_name, $option_value);
            }
        }
    }
}