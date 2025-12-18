<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/format-sse.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the format_sse static method of OpenAIPayloadFormatter.
 */
function format_sse_logic_for_payload_formatter(
    array $messages,
    $system_instruction,
    array $ai_params,
    string $model,
    bool $use_openai_conversation_state = false,
    ?string $previous_response_id = null
): array {
    $input_array = [];
    $instructions_text = is_string($system_instruction) ? $system_instruction : '';

    if ($use_openai_conversation_state && $previous_response_id !== null) {
        $last_message = end($messages);
        if ($last_message && $last_message['role'] === 'user') {
            $input_array[] = ['role' => 'user', 'content' => trim($last_message['content'])];
        } else {
            if (empty($instructions_text)) {
                $input_array[] = ['role' => 'system', 'content' => 'Continue the conversation.'];
            }
        }
        if (!empty($instructions_text)) {
            if (empty($input_array) || $input_array[0]['role'] !== 'system') {
                array_unshift($input_array, ['role' => 'system', 'content' => $instructions_text]);
            }
        }
    } else {
        if (!empty($instructions_text)) {
            $input_array[] = ['role' => 'system', 'content' => $instructions_text];
        }
        foreach ($messages as $msg) {
            $role = $msg['role'] ?? 'user';
            $content = isset($msg['content']) ? trim($msg['content']) : '';
            $api_role = ($role === 'bot') ? 'assistant' : $role;
            if ($content !== '' && in_array($api_role, ['system', 'assistant', 'user'], true)) {
                if ($api_role === 'system' && !empty($instructions_text)) {
                    continue;
                }
                $input_array[] = ['role' => $api_role, 'content' => $content];
            }
        }
    }

    if (!empty($ai_params['image_inputs']) && is_array($ai_params['image_inputs'])) {
        $last_message_key = array_key_last($input_array);
        if ($last_message_key !== null && isset($input_array[$last_message_key]['role']) && $input_array[$last_message_key]['role'] === 'user') {
            $user_text_content = '';
            if (is_string($input_array[$last_message_key]['content'])) {
                $user_text_content = $input_array[$last_message_key]['content'];
            } elseif (is_array($input_array[$last_message_key]['content'])) {
                foreach ($input_array[$last_message_key]['content'] as $part) {
                    if (isset($part['type']) && ($part['type'] === 'text' || $part['type'] === 'input_text') && isset($part['text'])) {
                        $user_text_content = $part['text'];
                        break;
                    }
                }
            }
            $new_content_parts = [];
            if (!empty($user_text_content) || empty($ai_params['image_inputs'])) {
                $new_content_parts[] = ['type' => 'input_text', 'text' => $user_text_content];
            }
            foreach ($ai_params['image_inputs'] as $image_input) {
                if (isset($image_input['base64']) && isset($image_input['type'])) {
                    $new_content_parts[] = [
                        'type' => 'input_image',
                        'image_url' => 'data:' . $image_input['type'] . ';base64,' . $image_input['base64']
                    ];
                }
            }
            if (!empty($new_content_parts)) {
                $input_array[$last_message_key]['content'] = $new_content_parts;
            } elseif (empty($user_text_content) && !empty($ai_params['image_inputs'])) {
                if (empty($input_array[$last_message_key]['content']) && !empty($ai_params['image_inputs'])) {
                    $input_array[$last_message_key]['content'] = [['type' => 'input_text', 'text' => '']];
                    foreach ($ai_params['image_inputs'] as $image_input) {
                        if (isset($image_input['base64']) && isset($image_input['type'])) {
                            $input_array[$last_message_key]['content'][] = [
                                'type' => 'input_image',
                                'image_url' => 'data:' . $image_input['type'] . ';base64,' . $image_input['base64']
                            ];
                        }
                    }
                }
            }
        }
    }

    $body_data = [
        'model'    => $model,
        'input'    => $input_array,
        'stream'   => true,
    ];

    if ($use_openai_conversation_state) {
        $body_data['store'] = true;
    } else {
        $store_conversation_globally = isset($ai_params['store_conversation']) && $ai_params['store_conversation'] === '1';
        $body_data['store'] = $store_conversation_globally;
    }

    if ($use_openai_conversation_state && $previous_response_id !== null) {
        $body_data['previous_response_id'] = $previous_response_id;
    }

    $tools = [];
    
    if (
        isset($ai_params['vector_store_tool_config']) &&
        is_array($ai_params['vector_store_tool_config']) &&
        $ai_params['vector_store_tool_config']['type'] === 'file_search' &&
        isset($ai_params['vector_store_tool_config']['vector_store_ids']) &&
        is_array($ai_params['vector_store_tool_config']['vector_store_ids']) &&
        !empty($ai_params['vector_store_tool_config']['vector_store_ids'])
    ) {
        $file_search_tool = [
            'type' => 'file_search',
            'vector_store_ids' => $ai_params['vector_store_tool_config']['vector_store_ids'],
            'max_num_results' => $ai_params['vector_store_tool_config']['max_num_results'] ?? 3
        ];        // Add ranking_options if provided
        if (isset($ai_params['vector_store_tool_config']['ranking_options']) && 
            is_array($ai_params['vector_store_tool_config']['ranking_options'])) {
            // Ensure score_threshold is clamped and rounded to avoid excessive decimals
            $ranking_opts = $ai_params['vector_store_tool_config']['ranking_options'];
            if (isset($ranking_opts['score_threshold'])) {
                $st = floatval($ranking_opts['score_threshold']);
                if ($st <= 0) { $st = 0.0; }
                elseif ($st >= 1) { $st = 1.0; }
                else { $st = round($st, 6); }
                $ranking_opts['score_threshold'] = $st;
            }
            $file_search_tool['ranking_options'] = $ranking_opts;
        }
        
        $tools[] = $file_search_tool;
    }
    $bot_allows_web_search_sse = isset($ai_params['web_search_tool_config']['enabled']) && $ai_params['web_search_tool_config']['enabled'] === true;
    $frontend_requests_web_search_sse = isset($ai_params['frontend_web_search_active']) && $ai_params['frontend_web_search_active'] === true;

    if ($bot_allows_web_search_sse && $frontend_requests_web_search_sse) {
        $web_search_tool_sse = ['type' => 'web_search_preview'];
        if (isset($ai_params['web_search_tool_config']['search_context_size']) && !empty($ai_params['web_search_tool_config']['search_context_size'])) {
            $web_search_tool_sse['search_context_size'] = $ai_params['web_search_tool_config']['search_context_size'];
        }
        if (isset($ai_params['web_search_tool_config']['user_location']) && is_array($ai_params['web_search_tool_config']['user_location']) && !empty(array_filter($ai_params['web_search_tool_config']['user_location']))) {
            $web_search_tool_sse['user_location'] = array_filter($ai_params['web_search_tool_config']['user_location']);
            if (!isset($web_search_tool_sse['user_location']['type'])) {
                $web_search_tool_sse['user_location']['type'] = 'approximate';
            }
        }
        $tools[] = $web_search_tool_sse;
    }

    if (!empty($tools)) {
        $body_data['tools'] = $tools;
    }

    if (isset($ai_params['temperature'])) {
        $body_data['temperature'] = floatval($ai_params['temperature']);
    }
    if (isset($ai_params['max_completion_tokens'])) {
        $body_data['max_output_tokens'] = absint($ai_params['max_completion_tokens']);
    }
    if (isset($ai_params['top_p'])) {
        $body_data['top_p'] = floatval($ai_params['top_p']);
    }
    if (!empty($system_instruction) && is_array($system_instruction)) { // System instruction can be an object for Responses API
        $body_data['instructions'] = $system_instruction;
    }

    // --- NEW: Add reasoning parameter ---
    if (isset($ai_params['reasoning']) && is_array($ai_params['reasoning'])) {
        $body_data['reasoning'] = $ai_params['reasoning'];
    }
    // --- END NEW ---
    // --- NEW: Unset unsupported parameters for specific models ---
    $model_lower = strtolower($model);
    if (strpos($model_lower, 'gpt-5') !== false || strpos($model_lower, 'o1') !== false || strpos($model_lower, 'o3') !== false || strpos($model_lower, 'o4') !== false) {
        unset($body_data['temperature'], $body_data['top_p'], $body_data['frequency_penalty'], $body_data['presence_penalty']);
    }
    // --- END NEW ---
    
    return $body_data;
}