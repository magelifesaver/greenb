<?php
// File: classes/core/providers/azure/_shared-format.php

namespace WPAICG\Core\Providers\Azure\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Shared formatting logic, previously a private static method in AzurePayloadFormatter.
 *
 * @param string $instructions System instructions.
 * @param array  $history Conversation history.
 * @param array  $ai_params AI parameters (temperature, max_tokens, etc.).
 * @return array The formatted payload base.
 */
function _shared_format_logic(string $instructions, array $history, array $ai_params): array {
    $messages = [];
    if (!empty($instructions)) {
        $messages[] = ['role' => 'system', 'content' => $instructions];
    }
    foreach ($history as $msg) {
        $role = ($msg['role'] === 'bot') ? 'assistant' : $msg['role']; // Map 'bot' to 'assistant'
        $content = isset($msg['content']) ? trim($msg['content']) : '';
        if ($content !== '' && in_array($role, ['system', 'user', 'assistant'], true)) {
            if ($role === 'system' && !empty($instructions)) continue; // Avoid duplicate system message
            $messages[] = ['role' => $role, 'content' => $content];
        }
    }

    $body_data = ['messages' => $messages];

    // Map AIPKit standard AI params to Chat Completions API params
    $param_map = [
        'temperature' => 'temperature',
        'max_completion_tokens' => 'max_completion_tokens', // API uses 'max_tokens'
        'top_p' => 'top_p',
        'stop' => 'stop',
        // Azure specific params can be added here if they differ from OpenAI Chat Completions
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
                if (empty($body_data[$api_key])) unset($body_data[$api_key]); // Remove if value results in empty
            }
        }
    }
    // unset top_p
    unset($body_data['top_p']);
    // unset temperature
    unset($body_data['temperature']);

    return $body_data;
}