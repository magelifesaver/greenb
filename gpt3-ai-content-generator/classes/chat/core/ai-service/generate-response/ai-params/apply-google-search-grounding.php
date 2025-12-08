<?php

// File: classes/chat/core/ai-service/generate-response/ai-params/apply-google-search-grounding.php
// Status: NEW FILE

namespace WPAICG\Chat\Core\AIService\GenerateResponse\AiParams;

use WPAICG\Chat\Storage\BotSettingsManager; // For default constants

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Applies Google Search Grounding parameters.
 *
 * @param array &$final_ai_params Reference to the final AI parameters array to be modified.
 * @param array $bot_settings Bot settings.
 * @param bool $frontend_google_search_grounding_active Flag for Google Search Grounding.
 */
function apply_google_search_grounding_logic(
    array &$final_ai_params,
    array $bot_settings,
    bool $frontend_google_search_grounding_active
): void {
    // Ensure BotSettingsManager constants are available
    if (!class_exists(BotSettingsManager::class)) {
        $bsm_path = WPAICG_PLUGIN_DIR . 'classes/chat/storage/class-aipkit_bot_settings_manager.php';
        if (file_exists($bsm_path)) {
            require_once $bsm_path;
        } else {
            return;
        }
    }

    $bot_allows_google_grounding = (isset($bot_settings['google_search_grounding_enabled']) && $bot_settings['google_search_grounding_enabled'] === '1');

    if ($bot_allows_google_grounding) {
        $final_ai_params['google_grounding_mode'] = $bot_settings['google_grounding_mode'] ?? BotSettingsManager::DEFAULT_GOOGLE_GROUNDING_MODE;
        if ($final_ai_params['google_grounding_mode'] === 'MODE_DYNAMIC') {
            $final_ai_params['google_grounding_dynamic_threshold'] = isset($bot_settings['google_grounding_dynamic_threshold']) ? floatval($bot_settings['google_grounding_dynamic_threshold']) : BotSettingsManager::DEFAULT_GOOGLE_GROUNDING_DYNAMIC_THRESHOLD;
        }
        $final_ai_params['frontend_google_search_grounding_active'] = $frontend_google_search_grounding_active;
    }
}
