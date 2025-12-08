<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/dashboard/ajax/qdrant/handler-collections/ajax-upsert-to-collection.php
// Status: MODIFIED

namespace WPAICG\Dashboard\Ajax\Qdrant\HandlerCollections;

use WP_Error;
use WPAICG\Dashboard\Ajax\AIPKit_Vector_Store_Qdrant_Ajax_Handler;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the logic for upserting points to a Qdrant collection.
 * Called by AIPKit_Vector_Store_Qdrant_Ajax_Handler::ajax_upsert_to_qdrant_collection().
 *
 * @param AIPKit_Vector_Store_Qdrant_Ajax_Handler $handler_instance
 * @return void
 */
function _aipkit_qdrant_ajax_upsert_to_collection_logic(AIPKit_Vector_Store_Qdrant_Ajax_Handler $handler_instance): void
{
    $vector_store_manager = $handler_instance->get_vector_store_manager();

    if (!$vector_store_manager) {
        $handler_instance->send_wp_error(new WP_Error('manager_not_ready_upsert_qdrant', __('Vector Store Manager not available for Qdrant upsert.', 'gpt3-ai-content-generator'), ['status' => 500]));
        return;
    }

    $qdrant_config = $handler_instance->_get_qdrant_config();
    if (is_wp_error($qdrant_config)) {
        $handler_instance->send_wp_error($qdrant_config);
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in the calling handler method.
    $post_data = wp_unslash($_POST);
    $collection_name = isset($post_data['collection_name']) ? sanitize_text_field($post_data['collection_name']) : '';
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON string, decoded and validated below.
    $vectors_json = isset($post_data['vectors']) ? $post_data['vectors'] : '';
    $embedding_provider = isset($post_data['embedding_provider']) ? sanitize_key($post_data['embedding_provider']) : null;
    $embedding_model = isset($post_data['embedding_model']) ? sanitize_text_field($post_data['embedding_model']) : null;
    $original_text_content = isset($post_data['original_text_content']) ? wp_kses_post($post_data['original_text_content']) : null;

    if (empty($collection_name)) {
        $handler_instance->send_wp_error(new WP_Error('missing_collection_name_qdrant_upsert', __('Qdrant collection name is required.', 'gpt3-ai-content-generator'), ['status' => 400]));
        return;
    }
    if (empty($vectors_json)) {
        $handler_instance->send_wp_error(new WP_Error('missing_vectors_qdrant_upsert', __('Points data is required for Qdrant upsert.', 'gpt3-ai-content-generator'), ['status' => 400]));
        return;
    }

    $points = json_decode($vectors_json, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($points) || empty($points)) {
        $handler_instance->send_wp_error(new WP_Error('invalid_points_json_qdrant_upsert', __('Invalid or empty points JSON format for Qdrant.', 'gpt3-ai-content-generator'), ['status' => 400]));
        return;
    }

    $result = $vector_store_manager->upsert_vectors('Qdrant', $collection_name, ['points' => $points], $qdrant_config);

    $qdrant_point_id = $points[0]['id'] ?? null;
    // Prioritize 'payload' for source, then 'metadata' (as JS sends 'metadata')
    $source_type_for_log = $points[0]['payload']['source'] ?? ($points[0]['metadata']['source'] ?? 'unknown');
    $wp_post_id_for_log = null;
    $wp_post_title_for_log = null;
    $content_for_log = null;

    if ($source_type_for_log === 'wordpress_post') {
        // Check 'payload' first, then 'metadata' for 'post_id'
        if (isset($points[0]['payload']['post_id'])) {
            $wp_post_id_for_log = absint($points[0]['payload']['post_id']);
        } elseif (isset($points[0]['metadata']['post_id'])) {
            $wp_post_id_for_log = absint($points[0]['metadata']['post_id']);
        }

        if ($wp_post_id_for_log) {
            $wp_post_title_for_log = get_the_title($wp_post_id_for_log) ?: 'Post ' . $wp_post_id_for_log;
        }
        $content_for_log = $original_text_content; // For WP posts, the full content was passed as original_text_content
    } elseif (in_array($source_type_for_log, ['text_entry_global_form', 'file_upload_global_form', 'text_entry_qdrant_direct']) && $original_text_content !== null) {
        $content_for_log = $original_text_content;
        if ($source_type_for_log === 'file_upload_global_form' && isset($points[0]['metadata']['filename'])) { // JS sends filename in metadata
            $wp_post_title_for_log = sanitize_file_name($points[0]['metadata']['filename']);
        } elseif ($source_type_for_log === 'file_upload_global_form' && isset($points[0]['payload']['filename'])) { // Fallback check
            $wp_post_title_for_log = sanitize_file_name($points[0]['payload']['filename']);
        }
    }


    if (is_wp_error($result)) {
        $handler_instance->_log_vector_data_source_entry([
            'vector_store_id' => $collection_name, 'vector_store_name' => $collection_name,
            'post_id' => $wp_post_id_for_log, 'post_title' => $wp_post_title_for_log,
            'status' => 'failed', 'message' => 'Qdrant upsert failed: ' . $result->get_error_message(),
            'embedding_provider' => $embedding_provider, 'embedding_model' => $embedding_model,
            'indexed_content' => $content_for_log,
            'file_id' => $qdrant_point_id,
            'source_type_for_log' => $source_type_for_log
        ]);
        $handler_instance->send_wp_error($result);
    } else {
        $handler_instance->_log_vector_data_source_entry([
            'vector_store_id' => $collection_name, 'vector_store_name' => $collection_name,
            'post_id' => $wp_post_id_for_log, 'post_title' => $wp_post_title_for_log,
            'status' => 'indexed', 'message' => 'Points upserted to Qdrant. Status: ' . ($result['status'] ?? 'unknown'),
            'embedding_provider' => $embedding_provider, 'embedding_model' => $embedding_model,
            'indexed_content' => $content_for_log,
            'file_id' => $qdrant_point_id,
            'source_type_for_log' => $source_type_for_log
        ]);
        wp_send_json_success(['message' => __('Points upserted to Qdrant successfully.', 'gpt3-ai-content-generator'), 'result' => $result]);
    }
}