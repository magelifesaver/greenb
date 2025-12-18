<?php
// File: classes/core/providers/google/_shared-format.php
// Status: MODIFIED

namespace WPAICG\Core\Providers\Google\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Shared formatting logic, previously a private static method in GooglePayloadFormatter.
 * UPDATED: Adds grounding tools to payload if active.
 * FIXED: Correctly maps internal 'bot' role to 'model' for Google API.
 * FIXED: Correctly assigns 'google_search' or 'google_search_retrieval' tool based on model.
 * FIXED: Exclude messages with original role 'system' from Google's 'contents' array.
 *
 * @param string $instructions System instructions.
 * @param array  $history Conversation history (roles: 'user', 'model', or plugin's internal 'bot', 'system').
 * @param array  $ai_params AI parameters, can include 'google_search_grounding_active', 'google_grounding_mode', 'google_grounding_dynamic_threshold', 'model_id_for_grounding'.
 * @return array The formatted payload base.
 */
function _shared_format_logic(string $instructions, array $history, array $ai_params): array {
    $contents = [];

    foreach ($history as $msg) {
        // --- MODIFICATION START: Skip 'system' role messages from history for Google 'contents' ---
        if (isset($msg['role']) && $msg['role'] === 'system') {
            // These are internal logs (like trigger logs) or system messages
            // not meant for direct inclusion in the user/model turns for Google.
            // The main AI system instruction is handled separately.
            continue;
        }
        // --- MODIFICATION END ---

        $role = ($msg['role'] === 'bot' || $msg['role'] === 'assistant') ? 'model' : 'user';
        
        $content_parts = [];
        if (isset($msg['content']) && is_array($msg['content'])) { 
            foreach ($msg['content'] as $part) {
                if (isset($part['type'])) {
                    if ($part['type'] === 'text' || $part['type'] === 'input_text') {
                        $content_parts[] = ['text' => $part['text'] ?? ''];
                    } elseif (($part['type'] === 'image_url' || $part['type'] === 'input_image') && isset($part['image_url'])) {
                        if (is_string($part['image_url']) && strpos($part['image_url'], 'data:') === 0) {
                            list($type, $data) = explode(';', $part['image_url']);
                            list(, $data)      = explode(',', $data);
                            $mime_type = substr($type, 5);
                            $content_parts[] = ['inline_data' => ['mime_type' => $mime_type, 'data' => $data]];
                        } else if (is_array($part['image_url']) && isset($part['image_url']['url']) && strpos($part['image_url']['url'], 'data:') === 0){
                            list($type, $data) = explode(';', $part['image_url']['url']);
                            list(, $data)      = explode(',', $data);
                            $mime_type = substr($type, 5);
                            $content_parts[] = ['inline_data' => ['mime_type' => $mime_type, 'data' => $data]];
                        }
                    }
                }
            }
        } elseif (isset($msg['content']) && is_string($msg['content'])) {
            $content_parts[] = ['text' => trim($msg['content'])];
        }

        if (!empty($content_parts)) {
            // Ensure role is either 'user' or 'model' before adding to contents
            if ($role === 'user' || $role === 'model') {
                $contents[] = ['role' => $role, 'parts' => $content_parts];
            }
        }
    }

    $cleaned_contents = [];
    $last_role = null;
    foreach ($contents as $msg) {
        if ($msg['role'] !== $last_role) {
            $cleaned_contents[] = $msg;
            $last_role = $msg['role'];
        } else {
            $last_index = count($cleaned_contents) - 1;
            if ($last_index >= 0 && $cleaned_contents[$last_index]['role'] === $msg['role']) {
                $cleaned_contents[$last_index]['parts'] = array_merge($cleaned_contents[$last_index]['parts'], $msg['parts']);
            } else {
                $cleaned_contents[] = $msg;
                $last_role = $msg['role'];
            }
        }
    }

    $body_data = ['contents' => $cleaned_contents];

    if (!empty($instructions)) {
        $body_data['system_instruction'] = ['parts' => [['text' => $instructions]]];
    }

    $generationConfig = [];
    $param_map = [
        'temperature' => 'temperature',
        'max_completion_tokens' => 'maxOutputTokens',
        'top_p' => 'topP',
        'stop' => 'stopSequences',
    ];
    foreach ($param_map as $aipkit_key => $api_key) {
        if (isset($ai_params[$aipkit_key])) {
            $value = $ai_params[$aipkit_key];
            if ($api_key === 'temperature' || $api_key === 'topP') {
                $generationConfig[$api_key] = floatval($value);
            } elseif ($api_key === 'maxOutputTokens') {
                $generationConfig[$api_key] = absint($value);
            } elseif ($api_key === 'stopSequences' && !empty($value)) {
                $generationConfig[$api_key] = is_string($value) ? [$value] : (is_array($value) ? $value : null);
                if (empty($generationConfig[$api_key])) unset($generationConfig[$api_key]);
            }
        }
    }
    if (!empty($generationConfig)) $body_data['generationConfig'] = $generationConfig;

    if (isset($ai_params['safety_settings']) && is_array($ai_params['safety_settings'])) {
        $body_data['safetySettings'] = $ai_params['safety_settings'];
    }

    $google_search_grounding_active = $ai_params['frontend_google_search_grounding_active'] ?? false;
    $model_name_for_grounding = $ai_params['model_id_for_grounding'] ?? '';

    if ($google_search_grounding_active) {
        $tools = [];
        // --- FIXED: Tool selection logic based on model name ---
        if (strpos($model_name_for_grounding, 'gemini-1.5-flash') !== false) {
            // Gemini 1.5 Flash uses google_search_retrieval
            $grounding_mode = $ai_params['google_grounding_mode'] ?? 'DEFAULT_MODE';
            if ($grounding_mode === 'MODE_DYNAMIC') {
                $dynamic_threshold = $ai_params['google_grounding_dynamic_threshold'] ?? 0.3;
                $tools[] = [
                    'google_search_retrieval' => [
                        'dynamic_retrieval_config' => [
                            'mode' => 'MODE_DYNAMIC',
                            'dynamic_threshold' => floatval($dynamic_threshold),
                        ]
                    ]
                ];
            } else { 
                $tools[] = ['google_search_retrieval' => new \stdClass()];
            }
        } elseif (strpos($model_name_for_grounding, 'gemini-pro') !== false || strpos($model_name_for_grounding, 'gemini-1.5-pro') !== false || strpos($model_name_for_grounding, 'gemini-2.0') !== false) {
            // Other Gemini Pro / 1.5 Pro / 2.0 models use google_search (Search as a tool)
            $tools[] = ['google_search' => new \stdClass()];
        }
        // --- END FIXED ---
        
        if (!empty($tools)) {
            $body_data['tools'] = $tools;
        }
    }
    return $body_data;
}