<?php

class Broken_Links_Remover {
    private $db;
    private $logger;

    public function __construct($logger) {
        global $wpdb;
        $this->db = $wpdb;
        $this->logger = $logger;
    }

    public function remove_link($post_id, $url) {
        if (!$post) {
            $this->logger->log("Error: Post with ID {$post_id} not found.");
            return false;
        }
    
        $content = $post->post_content;
        if (strpos($content, $url) === false) {
            $this->logger->log("Error: URL {$url} not found in post ID {$post_id}.");
            return false;
        }

        $post = get_post($post_id);
        if (!$post) {
            $this->logger->log("Error: Post with ID {$post_id} not found.");
            return false;
        }

        $content = $post->post_content;
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $xpath = new DOMXPath($dom);
        $links = $xpath->query("//a[@href='{$url}']");

        foreach ($links as $link) {
            $parent = $link->parentNode;
            while ($link->firstChild) {
                $parent->insertBefore($link->firstChild, $link);
            }
            $parent->removeChild($link);
        }

        $new_content = $dom->saveHTML();
        
        $update_result = wp_update_post(array(
            'ID' => $post_id,
            'post_content' => $new_content
        ));

        if ($update_result) {
            $this->remove_from_broken_links_table($post_id, $url);
            $this->logger->log("Removed broken link: {$url} from post ID: {$post_id}");
            return true;
        } else {
            $this->logger->log("Error: Failed to update post ID: {$post_id}");
            return false;
        }
    }

    public function bulk_remove_links($status_code) {
        $table_name = $this->db->prefix . 'broken_links';
        $links = $this->db->get_results($this->db->prepare(
            "SELECT post_id, url FROM $table_name WHERE status_code = %d",
            $status_code
        ));

        $success_count = 0;
        foreach ($links as $link) {
            if ($this->remove_link($link->post_id, $link->url)) {
                $success_count++;
            }
        }

        $this->logger->log("Bulk removal completed. Removed {$success_count} out of " . count($links) . " links with status code {$status_code}.");
        return $success_count > 0;
    }

    private function remove_from_broken_links_table($post_id, $url) {
        $table_name = $this->db->prefix . 'broken_links';
        $this->db->delete(
            $table_name,
            array('post_id' => $post_id, 'url' => $url),
            array('%d', '%s')
        );
    }
}