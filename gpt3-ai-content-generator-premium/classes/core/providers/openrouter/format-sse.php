<?php
// File: classes/core/providers/openrouter/format-sse.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenRouter\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the format_sse static method of OpenRouterPayloadFormatter.
 *
 * @param array  $messages Formatted messages array (user/assistant).
 * @param string $system_instruction System instructions.
 * @param array  $ai_params AI parameters.
 * @param string $model Model name.
 * @return array The formatted SSE payload.
 */
function format_sse_logic_for_payload_formatter(array $messages, string $system_instruction, array $ai_params, string $model): array {
    // The 'history' array for _shared_format_logic should already contain all necessary messages
    // (user/assistant/system) from the $messages array, and the system instruction is handled by the shared logic.
    $payload = _shared_format_logic($system_instruction, $messages, $ai_params, $model);
    $payload['stream'] = true;
    // OpenRouter supports requesting usage in stream options
    $payload['stream_options'] = ['include_usage' => true];
    return $payload;
}