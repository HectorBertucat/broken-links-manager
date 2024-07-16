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
        $this->logger->log("Scanning post ID: {$post->ID}");

        $content = $post->post_content;
        if (empty($content)) {
            $this->logger->log("Post {$post->ID} has no content to scan.");
            return false;
        }

        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
        $links = $dom->getElementsByTagName('a');

        $broken_links_found = false;

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if ($href) {
                $this->logger->log("Checking link: {$href}");
                $status_code = $this->check_link_status($href);
                $this->logger->log("Status code for {$href}: {$status_code}");
                if ($status_code >= 400) {
                    $this->store_broken_link($post->ID, $href, $status_code);
                    $broken_links_found = true;
                }
            }
        }

        return $broken_links_found;
    }

    private function check_link_status($url) {
        $response = wp_remote_head($url, array('timeout' => 5, 'sslverify' => false));
        if (is_wp_error($response)) {
            $this->logger->log("Error checking {$url}: " . $response->get_error_message());
            return 500; // Assume server error if we can't reach the URL
        }
        return wp_remote_retrieve_response_code($response);
    }

    private function store_broken_link($post_id, $url, $status_code) {
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
            $this->logger->log("Stored broken link: Post ID {$post_id}, URL {$url}, Status Code {$status_code}");
        } else {
            $this->logger->log("Failed to store broken link: Post ID {$post_id}, URL {$url}, Status Code {$status_code}");
        }
    }
}