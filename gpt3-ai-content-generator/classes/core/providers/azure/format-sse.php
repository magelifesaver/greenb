<?php
// File: classes/core/providers/azure/format-sse.php

namespace WPAICG\Core\Providers\Azure\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the format_sse static method of AzurePayloadFormatter.
 *
 * @param array  $messages Formatted messages array (user/assistant).
 * @param string $system_instruction System instructions.
 * @param array  $ai_params AI parameters.
 * @param string $model Model/deployment ID.
 * @param bool   $request_usage Whether to request usage (Azure specific stream option).
 * @return array The formatted SSE payload.
 */
function format_sse_logic_for_payload_formatter(array $messages, string $system_instruction, array $ai_params, string $model, bool $request_usage = true): array {
    // Convert message roles for history argument for _shared_format_logic
    $history_for_shared_format = array_map(function($msg) {
        if ($msg['role'] === 'bot') $msg['role'] = 'assistant'; // Ensure role consistency for shared formatter
        return $msg;
    }, $messages);

    $payload = _shared_format_logic($system_instruction, $history_for_shared_format, $ai_params);
    $payload['stream'] = true;
    if ($request_usage) {
        // Azure specific stream option for usage
        $payload['stream_options'] = ['include_usage' => true];
    }
    return $payload;
}