<?php

class Broken_Links_Manager_Activator {

    public static function activate() {
        self::create_broken_links_table();
        self::set_default_options();
    }

    private static function create_broken_links_table() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Create posts table
        $table_name = $wpdb->prefix . 'blm_posts';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            wordpress_id bigint(20) NOT NULL,
            title text NOT NULL,
            public_link varchar(255) NOT NULL,
            date_last_scanned datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY wordpress_id (wordpress_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Create links table
        $table_name = $wpdb->prefix . 'blm_links';
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            wordpress_id bigint(20) NOT NULL,
            link varchar(255) NOT NULL,
            text_of_link text NOT NULL,
            status smallint(4),
            date_deleted datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY wordpress_id (wordpress_id),
            KEY link (link)
        ) $charset_collate;";

        dbDelta($sql);
        
        add_option('broken_links_manager_version', BROKEN_LINKS_MANAGER_VERSION);
        add_option('blm_db_version', '1.0');
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