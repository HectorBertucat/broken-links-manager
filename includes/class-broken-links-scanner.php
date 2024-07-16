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

    private function scan_post_content($post) {
        if (empty($post->post_content)) {
            $this->logger->log("Skipping empty post content for post ID: {$post->ID}");
            return;
        }
        
        $dom = new DOMDocument();
        @$dom->loadHTML($post->post_content);
        $links = $dom->getElementsByTagName('a');

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if ($href) {
                $status_code = $this->check_link_status($href);
                if ($status_code >= 400) {
                    $this->store_broken_link($post->ID, $href, $status_code);
                }
            }
        }
    }

    private function check_link_status($url) {
        $response = wp_remote_head($url, array('timeout' => 5));
        if (is_wp_error($response)) {
            return 500; // Assume server error if we can't reach the URL
        }
        return wp_remote_retrieve_response_code($response);
    }

    private function store_broken_link($post_id, $url, $status_code) {
        $table_name = $this->db->prefix . 'broken_links';
        $this->db->insert(
            $table_name,
            array(
                'post_id' => $post_id,
                'url' => $url,
                'status_code' => $status_code,
                'found_date' => current_time('mysql')
            ),
            array('%d', '%s', '%d', '%s')
        );
        $this->logger->log("Broken link found: Post ID {$post_id}, URL {$url}, Status Code {$status_code}");
    }
}