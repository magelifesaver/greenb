<?php

// File: classes/chat/core/ai-service/generate-response/prepare-final-ai-params.php
// Status: MODIFIED

namespace WPAICG\Chat\Core\AIService\GenerateResponse;

use WPAICG\AIPKit_Providers;
use WPAICG\Chat\Storage\BotSettingsManager; // For default constants

// Load the new AI Param sub-module logic files
$ai_params_logic_path = __DIR__ . '/ai-params/';
require_once $ai_params_logic_path . 'apply-openai-stateful-conversation.php';
require_once $ai_params_logic_path . 'apply-openai-vector-tool-config.php';
require_once $ai_params_logic_path . 'apply-openai-web-search.php';
require_once $ai_params_logic_path . 'apply-google-search-grounding.php';
// --- NEW: Require reasoning logic file ---
require_once $ai_params_logic_path . 'apply-openai-reasoning.php';
// --- END NEW ---


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Prepares the final AI parameters, including provider-specific adjustments.
 * Orchestrates calls to sub-module logic functions.
 *
 * @param array $ai_params_override Initial AI parameter overrides from bot settings or image inputs.
 * @param array $bot_settings Bot settings.
 * @param string $main_provider The main AI provider.
 * @param string $model The selected AI model.
 * @param string|null $frontend_previous_openai_response_id Previous OpenAI response ID from frontend.
 * @param string|null $last_openai_response_id_from_history Last OpenAI response ID from history.
 * @param array &$messages_payload_ref Reference to the messages payload (can be modified for OpenAI stateful).
 * @param bool $frontend_openai_web_search_active Flag for OpenAI web search.
 * @param bool $frontend_google_search_grounding_active Flag for Google Search Grounding.
 * @param string|null $frontend_active_openai_vs_id Active OpenAI Vector Store ID.
 * @return array ['final_ai_params' => array, 'actual_previous_response_id_to_use' => string|null]
 */
function prepare_final_ai_params_logic(
    array $ai_params_override,
    array $bot_settings,
    string $main_provider,
    string $model,
    ?string $frontend_previous_openai_response_id,
    ?string $last_openai_response_id_from_history,
    array &$messages_payload_ref, // Pass by reference
    bool $frontend_openai_web_search_active,
    bool $frontend_google_search_grounding_active,
    ?string $frontend_active_openai_vs_id
): array {
    // Ensure dependencies are loaded (already handled in original file, repeated here for safety if this file were called standalone)
    if (!class_exists(AIPKit_Providers::class)) {
        $path = WPAICG_PLUGIN_DIR . 'classes/dashboard/class-aipkit_providers.php';
        if (file_exists($path)) {
            require_once $path;
        }
    }
    if (!class_exists(BotSettingsManager::class)) {
        $path = WPAICG_PLUGIN_DIR . 'classes/chat/storage/class-aipkit_bot_settings_manager.php';
        if (file_exists($path)) {
            require_once $path;
        }
    }

    $final_ai_params = $ai_params_override; // Start with overrides (temperature, max_tokens, image_inputs)
    $actual_previous_response_id_to_use = null;

    if ($main_provider === 'OpenAI') {
        $actual_previous_response_id_to_use = AiParams\apply_openai_stateful_conversation_logic(
            $final_ai_params,
            $messages_payload_ref, // Pass by reference
            $bot_settings,
            $frontend_previous_openai_response_id,
            $last_openai_response_id_from_history
        );
        
        // Get vector store IDs from bot settings
        $vector_store_ids_to_use_for_tool = $bot_settings['openai_vector_store_ids'] ?? [];
        if ($frontend_active_openai_vs_id && !in_array($frontend_active_openai_vs_id, $vector_store_ids_to_use_for_tool, true)) {
            $vector_store_ids_to_use_for_tool[] = $frontend_active_openai_vs_id;
        }
        
        AiParams\apply_openai_vector_tool_config_logic(
            $final_ai_params,
            $bot_settings,
            $vector_store_ids_to_use_for_tool,
            null // ai_service not needed for this function
        );
        AiParams\apply_openai_web_search_logic(
            $final_ai_params,
            $bot_settings,
            $frontend_openai_web_search_active
        );
        // --- NEW: Call reasoning logic ---
        AiParams\apply_openai_reasoning_logic(
            $final_ai_params,
            $bot_settings,
            $model
        );
        // --- END NEW ---
    } elseif ($main_provider === 'Google') {
        AiParams\apply_google_search_grounding_logic(
            $final_ai_params,
            $bot_settings,
            $frontend_google_search_grounding_active
        );
    }

    return [
        'final_ai_params' => $final_ai_params,
        'actual_previous_response_id_to_use' => $actual_previous_response_id_to_use
    ];
}