<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/shortcode/class-aipkit-chat-featuremanager.php
// Status: MODIFIED

namespace WPAICG\Chat\Frontend\Shortcode;

use WPAICG\aipkit_dashboard;
use WPAICG\Chat\Storage\BotStorage;
use WPAICG\Chat\Storage\BotSettingsManager; // Use for constants

// Load the new method logic files
$featuremanager_methods_path = __DIR__ . '/featuremanager/';
require_once $featuremanager_methods_path . 'get-core-flag-values.php';
require_once $featuremanager_methods_path . 'get-addon-status.php';
require_once $featuremanager_methods_path . 'get-ui-flags.php';
require_once $featuremanager_methods_path . 'get-upload-flags.php';
require_once $featuremanager_methods_path . 'get-web-search-flag.php';
require_once $featuremanager_methods_path . 'get-google-grounding-flags.php';
require_once $featuremanager_methods_path . 'get-realtime-voice-flag.php';
require_once $featuremanager_methods_path . 'compute-derived-flags.php';


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Determines feature flags for the Chatbot Shortcode based on settings and global configs.
 * Now orchestrates calls to modularized logic functions.
 */
class FeatureManager {

    /**
     * Determines feature flags based on bot settings and global configurations (addons, pro plan).
     *
     * @param array $settings Bot settings array.
     * @return array Associative array of boolean feature flags.
     */
    public static function determine_flags(array $settings): array {
        $flags = [];

        // 1. Get core flag values directly from settings
        $core_flags = FeatureManagerMethods\get_core_flag_values_logic($settings);
        $flags = array_merge($flags, [
            'popup_enabled'      => $core_flags['popup_enabled'],
            'stream_enabled'     => $core_flags['stream_enabled'],
            'enable_fullscreen'  => $core_flags['enable_fullscreen'],
            'enable_download'    => $core_flags['enable_download'],
            'enable_copy_button' => $core_flags['enable_copy_button'],
            'enable_feedback'    => $core_flags['enable_feedback'], // This becomes feedback_ui_enabled
            'enable_voice_input_ui' => $core_flags['enable_voice_input_ui'], // Direct UI flag
        ]);
        // Rename for consistency if needed, or use directly
        $flags['feedback_ui_enabled'] = $flags['enable_feedback'];


        // 2. Get addon statuses
        $addon_statuses = FeatureManagerMethods\get_addon_status_logic();

        // 3. Determine UI flags based on core flags and addon statuses
        $ui_flags = FeatureManagerMethods\get_ui_flags_logic($core_flags, $addon_statuses);
        $flags = array_merge($flags, $ui_flags);

        // 4. Determine upload related flags
        $upload_flags = FeatureManagerMethods\get_upload_flags_logic($core_flags);
        $flags = array_merge($flags, $upload_flags);

        // 5. Determine Web Search flag
        $web_search_flag = FeatureManagerMethods\get_web_search_flag_logic($settings, $core_flags['allow_openai_web_search_tool_setting']);
        $flags = array_merge($flags, $web_search_flag);

        // 6. Determine Google Search Grounding flags
        $google_grounding_flags = FeatureManagerMethods\get_google_grounding_flags_logic($settings, $core_flags['allow_google_search_grounding_setting']);
        $flags = array_merge($flags, $google_grounding_flags);

        // 7. Determine Realtime Voice flag
        $realtime_voice_flag = FeatureManagerMethods\get_realtime_voice_flag_logic($core_flags, $addon_statuses);
        $flags = array_merge($flags, $realtime_voice_flag);

        // 8. Compute derived flags
        $derived_flags = FeatureManagerMethods\compute_derived_flags_logic($flags);
        $flags = array_merge($flags, $derived_flags);

        return $flags;
    }
}