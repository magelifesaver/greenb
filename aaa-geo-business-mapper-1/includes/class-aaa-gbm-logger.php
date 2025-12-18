<?php
/**
 * Simple logging utility used across the plugin. Logging is controlled
 * via the DEBUG_THIS_FILE constant defined in each class using it. If
 * disabled, calls to log() become no‑ops. Messages are prefixed to
 * allow easy filtering in the PHP error log.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_GBM_Logger {

    /**
     * Write a message to the error log. To minimise noise in production
     * installations, set DEBUG_THIS_FILE to false in the calling file.
     *
     * @param string $message   The message to log.
     * @param array  $context   Optional additional key/value context.
     */
    public static function log( $message, $context = array() ) {
        // Each file can override DEBUG_THIS_FILE to disable logging.
        // If not defined we assume logging should occur to aid debugging.
        $caller = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );
        $class  = ! empty( $caller[1]['class'] ) ? $caller[1]['class'] : 'AAA_GBM';
        $debug_constant = defined( "$class::DEBUG_THIS_FILE" ) ? constant( "$class::DEBUG_THIS_FILE" ) : true;
        if ( ! $debug_constant ) {
            return;
        }
        $prefix = '[' . $class . '] ';
        if ( ! empty( $context ) && is_array( $context ) ) {
            $message .= ' | ' . wp_json_encode( $context );
        }
        error_log( $prefix . $message );
    }
}