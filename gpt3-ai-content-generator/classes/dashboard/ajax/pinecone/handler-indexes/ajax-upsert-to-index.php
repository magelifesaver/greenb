<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/dashboard/ajax/pinecone/handler-indexes/ajax-upsert-to-index.php
// Status: MODIFIED

namespace WPAICG\Dashboard\Ajax\Pinecone\HandlerIndexes;

use WP_Error;
use WPAICG\Dashboard\Ajax\AIPKit_Vector_Store_Pinecone_Ajax_Handler;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the logic for upserting vectors to a Pinecone index.
 * Called by AIPKit_Vector_Store_Pinecone_Ajax_Handler::ajax_upsert_to_pinecone_index().
 *
 * @param AIPKit_Vector_Store_Pinecone_Ajax_Handler $handler_instance
 * @return void
 */
function do_ajax_upsert_to_index_logic(AIPKit_Vector_Store_Pinecone_Ajax_Handler $handler_instance): void
{
    $vector_store_manager = $handler_instance->get_vector_store_manager();

    if (!$vector_store_manager) {
        $handler_instance->send_wp_error(new WP_Error('manager_not_ready_pinecone_upsert', __('Vector Store Manager not available.', 'gpt3-ai-content-generator'), ['status' => 500]));
        return;
    }

    $pinecone_config = $handler_instance->_get_pinecone_config();
    if (is_wp_error($pinecone_config)) {
        $handler_instance->send_wp_error($pinecone_config);
        return;
    }
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in the calling handler method.
    $post_data = wp_unslash($_POST);
    $index_name = isset($post_data['index_name']) ? sanitize_text_field($post_data['index_name']) : '';
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON string, decoded and validated below.
    $vectors_json = isset($post_data['vectors']) ? $post_data['vectors'] : '';
    $embedding_provider = isset($post_data['embedding_provider']) ? sanitize_key($post_data['embedding_provider']) : null;
    $embedding_model = isset($post_data['embedding_model']) ? sanitize_text_field($post_data['embedding_model']) : null;
    $original_text_content = isset($post_data['original_text_content']) ? wp_kses_post($post_data['original_text_content']) : null;

    if (empty($index_name)) {
        $handler_instance->send_wp_error(new WP_Error('missing_index_name_pinecone', __('Pinecone index name is required.', 'gpt3-ai-content-generator'), ['status' => 400]));
        return;
    }
    if (empty($vectors_json)) {
        $handler_instance->send_wp_error(new WP_Error('missing_vectors_pinecone', __('Vectors data is required for Pinecone upsert.', 'gpt3-ai-content-generator'), ['status' => 400]));
        return;
    }
    $vectors = json_decode($vectors_json, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($vectors) || empty($vectors)) {
        $handler_instance->send_wp_error(new WP_Error('invalid_vectors_json_pinecone', __('Invalid or empty vectors JSON format.', 'gpt3-ai-content-generator'), ['status' => 400]));
        return;
    }

    $result = $vector_store_manager->upsert_vectors('Pinecone', $index_name, $vectors, $pinecone_config);

    $pinecone_vector_id = $vectors[0]['id'] ?? null;
    $source_type_for_log = $vectors[0]['metadata']['source'] ?? 'unknown';
    $wp_post_id_for_log = null;
    $wp_post_title_for_log = null;
    $content_for_log = null;

    if ($source_type_for_log === 'wordpress_post' && isset($vectors[0]['metadata']['post_id'])) {
        $wp_post_id_for_log = absint($vectors[0]['metadata']['post_id']);
        $wp_post_title_for_log = get_the_title($wp_post_id_for_log) ?: 'Post ' . $wp_post_id_for_log;
        $content_for_log = $original_text_content;
    } elseif (in_array($source_type_for_log, ['text_entry_global_form', 'file_upload_global_form', 'text_entry_pinecone_direct']) && $original_text_content !== null) {
        $content_for_log = $original_text_content;
        if ($source_type_for_log === 'file_upload_global_form' && isset($vectors[0]['metadata']['filename'])) {
            $wp_post_title_for_log = sanitize_file_name($vectors[0]['metadata']['filename']);
        }
    }


    if (is_wp_error($result)) {
        $handler_instance->_log_vector_data_source_entry([
            'vector_store_id' => $index_name, 'vector_store_name' => $index_name,
            'post_id' => $wp_post_id_for_log, 'post_title' => $wp_post_title_for_log,
            'status' => 'failed', 'message' => 'Upsert failed: ' . $result->get_error_message(),
            'embedding_provider' => $embedding_provider, 'embedding_model' => $embedding_model,
            'indexed_content' => $content_for_log,
            'file_id' => $pinecone_vector_id,
            'source_type_for_log' => $source_type_for_log
        ]);
        $handler_instance->send_wp_error($result);
    } else {
        $handler_instance->_log_vector_data_source_entry([
            'vector_store_id' => $index_name, 'vector_store_name' => $index_name,
            'post_id' => $wp_post_id_for_log, 'post_title' => $wp_post_title_for_log,
            'status' => 'indexed', 'message' => 'Vectors upserted. Count: ' . ($result['upserted_count'] ?? count($vectors)),
            'embedding_provider' => $embedding_provider, 'embedding_model' => $embedding_model,
            'indexed_content' => $content_for_log,
            'file_id' => $pinecone_vector_id,
            'source_type_for_log' => $source_type_for_log
        ]);
        wp_send_json_success(['message' => __('Vectors upserted to Pinecone successfully.', 'gpt3-ai-content-generator'), 'result' => $result]);
    }
}