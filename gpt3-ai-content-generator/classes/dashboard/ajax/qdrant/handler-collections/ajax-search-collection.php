<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/dashboard/ajax/qdrant/handler-collections/ajax-search-collection.php
// Status: MODIFIED

namespace WPAICG\Dashboard\Ajax\Qdrant\HandlerCollections;

use WP_Error;
use WPAICG\Dashboard\Ajax\AIPKit_Vector_Store_Qdrant_Ajax_Handler;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the logic for searching a Qdrant collection.
 * Called by AIPKit_Vector_Store_Qdrant_Ajax_Handler::ajax_search_qdrant_collection().
 *
 * @param AIPKit_Vector_Store_Qdrant_Ajax_Handler $handler_instance
 * @return void
 */
function _aipkit_qdrant_ajax_search_collection_logic(AIPKit_Vector_Store_Qdrant_Ajax_Handler $handler_instance): void
{
    $vector_store_manager = $handler_instance->get_vector_store_manager();
    $ai_caller = $handler_instance->get_ai_caller();

    if (!$vector_store_manager || !$ai_caller) {
        $handler_instance->send_wp_error(new WP_Error('manager_not_ready_qdrant_search', __('Vector Store or AI components not available for Qdrant search.', 'gpt3-ai-content-generator'), ['status' => 500]));
        return;
    }

    $qdrant_config = $handler_instance->_get_qdrant_config();
    if (is_wp_error($qdrant_config)) {
        $handler_instance->send_wp_error($qdrant_config);
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in the calling handler method.
    $post_data = wp_unslash($_POST);
    $collection_id = isset($post_data['collection_id']) ? sanitize_text_field($post_data['collection_id']) : '';
    $query_text = isset($post_data['query_text']) ? sanitize_textarea_field($post_data['query_text']) : '';
    $top_k = isset($post_data['top_k']) ? absint($post_data['top_k']) : 3;
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON string, decoded and validated below.
    $filter_json = isset($post_data['filter']) ? $post_data['filter'] : null;
    $embedding_provider_key = isset($post_data['embedding_provider']) ? sanitize_key($post_data['embedding_provider']) : 'openai';
    $embedding_model = isset($post_data['embedding_model']) ? sanitize_text_field($post_data['embedding_model']) : '';

    if (empty($collection_id)) {
        $handler_instance->send_wp_error(new WP_Error('missing_collection_id_qdrant_search', __('Qdrant collection name is required.', 'gpt3-ai-content-generator'), ['status' => 400]));
        return;
    }
    if (empty($query_text)) {
        $handler_instance->send_wp_error(new WP_Error('missing_query_text_qdrant_search', __('Search query text cannot be empty.', 'gpt3-ai-content-generator'), ['status' => 400]));
        return;
    }
    if (empty($embedding_model)) {
        $handler_instance->send_wp_error(new WP_Error('missing_embedding_model_qdrant_search', __('Embedding model must be specified for search.', 'gpt3-ai-content-generator'), ['status' => 400]));
        return;
    }

    $filter = null;
    if (!empty($filter_json)) {
        $filter = json_decode($filter_json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $handler_instance->send_wp_error(new WP_Error('invalid_filter_json_qdrant_search', __('Invalid JSON format for Qdrant filter.', 'gpt3-ai-content-generator'), ['status' => 400]));
            return;
        }
    }

    $provider_map = ['openai' => 'OpenAI', 'google' => 'Google', 'azure' => 'Azure'];
    $embedding_provider_norm = $provider_map[$embedding_provider_key] ?? 'OpenAI';
    $embedding_options = ['model' => $embedding_model];

    $embedding_result = $ai_caller->generate_embeddings($embedding_provider_norm, $query_text, $embedding_options);
    if (is_wp_error($embedding_result) || empty($embedding_result['embeddings'][0])) {
        $error = is_wp_error($embedding_result) ? $embedding_result : new WP_Error('embedding_failed_qdrant_search', __('Failed to generate query vector for Qdrant search.', 'gpt3-ai-content-generator'));
        $handler_instance->send_wp_error($error);
        return;
    }

    $query_vector_values = $embedding_result['embeddings'][0];
    $query_vector_for_qdrant = ['vector' => $query_vector_values];

    $search_results = $vector_store_manager->query_vectors('Qdrant', $collection_id, $query_vector_for_qdrant, $top_k, $filter ?: [], $qdrant_config);

    if (is_wp_error($search_results)) {
        $handler_instance->send_wp_error($search_results);
    } else {
        wp_send_json_success(['results' => $search_results, 'message' => __('Qdrant search complete.', 'gpt3-ai-content-generator')]);
    }
}