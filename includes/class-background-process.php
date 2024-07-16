<?php

// Include the WP Background Process library
require_once plugin_dir_path(__FILE__) . '../lib/classes/wp-async-request.php';
require_once plugin_dir_path(__FILE__) . '../lib/classes/wp-background-process.php';

class Broken_Links_Background_Process extends BLM_WP_Background_Process {
    
    protected $action = 'broken_links_scan';

    protected function task($item) {
        // Ensure required classes are loaded
        if (!class_exists('Broken_Links_Scanner')) {
            require_once plugin_dir_path(__FILE__) . 'class-broken-links-scanner.php';
        }
        if (!class_exists('Logger')) {
            require_once plugin_dir_path(__FILE__) . 'class-logger.php';
        }

        // Process a single item here
        $scanner = new Broken_Links_Scanner(new Logger());
        $scanner->scan_post_content($item);

        return false; // False to remove the item from the queue
    }

    protected function complete() {
        parent::complete();

        // Ensure Logger class is loaded
        if (!class_exists('Logger')) {
            require_once plugin_dir_path(__FILE__) . 'class-logger.php';
        }

        // Log the completion of the background process
        $logger = new Logger();
        $logger->log('Background broken links scan completed');
    }
}