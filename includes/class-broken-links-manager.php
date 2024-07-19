<?php

class Broken_Links_Manager {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        if (defined('BROKEN_LINKS_MANAGER_VERSION')) {
            $this->version = BROKEN_LINKS_MANAGER_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'broken-links-manager';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_ajax_hooks();
    }

    private function load_dependencies() {
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-broken-links-manager-loader.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-broken-links-scanner.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-broken-links-remover.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-logger.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-background-process.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-broken-links-manager-admin.php';

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
        $this->loader->add_action('wp_ajax_get_broken_links', $this, 'ajax_get_broken_links');
        $this->loader->add_action('wp_ajax_check_scan_status', $this, 'ajax_check_scan_status');
        $this->loader->add_action('wp_ajax_pre_scan_links', $this, 'ajax_pre_scan_links');
        $this->loader->add_action('wp_ajax_check_links_batch', $this, 'ajax_check_links_batch');
        $this->loader->add_action('wp_ajax_stop_scan', $this, 'ajax_stop_scan');
    }

    private function clear_existing_links() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'broken_links';
        $wpdb->query("TRUNCATE TABLE $table_name");
    }

    public function ajax_scan_links() {
        check_ajax_referer('broken_links_manager_nonce', 'security');

        $logger = new Logger();
        $logger->log('Scan initiated via AJAX');

        $batch_size = 10; // Number of posts to scan in each batch
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

        $total_posts = wp_count_posts()->publish;
        $posts = get_posts(array('posts_per_page' => $batch_size, 'offset' => $offset, 'post_type' => 'any', 'post_status' => 'publish'));

        $scanner = new Broken_Links_Scanner($logger);
        $links_found = 0;
        $links_checked = 0;
        $total_links = 0;

        foreach ($posts as $post) {
            $post_links = $scanner->count_links_in_post($post);
            $total_links += $post_links;
            $result = $scanner->scan_post_content($post);
            if ($result['links_found'] > 0) {
                $links_found += $result['links_found'];
            }
            $links_checked += $result['links_checked'];
        }

        $progress = array(
            'posts_scanned' => count($posts),
            'total_posts' => $total_posts,
            'links_found' => $links_found,
            'links_checked' => $links_checked,
            'total_links' => $total_links,
            'offset' => $offset + count($posts),
            'is_complete' => ($offset + count($posts) >= $total_posts)
        );

        $logger->log("Batch scan completed. Progress: " . json_encode($progress));
        wp_send_json_success($progress);
    }

    public function ajax_remove_link() {
        check_ajax_referer('broken_links_manager_nonce', 'security');

        $link_id = isset($_POST['link_id']) ? intval($_POST['link_id']) : 0;

        $this->logger->log("AJAX remove_link called with link_id: $link_id");

        if (!$link_id) {
            $this->logger->log("Error: Invalid link ID received");
            wp_send_json_error('Invalid link ID');
            return;
        }

        $scanner = new Broken_Links_Scanner($this->logger);
        $result = $scanner->remove_from_database($link_id);

        if ($result) {
            $this->logger->log("Link removal successful for ID: $link_id");
            wp_send_json_success('Link removed successfully');
        } else {
            $this->logger->log("Link removal failed for ID: $link_id");
            wp_send_json_error('Failed to remove link');
        }
    }

    public function ajax_pre_scan_links() {
        check_ajax_referer('broken_links_manager_nonce', 'security');

        if (get_option('blm_scan_status') !== 'running') {
            update_option('blm_scan_status', 'running');
        }

        $logger = new Logger();
        $logger->log('Pre-scan initiated via AJAX');

        $batch_size = 10; // Number of posts to scan in each batch
        $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;

        $total_posts = wp_count_posts()->publish;
        $posts = get_posts(array('posts_per_page' => $batch_size, 'offset' => $offset, 'post_type' => 'any', 'post_status' => 'publish'));

        $scanner = new Broken_Links_Scanner($logger);
        $links_found = 0;

        foreach ($posts as $post) {
            if (get_option('blm_scan_status') !== 'running') {
                break;
            }
            $links_found += $scanner->pre_scan_post($post);
        }

        $progress = array(
            'posts_scanned' => count($posts),
            'total_posts' => $total_posts,
            'links_found' => $links_found,
            'offset' => $offset + count($posts),
            'is_complete' => ($offset + count($posts) >= $total_posts) || (get_option('blm_scan_status') !== 'running')
        );

        $logger->log("Pre-scan batch completed. Progress: " . json_encode($progress));
        wp_send_json_success($progress);
    }

    public function ajax_check_links_batch() {
        check_ajax_referer('broken_links_manager_nonce', 'security');

        if (get_option('blm_scan_status') !== 'running') {
            update_option('blm_scan_status', 'running');
        }

        $logger = new Logger();
        $logger->log('Link check batch initiated via AJAX');

        $batch_size = 50; // Number of links to check in each batch
        $scanner = new Broken_Links_Scanner($logger);

        $total_links = $scanner->get_total_links_count();
        $unchecked_before = $scanner->get_unchecked_links_count();
        $checked_count = $scanner->check_links_batch($batch_size);
        $unchecked_after = $scanner->get_unchecked_links_count();

        $progress = array(
            'links_checked' => $total_links - $unchecked_after,
            'links_remaining' => $unchecked_after,
            'total_links' => $total_links,
            'is_complete' => ($unchecked_after == 0) || (get_option('blm_scan_status') !== 'running')
        );

        $logger->log("Link check batch completed. Progress: " . json_encode($progress));
        wp_send_json_success($progress);
    }


    public function ajax_stop_scan() {
        check_ajax_referer('broken_links_manager_nonce', 'security');
        update_option('blm_scan_status', 'stopped');
        wp_send_json_success('Scan stopped');
    }

    public function ajax_bulk_remove_links() {
        $logger = new Logger();
        check_ajax_referer('broken_links_manager_nonce', 'security');

        $logger->log('removing ' . json_encode($_POST['links']));

        $links = $_POST['links'];
        $remover = new Broken_Links_Remover(new Logger());
        $remover->bulk_remove_links($links);

        wp_send_json_success('Bulk removal completed.');
    }

    public function ajax_get_logs() {
        check_ajax_referer('broken_links_manager_nonce', 'security');

        $logger = new Logger();
        $logs = $logger->get_logs();

        wp_send_json_success($logs);
    }

    public function ajax_get_broken_links() {
        check_ajax_referer('broken_links_manager_nonce', 'security');

        global $wpdb;
        $links_table = $wpdb->prefix . 'blm_links';
        $posts_table = $wpdb->prefix . 'blm_posts';

        $logger = new Logger();
        $logger->log("Ajax get links initiated");

        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 50;
        $offset = ($page - 1) * $per_page;

        $show_errors_only = isset($_POST['show_errors_only']) ? $_POST['show_errors_only'] === 'true' : false;
        $logger->log("Show errors only: " . ($show_errors_only ? 'true' : 'false'));

        $where_clause = $show_errors_only ? "WHERE l.status >= 400 OR l.status = 0" : "";

        $total_links_query = "
            SELECT COUNT(DISTINCT l.link) 
            FROM $links_table l
            JOIN $posts_table p ON l.wordpress_id = p.wordpress_id
            $where_clause
        ";
        $logger->log("Total links query: " . $total_links_query);

        $total_links = $wpdb->get_var($total_links_query);
        $logger->log("Total links found: " . $total_links);

        if ($total_links === null) {
            $logger->log("Error in total links query: " . $wpdb->last_error);
            wp_send_json_error("Database error when counting total links");
            return;
        }

        $links_query = "
            SELECT l.*, p.title as post_title, p.public_link
            FROM $links_table l
            JOIN $posts_table p ON l.wordpress_id = p.wordpress_id
            $where_clause
            GROUP BY l.link
            ORDER BY l.id DESC
            LIMIT $offset, $per_page
        ";
        $logger->log("Links query: " . $links_query);

        $links = $wpdb->get_results($links_query);
        $logger->log("Number of links retrieved: " . count($links));

        if ($links === null) {
            $logger->log("Error in links query: " . $wpdb->last_error);
            wp_send_json_error("Database error when retrieving links");
            return;
        }

        $html = '';
        foreach ($links as $link) {
            $class = ($link->status >= 400 || $link->status == 0) ? 'broken' : 'working';
            $html .= "<tr class='$class'>";
            $html .= "<td><input type='checkbox' data-id='{$link->id}' data-post-id='{$link->wordpress_id}' data-url='" . esc_attr($link->link) . "' class='blm-link-checkbox' data-id='{$link->id}'></td>";
            $html .= "<td>{$link->wordpress_id}</td>";
            $html .= "<td><a href='{$link->public_link}' target='_blank'>" . esc_html($link->post_title) . "</a></td>";
            $html .= "<td>" . esc_html($link->link) . "</td>";
            $html .= "<td>" . esc_html($link->text_of_link) . "</td>";
            $html .= "<td>{$link->status}</td>";
            $html .= "</tr>";
        }

        $logger->log("HTML generated. Length: " . strlen($html));

        wp_send_json_success(array(
            'html' => $html,
            'total_pages' => ceil($total_links / $per_page),
            'current_page' => $page
        ));
    }


    public function ajax_check_scan_status() {
        check_ajax_referer('broken_links_manager_nonce', 'security');

        $logger = new Logger();
        $logs = $logger->get_logs(10); // Get the last 10 log entries

        $status = 'unknown';
        $message = 'Unable to determine scan status.';

        foreach ($logs as $log) {
            if (strpos($log, 'Background process: Broken links scan completed') !== false) {
                $status = 'completed';
                $message = 'Scan completed.';
                break;
            } elseif (strpos($log, 'Background process: Handle method called') !== false) {
                $status = 'in_progress';
                $message = 'Scan in progress.';
                break;
            }
        }

        wp_send_json_success(array(
            'status' => $status,
            'message' => $message,
            'logs' => $logs
        ));
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
}