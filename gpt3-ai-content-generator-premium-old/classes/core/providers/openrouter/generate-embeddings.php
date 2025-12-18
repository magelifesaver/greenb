<?php
// File: classes/core/providers/openrouter/generate-embeddings.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenRouter\Methods;

use WPAICG\Core\Providers\OpenRouterProviderStrategy;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the generate_embeddings method of OpenRouterProviderStrategy.
 *
 * @param OpenRouterProviderStrategy $strategyInstance The instance of the strategy class.
 * @param string|array $input The input text or array of texts.
 * @param array $api_params Provider-specific API connection parameters.
 * @param array $options Embedding options (model, dimensions, encoding_format, etc.).
 * @return array|WP_Error Always returns a WP_Error indicating not supported.
 */
function generate_embeddings_logic(
    OpenRouterProviderStrategy $strategyInstance,
    $input,
    array $api_params,
    array $options = []
): array|WP_Error {
    return new WP_Error(
        'embeddings_not_supported_openrouter_logic',
        __('Dedicated embedding generation is not directly supported via the common OpenRouter API strategy. Some models might offer embeddings via their native APIs routed through OpenRouter.', 'gpt3-ai-content-generator')
    );
}