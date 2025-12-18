<?php
/**
 * File Path: /wp-content/plugins/aaa-geo-business-mapper/includes/class-aaa-gbm-logger.php
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AAA_GBM_Logger {

    const DEBUG_THIS_FILE = true;

    public static function log( $message, $context = array() ) {
        if ( ! self::DEBUG_THIS_FILE ) {
            return;
        }

        $prefix = '[AAA_GBM] ';
        if ( ! empty( $context ) && is_array( $context ) ) {
            $message .= ' | ' . wp_json_encode( $context );
        }

        error_log( $prefix . $message );
    }
}
