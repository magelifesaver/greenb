<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/contexts/ai-forms/process/prepare-stream-data.php
// Status: MODIFIED

namespace WPAICG\Core\Stream\Contexts\AIForms\Process;

use WPAICG\Core\Stream\Contexts\AIForms\SSEAIFormsStreamContextHandler;
use WPAICG\AIPKit\Addons\AIPKit_IP_Anonymization;
use WPAICG\AIPKit_Providers;
use WPAICG\AIPKIT_AI_Settings;
use WPAICG\Core\Providers\Google\GoogleSettingsHandler;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Logs the user request and prepares the final data array for the SSE stream processor.
 *
 * @param SSEAIFormsStreamContextHandler $handlerInstance The instance of the context handler.
 * @param array $validated_params Validated request parameters.
 * @param array $form_config The configuration of the form.
 * @param string $final_user_prompt The final constructed user prompt.
 * @param string $system_instruction The system instruction, potentially with vector context.
 * @param array $vector_search_scores Array of captured vector search scores for logging.
 * @return array|WP_Error The structured data for the SSE processor, or a WP_Error on failure.
 */
function prepare_stream_data_logic(
    SSEAIFormsStreamContextHandler $handlerInstance,
    array $validated_params,
    array $form_config,
    string $final_user_prompt,
    string $system_instruction,
    array $vector_search_scores = []
): array|WP_Error {
    $log_storage = $handlerInstance->get_log_storage();
    $user_id = $validated_params['user_id'];
    $user_wp_role = $user_id ? implode(', ', wp_get_current_user()->roles) : null;
    $provider = $form_config['ai_provider'];
    $model = $form_config['ai_model'];

    // 1. Log User Request
    $base_log_data = [
        'bot_id'            => null,
        'user_id'           => $user_id ?: null,
        'session_id'        => $user_id ? null : $validated_params['session_id'],
        'conversation_uuid' => $validated_params['conversation_uuid'],
        'module'            => 'ai_forms',
        'is_guest'          => ($user_id === 0),
        'role'              => $user_wp_role,
        'ip_address'        => AIPKit_IP_Anonymization::maybe_anonymize($validated_params['client_ip']),
        'form_id'           => $validated_params['form_id'],
    ];
    $bot_message_id = 'aif-msg-' . uniqid('', true);
    $base_log_data['bot_message_id'] = $bot_message_id;

    $log_user_data = array_merge($base_log_data, [
        'message_role'       => 'user',
        'message_content'    => "AI Form Submission (ID: {$validated_params['form_id']}): " . ($form_config['title'] ?? 'Untitled'),
        'timestamp'          => time(),
        'request_payload'    => ['form_id' => $validated_params['form_id'], 'inputs' => $validated_params['user_input_values'], 'constructed_prompt' => $final_user_prompt]
    ]);
    $log_storage->log_message($log_user_data);

    // 2. Prepare AI and API Parameters
    $global_ai_params = AIPKIT_AI_Settings::get_ai_parameters();
    $ai_params_for_payload = $global_ai_params; // Start with all global defaults

    // Override with form-specific settings if they are numeric
    if (isset($form_config['temperature']) && is_numeric($form_config['temperature'])) {
        $ai_params_for_payload['temperature'] = floatval($form_config['temperature']);
    }
    if (isset($form_config['max_tokens']) && is_numeric($form_config['max_tokens'])) {
        $ai_params_for_payload['max_completion_tokens'] = absint($form_config['max_tokens']);
    }
    if (isset($form_config['top_p']) && is_numeric($form_config['top_p'])) {
        $ai_params_for_payload['top_p'] = floatval($form_config['top_p']);
    }
    if (isset($form_config['frequency_penalty']) && is_numeric($form_config['frequency_penalty'])) {
        $ai_params_for_payload['frequency_penalty'] = floatval($form_config['frequency_penalty']);
    }
    if (isset($form_config['presence_penalty']) && is_numeric($form_config['presence_penalty'])) {
        $ai_params_for_payload['presence_penalty'] = floatval($form_config['presence_penalty']);
    }
    // Add reasoning effort to AI params
    if ($provider === 'OpenAI' && isset($form_config['reasoning_effort']) && !empty($form_config['reasoning_effort'])) {
        $model_lower = strtolower($model);
        if (strpos($model_lower, 'gpt-5') !== false || strpos($model_lower, 'o1') !== false || strpos($model_lower, 'o3') !== false || strpos($model_lower, 'o4') !== false) {
             $ai_params_for_payload['reasoning'] = ['effort' => sanitize_key($form_config['reasoning_effort'])];
        }
    }


    if ($provider === 'Google' && class_exists(GoogleSettingsHandler::class)) {
        $ai_params_for_payload['safety_settings'] = GoogleSettingsHandler::get_safety_settings();
    }
    $ai_params_for_payload['model_id_for_grounding'] = $model;

    // Vector Store Tool Config (OpenAI)
    $is_vector_enabled = ($form_config['enable_vector_store'] ?? '0') === '1';
    $is_openai_vector_provider = ($form_config['vector_store_provider'] ?? '') === 'openai';
    $has_vector_store_ids = !empty($form_config['openai_vector_store_ids']) && is_array($form_config['openai_vector_store_ids']);

    if ($provider === 'OpenAI' && $is_vector_enabled && $is_openai_vector_provider && $has_vector_store_ids) {
        $vector_top_k = isset($form_config['vector_store_top_k']) ? absint($form_config['vector_store_top_k']) : 3;
        $vector_top_k = max(1, min($vector_top_k, 20));

        // Get confidence threshold and convert to OpenAI score threshold
        $confidence_threshold_percent = (int)($form_config['vector_store_confidence_threshold'] ?? 20);
        $openai_score_threshold = round($confidence_threshold_percent / 100, 4); // Round to avoid precision issues

        $ai_params_for_payload['vector_store_tool_config'] = [
            'type'             => 'file_search',
            'vector_store_ids' => $form_config['openai_vector_store_ids'],
            'max_num_results'  => $vector_top_k,
            'ranking_options'  => [
                'score_threshold' => $openai_score_threshold
            ]
        ];
    }

    // --- NEW: Add Web Search & Grounding Params ---
    if ($provider === 'OpenAI' && ($form_config['openai_web_search_enabled'] ?? '0') === '1') {
        $ai_params_for_payload['web_search_tool_config'] = ['enabled' => true];
        // For AI Forms, web search is implicitly active if the form setting is enabled.
        $ai_params_for_payload['frontend_web_search_active'] = true;
    }
    if ($provider === 'Google' && ($form_config['google_search_grounding_enabled'] ?? '0') === '1') {
        // For AI Forms, grounding is implicitly active if the form setting is enabled.
        $ai_params_for_payload['frontend_google_search_grounding_active'] = true;
    }
    // --- END NEW ---

        $provData = AIPKit_Providers::get_provider_data($provider);
    $api_params_for_stream = [
        'api_key' => $provData['api_key'] ?? '', 'base_url' => $provData['base_url'] ?? '', 'api_version' => $provData['api_version'] ?? '',
        'azure_endpoint' => ($provider === 'Azure') ? ($provData['endpoint'] ?? '') : '',
        'stream' => true,
    ];

    if (empty($api_params_for_stream['api_key']) && $provider !== 'Ollama') {
        /* translators: %s: The name of the AI provider (e.g., OpenAI, Google). */
        return new WP_Error('missing_api_key_ai_forms_logic', sprintf(__('API key missing for %s (AI Forms).', 'gpt3-ai-content-generator'), $provider), ['status' => 400]);
    }
    if ($provider === 'Azure' && empty($api_params_for_stream['azure_endpoint'])) {
        return new WP_Error('missing_azure_endpoint_ai_forms_logic', __('Azure endpoint is missing (AI Forms).', 'gpt3-ai-content-generator'), ['status' => 400]);
    }

    // 3. Construct and return the final data array
    return [
        'provider'                      => $provider,
        'model'                         => $model,
        'user_message'                  => $final_user_prompt,
        'history'                       => [], // AI Forms do not have chat history
        'system_instruction_filtered'   => $system_instruction, // Pass the (potentially new) system instruction
        'api_params'                    => $api_params_for_stream,
        'ai_params'                     => $ai_params_for_payload,
        'conversation_uuid'             => $validated_params['conversation_uuid'],
        'base_log_data'                 => $base_log_data,
        'bot_message_id'                => $bot_message_id,
        'vector_search_scores'          => $vector_search_scores, // Include captured vector search scores
    ];
}