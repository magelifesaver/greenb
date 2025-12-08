<?php
// File: classes/core/providers/openrouter/bootstrap-payload-formatter.php
// Status: MODIFIED
// Was: classes/core/providers/openrouter/OpenRouterPayloadFormatter.php

namespace WPAICG\Core\Providers\OpenRouter;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load method logic files
require_once __DIR__ . '/_shared-format.php';
require_once __DIR__ . '/format-chat.php';
require_once __DIR__ . '/format-sse.php';


/**
 * Handles formatting request payloads for the OpenRouter API (Chat Completions format) (Modularized).
 * Original logic for static methods is now in separate files within the 'Methods' namespace.
 */
class OpenRouterPayloadFormatter {

    // The private static method `format` is now a namespaced function in _shared-format.php
    // and is called by the namespaced logic functions for format_chat and format_sse.

    /**
     * Formats the payload for a standard chat request.
     */
    public static function format_chat(string $instructions, array $history, array $ai_params, string $model): array {
        return \WPAICG\Core\Providers\OpenRouter\Methods\format_chat_logic_for_payload_formatter($instructions, $history, $ai_params, $model);
    }

    /**
     * Formats the payload for an SSE (streaming) chat request.
     */
    public static function format_sse(array $messages, string $system_instruction, array $ai_params, string $model): array {
        return \WPAICG\Core\Providers\OpenRouter\Methods\format_sse_logic_for_payload_formatter($messages, $system_instruction, $ai_params, $model);
    }
}