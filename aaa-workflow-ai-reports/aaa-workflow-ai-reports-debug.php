<?php
/**
 * ============================================================================
 * File Path: /wp-content/plugins/aaa-workflow-ai-reports/aaa-workflow-ai-reports-debug.php
 * Description: Universal debug helper for AAA Workflow AI Reports.
 * Author: AAA Workflow DevOps
 * Version: 2.1.0
 * Updated: 2025-12-02
 * ============================================================================
 *
 * ✅ Safe for production:
 *    - No database lookups
 *    - No custom table dependencies
 *    - Logs only when WP_DEBUG is true
 *    - Handles strings, arrays, objects, and WP_Error automatically
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * --------------------------------------------------------------------------
 * 🪵 Core Logger
 * --------------------------------------------------------------------------
 * Lightweight error_log wrapper. Safe to call anywhere.
 *
 * @param string|array|object $message Data or text to log.
 * @param string $file Optional. Source file name.
 * @param string $scope Optional. Context category (eg: 'ajax', 'rest', 'openai').
 */
if ( ! function_exists( 'aaa_wf_ai_debug' ) ) {
        function aaa_wf_ai_debug( $message, $file = '', $scope = 'general' ) {

                // Only log if WordPress debugging is enabled
                if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) return;

                /*
                 * Additionally respect the plugin-level debug flag.  If the
                 * helper function is available, read the stored option from
                 * the custom table via the safe fallback; otherwise
                 * default to enabled during early bootstrap (to avoid
                 * suppressing critical errors).  This allows site admins to
                 * disable logging from the plugin UI while keeping WP_DEBUG
                 * enabled for other plugins.
                 */
                $plugin_debug_enabled = true;
                if ( function_exists( 'aaa_wf_ai_safe_option_fallback' ) ) {
                        $plugin_debug_enabled = (bool) aaa_wf_ai_safe_option_fallback( 'aaa_wf_ai_debug_enabled', false );
                }
                if ( ! $plugin_debug_enabled ) {
                        return;
                }

                $time   = gmdate( 'Y-m-d H:i:s' );
                $prefix = "[AAA Workflow AI][$scope][$time]";
                $tag    = $file ? " [{$file}]" : '';

                // Normalize message
                if ( is_array( $message ) || is_object( $message ) ) {
                        $message = print_r( $message, true );
                }

                error_log( "{$prefix}{$tag} {$message}" );
        }
}

/**
 * --------------------------------------------------------------------------
 * 🧩 Structured Dump
 * --------------------------------------------------------------------------
 * Pretty-print JSON or data arrays to debug.log.
 *
 * @param mixed  $data  Any variable (array, object, string, number)
 * @param string $label Optional label to identify context.
 * @param string $scope Optional subsystem scope.
 */
if ( ! function_exists( 'aaa_wf_ai_debug_dump' ) ) {
        function aaa_wf_ai_debug_dump( $data, $label = 'Dump', $scope = 'general' ) {

                if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) return;
                // Respect plugin-level debug option (see aaa_wf_ai_debug for details)
                $plugin_debug_enabled = true;
                if ( function_exists( 'aaa_wf_ai_safe_option_fallback' ) ) {
                        $plugin_debug_enabled = (bool) aaa_wf_ai_safe_option_fallback( 'aaa_wf_ai_debug_enabled', false );
                }
                if ( ! $plugin_debug_enabled ) {
                        return;
                }

                $time = gmdate( 'Y-m-d H:i:s' );
                $prefix = "[AAA Workflow AI][$scope][$time]";

                // Handle complex data types
                if ( is_wp_error( $data ) ) {
                        $payload = [
                                'error_code' => $data->get_error_code(),
                                'error_message' => $data->get_error_message(),
                                'error_data' => $data->get_error_data(),
                        ];
                } elseif ( is_array( $data ) || is_object( $data ) ) {
                        $payload = json_encode( $data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
                } else {
                        $payload = (string) $data;
                }

                $log_entry = "{$prefix} [{$label}] → {$payload}";
                error_log( $log_entry );
        }
}

/**
 * --------------------------------------------------------------------------
 * 🚨 Error Handler Helper
 * --------------------------------------------------------------------------
 * Specialized wrapper for WP_Error instances.
 *
 * @param WP_Error|string $error Error object or message.
 * @param string $context Optional context label.
 * @param string $scope Optional category (ajax, rest, etc.)
 */
if ( ! function_exists( 'aaa_wf_ai_debug_error' ) ) {
        function aaa_wf_ai_debug_error( $error, $context = 'Error', $scope = 'error' ) {

                if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) return;
                // Respect plugin-level debug option
                $plugin_debug_enabled = true;
                if ( function_exists( 'aaa_wf_ai_safe_option_fallback' ) ) {
                        $plugin_debug_enabled = (bool) aaa_wf_ai_safe_option_fallback( 'aaa_wf_ai_debug_enabled', false );
                }
                if ( ! $plugin_debug_enabled ) {
                        return;
                }

                if ( is_wp_error( $error ) ) {
                        $entry = sprintf(
                                "%s [%s] Code: %s | Message: %s",
                                "[AAA Workflow AI][$scope][" . gmdate('Y-m-d H:i:s') . "]",
                                $context,
                                $error->get_error_code(),
                                $error->get_error_message()
                        );
                } else {
                        $entry = sprintf(
                                "%s [%s] %s",
                                "[AAA Workflow AI][$scope][" . gmdate('Y-m-d H:i:s') . "]",
                                $context,
                                $error
                        );
                }

                error_log( $entry );
        }
}

/**
 * --------------------------------------------------------------------------
 * ✅ Startup confirmation
 * --------------------------------------------------------------------------
 */
aaa_wf_ai_debug( 'Debug system loaded successfully.', basename( __FILE__ ), 'startup' );