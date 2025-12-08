<?php
// File: classes/core/providers/openrouter/bootstrap-provider-strategy.php
// Status: MODIFIED
// Was: classes/core/providers/openrouter-provider-strategy.php

namespace WPAICG\Core\Providers;

use WP_Error;
// Traits are no longer needed directly in the strategy class, they are used by the component classes/methods.
// use WPAICG\Core\Providers\Traits\ChatCompletionsPayloadTrait;
// use WPAICG\Core\Providers\Traits\ChatCompletionsResponseParserTrait;
// use WPAICG\Core\Providers\Traits\ChatCompletionsSSEParserTrait;

// Ensure method logic files are loaded.
// These files will define functions within the WPAICG\Core\Providers\OpenRouter\Methods namespace.
$methods_path = __DIR__ . '/';
require_once $methods_path . 'build-api-url.php';
require_once $methods_path . 'get-api-headers.php';
require_once $methods_path . 'format-chat-payload.php';
require_once $methods_path . 'parse-chat-response.php';
require_once $methods_path . 'parse-error-response.php';
require_once $methods_path . 'get-models.php';
require_once $methods_path . 'build-sse-payload.php';
require_once $methods_path . 'parse-sse-chunk.php';
require_once $methods_path . 'generate-embeddings.php';


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * OpenRouter Provider Strategy (Modularized).
 * Uses standard Chat Completions format.
 * Delegates logic to namespaced functions.
 */
class OpenRouterProviderStrategy extends BaseProviderStrategy {
    // Traits are now used within the individual method logic files if needed,
    // or more likely, the OpenRouterPayloadFormatter and OpenRouterResponseParser
    // will handle the logic previously in those traits.

    public function __construct() {
        // Ensure component classes are loaded (should be handled by ProviderDependenciesLoader)
        // For robustness, we can add checks or includes here if needed, but it's better if loader handles it.
        $or_dir = __DIR__ . '/';
        if (!class_exists(\WPAICG\Core\Providers\OpenRouter\OpenRouterUrlBuilder::class)) {
             $bs_path = $or_dir . 'bootstrap-url-builder.php';
             if(file_exists($bs_path)) require_once $bs_path;
        }
        if (!class_exists(\WPAICG\Core\Providers\OpenRouter\OpenRouterPayloadFormatter::class)) {
             $bs_path = $or_dir . 'bootstrap-payload-formatter.php';
             if(file_exists($bs_path)) require_once $bs_path;
        }
        if (!class_exists(\WPAICG\Core\Providers\OpenRouter\OpenRouterResponseParser::class)) {
             $bs_path = $or_dir . 'bootstrap-response-parser.php';
             if(file_exists($bs_path)) require_once $bs_path;
        }
    }

    public function build_api_url(string $operation, array $params): string|WP_Error {
        return \WPAICG\Core\Providers\OpenRouter\Methods\build_api_url_logic($this, $operation, $params);
    }

    public function get_api_headers(string $api_key, string $operation): array {
        return \WPAICG\Core\Providers\OpenRouter\Methods\get_api_headers_logic($this, $api_key, $operation);
    }

    public function format_chat_payload(string $user_message, string $instructions, array $history, array $ai_params, string $model): array {
       return \WPAICG\Core\Providers\OpenRouter\Methods\format_chat_payload_logic($this, $user_message, $instructions, $history, $ai_params, $model);
    }

    public function parse_chat_response(array $decoded_response, array $request_data): array|WP_Error {
        return \WPAICG\Core\Providers\OpenRouter\Methods\parse_chat_response_logic($this, $decoded_response, $request_data);
    }

    public function parse_error_response($response_body, int $status_code): string {
        return \WPAICG\Core\Providers\OpenRouter\Methods\parse_error_response_logic($this, $response_body, $status_code);
    }

    public function get_models(array $api_params): array|WP_Error {
        return \WPAICG\Core\Providers\OpenRouter\Methods\get_models_logic($this, $api_params);
    }

    public function build_sse_payload(array $messages, $system_instruction, array $ai_params, string $model): array {
        return \WPAICG\Core\Providers\OpenRouter\Methods\build_sse_payload_logic($this, $messages, $system_instruction, $ai_params, $model);
    }

     public function parse_sse_chunk(string $sse_chunk, string &$current_buffer): array {
        return \WPAICG\Core\Providers\OpenRouter\Methods\parse_sse_chunk_logic($this, $sse_chunk, $current_buffer);
    }

    public function generate_embeddings($input, array $api_params, array $options = []): array|WP_Error {
        return \WPAICG\Core\Providers\OpenRouter\Methods\generate_embeddings_logic($this, $input, $api_params, $options);
    }
}