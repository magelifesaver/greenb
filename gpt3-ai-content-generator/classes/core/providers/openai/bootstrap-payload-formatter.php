<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/bootstrap-payload-formatter.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Load individual method logic files
$methods_path = __DIR__ . '/';
require_once $methods_path . 'format-chat.php';
require_once $methods_path . 'format-sse.php';
require_once $methods_path . 'format-moderation.php';
require_once $methods_path . 'format-embeddings.php';

/**
 * Handles formatting request payloads for the OpenAI Responses API (v1/responses).
 * Original logic for methods is now in separate files within the 'Methods' namespace.
 */
class OpenAIPayloadFormatter {

    public static function format_chat(
        string $instructions,
        array $history,
        array $ai_params,
        string $model,
        bool $use_openai_conversation_state = false,
        ?string $previous_response_id = null
    ): array {
        return \WPAICG\Core\Providers\OpenAI\Methods\format_chat_logic_for_payload_formatter($instructions, $history, $ai_params, $model, $use_openai_conversation_state, $previous_response_id);
    }

    public static function format_sse(
        array $messages,
        $system_instruction,
        array $ai_params,
        string $model,
        bool $use_openai_conversation_state = false,
        ?string $previous_response_id = null
    ): array {
        return \WPAICG\Core\Providers\OpenAI\Methods\format_sse_logic_for_payload_formatter($messages, $system_instruction, $ai_params, $model, $use_openai_conversation_state, $previous_response_id);
    }

    public static function format_moderation(string $text): array {
        return \WPAICG\Core\Providers\OpenAI\Methods\format_moderation_logic_for_payload_formatter($text);
    }

    public static function format_embeddings($input, array $options): array {
        return \WPAICG\Core\Providers\OpenAI\Methods\format_embeddings_logic_for_payload_formatter($input, $options);
    }
}