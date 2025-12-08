<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/admin/ajax/traits/Trait_CheckAdminPermissions.php
// Status: NEW FILE

namespace WPAICG\Chat\Admin\Ajax\Traits;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

trait Trait_CheckAdminPermissions {
    /**
     * Helper to check nonce and capability for ADMIN actions.
     * Ensures the user has the default 'manage_options' capability.
     *
     * @param string $nonce_action The nonce action string.
     * @return bool|WP_Error True if permissions are valid, WP_Error otherwise.
     */
    protected function check_admin_permissions(string $nonce_action = 'aipkit_nonce'): bool|WP_Error {
        // Use check_ajax_referer for standard WP behavior, checking $_REQUEST
        if (!check_ajax_referer($nonce_action, '_ajax_nonce', false)) {
            return new WP_Error('nonce_failure', __('Security check failed (nonce).', 'gpt3-ai-content-generator'), ['status' => 403]);
        }
        if (!current_user_can($this->required_capability)) {
            return new WP_Error('permission_denied', __('Permission denied.', 'gpt3-ai-content-generator'), ['status' => 403]);
        }
        return true;
    }
}