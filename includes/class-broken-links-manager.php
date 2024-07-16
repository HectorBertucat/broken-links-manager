<?php

class Broken_Links_Manager {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->plugin_name = 'broken-links-manager';
        $this->version = BROKEN_LINKS_MANAGER_VERSION;
        
        if ($this->check_requirements()) {
            $this->load_dependencies();
            $this->define_admin_hooks();
            $this->define_ajax_hooks();
        }
    }

    private function load_dependencies() {
        require_once BROKEN_LINKS_MANAGER_PLUGIN_DIR . 'includes/class-broken-links-manager-loader.php';
        require_once BROKEN_LINKS_MANAGER_PLUGIN_DIR . 'includes/class-broken-links-scanner.php';
        require_once BROKEN_LINKS_MANAGER_PLUGIN_DIR . 'includes/class-broken-links-remover.php';
        require_once BROKEN_LINKS_MANAGER_PLUGIN_DIR . 'includes/class-logger.php';
        require_once BROKEN_LINKS_MANAGER_PLUGIN_DIR . 'includes/class-background-process.php';
        require_once BROKEN_LINKS_MANAGER_PLUGIN_DIR . 'admin/class-broken-links-manager-admin.php';

        $this->loader = new Broken_Links_Manager_Loader();
    }

    private function define_admin_hooks() {
        $plugin_admin = new Broken_Links_Manager_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
    }

    private function define_ajax_hooks() {
        $this->loader->add_action('wp_ajax_scan_links', $this, 'ajax_scan_links');
        $this->loader->add_action('wp_ajax_remove_link', $this, 'ajax_remove_link');
        $this->loader->add_action('wp_ajax_bulk_remove_links', $this, 'ajax_bulk_remove_links');
        $this->loader->add_action('wp_ajax_get_logs', $this, 'ajax_get_logs');
    }

    public function ajax_scan_links() {
        check_ajax_referer('broken_links_manager_nonce', 'security');

        $scanner = new Broken_Links_Scanner(new Logger());
        $background_process = new Broken_Links_Background_Process();
        
        $posts = get_posts(array('posts_per_page' => -1, 'post_type' => 'any'));
        foreach ($posts as $post) {
            $background_process->push_to_queue($post);
        }
        $background_process->save()->dispatch();

        wp_send_json_success('Scan initiated. It will run in the background.');
    }

    public function ajax_remove_link() {
        check_ajax_referer('broken_links_manager_nonce', 'security');

        $post_id = intval($_POST['post_id']);
        $url = sanitize_text_field($_POST['url']);

        $remover = new Broken_Links_Remover(new Logger());
        $result = $remover->remove_link($post_id, $url);

        if ($result) {
            wp_send_json_success('Link removed successfully.');
        } else {
            wp_send_json_error('Failed to remove link.');
        }
    }

    public function ajax_bulk_remove_links() {
        check_ajax_referer('broken_links_manager_nonce', 'security');

        $status_code = intval($_POST['status_code']);

        $remover = new Broken_Links_Remover(new Logger());
        $result = $remover->bulk_remove_links($status_code);

        if ($result) {
            wp_send_json_success("All links with status code {$status_code} removed successfully.");
        } else {
            wp_send_json_error("Failed to remove links with status code {$status_code}.");
        }
    }

    public function ajax_get_logs() {
        check_ajax_referer('broken_links_manager_nonce', 'security');

        $logger = new Logger();
        $logs = $logger->get_logs();

        wp_send_json_success($logs);
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }

    private function check_requirements() {
        $required_extensions = array('dom', 'mbstring');
        $missing_extensions = array();
    
        foreach ($required_extensions as $ext) {
            if (!extension_loaded($ext)) {
                $missing_extensions[] = $ext;
            }
        }
    
        if (!empty($missing_extensions)) {
            add_action('admin_notices', function() use ($missing_extensions) {
                $message = sprintf(
                    __('Broken Links Manager requires the following PHP extensions: %s. Please contact your hosting provider to enable them.', 'broken-links-manager'),
                    implode(', ', $missing_extensions)
                );
                echo '<div class="error"><p>' . $message . '</p></div>';
            });
            return false;
        }
    
        return true;
    }
}