<?php

class Broken_Links_Manager_Admin {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/broken-links-manager-admin.css', array(), $this->version, 'all');
    }

    public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/broken-links-manager-admin.js', array('jquery'), $this->version, false);
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
        include_once 'partials/broken-links-manager-admin-display.php';
    }

    // Add more admin-related methods here as needed
}