<?php
// File: classes/core/providers/openrouter/_shared-format.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenRouter\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Shared formatting logic, previously a private static method in OpenRouterPayloadFormatter.
 *
 * @param string $instructions System instructions.
 * @param array  $history Conversation history.
 * @param array  $ai_params AI parameters (temperature, max_tokens, etc.).
 * @param string $model Model name (required by OpenRouter in payload).
 * @return array The formatted payload base.
 */
function _shared_format_logic(string $instructions, array $history, array $ai_params, string $model): array {
    $messages = [];
    if (!empty($instructions)) {
        $messages[] = ['role' => 'system', 'content' => $instructions];
    }
    foreach ($history as $msg) {
        $role = ($msg['role'] === 'bot') ? 'assistant' : $msg['role'];
        $content = isset($msg['content']) ? trim($msg['content']) : '';
        if ($content !== '' && in_array($role, ['system', 'user', 'assistant'])) {
             // Prevent duplicate system message if already added above
             if ($role === 'system' && !empty($instructions)) continue;
            $messages[] = ['role' => $role, 'content' => $content];
        }
    }

    $body_data = [
        'model' => $model, // OpenRouter requires model in payload
        'messages' => $messages
    ];

    // Map AIPKit standard AI params to Chat Completions API params
    $param_map = [
        'temperature' => 'temperature',
        'max_completion_tokens' => 'max_tokens',
        'top_p' => 'top_p',
        'stop' => 'stop',
    ];

    foreach ($param_map as $aipkit_key => $api_key) {
        if (isset($ai_params[$aipkit_key])) {
            $value = $ai_params[$aipkit_key];
            if (in_array($api_key, ['temperature', 'top_p'])) {
                $body_data[$api_key] = floatval($value);
            } elseif ($api_key === 'max_tokens') {
                $body_data[$api_key] = absint($value);
            } elseif ($api_key === 'stop' && !empty($value)) {
                $body_data[$api_key] = is_string($value) ? [$value] : (is_array($value) ? $value : null);
                if (empty($body_data[$api_key])) unset($body_data[$api_key]);
            }
        }
    }

    // Remove 'max_completion_tokens' if it exists from the direct mapping
    unset($body_data['max_completion_tokens']);

    return $body_data;
}