<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/bootstrap-provider-strategy.php
// Status: NEW FILE

namespace WPAICG\Core\Providers;

use WP_Error;

// Ensure BaseProviderStrategy is loaded if not already by autoloader
if (!class_exists(BaseProviderStrategy::class)) {
    $base_strategy_path = WPAICG_PLUGIN_DIR . 'classes/core/providers/base-provider-strategy.php';
    if (file_exists($base_strategy_path)) {
        require_once $base_strategy_path;
    } else {
        // This is a critical error, the class cannot be defined.
        return;
    }
}
// Ensure OpenAIUrlBuilder and other specific utility classes are loaded by their own bootstraps
// The ProviderDependenciesLoader will handle loading bootstrap-url-builder.php etc.

// Load individual method logic files
$methods_path = __DIR__ . '/';
require_once $methods_path . 'build-api-url.php';
require_once $methods_path . 'get-api-headers.php';
require_once $methods_path . 'format-chat-payload.php';
require_once $methods_path . 'parse-chat-response.php';
require_once $methods_path . 'parse-error-response.php';
require_once $methods_path . 'get-models.php';
require_once $methods_path . 'build-sse-payload.php';
require_once $methods_path . 'parse-sse-chunk.php';
require_once $methods_path . 'moderate-text.php';
require_once $methods_path . 'generate-embeddings.php';


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * OpenAI Provider Strategy (Modularized).
 * Handles OpenAI specific API interactions.
 * Original logic for methods is now in separate files within the 'Methods' namespace.
 */
class OpenAIProviderStrategy extends BaseProviderStrategy {

    public function __construct() {
        // Constructor logic from the original OpenAIProviderStrategy.php
        // For example, initializing OpenAIUrlBuilder, OpenAIPayloadFormatter, OpenAIResponseParser
        // if they were instantiated here. However, those were static classes.
        // If there was any other specific constructor logic, it would go here.
    }

    public function build_api_url(string $operation, array $params): string|WP_Error {
        return \WPAICG\Core\Providers\OpenAI\Methods\build_api_url_logic($this, $operation, $params);
    }

    public function get_api_headers(string $api_key, string $operation): array {
        return \WPAICG\Core\Providers\OpenAI\Methods\get_api_headers_logic($this, $api_key, $operation);
    }

    public function format_chat_payload(string $user_message, string $instructions, array $history, array $ai_params, string $model): array {
        return \WPAICG\Core\Providers\OpenAI\Methods\format_chat_payload_logic($this, $user_message, $instructions, $history, $ai_params, $model);
    }

    public function parse_chat_response(array $decoded_response, array $request_data): array|WP_Error {
        return \WPAICG\Core\Providers\OpenAI\Methods\parse_chat_response_logic($this, $decoded_response, $request_data);
    }

    public function parse_error_response($response_body, int $status_code): string {
        return \WPAICG\Core\Providers\OpenAI\Methods\parse_error_response_logic($this, $response_body, $status_code);
    }

    public function get_models(array $api_params): array|WP_Error {
        return \WPAICG\Core\Providers\OpenAI\Methods\get_models_logic($this, $api_params);
    }

    public function build_sse_payload(array $messages, $system_instruction, array $ai_params, string $model): array {
        return \WPAICG\Core\Providers\OpenAI\Methods\build_sse_payload_logic($this, $messages, $system_instruction, $ai_params, $model);
    }

    public function parse_sse_chunk(string $sse_chunk, string &$current_buffer): array {
        return \WPAICG\Core\Providers\OpenAI\Methods\parse_sse_chunk_logic($this, $sse_chunk, $current_buffer);
    }

    public function moderate_text(string $text, array $api_params): bool|WP_Error {
        return \WPAICG\Core\Providers\OpenAI\Methods\moderate_text_logic($this, $text, $api_params);
    }

    public function generate_embeddings($input, array $api_params, array $options = []): array|WP_Error {
        return \WPAICG\Core\Providers\OpenAI\Methods\generate_embeddings_logic($this, $input, $api_params, $options);
    }

    // Public wrapper for the protected decode_json method from BaseProviderStrategy
    // This allows externalized logic functions to call it via the strategy instance.
    public function decode_json_public(string $json_string, string $context): array|WP_Error {
        return $this->decode_json($json_string, $context);
    }
}