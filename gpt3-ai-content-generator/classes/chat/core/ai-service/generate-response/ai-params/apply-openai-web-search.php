<?php

// File: classes/chat/core/ai-service/generate-response/ai-params/apply-openai-web-search.php
// Status: NEW FILE

namespace WPAICG\Chat\Core\AIService\GenerateResponse\AiParams;

use WPAICG\Chat\Storage\BotSettingsManager; // For default constants

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Applies OpenAI Web Search tool configuration to AI parameters.
 *
 * @param array &$final_ai_params Reference to the final AI parameters array to be modified.
 * @param array $bot_settings Bot settings.
 * @param bool $frontend_openai_web_search_active Flag for OpenAI web search.
 */
function apply_openai_web_search_logic(
    array &$final_ai_params,
    array $bot_settings,
    bool $frontend_openai_web_search_active
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

    $bot_allows_openai_web_search = (isset($bot_settings['openai_web_search_enabled']) && $bot_settings['openai_web_search_enabled'] === '1');

    if ($bot_allows_openai_web_search) {
        $final_ai_params['web_search_tool_config'] = [
            'enabled' => true,
            'search_context_size' => $bot_settings['openai_web_search_context_size'] ?? BotSettingsManager::DEFAULT_OPENAI_WEB_SEARCH_CONTEXT_SIZE,
        ];
        if (($bot_settings['openai_web_search_loc_type'] ?? 'none') === 'approximate') {
            $user_location = array_filter([
                'country' => $bot_settings['openai_web_search_loc_country'] ?? null,
                'city' => $bot_settings['openai_web_search_loc_city'] ?? null,
                'region' => $bot_settings['openai_web_search_loc_region'] ?? null,
                'timezone' => $bot_settings['openai_web_search_loc_timezone'] ?? null
            ]);
            if (!empty($user_location)) {
                $final_ai_params['web_search_tool_config']['user_location'] = $user_location;
            }
        }
        $final_ai_params['frontend_web_search_active'] = $frontend_openai_web_search_active;
    }
}
