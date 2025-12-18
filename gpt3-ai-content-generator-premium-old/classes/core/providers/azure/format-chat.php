<?php
// File: classes/core/providers/azure/format-chat.php

namespace WPAICG\Core\Providers\Azure\Methods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the format_chat static method of AzurePayloadFormatter.
 *
 * @param string $instructions System instructions.
 * @param array  $history Conversation history.
 * @param array  $ai_params AI parameters.
 * @param string $model Model/deployment ID (not used directly by Azure format logic, but kept for signature).
 * @return array The formatted payload.
 */
function format_chat_logic_for_payload_formatter(string $instructions, array $history, array $ai_params, string $model): array {
    return _shared_format_logic($instructions, $history, $ai_params);
}