<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/contexts/chat/process/build-ai-request-data-for-stream.php
// Status: NEW FILE

namespace WPAICG\Core\Stream\Contexts\Chat\Process;

use WPAICG\Core\AIPKit_AI_Caller;
use WPAICG\Vector\AIPKit_Vector_Store_Manager;
use WPAICG\Core\AIPKit_Instruction_Manager;
use WPAICG\AIPKit_Providers;
use WPAICG\AIPKIT_AI_Settings;
use WPAICG\Core\Providers\Google\GoogleSettingsHandler;
use WPAICG\Core\Providers\OpenAI\OpenAIStatefulConversationHelper;
use WPAICG\Chat\Storage\BotSettingsManager; // For default constants
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Builds all necessary data for the AI stream request.
 *
 * @param AIPKit_AI_Caller $ai_caller Instance of AI Caller.
 * @param AIPKit_Vector_Store_Manager $vector_store_manager Instance of Vector Store Manager.
 * @param string $final_user_message_for_ai The user's message after trigger processing.
 * @param array $bot_settings Bot settings.
 * @param string $main_provider_for_ai The main AI provider for the chat.
 * @param string $model_id_for_ai The selected AI model.
 * @param array $final_history_for_ai Conversation history after trigger processing.
 * @param string $system_instruction_after_triggers System instruction after trigger processing.
 * @param int $post_id Current post ID.
 * @param array|null $image_inputs Processed image inputs.
 * @param string|null $frontend_previous_openai_response_id Previous OpenAI response ID.
 * @param bool $frontend_openai_web_search_active Flag for OpenAI web search.
 * @param bool $frontend_google_search_grounding_active Flag for Google Search Grounding.
 * @param string|null $frontend_active_openai_vs_id Active OpenAI Vector Store ID.
 * @param string|null $frontend_active_pinecone_index_name Active Pinecone index name.
 * @param string|null $frontend_active_pinecone_namespace Active Pinecone namespace.
 * @param string|null $frontend_active_qdrant_collection_name Active Qdrant collection name.
 * @param string|null $frontend_active_qdrant_file_upload_context_id Active Qdrant file context ID.
 * @return array|WP_Error Prepared data array or WP_Error.
 */
function build_ai_request_data_for_stream_logic(
    AIPKit_AI_Caller $ai_caller,
    AIPKit_Vector_Store_Manager $vector_store_manager,
    string $final_user_message_for_ai,
    array $bot_settings,
    string $main_provider_for_ai,
    string $model_id_for_ai,
    array $final_history_for_ai,
    string $system_instruction_after_triggers,
    int $post_id,
    ?array $image_inputs,
    ?string $frontend_previous_openai_response_id,
    bool $frontend_openai_web_search_active,
    bool $frontend_google_search_grounding_active,
    ?string $frontend_active_openai_vs_id,
    ?string $frontend_active_pinecone_index_name,
    ?string $frontend_active_pinecone_namespace,
    ?string $frontend_active_qdrant_collection_name,
    ?string $frontend_active_qdrant_file_upload_context_id
): array|WP_Error {

    // Ensure dependencies for sub-logics are loaded
    if (!class_exists(AIPKit_Instruction_Manager::class)) {
        return new WP_Error('dependency_missing_instr_mgr', 'Instruction Manager component is missing.');
    }
    if (!class_exists(AIPKit_Providers::class) || !class_exists(AIPKIT_AI_Settings::class)) {
        return new WP_Error('dependency_missing_global_settings', 'Global settings components (Providers/AI_Settings) are missing.');
    }
    if ($main_provider_for_ai === 'Google' && !class_exists(GoogleSettingsHandler::class)) {
        return new WP_Error('dependency_missing_google_handler', 'Google Settings Handler component is missing.');
    }
    if ($main_provider_for_ai === 'OpenAI' && !class_exists(OpenAIStatefulConversationHelper::class)) {
        return new WP_Error('dependency_missing_openai_helper', 'OpenAI Stateful Helper component is missing.');
    }

    $all_formatted_results_for_instruction = "";
    $vector_search_scores = []; // Initialize array to capture vector search scores
    if (function_exists('\WPAICG\Core\Stream\Vector\build_vector_search_context_logic')) {
        $all_formatted_results_for_instruction = \WPAICG\Core\Stream\Vector\build_vector_search_context_logic(
            $ai_caller,
            $vector_store_manager,
            $final_user_message_for_ai,
            $bot_settings,
            $main_provider_for_ai,
            $frontend_active_openai_vs_id,
            $frontend_active_pinecone_index_name,
            $frontend_active_pinecone_namespace,
            $frontend_active_qdrant_collection_name,
            $frontend_active_qdrant_file_upload_context_id,
            $vector_search_scores // Pass reference to capture scores
        );
    }

    $instruction_context = [
        'base_instructions' => $system_instruction_after_triggers,
        'bot_settings' => $bot_settings,
        'post_id' => $post_id
    ];
    if (!empty($all_formatted_results_for_instruction)) {
        $instruction_context['vector_search_results'] = trim($all_formatted_results_for_instruction);
    }
    $instructions_built = AIPKit_Instruction_Manager::build_instructions($instruction_context);
    $instructions_filtered_for_api = apply_filters('aipkit_system_instruction', $instructions_built, $main_provider_for_ai, $model_id_for_ai, $final_user_message_for_ai, $final_history_for_ai, $bot_settings, $bot_settings['session_id'] ?? null);

    $global_ai_params = AIPKIT_AI_Settings::get_ai_parameters();
    $ai_params_for_payload = [
        'temperature' => isset($bot_settings['temperature']) ? floatval($bot_settings['temperature']) : floatval($global_ai_params['temperature'] ?? 1.0),
        'max_completion_tokens' => isset($bot_settings['max_completion_tokens']) ? absint($bot_settings['max_completion_tokens']) : absint($global_ai_params['max_completion_tokens'] ?? 4000),
    ];
    $global_only_keys = ['top_p', 'stop'];
    foreach ($global_only_keys as $k) {
        if (isset($global_ai_params[$k])) {
            $ai_params_for_payload[$k] = $global_ai_params[$k];
        }
    }
    if (!empty($image_inputs)) {
        $ai_params_for_payload['image_inputs'] = $image_inputs;
    }

    // Make a mutable copy of history for potential modification by stateful helper
    $history_for_stateful_check = $final_history_for_ai;

    if ($main_provider_for_ai === 'Google' && class_exists(GoogleSettingsHandler::class)) {
        $ai_params_for_payload['safety_settings'] = GoogleSettingsHandler::get_safety_settings();
    }
    if ($main_provider_for_ai === 'OpenAI' && class_exists(OpenAIStatefulConversationHelper::class)) {
        $stateful_result = OpenAIStatefulConversationHelper::prepare_parameters_and_history(
            $ai_params_for_payload, // Passed by value, modified array returned
            $history_for_stateful_check, // Passed by value, modified array returned
            $bot_settings,
            $frontend_previous_openai_response_id
        );
        $ai_params_for_payload = $stateful_result['ai_params'];
        $history_for_stateful_check = $stateful_result['history']; // Update history if stateful logic modified it
    }

    if ($main_provider_for_ai === 'OpenAI') {
        $vector_store_ids_to_use = $bot_settings['openai_vector_store_ids'] ?? [];
        if ($frontend_active_openai_vs_id && !in_array($frontend_active_openai_vs_id, $vector_store_ids_to_use, true)) {
            $vector_store_ids_to_use[] = $frontend_active_openai_vs_id;
        }
        $vector_store_ids_to_use = array_unique(array_filter($vector_store_ids_to_use));
        $vector_top_k_openai = absint($bot_settings['vector_store_top_k'] ?? 3);
        $vector_top_k_openai = max(1, min($vector_top_k_openai, 20));
        if (($bot_settings['enable_vector_store'] ?? '0') === '1' && ($bot_settings['vector_store_provider'] ?? '') === 'openai' && !empty($vector_store_ids_to_use)) {
            // Get confidence threshold and convert to OpenAI score threshold
            $confidence_threshold_percent = (int)($bot_settings['vector_store_confidence_threshold'] ?? 20);
            $openai_score_threshold = round($confidence_threshold_percent / 100, 4); // Round to avoid precision issues
            
            $ai_params_for_payload['vector_store_tool_config'] = [
                'type' => 'file_search', 
                'vector_store_ids' => $vector_store_ids_to_use, 
                'max_num_results' => $vector_top_k_openai,
                'ranking_options' => [
                    'score_threshold' => $openai_score_threshold
                ]
            ];
        }
        if (($bot_settings['openai_web_search_enabled'] ?? '0') === '1') {
            $ai_params_for_payload['web_search_tool_config'] = ['enabled' => true, 'search_context_size' => $bot_settings['openai_web_search_context_size'] ?? BotSettingsManager::DEFAULT_OPENAI_WEB_SEARCH_CONTEXT_SIZE];
            if (($bot_settings['openai_web_search_loc_type'] ?? 'none') === 'approximate') {
                $user_loc = array_filter(['country' => $bot_settings['openai_web_search_loc_country'] ?? null, 'city' => $bot_settings['openai_web_search_loc_city'] ?? null, 'region' => $bot_settings['openai_web_search_loc_region'] ?? null, 'timezone' => $bot_settings['openai_web_search_loc_timezone'] ?? null]);
                if (!empty($user_loc)) {
                    $ai_params_for_payload['web_search_tool_config']['user_location'] = $user_loc;
                }
            }
            $ai_params_for_payload['frontend_web_search_active'] = $frontend_openai_web_search_active;
        }
        // --- NEW: Add reasoning parameter ---
        if (isset($bot_settings['reasoning_effort']) && !empty($bot_settings['reasoning_effort']) && (strpos($model_id_for_ai, 'gpt-5') !== false || strpos($model_id_for_ai, 'o1') !== false || strpos($model_id_for_ai, 'o3') !== false || strpos($model_id_for_ai, 'o4') !== false)) {
            $ai_params_for_payload['reasoning'] = ['effort' => $bot_settings['reasoning_effort']];
        }
        // --- END NEW ---
    } elseif ($main_provider_for_ai === 'Google') {
        if (($bot_settings['google_search_grounding_enabled'] ?? '0') === '1') {
            $ai_params_for_payload['google_grounding_mode'] = $bot_settings['google_grounding_mode'] ?? BotSettingsManager::DEFAULT_GOOGLE_GROUNDING_MODE;
            if ($ai_params_for_payload['google_grounding_mode'] === 'MODE_DYNAMIC') {
                $ai_params_for_payload['google_grounding_dynamic_threshold'] = isset($bot_settings['google_grounding_dynamic_threshold']) ? floatval($bot_settings['google_grounding_dynamic_threshold']) : BotSettingsManager::DEFAULT_GOOGLE_GROUNDING_DYNAMIC_THRESHOLD;
            }
            $ai_params_for_payload['frontend_google_search_grounding_active'] = $frontend_google_search_grounding_active;
        }
        $ai_params_for_payload['model_id_for_grounding'] = $model_id_for_ai;
    }

    $provData = AIPKit_Providers::get_provider_data($main_provider_for_ai);
    $api_params_for_stream = [
        'api_key' => $provData['api_key'] ?? '', 'base_url' => $provData['base_url'] ?? '', 'api_version' => $provData['api_version'] ?? '',
        'azure_endpoint' => ($main_provider_for_ai === 'Azure') ? ($provData['endpoint'] ?? '') : '',
        'azure_inference_version' => ($main_provider_for_ai === 'Azure') ? ($provData['api_version_inference'] ?? '2025-01-01-preview') : '',
        'azure_authoring_version' => ($main_provider_for_ai === 'Azure') ? ($provData['api_version_authoring'] ?? '2023-03-15-preview') : '',
    ];
    // Ollama doesn't require an API key, so skip validation for it
    if ($main_provider_for_ai !== 'Ollama' && empty($api_params_for_stream['api_key'])) {
        /* translators: %s: The name of the AI provider (e.g., OpenAI, Google). */
        return new WP_Error('missing_api_key', sprintf(__('API key missing for %s.', 'gpt3-ai-content-generator'), $main_provider_for_ai), ['status' => 400]);
    }
    if ($main_provider_for_ai === 'Azure' && empty($api_params_for_stream['azure_endpoint'])) {
        return new WP_Error('missing_azure_endpoint', __('Azure endpoint is missing.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }

    // The history for the API call should include the current user message
    $final_history_for_api_call = $history_for_stateful_check;
    if (!empty($final_user_message_for_ai)) {
        $final_history_for_api_call[] = ['role' => 'user', 'content' => $final_user_message_for_ai];
    }

    return [
        'provider'                      => $main_provider_for_ai,
        'model'                         => $model_id_for_ai,
        'user_message'                  => $final_user_message_for_ai, // Still needed for some strategy formatters
        'history'                       => $final_history_for_api_call,
        'system_instruction_filtered'   => $instructions_filtered_for_api,
        'api_params'                    => $api_params_for_stream,
        'ai_params'                     => $ai_params_for_payload,
        'vector_search_scores'          => $vector_search_scores, // Include captured vector search scores
    ];
}