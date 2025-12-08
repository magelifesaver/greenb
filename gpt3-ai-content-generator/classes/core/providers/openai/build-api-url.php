<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/openai/build-api-url.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenAI\Methods;

use WPAICG\Core\Providers\OpenAIProviderStrategy;
use WPAICG\Core\Providers\OpenAI\OpenAIUrlBuilder; // Use the new UrlBuilder class
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the build_api_url method of OpenAIProviderStrategy.
 *
 * @param OpenAIProviderStrategy $strategyInstance The instance of the strategy class.
 * @param string $operation ('responses', 'models', 'moderation', 'audio/speech', 'audio/transcriptions', 'images/generations', 'files', 'embeddings', etc.)
 * @param array  $params Required parameters (api_key, base_url, api_version, model, deployment, etc.)
 * @return string|WP_Error The full URL or WP_Error.
 */
function build_api_url_logic(OpenAIProviderStrategy $strategyInstance, string $operation, array $params): string|WP_Error {
    // The operation for OpenAI's standard chat/stream is 'responses' in the Responses API,
    // but the general ProviderStrategyInterface might use 'chat' or 'stream'.
    // We map 'chat' or 'stream' to 'responses' for the OpenAIUrlBuilder.
    $url_operation_key = ($operation === 'chat' || $operation === 'stream') ? 'responses' : $operation;
    return OpenAIUrlBuilder::build($url_operation_key, $params);
}