<?php
// File: classes/core/providers/google/format-chat.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the format_chat static method of GooglePayloadFormatter.
 *
 * @param string $instructions System instructions.
 * @param array  $history Conversation history.
 * @param array  $ai_params AI parameters.
 * @param string $model Model ID (for grounding tool determination).
 * @return array The formatted payload.
 */
function format_chat_logic_for_payload_formatter(string $instructions, array $history, array $ai_params, string $model): array {
    $ai_params['model_id_for_grounding'] = $model;
    return _shared_format_logic($instructions, $history, $ai_params);
}