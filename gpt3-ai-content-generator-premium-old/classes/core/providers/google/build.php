<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/providers/google/build.php
// Status: MODIFIED

namespace WPAICG\Core\Providers\Google\Methods;

use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the build static method of GoogleUrlBuilder.
 *
 * @param string $operation ('chat', 'models', 'stream', 'embedContent')
 * @param array  $params Required parameters (base_url, api_version, api_key, model) and optional (pageSize, pageToken).
 * @return string|WP_Error The full URL or WP_Error.
 */
function build_logic_for_url_builder(string $operation, array $params): string|WP_Error {
    $base_url = !empty($params['base_url']) ? rtrim($params['base_url'], '/') : '';
    $api_version = !empty($params['api_version']) ? $params['api_version'] : '';
    $api_key = !empty($params['api_key']) ? $params['api_key'] : '';
    $model_id = !empty($params['model']) ? $params['model'] : '';

    if (empty($base_url)) return new WP_Error("missing_base_url_Google_logic", __('Google Base URL is required.', 'gpt3-ai-content-generator'));
    if (empty($api_version)) return new WP_Error("missing_api_version_Google_logic", __('Google API Version is required.', 'gpt3-ai-content-generator'));
    if (empty($api_key)) return new WP_Error('missing_google_api_key_for_url_logic', __('Google API key is required for URL construction.', 'gpt3-ai-content-generator'));

    $paths = [
        'models'       => '/models',
        'chat'         => '/models/{model}:generateContent',
        'stream'       => '/models/{model}:streamGenerateContent',
        'embedContent' => '/models/{model}:embedContent',
    ];

    $path_key = ($operation === 'stream') ? 'stream' :
                (($operation === 'chat') ? 'chat' :
                (($operation === 'embedContent') ? 'embedContent' : 'models'));
    $path_segment = $paths[$path_key] ?? null;

    if ($path_segment === null) {
        /* translators: %s: The name of the API operation (e.g., 'chat'). */
        return new WP_Error('unsupported_operation_Google_logic', sprintf(__('Operation "%s" not supported for Google.', 'gpt3-ai-content-generator'), $operation));
    }

    $full_path = '/' . trim($api_version, '/') . $path_segment;

    if ($operation === 'chat' || $operation === 'stream' || $operation === 'embedContent') {
        /* translators: %s: The name of the API endpoint path. */
        if (empty($model_id)) return new WP_Error('missing_google_model_logic', sprintf(__('Google model ID is required for the "%s" endpoint path.', 'gpt3-ai-content-generator'), $operation));
        
        // The endpoint path already includes '/models/{model}',
        // so the placeholder should receive the raw model id WITHOUT the 'models/' prefix.
        $model_id_for_path = (strpos($model_id, 'models/') === 0)
            ? substr($model_id, 7)
            : $model_id;
        $full_path = str_replace('{model}', urlencode($model_id_for_path), $full_path);
    }

    $url_with_key = $base_url . $full_path . '?key=' . urlencode($api_key);

    if ($operation === 'stream') {
        $url_with_key = add_query_arg('alt', 'sse', $url_with_key);
    }

    if ($operation === 'models') {
        if (!empty($params['pageSize'])) $url_with_key = add_query_arg('pageSize', absint($params['pageSize']), $url_with_key);
        if (!empty($params['pageToken'])) $url_with_key = add_query_arg('pageToken', urlencode($params['pageToken']), $url_with_key);
    }

    return $url_with_key;
}
