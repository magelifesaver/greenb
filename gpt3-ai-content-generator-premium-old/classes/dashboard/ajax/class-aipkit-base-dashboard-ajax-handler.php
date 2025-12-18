<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/dashboard/ajax/class-aipkit-base-dashboard-ajax-handler.php
// Status: MODIFIED

namespace WPAICG\Dashboard\Ajax;

use WP_Error;
use WPAICG\AIPKit_Role_Manager;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Base class for Dashboard AJAX Handlers.
 * Provides common permission checks.
 */
abstract class BaseDashboardAjaxHandler {

    protected $required_capability = 'manage_options'; // Default capability for most dashboard actions

    /**
     * Helper to check nonce and module-specific access via Role Manager.
     * Use this for actions within a module that don't require full admin rights.
     *
     * @param string $module_slug The slug of the module to check access for.
     * @param string $nonce_action The nonce action string.
     * @return bool|WP_Error True if permissions are valid, WP_Error otherwise.
     */
    public function check_module_access_permissions(string $module_slug, string $nonce_action = 'aipkit_nonce'): bool|WP_Error {
        // 1. Check nonce first
        if (!check_ajax_referer($nonce_action, '_ajax_nonce', false)) {
            return new WP_Error('nonce_failure', __('Security check failed (nonce).', 'gpt3-ai-content-generator'), ['status' => 403]);
        }

        // 2. Check if the user can access the specified module
        if (!AIPKit_Role_Manager::user_can_access_module($module_slug)) {
             return new WP_Error('permission_denied', __('You do not have permission to perform this action.', 'gpt3-ai-content-generator'), ['status' => 403]);
        }

        // 3. If both checks pass
        return true;
    }

    /**
     * Helper to send WP_Error as a standard JSON error response.
     *
     * @param WP_Error $error The WP_Error object.
     */
    public function send_wp_error(WP_Error $error) { // MODIFIED: Changed from protected to public
        $error_data = [
            'message' => $error->get_error_message(),
            'code'    => $error->get_error_code(),
        ];
        // Extract status code from error data if set, otherwise default
        $error_data_payload = $error->get_error_data();
        $status_code = isset($error_data_payload['status']) && is_int($error_data_payload['status'])
                       ? $error_data_payload['status']
                       : 400; // Default to 400 Bad Request if not specified

        // Attach sanitized details for debugging (without huge payloads)
        $details = [];
        if (is_array($error_data_payload)) {
            $details = $error_data_payload;
            if (isset($details['log_data']) && is_array($details['log_data'])) {
                $log = $details['log_data'];
                // Compute content length and truncate indexed_content if present
                if (isset($log['indexed_content']) && is_string($log['indexed_content'])) {
                    $content = $log['indexed_content'];
                    $details['log_data']['indexed_content_length'] = function_exists('mb_strlen') ? mb_strlen($content) : strlen($content);
                    // Truncate to avoid massive responses
                    $max = 1000;
                    if ((function_exists('mb_strlen') ? mb_strlen($content) : strlen($content)) > $max) {
                        $details['log_data']['indexed_content'] = (function_exists('mb_substr') ? mb_substr($content, 0, $max) : substr($content, 0, $max)) . '...';
                    }
                }
            }
        }

        if (!empty($details)) {
            $error_data['details'] = $details;
        }

        wp_send_json_error($error_data, $status_code);
    }
}