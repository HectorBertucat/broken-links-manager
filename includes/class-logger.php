<?php

class Logger {
    private $log_file;

    public function __construct() {
        $upload_dir = wp_upload_dir();
        $this->log_file = $upload_dir['basedir'] . '/broken-links-manager-log.txt';
    }

    public function log($message) {
        $timestamp = current_time('mysql');
        $log_message = "[{$timestamp}] {$message}\n";
        
        file_put_contents($this->log_file, $log_message, FILE_APPEND);
    }

    public function get_logs($limit = 100) {
        if (!file_exists($this->log_file)) {
            return array();
        }

        $logs = file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = array_reverse($logs);
        return array_slice($logs, 0, $limit);
    }

    public function clear_logs() {
        file_put_contents($this->log_file, '');
    }
}