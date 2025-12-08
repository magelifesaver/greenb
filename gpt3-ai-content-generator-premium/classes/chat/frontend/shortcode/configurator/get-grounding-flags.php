<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/shortcode/configurator/get-grounding-flags.php
// Status: NEW FILE

namespace WPAICG\Chat\Frontend\Shortcode\ConfiguratorMethods;

use WPAICG\Chat\Storage\BotSettingsManager; // For default constants

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Prepares Google Search Grounding related flags and settings.
 *
 * @param array $settings Bot settings.
 * @param array $feature_flags Determined feature flags.
 * @return array An array containing allowGoogleSearchGrounding, googleGroundingMode, and googleGroundingDynamicThreshold.
 */
function get_google_grounding_settings_logic(array $settings, array $feature_flags): array {
    if (!class_exists(BotSettingsManager::class)) {
        return [
            'allowGoogleSearchGrounding' => false,
            'googleGroundingMode' => 'DEFAULT_MODE',
            'googleGroundingDynamicThreshold' => 0.3,
        ];
    }

    $allow_google_search_grounding = $feature_flags['allowGoogleSearchGrounding'] ?? false;
    $google_grounding_mode = BotSettingsManager::DEFAULT_GOOGLE_GROUNDING_MODE;
    $google_grounding_dynamic_threshold = BotSettingsManager::DEFAULT_GOOGLE_GROUNDING_DYNAMIC_THRESHOLD;

    if ($allow_google_search_grounding) {
        $google_grounding_mode = $settings['google_grounding_mode'] ?? BotSettingsManager::DEFAULT_GOOGLE_GROUNDING_MODE;
        if ($google_grounding_mode === 'MODE_DYNAMIC') {
            $google_grounding_dynamic_threshold = isset($settings['google_grounding_dynamic_threshold'])
                                                 ? floatval($settings['google_grounding_dynamic_threshold'])
                                                 : BotSettingsManager::DEFAULT_GOOGLE_GROUNDING_DYNAMIC_THRESHOLD;
        }
    }

    return [
        'allowGoogleSearchGrounding' => $allow_google_search_grounding,
        'googleGroundingMode' => $google_grounding_mode,
        'googleGroundingDynamicThreshold' => $google_grounding_dynamic_threshold,
    ];
}