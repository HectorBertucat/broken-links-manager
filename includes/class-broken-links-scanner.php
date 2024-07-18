<?php

class Broken_Links_Scanner {
    private $db;
    private $logger;
    private $batch_size = 10; // Number of posts to process in each batch

    public function __construct($logger) {
        global $wpdb;
        $this->db = $wpdb;
        $this->logger = $logger;
    }

    public function start_scan() {
        $this->logger->log('Starting broken links scan');
        $total_posts = $this->get_total_posts();
        $batches = ceil($total_posts / $this->batch_size);

        for ($i = 0; $i < $batches; $i++) {
            $this->process_batch($i);
        }

        $this->logger->log('Broken links scan completed');
    }

    private function get_total_posts() {
        $count_query = "SELECT COUNT(*) FROM {$this->db->posts} WHERE post_status = 'publish'";
        return $this->db->get_var($count_query);
    }

    private function process_batch($batch_number) {
        $offset = $batch_number * $this->batch_size;
        $query = $this->db->prepare(
            "SELECT ID, post_content FROM {$this->db->posts} 
            WHERE post_status = 'publish' 
            LIMIT %d OFFSET %d",
            $this->batch_size,
            $offset
        );

        $posts = $this->db->get_results($query);

        foreach ($posts as $post) {
            $this->scan_post_content($post);
        }
    }

    public function scan_post_content($post) {
        $this->logger->log("Scanner: Starting scan for post ID: {$post->ID}");

        $content = $post->post_content;
        if (empty($content)) {
            $this->logger->log("Scanner: Post {$post->ID} has no content to scan.");
            return array('links_found' => 0, 'links_checked' => 0);
        }

        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        $links = $dom->getElementsByTagName('a');

        $this->logger->log("Scanner: Found " . $links->length . " links in post {$post->ID}");

        $links_found = 0;
        $links_checked = 0;

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if ($href) {
                $links_checked++;
                $this->logger->log("Scanner: Checking link: {$href}");
                $status_code = $this->check_link_status($href);
                $this->logger->log("Scanner: Status code for {$href}: {$status_code}");
                $this->store_link($post->ID, $href, $status_code);
                if ($status_code >= 400 || $status_code == 0) {
                    $links_found++;
                }
            }
        }

        $this->logger->log("Scanner: Completed scan for post ID: {$post->ID}. Broken links found: {$links_found}");
        return array('links_found' => $links_found, 'links_checked' => $links_checked);
    }

    private function check_link_status($url) {
        $this->logger->log("Scanner: Checking status for URL: {$url}");
        $response = wp_remote_head($url, array('timeout' => 5, 'sslverify' => false));
        if (is_wp_error($response)) {
            $this->logger->log("Scanner: Error checking {$url}: " . $response->get_error_message());
            return 0; // Use 0 to indicate an error occurred
        }
        $status_code = wp_remote_retrieve_response_code($response);
        $this->logger->log("Scanner: Status code received for {$url}: {$status_code}");
        return $status_code;
    }

    private function store_link($post_id, $url, $status_code) {
        $this->logger->log("Scanner: Storing link for post ID: {$post_id}, URL: {$url}, Status Code: {$status_code}");
        $table_name = $this->db->prefix . 'broken_links';
        $result = $this->db->insert(
            $table_name,
            array(
                'post_id' => $post_id,
                'url' => $url,
                'status_code' => $status_code,
                'found_date' => current_time('mysql')
            ),
            array('%d', '%s', '%d', '%s')
        );

        if ($result) {
            $this->logger->log("Scanner: Successfully stored link: Post ID {$post_id}, URL {$url}, Status Code {$status_code}");
        } else {
            $this->logger->log("Scanner: Failed to store link: Post ID {$post_id}, URL {$url}, Status Code {$status_code}");
        }
    }

    public function count_links_in_post($post) {
        $content = $post->post_content;
        if (empty($content)) {
            return 0;
        }

        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        $links = $dom->getElementsByTagName('a');

        return $links->length;
    }

    public function verify_stored_links() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'broken_links';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $this->logger->log("Verification: Total links stored in database: $count");

        $sample = $wpdb->get_results("SELECT * FROM $table_name LIMIT 5");
        foreach ($sample as $link) {
            $this->logger->log("Verification: Sample link - Post ID: {$link->post_id}, URL: {$link->url}, Status Code: {$link->status_code}");
        }
    }
}