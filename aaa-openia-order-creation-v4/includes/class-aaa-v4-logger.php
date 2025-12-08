<?php
// File: /aaa-openia-order-creation-v4/includes/class-aaa-v4-logger.php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_V4_Logger {

    /**
     * Write a log entry to /logs/aaa-v4-log.txt inside the plugin folder.
     *
     * @param string $message
     */
    public static function log( $message ) {
        $log_dir = plugin_dir_path( __FILE__ ) . 'logs/';
        $log_file = $log_dir . 'aaa-v4-log.txt';

        if ( ! file_exists( $log_dir ) ) {
            wp_mkdir_p( $log_dir );
        }

        $timestamp = date('Y-m-d H:i:s');
        $formatted_message = "[{$timestamp}] {$message}\n";

        file_put_contents( $log_file, $formatted_message, FILE_APPEND );
    }
}
?>
