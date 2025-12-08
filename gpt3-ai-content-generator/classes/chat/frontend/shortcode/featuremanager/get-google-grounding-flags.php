<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/frontend/shortcode/featuremanager/get-google-grounding-flags.php
// Status: NEW FILE

namespace WPAICG\Chat\Frontend\Shortcode\FeatureManagerMethods;

use WPAICG\Chat\Storage\BotSettingsManager;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Determines Google Search Grounding related feature flags.
 *
 * @param array $settings Bot settings array (needs 'provider', 'google_grounding_mode',
 *                        'google_grounding_dynamic_threshold').
 * @param bool $allow_google_search_grounding_setting Intermediate flag value from core flags.
 * @return array An array containing Google Search Grounding flags:
 *               'allowGoogleSearchGrounding', 'googleGroundingMode', 'googleGroundingDynamicThreshold'.
 */
function get_google_grounding_flags_logic(array $settings, bool $allow_google_search_grounding_setting): array {
    $grounding_flags = [];

    if (!class_exists(BotSettingsManager::class)) {
        // Fallback if BotSettingsManager is not available for defaults
        $defaults = [
            'DEFAULT_GOOGLE_GROUNDING_MODE' => 'DEFAULT_MODE',
            'DEFAULT_GOOGLE_GROUNDING_DYNAMIC_THRESHOLD' => 0.3,
        ];
    } else {
        $defaults = [
            'DEFAULT_GOOGLE_GROUNDING_MODE' => BotSettingsManager::DEFAULT_GOOGLE_GROUNDING_MODE,
            'DEFAULT_GOOGLE_GROUNDING_DYNAMIC_THRESHOLD' => BotSettingsManager::DEFAULT_GOOGLE_GROUNDING_DYNAMIC_THRESHOLD,
        ];
    }


    $grounding_flags['allowGoogleSearchGrounding'] = ($settings['provider'] ?? 'OpenAI') === 'Google' &&
                                                   $allow_google_search_grounding_setting;

    if ($grounding_flags['allowGoogleSearchGrounding']) {
        $grounding_flags['googleGroundingMode'] = $settings['google_grounding_mode'] ?? $defaults['DEFAULT_GOOGLE_GROUNDING_MODE'];
        if ($grounding_flags['googleGroundingMode'] === 'MODE_DYNAMIC') {
            $grounding_flags['googleGroundingDynamicThreshold'] = isset($settings['google_grounding_dynamic_threshold'])
                                                                  ? floatval($settings['google_grounding_dynamic_threshold'])
                                                                  : $defaults['DEFAULT_GOOGLE_GROUNDING_DYNAMIC_THRESHOLD'];
        } else {
            // Set a default or null if mode is not dynamic, to ensure the key exists if expected.
            $grounding_flags['googleGroundingDynamicThreshold'] = null;
        }
    } else {
        // Ensure keys exist even if grounding is not allowed, with default/null values.
        $grounding_flags['googleGroundingMode'] = null;
        $grounding_flags['googleGroundingDynamicThreshold'] = null;
    }

    return $grounding_flags;
}