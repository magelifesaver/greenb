<?php
// File: classes/core/providers/azure/bootstrap-payload-formatter.php

namespace WPAICG\Core\Providers\Azure;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load method logic files
require_once __DIR__ . '/_shared-format.php'; // For the private static format method
require_once __DIR__ . '/format-chat.php';
require_once __DIR__ . '/format-sse.php';
require_once __DIR__ . '/format-embeddings.php';

/**
 * Handles formatting request payloads for the Azure OpenAI API (Chat Completions format).
 * Original logic for static methods is now in separate files within the 'Methods' namespace.
 */
class AzurePayloadFormatter {

    // The private static method `format` is now a namespaced function in _shared-format.php
    // Public static methods will call it.

    public static function format_chat(string $instructions, array $history, array $ai_params, string $model): array {
        return \WPAICG\Core\Providers\Azure\Methods\format_chat_logic_for_payload_formatter($instructions, $history, $ai_params, $model);
    }

    public static function format_sse(array $messages, string $instructions, array $ai_params, string $model, bool $request_usage = true): array {
        return \WPAICG\Core\Providers\Azure\Methods\format_sse_logic_for_payload_formatter($messages, $instructions, $ai_params, $model, $request_usage);
    }

    public static function format_embeddings($input, array $options): array {
        return \WPAICG\Core\Providers\Azure\Methods\format_embeddings_logic_for_payload_formatter($input, $options);
    }
}