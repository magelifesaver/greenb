<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/dashboard/ajax/openai/handler-stores/ajax-search-vector-store-openai.php
// Status: MODIFIED (Logic moved here)

namespace WPAICG\Dashboard\Ajax\OpenAI\HandlerStores;

use WPAICG\Dashboard\Ajax\AIPKit_OpenAI_Vector_Stores_Ajax_Handler;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the logic for searching an OpenAI Vector Store.
 * Called by AIPKit_OpenAI_Vector_Stores_Ajax_Handler::ajax_search_vector_store_openai().
 *
 * @param AIPKit_OpenAI_Vector_Stores_Ajax_Handler $handler_instance
 * @return void
 */
function do_ajax_search_vector_store_openai_logic(AIPKit_OpenAI_Vector_Stores_Ajax_Handler $handler_instance): void
{
    // Permission check already done by the handler calling this

    $vector_store_manager = $handler_instance->get_vector_store_manager();

    if (!$vector_store_manager) {
        $handler_instance->send_wp_error(new WP_Error('manager_not_ready', __('Vector Store Manager not available.', 'gpt3-ai-content-generator'), ['status' => 500]));
        return;
    }

    $openai_config = $handler_instance->_get_openai_config();
    if (is_wp_error($openai_config)) {
        $handler_instance->send_wp_error($openai_config);
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in the calling handler method.
    $post_data = wp_unslash($_POST);
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in the calling handler method.
    $store_id = isset($post_data['store_id']) ? sanitize_text_field($post_data['store_id']) : '';
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in the calling handler method.
    $query_text = isset($post_data['query_text']) ? sanitize_textarea_field($post_data['query_text']) : '';
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in the calling handler method.
    $top_k = isset($post_data['top_k']) ? absint($post_data['top_k']) : 3;

    if (empty($store_id)) {
        $handler_instance->send_wp_error(new WP_Error('missing_store_id_search', __('Vector Store ID is required for search.', 'gpt3-ai-content-generator'), ['status' => 400]));
        return;
    }
    if (empty($query_text)) {
        $handler_instance->send_wp_error(new WP_Error('missing_query_text', __('Search query text cannot be empty.', 'gpt3-ai-content-generator'), ['status' => 400]));
        return;
    }

    $results = $vector_store_manager->query_vectors('OpenAI', $store_id, ['query_text' => $query_text], $top_k, [], $openai_config);

    if (is_wp_error($results)) {
        $handler_instance->send_wp_error($results);
        return;
    }

    wp_send_json_success(['results' => $results]);
}
