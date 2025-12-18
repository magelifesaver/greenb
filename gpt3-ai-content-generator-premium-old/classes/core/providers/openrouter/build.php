<?php
// File: classes/core/providers/openrouter/build.php
// Status: NEW FILE

namespace WPAICG\Core\Providers\OpenRouter\Methods;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the build static method of OpenRouterUrlBuilder.
 *
 * @param string $operation ('chat', 'models', 'stream')
 * @param array  $params Required parameters (base_url, api_version).
 * @return string|WP_Error The full URL or WP_Error.
 */
function build_logic_for_url_builder(string $operation, array $params): string|WP_Error {
    $base_url = !empty($params['base_url']) ? rtrim($params['base_url'], '/') : '';
    $api_version = !empty($params['api_version']) ? $params['api_version'] : '';

    if (empty($base_url)) return new WP_Error("missing_base_url_OpenRouter_logic", __('OpenRouter Base URL is required.', 'gpt3-ai-content-generator'));

    $paths = [
        'chat'   => '/chat/completions', // Map 'chat' and 'stream' to this
        'models' => '/models',
    ];

    // Map 'stream' operation to use 'chat' path key
    $path_key = ($operation === 'stream') ? 'chat' : $operation;
    $path_segment = $paths[$path_key] ?? null;

    if ($path_segment === null) {
        // translators: %s is the operation name (e.g., "chat" or "stream")
        return new WP_Error('unsupported_operation_OpenRouter_logic', sprintf(__('Operation "%s" not supported for OpenRouter.', 'gpt3-ai-content-generator'), $operation));
    }

    $full_path = $path_segment;
    // Prepend version if it's provided and *not* already in the base URL
    if (!empty($api_version) && strpos($base_url, '/' . trim($api_version, '/')) === false) {
        $full_path = '/' . trim($api_version, '/') . $path_segment;
    }

    return $base_url . $full_path;
}