<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/dashboard/ajax/pinecone/handler-indexes/ajax-search-index.php
// Status: MODIFIED

namespace WPAICG\Dashboard\Ajax\Pinecone\HandlerIndexes;

use WP_Error;
use WPAICG\Dashboard\Ajax\AIPKit_Vector_Store_Pinecone_Ajax_Handler;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the logic for searching a Pinecone index.
 * Called by AIPKit_Vector_Store_Pinecone_Ajax_Handler::ajax_search_pinecone_index().
 *
 * @param AIPKit_Vector_Store_Pinecone_Ajax_Handler $handler_instance
 * @return void
 */
function do_ajax_search_index_logic(AIPKit_Vector_Store_Pinecone_Ajax_Handler $handler_instance): void
{
    $vector_store_manager = $handler_instance->get_vector_store_manager();
    $ai_caller = $handler_instance->get_ai_caller();

    if (!$vector_store_manager || !$ai_caller) {
        $handler_instance->send_wp_error(new WP_Error('manager_not_ready_pinecone_search', __('Vector Store or AI components not available.', 'gpt3-ai-content-generator'), ['status' => 500]));
        return;
    }

    $pinecone_config = $handler_instance->_get_pinecone_config();
    if (is_wp_error($pinecone_config)) {
        $handler_instance->send_wp_error($pinecone_config);
        return;
    }
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in the calling handler method.
    $post_data = wp_unslash($_POST);
    $index_id = isset($post_data['index_id']) ? sanitize_text_field($post_data['index_id']) : '';
    $query_text = isset($post_data['query_text']) ? sanitize_textarea_field($post_data['query_text']) : '';
    $top_k = isset($post_data['top_k']) ? absint($post_data['top_k']) : 3;
    $namespace = isset($post_data['namespace']) ? sanitize_text_field($post_data['namespace']) : ''; // Get namespace
    $embedding_provider_key = isset($post_data['embedding_provider']) ? sanitize_key($post_data['embedding_provider']) : 'openai';
    $embedding_model = isset($post_data['embedding_model']) ? sanitize_text_field($post_data['embedding_model']) : '';

    if (empty($index_id)) {
        $handler_instance->send_wp_error(new WP_Error('missing_index_id_pinecone_search', __('Pinecone index name is required.', 'gpt3-ai-content-generator'), ['status' => 400]));
        return;
    }
    if (empty($query_text)) {
        $handler_instance->send_wp_error(new WP_Error('missing_query_text_pinecone', __('Search query text cannot be empty.', 'gpt3-ai-content-generator'), ['status' => 400]));
        return;
    }
    if (empty($embedding_model)) {
        $handler_instance->send_wp_error(new WP_Error('missing_embedding_model_pinecone', __('Embedding model must be specified for search.', 'gpt3-ai-content-generator'), ['status' => 400]));
        return;
    }

    $provider_map = ['openai' => 'OpenAI', 'google' => 'Google', 'azure' => 'Azure'];
    $embedding_provider_norm = $provider_map[$embedding_provider_key] ?? 'OpenAI';
    $embedding_options = ['model' => $embedding_model];

    $embedding_result = $ai_caller->generate_embeddings($embedding_provider_norm, $query_text, $embedding_options);

    if (is_wp_error($embedding_result) || empty($embedding_result['embeddings'][0])) {
        $error = is_wp_error($embedding_result) ? $embedding_result : new WP_Error('embedding_failed_pinecone_search', __('Failed to generate query vector for Pinecone search.', 'gpt3-ai-content-generator'));
        $handler_instance->send_wp_error($error);
        return;
    }

    $query_vector_values = $embedding_result['embeddings'][0];
    $query_vector_for_pinecone = ['vector' => $query_vector_values];
    if (!empty($namespace)) { // Add namespace if provided
        $query_vector_for_pinecone['namespace'] = $namespace;
    }

    $search_results = $vector_store_manager->query_vectors('Pinecone', $index_id, $query_vector_for_pinecone, $top_k, [], $pinecone_config);

    if (is_wp_error($search_results)) {
        $handler_instance->send_wp_error($search_results);
    } else {
        wp_send_json_success(['results' => $search_results, 'message' => __('Pinecone search complete.', 'gpt3-ai-content-generator')]);
    }
}
