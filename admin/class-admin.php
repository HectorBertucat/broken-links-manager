<?php

class Broken_Links_Manager_Admin {
    private $plugin_name;
    private $version;
    private $scanner;
    private $remover;
    private $logger;

    public function __construct($plugin_name, $version, $scanner, $remover, $logger) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->scanner = $scanner;
        $this->remover = $remover;
        $this->logger = $logger;
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/broken-links-manager-admin.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/broken-links-manager-admin.js', array('jquery'), $this->version, false);
        wp_localize_script($this->plugin_name, 'broken_links_manager_ajax', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    public function add_plugin_admin_menu() {
        add_menu_page(
            'Broken Links Manager', 
            'Broken Links', 
            'manage_options', 
            $this->plugin_name, 
            array($this, 'display_plugin_admin_page'),
            'dashicons-admin-links',
            80
        );
    }

    public function display_plugin_admin_page() {
        include_once BROKEN_LINKS_MANAGER_PLUGIN_DIR . 'admin/partials/broken-links-manager-admin-display.php';
    }

    public function handle_ajax_scan_links() {
        check_ajax_referer('broken_links_manager_nonce', 'security');
        $this->scanner->start_scan();
        wp_send_json_success('Scan completed successfully.');
    }

    public function handle_ajax_remove_link() {
        check_ajax_referer('broken_links_manager_nonce', 'security');
        $post_id = intval($_POST['post_id']);
        $url = sanitize_text_field($_POST['url']);
        $result = $this->remover->remove_link($post_id, $url);
        if ($result) {
            wp_send_json_success('Link removed successfully.');
        } else {
            wp_send_json_error('Failed to remove link.');
        }
    }

    public function handle_ajax_bulk_remove_links() {
        check_ajax_referer('broken_links_manager_nonce', 'security');
        $status_code = intval($_POST['status_code']);
        $result = $this->remover->bulk_remove_links($status_code);
        if ($result) {
            wp_send_json_success("All links with status code {$status_code} removed successfully.");
        } else {
            wp_send_json_error("Failed to remove links with status code {$status_code}.");
        }
    }

    public function get_broken_links() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'broken_links';
        return $wpdb->get_results("SELECT * FROM $table_name ORDER BY found_date DESC");
    }
}