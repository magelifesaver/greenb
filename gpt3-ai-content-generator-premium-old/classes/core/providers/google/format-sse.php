<?php
// File: classes/core/providers/google/format-sse.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the format_sse static method of GooglePayloadFormatter.
 *
 * @param array  $messages Formatted messages array (user/model).
 * @param string $system_instruction System instructions.
 * @param array  $ai_params AI parameters.
 * @param string $model Model ID (for grounding tool determination).
 * @return array The formatted SSE payload.
 */
function format_sse_logic_for_payload_formatter(array $messages, string $system_instruction, array $ai_params, string $model): array {
    $history = array_map(function($msg) { 
        if ($msg['role'] === 'assistant') $msg['role'] = 'model';
        return $msg;
    }, $messages);
    $ai_params['model_id_for_grounding'] = $model;
    $payload = _shared_format_logic($system_instruction, $history, $ai_params);
    return $payload;
}