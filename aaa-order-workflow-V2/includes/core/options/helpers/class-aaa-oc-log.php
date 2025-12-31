<?php
/**
 * File: /includes/core/options/helpers/class-aaa-oc-log.php
 * Purpose: Lightweight logging wrapper writing to the existing aaa_oc.log file with scopes.
 * Version: 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class AAA_OC_Log {
    public static function write( string $scope, string $message ) : void {
        if ( empty($message) ) return;

        // Allow per-module debug flags saved under scope 'debug'
        if ( function_exists('aaa_oc_get_option') ) {
            $flag = aaa_oc_get_option( strtolower($scope) . '_debug', 'debug', false );
            if ( ! $flag ) return;
        }

        $file = defined('AAA_OC_PLUGIN_DIR') ? AAA_OC_PLUGIN_DIR . 'aaa_oc.log' : WP_CONTENT_DIR . '/aaa_oc.log';
        $time = date('Y-m-d H:i:s');
        @file_put_contents($file, "[$time][{$scope}] {$message}\n", FILE_APPEND);
    }
}
