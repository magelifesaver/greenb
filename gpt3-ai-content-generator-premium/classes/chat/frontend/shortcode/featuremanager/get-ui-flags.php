<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/shortcode/featuremanager/get-ui-flags.php
// Status: NEW FILE

namespace WPAICG\Chat\Frontend\Shortcode\FeatureManagerMethods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Determines UI-related feature flags based on intermediate core settings and addon statuses.
 *
 * @param array $core_flags An array of intermediate flags from get_core_flag_values_logic.
 *                          Expected keys: 'enable_starters_setting', 'enable_sidebar_setting',
 *                                         'popup_enabled', 'enable_download', 'enable_tts_setting'.
 * @param array $addon_statuses An array of addon statuses from get_addon_status_logic.
 *                              Expected keys: 'starters_addon_active', 'pdf_addon_active',
 *                                             'is_pro_plan', 'tts_addon_active'.
 * @return array An array of UI feature flags:
 *               'starters_ui_enabled', 'sidebar_ui_enabled', 'pdf_ui_enabled', 'tts_ui_enabled'.
 */
function get_ui_flags_logic(array $core_flags, array $addon_statuses): array {
    $ui_flags = [];

    $ui_flags['starters_ui_enabled'] = ($addon_statuses['starters_addon_active'] ?? false) &&
                                      ($core_flags['enable_starters_setting'] ?? false);

    $ui_flags['sidebar_ui_enabled']  = ($core_flags['enable_sidebar_setting'] ?? false) &&
                                      !($core_flags['popup_enabled'] ?? false); // Sidebar disabled in popup mode

    $ui_flags['pdf_ui_enabled']      = ($core_flags['enable_download'] ?? false) &&
                                      ($addon_statuses['is_pro_plan'] ?? false) &&
                                      ($addon_statuses['pdf_addon_active'] ?? false);

    $ui_flags['tts_ui_enabled']      = ($addon_statuses['tts_addon_active'] ?? false) &&
                                      ($core_flags['enable_tts_setting'] ?? false);

    // Note: 'feedback_ui_enabled' and 'enable_voice_input_ui' are taken directly
    // from $core_flags in the main orchestrator.

    return $ui_flags;
}