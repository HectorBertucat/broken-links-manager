<?php

if (!class_exists('WP_Async_Request')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-async-request.php');
}

if (!class_exists('WP_Background_Process')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-background-process.php');
}

class Broken_Links_Background_Process extends WP_Background_Process {
    protected $action = 'broken_links_scan';

    protected function task($item) {
        try {
            $scanner = new Broken_Links_Scanner(new Logger());
            $scanner->scan_post_content($item);
        } catch (Exception $e) {
            $logger = new Logger();
            $logger->log("Error processing post ID {$item->ID}: " . $e->getMessage());
        }
    
        return false;
    }

    protected function complete() {
        parent::complete();

        // Log the completion of the background process
        $logger = new Logger();
        $logger->log('Background broken links scan completed');
    }
}