<?php
// File: classes/vector/providers/openai/list-indexes.php
// Status: MODIFIED

namespace WPAICG\Vector\Providers\OpenAI\Methods;

use WPAICG\Vector\Providers\AIPKit_Vector_OpenAI_Strategy;
use WPAICG\Core\Providers\OpenAI\OpenAIUrlBuilder; // Corrected namespace for OpenAIUrlBuilder
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the list_indexes method of AIPKit_Vector_OpenAI_Strategy.
 * Lists OpenAI Vector Stores with pagination.
 *
 * @param AIPKit_Vector_OpenAI_Strategy $strategyInstance The instance of the strategy class.
 * @param int|null $limit Max items.
 * @param string|null $order Sort order.
 * @param string|null $after Cursor for next page.
 * @param string|null $before Cursor for previous page.
 * @return array|WP_Error List of stores or WP_Error.
 */
function list_indexes_logic(AIPKit_Vector_OpenAI_Strategy $strategyInstance, ?int $limit = 20, ?string $order = 'desc', ?string $after = null, ?string $before = null): array|WP_Error {
    if (!$strategyInstance->get_is_connected_status()) {
        return new WP_Error('not_connected', __('Not connected to OpenAI.', 'gpt3-ai-content-generator'));
    }

    // Ensure OpenAIUrlBuilder is available
    if (!class_exists(\WPAICG\Core\Providers\OpenAI\OpenAIUrlBuilder::class)) {
        $url_builder_bootstrap = WPAICG_PLUGIN_DIR . 'classes/core/providers/openai/bootstrap-url-builder.php';
        if (file_exists($url_builder_bootstrap)) {
            require_once $url_builder_bootstrap;
        } else {
            return new WP_Error('openai_url_builder_missing_logic', 'OpenAI URL builder component is not available.');
        }
    }

    $url_params = [
        'base_url' => $strategyInstance->get_base_url(),
        'api_version' => $strategyInstance->get_api_version(),
        'limit' => $limit,
        'order' => $order,
        'after' => $after,
        'before' => $before,
    ];
    $url = \WPAICG\Core\Providers\OpenAI\OpenAIUrlBuilder::build('vector_stores', $url_params);
    if (is_wp_error($url)) return $url;

    $response = _request_logic($strategyInstance, 'GET', $url);
    if (is_wp_error($response)) return $response;

    // OpenAI list vector stores returns: {"object": "list", "data": [...], "first_id": "...", "last_id": "...", "has_more": true/false}
    return [
        'data' => $response['data'] ?? [],
        'first_id' => $response['first_id'] ?? null,
        'last_id' => $response['last_id'] ?? null,
        'has_more' => $response['has_more'] ?? false,
    ];
}