<?php
// File: classes/core/providers/google/bootstrap-payload-formatter.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\Google;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load method logic files
require_once __DIR__ . '/_shared-format.php';
require_once __DIR__ . '/format-chat.php';
require_once __DIR__ . '/format-sse.php';
require_once __DIR__ . '/format-embeddings.php';

/**
 * Handles formatting request payloads for the Google Gemini API (Modularized).
 * Original logic for static methods is now in separate files within the 'Methods' namespace.
 */
class GooglePayloadFormatter {

    public static function format_chat(string $instructions, array $history, array $ai_params, string $model): array {
        return \WPAICG\Core\Providers\Google\Methods\format_chat_logic_for_payload_formatter($instructions, $history, $ai_params, $model);
    }

    public static function format_sse(array $messages, $system_instruction, array $ai_params, string $model): array {
        return \WPAICG\Core\Providers\Google\Methods\format_sse_logic_for_payload_formatter($messages, $system_instruction, $ai_params, $model);
    }

    public static function format_embeddings($input, array $options): array {
        return \WPAICG\Core\Providers\Google\Methods\format_embeddings_logic_for_payload_formatter($input, $options);
    }
}