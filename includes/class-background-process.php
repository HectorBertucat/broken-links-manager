<?php

// Include the WP Background Process library
require_once plugin_dir_path(__FILE__) . '../lib/classes/wp-async-request.php';
require_once plugin_dir_path(__FILE__) . '../lib/classes/wp-background-process.php';

class Broken_Links_Background_Process extends BLM_WP_Background_Process {
    
    protected $action = 'broken_links_scan';

    protected function task($item) {
        $logger = new Logger();
        $logger->log("Background process: Starting task for item ID: " . $item->ID);

        try {
            // Ensure required classes are loaded
            if (!class_exists('Broken_Links_Scanner')) {
                require_once plugin_dir_path(__FILE__) . 'class-broken-links-scanner.php';
            }

            $scanner = new Broken_Links_Scanner($logger);
            $result = $scanner->scan_post_content($item);
            
            $logger->log("Background process: Scan result for post {$item->ID}: " . ($result ? "Links found" : "No broken links found"));
        } catch (Exception $e) {
            $logger->log("Background process: Error processing post {$item->ID}: " . $e->getMessage());
        }

        $logger->log("Background process: Completed task for item ID: " . $item->ID);
        return false; // False to remove the item from the queue
    }

    protected function complete() {
        parent::complete();

        $logger = new Logger();
        $logger->log('Background process: Broken links scan completed');
    }

    // Override the handle method to add logging
    public function handle() {
        $logger = new Logger();
        $logger->log('Background process: Handle method called');
        
        $this->lock_process();

        do {
            $batch = $this->get_batch();

            if ( empty( $batch->data ) ) {
                break;
            }

            foreach ( $batch->data as $key => $value ) {
                $task = $this->task( $value );

                if ( false !== $task ) {
                    $batch->data[ $key ] = $task;
                } else {
                    unset( $batch->data[ $key ] );
                }

                // Time exceeded, or memory limit reached
                if ( $this->time_exceeded() || $this->memory_exceeded() ) {
                    break;
                }
            }

            // Update or delete current batch
            if ( ! empty( $batch->data ) ) {
                $this->update( $batch->key, $batch->data );
            } else {
                $this->delete( $batch->key );
            }
        } while ( ! $this->time_exceeded() && ! $this->memory_exceeded() && ! $this->is_queue_empty() );

        $this->unlock_process();

        // Start next batch or complete process
        if ( ! $this->is_queue_empty() ) {
            $this->dispatch();
        } else {
            $this->complete();
        }

        $logger->log('Background process: Handle method completed');
        wp_die();
    }
}