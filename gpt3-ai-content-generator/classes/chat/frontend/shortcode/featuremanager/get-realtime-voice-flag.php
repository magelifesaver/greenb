<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/shortcode/featuremanager/get-realtime-voice-flag.php
// Status: NEW FILE

namespace WPAICG\Chat\Frontend\Shortcode\FeatureManagerMethods;

use WPAICG\aipkit_dashboard;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Determines the 'enable_realtime_voice_ui' feature flag.
 *
 * @param array $core_flags An array of intermediate flags from get_core_flag_values_logic.
 * @return array An array containing the 'enable_realtime_voice_ui' flag.
 */
function get_realtime_voice_flag_logic(array $core_flags): array
{
    $is_pro = false;
    $addon_active = false;

    if (class_exists(aipkit_dashboard::class)) {
        $is_pro = aipkit_dashboard::is_pro_plan();
        $addon_active = aipkit_dashboard::is_addon_active('realtime_voice');
    }

    return [
        'enable_realtime_voice_ui' => ($core_flags['enable_realtime_voice_setting'] ?? false) &&
                                       $is_pro &&
                                       $addon_active,
    ];
}