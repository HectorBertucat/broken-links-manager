<?php

class Broken_Links_Remover {
    private $db;
    private $logger;

    public function __construct($logger) {
        global $wpdb;
        $this->db = $wpdb;
        $this->logger = $logger;
    }

    public function remove_link($id, $post_id, $url) {
        $this->logger->log("REMOVE LINK function: Removing link: ID {$id}, Post ID {$post_id}, URL {$url}");
        $post = get_post($post_id);
        if (!$post) {
            $this->logger->log("Error: Post with ID {$post_id} not found.");
            return false;
        }

        $content = $post->post_content;
        $updated_content = $this->remove_link_from_content($content, $url);

        if ($content !== $updated_content) {
            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $updated_content
            ));
            $this->logger->log("Link removed from post content: Post ID {$post_id}, URL {$url}");
        }

        $this->logger->log("REMOVE LINK beggin remove databse: ID {$id}, Post ID {$post_id}, URL {$url}");
        $this->remove_from_database($id);
        return true;
    }

    public function bulk_remove_links($links) {
        foreach ($links as $link) {
            $this->remove_link($link['id'], $link['post_id'], $link['url']);
        }
    }

    private function remove_link_from_content($content, $url) {
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        $xpath = new DOMXPath($dom);
        $links = $xpath->query("//a[@href='" . $url . "']");

        foreach ($links as $link) {
            $parent = $link->parentNode;
            while ($link->firstChild) {
                $parent->insertBefore($link->firstChild, $link);
            }
            $parent->removeChild($link);
        }

        return $dom->saveHTML();
    }

    private function remove_from_database($link_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'blm_links';
        
        $this->logger->log("Attempting to delete link with ID: $link_id");
        
        // Check if the link exists before attempting to delete
        $link = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $link_id));
        if (!$link) {
            $this->logger->log("Error: Link with ID $link_id not found in the database.");
            return false;
        }
        
        $result = $wpdb->delete(
            $table_name,
            array('id' => $link_id),
            array('%d')
        );

        $this->logger->log("Checking if link still exists after deletion...");
        $link_check = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE id = %d", $link_id));
        $this->logger->log("Link check result: " . ($link_check ? "Link still exists" : "Link successfully deleted"));
    
        if ($result === false) {
            $this->logger->log("Error: Failed to delete link (ID: $link_id). Database error: " . $wpdb->last_error);
            return false;
        } elseif ($result === 0) {
            $this->logger->log("Warning: No rows affected when trying to delete link (ID: $link_id).");
            return false;
        } else {
            $this->logger->log("Success: Link (ID: $link_id) deleted from the database. Rows affected: $result");
            return true;
        }
    }
}