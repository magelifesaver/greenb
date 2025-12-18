<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/dashboard/ajax/openai/handler-files/ajax-add-files-to-vector-store-openai.php
// Status: MODIFIED

namespace WPAICG\Dashboard\Ajax\OpenAI\HandlerFiles;

use WPAICG\Dashboard\Ajax\AIPKit_OpenAI_Vector_Store_Files_Ajax_Handler;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the logic for adding files to an OpenAI Vector Store.
 * Called by AIPKit_OpenAI_Vector_Store_Files_Ajax_Handler::ajax_add_files_to_vector_store_openai().
 *
 * @param AIPKit_OpenAI_Vector_Store_Files_Ajax_Handler $handler_instance
 * @return void
 */
function do_ajax_add_files_to_vector_store_openai_logic(AIPKit_OpenAI_Vector_Store_Files_Ajax_Handler $handler_instance): void
{
    // Permission check already done by the handler calling this

    $vector_store_manager = $handler_instance->get_vector_store_manager();
    $vector_store_registry = $handler_instance->get_vector_store_registry();
    $wpdb = $handler_instance->get_wpdb();
    $data_source_table_name = $handler_instance->get_data_source_table_name();

    if (!$vector_store_manager || !$vector_store_registry) {
        $handler_instance->send_wp_error(new WP_Error('manager_not_ready', __('Vector Store Manager or Registry not available.', 'gpt3-ai-content-generator'), ['status' => 500]));
        return;
    }

    $openai_config = $handler_instance->_get_openai_config();
    if (is_wp_error($openai_config)) {
        $handler_instance->send_wp_error($openai_config);
        return;
    }

    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in the calling handler method.
    $post_data = wp_unslash($_POST);
    $store_id = isset($post_data['store_id']) ? sanitize_text_field($post_data['store_id']) : '';
    // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized in a loop below.
    $file_ids_raw = isset($post_data['file_ids']) ? $post_data['file_ids'] : '';
    if (empty($store_id)) {
        $handler_instance->send_wp_error(new WP_Error('missing_store_id', __('Vector Store ID is required.', 'gpt3-ai-content-generator'), ['status' => 400]));
        return;
    }
    if (empty($file_ids_raw)) {
        $handler_instance->send_wp_error(new WP_Error('missing_file_ids', __('File IDs are required.', 'gpt3-ai-content-generator'), ['status' => 400]));
        return;
    }
    $file_ids_array = is_array($file_ids_raw) ? array_map('sanitize_text_field', $file_ids_raw) : array_map('sanitize_text_field', array_map('trim', explode(',', (string) $file_ids_raw)));
    $file_ids_array = array_filter($file_ids_array, function ($id) {
        return preg_match('/^file-[a-zA-Z0-9]+$/', $id);
    });
    if (empty($file_ids_array)) {
        $handler_instance->send_wp_error(new WP_Error('invalid_file_ids', __('No valid File IDs provided.', 'gpt3-ai-content-generator'), ['status' => 400]));
        return;
    }

    $batch_result = $vector_store_manager->upsert_vectors('OpenAI', $store_id, ['file_ids' => $file_ids_array], $openai_config);
    if (is_wp_error($batch_result)) {
        $handler_instance->send_wp_error($batch_result);
        return;
    }

    $store_details = $vector_store_manager->describe_single_index('OpenAI', $store_id, $openai_config);
    $store_name_for_log = !is_wp_error($store_details) ? ($store_details['name'] ?? $store_id) : $store_id;

    \WPAICG\Dashboard\Ajax\OpenAI\_aipkit_openai_vs_files_log_vector_data_source_entry($wpdb, $data_source_table_name, [
        'vector_store_id' => $store_id,
        'vector_store_name' => $store_name_for_log,
        'status' => 'indexed',
        'message' => 'Files submitted to store for indexing. Batch ID: ' . ($batch_result['id'] ?? 'N/A'),
        'file_id' => implode(', ', $file_ids_array),
        'batch_id' => $batch_result['id'] ?? null,
        'source_type_for_log' => 'file_batch_addition'
    ]);

    if (!is_wp_error($store_details) && is_array($store_details) && isset($store_details['id'])) {
        $vector_store_registry->add_registered_store('OpenAI', $store_details);
    }

    wp_send_json_success(['message' => __('Files added to vector store batch successfully.', 'gpt3-ai-content-generator'), 'batch' => $batch_result]);
}