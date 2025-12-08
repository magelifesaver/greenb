<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/shortcode/featuremanager/get-upload-flags.php
// Status: MODIFIED

namespace WPAICG\Chat\Frontend\Shortcode\FeatureManagerMethods;

use WPAICG\aipkit_dashboard; // ADDED for Pro check

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Determines file/image upload related feature flags.
 *
 * @param array $core_flags An array of intermediate flags from get_core_flag_values_logic.
 *                          Expected keys: 'enable_file_upload_setting', 'enable_image_upload_setting'.
 * @return array An array of upload feature flags:
 *               'file_upload_ui_enabled', 'image_upload_ui_enabled', 'input_action_button_enabled'.
 */
function get_upload_flags_logic(array $core_flags): array {
    $upload_flags = [];
    $is_pro = false;
    $file_upload_addon_active = false;

    // Ensure aipkit_dashboard class is loaded before calling its static methods
    if (!class_exists(aipkit_dashboard::class)) {
        $dashboard_path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_dashboard.php';
        if (file_exists($dashboard_path)) {
            require_once $dashboard_path;
        }
    }

    if (class_exists(aipkit_dashboard::class)) {
        $is_pro = aipkit_dashboard::is_pro_plan();
        $file_upload_addon_active = aipkit_dashboard::is_addon_active('file_upload');
    }

    // File upload UI is enabled if the setting is on AND it's a Pro feature that's active
    $upload_flags['file_upload_ui_enabled'] = ($core_flags['enable_file_upload_setting'] ?? false) && $is_pro && $file_upload_addon_active;
    // Image upload UI is enabled if the setting is on (it's a free feature, might also depend on provider in future)
    $upload_flags['image_upload_ui_enabled'] = $core_flags['enable_image_upload_setting'] ?? false;

    $upload_flags['input_action_button_enabled'] = $upload_flags['file_upload_ui_enabled'] ||
                                                 $upload_flags['image_upload_ui_enabled'];

    return $upload_flags;
}