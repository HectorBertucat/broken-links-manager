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
        $this->remove_from_database($post_id, $url, $id);
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

    private function remove_from_database($post_id, $url, $id) {
        $table_name = $this->db->prefix . 'blm_links';

        $delete_from_db_query = "DELETE FROM $table_name WHERE id = %d";
        $this->logger($delete_from_db_query);
        $this->db->query($this->db->prepare($delete_from_db_query, $id));
        $this->logger->log("Link removed from database: Post ID {$post_id}, URL {$url}");
    }
}