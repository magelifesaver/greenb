<?php
/**
 * File: includes/class-adbg-logger.php
 * Minimal logger patterned after coords plugin's logger.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
class ADBG_Logger {
    const DEBUG_THIS_FILE = true;
    public static function log( $message, $context = array() ) {
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) return;
        if ( ! defined( 'ADBG_DEBUG' ) || ! ADBG_DEBUG ) return;
        if ( ! self::DEBUG_THIS_FILE ) return;
        if ( ! empty( $context ) ) $message .= ' | ' . wp_json_encode( $context );
        error_log( '[AAA-DBlocks-Geo] ' . $message );
    }
}
