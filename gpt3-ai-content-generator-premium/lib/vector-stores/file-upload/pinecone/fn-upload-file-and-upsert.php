<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/vector-stores/file-upload/pinecone/fn-upload-file-and-upsert.php
// Status: MODIFIED

namespace WPAICG\Lib\VectorStores\FileUpload\Pinecone;

use WP_Error;
use WPAICG\Vector\AIPKit_Vector_Store_Manager;
use WPAICG\Core\AIPKit_AI_Caller;
use WPAICG\Includes\AIPKit_Upload_Utils;
use WPAICG\Lib\Utils\AIPKit_File_Text_Extractor;
use WPAICG\Dashboard\Ajax\AIPKit_Vector_Store_Pinecone_Ajax_Handler;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Handles the logic for ajax_upload_file_and_upsert_to_pinecone.
 * This file is intended to be conditionally included by the AJAX handler if Pro.
 *
 * @param AIPKit_Vector_Store_Manager $vector_store_manager
 * @param AIPKit_AI_Caller $ai_caller
 * @param array $pinecone_config
 * @param AIPKit_Vector_Store_Pinecone_Ajax_Handler $handler_instance The AJAX handler instance for logging.
 * @return array|WP_Error An array including data for logging by the caller, or WP_Error.
 */
function _aipkit_pinecone_ajax_upload_file_and_upsert_logic(
    AIPKit_Vector_Store_Manager $vector_store_manager,
    AIPKit_AI_Caller $ai_caller,
    array $pinecone_config,
    AIPKit_Vector_Store_Pinecone_Ajax_Handler $handler_instance
): array|\WP_Error {

    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked by the calling handler method.
    $target_index_name = isset($_POST['index_name']) ? sanitize_text_field(wp_unslash($_POST['index_name'])) : '';
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked by the calling handler method.
    $embedding_provider_key = isset($_POST['embedding_provider']) ? sanitize_key($_POST['embedding_provider']) : '';
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked by the calling handler method.
    $embedding_model = isset($_POST['embedding_model']) ? sanitize_text_field(wp_unslash($_POST['embedding_model'])) : '';

    if (empty($target_index_name)) {
        return new WP_Error('missing_target_store_pinecone_upload_lib', __('Target Pinecone index is required.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }
    // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked by the calling handler method.
    if (!isset($_FILES['file_to_upload'])) {
        return new WP_Error('no_file_pinecone_upload_lib', __('No file provided for Pinecone upload.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }
    if (empty($embedding_provider_key) || empty($embedding_model)) {
        return new WP_Error('missing_embedding_config_pinecone_lib', __('Embedding provider and model are required.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }

    if (!class_exists(\WPAICG\Includes\AIPKit_Upload_Utils::class)) {
        return new WP_Error('upload_util_missing_lib', __('Upload utility is missing.', 'gpt3-ai-content-generator'), ['status' => 500]);
    }
    // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Nonce checked in handler; file data is validated by AIPKit_Upload_Utils::validate_upload_file().
    $file_data = $_FILES['file_to_upload'];
    $upload_limits = \WPAICG\Includes\AIPKit_Upload_Utils::get_effective_upload_limit_summary();

    // --- Validate supported MIME types (reuse utils) ---
    $allowed_mime_types_for_pinecone = \WPAICG\Includes\AIPKit_Upload_Utils::get_vector_upload_allowed_mime_types();
    $validation_error = \WPAICG\Includes\AIPKit_Upload_Utils::validate_upload_file(
        $file_data,
        $allowed_mime_types_for_pinecone, // Pass allowed types
        $upload_limits['limit_bytes']
    );
    if (is_wp_error($validation_error)) {
        // Attach context
        $validation_error->add_data([
            'where' => 'pinecone_upload:validation',
            'limit_bytes' => $upload_limits['limit_bytes'] ?? null,
        ]);
        return $validation_error;
    }
    // --- END MODIFICATION ---

    // --- Unified Text Extraction ---
    $extractor = new AIPKit_File_Text_Extractor();
    $extracted_text_or_error = $extractor->extract($file_data);
    if (is_wp_error($extracted_text_or_error)) {
        return new WP_Error('file_extraction_failed_pinecone_lib', 'File extraction failed: ' . $extracted_text_or_error->get_error_message(), [
            'status' => 500,
            'log_data' => [
                'vector_store_id' => $target_index_name, 'vector_store_name' => $target_index_name,
                'status' => 'content_error', 'message' => 'File extraction failed: ' . $extracted_text_or_error->get_error_message(),
                'embedding_provider' => $embedding_provider_key, 'embedding_model' => $embedding_model,
                'indexed_content' => sanitize_file_name($file_data['name']),
                'post_title' => sanitize_file_name($file_data['name']),
                'source_type_for_log' => 'file_upload_global_form'
            ]
        ]);
    }
    $file_content_for_embedding = $extracted_text_or_error;
    // --- End Unified Text Extraction ---
    if ($file_content_for_embedding === false || empty(trim($file_content_for_embedding))) {
        return new WP_Error('file_read_error_pinecone_lib', __('Could not read file content or file is empty.', 'gpt3-ai-content-generator'), ['status' => 500, 'log_data' => [
            'vector_store_id' => $target_index_name, 'vector_store_name' => $target_index_name,
            'status' => 'content_error', 'message' => 'Could not read file content or file is empty for Pinecone.',
            'embedding_provider' => $embedding_provider_key, 'embedding_model' => $embedding_model,
            'indexed_content' => sanitize_file_name($file_data['name']),
            'post_title' => sanitize_file_name($file_data['name']),
            'source_type_for_log' => 'file_upload_global_form'
        ]]);
    }
    // --- NEW: Token-aware chunking to avoid provider context limits ---
    $provider_map = ['openai' => 'OpenAI', 'google' => 'Google', 'azure' => 'Azure'];
    $embedding_provider_norm = $provider_map[$embedding_provider_key] ?? 'OpenAI';
    $embedding_options = ['model' => $embedding_model];

    // Allow integrators to tune chunking via filter
    $saved_general = get_option('aipkit_training_general_settings', []);
    $default_chunk_cfg = [
        'avg_chars_per_token' => isset($saved_general['chunk_avg_chars_per_token']) ? (int)$saved_general['chunk_avg_chars_per_token'] : 4,     // heuristic
        'max_tokens_per_chunk' => isset($saved_general['chunk_max_tokens_per_chunk']) ? (int)$saved_general['chunk_max_tokens_per_chunk'] : 3000, // safe under 8k context
        'overlap_tokens' => isset($saved_general['chunk_overlap_tokens']) ? (int)$saved_general['chunk_overlap_tokens'] : 150,        // ~10% overlap
    ];
    $chunk_cfg = apply_filters(
        'aipkit_vector_chunking_config',
        $default_chunk_cfg,
        $embedding_model,
        $embedding_provider_key,
        $target_index_name,
        'pinecone'
    );
    $avg_chars_per_token = isset($chunk_cfg['avg_chars_per_token']) ? (int)$chunk_cfg['avg_chars_per_token'] : $default_chunk_cfg['avg_chars_per_token'];
    $max_tokens_per_chunk = isset($chunk_cfg['max_tokens_per_chunk']) ? (int)$chunk_cfg['max_tokens_per_chunk'] : $default_chunk_cfg['max_tokens_per_chunk'];
    $overlap_tokens = isset($chunk_cfg['overlap_tokens']) ? (int)$chunk_cfg['overlap_tokens'] : $default_chunk_cfg['overlap_tokens'];
    $chunk_chars = $max_tokens_per_chunk * $avg_chars_per_token;
    $overlap_chars = $overlap_tokens * $avg_chars_per_token;
    $content_len = function_exists('mb_strlen') ? mb_strlen($file_content_for_embedding) : strlen($file_content_for_embedding);

    $chunks = [];
    if ($content_len > $chunk_chars) {
        $step = $chunk_chars - $overlap_chars;
        for ($start = 0, $idx = 0; $start < $content_len; $start += $step, $idx++) {
            $chunk = function_exists('mb_substr')
                ? mb_substr($file_content_for_embedding, $start, $chunk_chars)
                : substr($file_content_for_embedding, $start, $chunk_chars);
            if ($chunk === '' || $chunk === false) {
                break;
            }
            $chunks[] = ['text' => $chunk, 'start' => $start, 'end' => min($start + $chunk_chars, $content_len), 'index' => $idx];
            // Safety cap to avoid extreme chunk counts
            if ($idx > 500) { break; }
        }
    } else {
        $chunks[] = ['text' => $file_content_for_embedding, 'start' => 0, 'end' => $content_len, 'index' => 0];
    }

    $vectors_to_upsert = [];
    $chunk_records = [];
    // Prepare a preview snippet from the first chunk to display in admin logs
    $first_chunk_text = '';
    foreach ($chunks as $c) {
        if ($first_chunk_text === '' && !empty($c['text'])) {
            $first_chunk_text = (string) $c['text'];
        }
        $embedding_result = $ai_caller->generate_embeddings($embedding_provider_norm, $c['text'], $embedding_options);
        if (is_wp_error($embedding_result) || empty($embedding_result['embeddings'][0])) {
            $error_msg = is_wp_error($embedding_result) ? $embedding_result->get_error_message() : 'No embeddings returned.';
            return new WP_Error('embedding_failed_pinecone_file_lib', __('Failed to generate vector for file content.', 'gpt3-ai-content-generator'), ['status' => 500, 'log_data' => [
                'vector_store_id' => $target_index_name, 'vector_store_name' => $target_index_name,
                'status' => 'failed', 'message' => 'Embedding failed: ' . $error_msg,
                'embedding_provider' => $embedding_provider_key, 'embedding_model' => $embedding_model,
                'indexed_content' => 'chunk_' . $c['index'] . ' of ' . count($chunks) . ' for ' . sanitize_file_name($file_data['name']),
                'post_title' => sanitize_file_name($file_data['name']),
                'source_type_for_log' => 'file_upload_global_form'
            ], 'details' => [ 'stage' => 'embed', 'provider' => $embedding_provider_norm, 'model' => $embedding_model, 'chunk_index' => $c['index'] ]]);
        }

        $vector_values = $embedding_result['embeddings'][0];
        $pinecone_vector_id = 'pinecone_file_' . hash('md5', $file_data['name'] . $c['index']) . '_' . time();
        $metadata = [
            'source' => 'file_upload_global_form',
            'filename' => sanitize_file_name($file_data['name']),
            'uploaded_at' => current_time('mysql', 1),
            'vector_id' => $pinecone_vector_id,
            'chunk_index' => $c['index'],
            'total_chunks' => count($chunks),
            'char_start' => $c['start'],
            'char_end' => $c['end'],
        ];
        $vectors_to_upsert[] = ['id' => $pinecone_vector_id, 'values' => $vector_values, 'metadata' => $metadata];
        // Collect mapping for per-chunk logging
        $chunk_records[] = [
            'id' => $pinecone_vector_id,
            'text' => (string) $c['text'],
            'index' => (int) $c['index'],
            'total' => count($chunks),
        ];
    }

    $upsert_result = $vector_store_manager->upsert_vectors('Pinecone', $target_index_name, $vectors_to_upsert, $pinecone_config);

    if (is_wp_error($upsert_result)) {
        return new WP_Error('upsert_failed_pinecone_lib', 'Upsert to Pinecone failed: ' . $upsert_result->get_error_message(), ['status' => 500, 'log_data' => [
            'vector_store_id' => $target_index_name, 'vector_store_name' => $target_index_name,
            'status' => 'failed', 'message' => 'Upsert to Pinecone failed: ' . $upsert_result->get_error_message(),
            'embedding_provider' => $embedding_provider_key, 'embedding_model' => $embedding_model,
            'indexed_content' => 'chunked_upload:' . sanitize_file_name($file_data['name']),
            'post_title' => sanitize_file_name($file_data['name']),
            'source_type_for_log' => 'file_upload_global_form'
        ], 'details' => [ 'stage' => 'upsert', 'chunk_count' => count($vectors_to_upsert) ]]);
    }

    // Per-chunk logging into local DB so each chunk ID maps to its content in indexed_content
    if ($handler_instance && method_exists($handler_instance, '_log_vector_data_source_entry')) {
        // Generate a batch id to group chunks from the same file upload
        $batch_id = 'pinecone_file_' . md5(sanitize_file_name($file_data['name']) . '|' . (string) wp_rand()) . '_' . time();
        foreach ($chunk_records as $rec) {
            $safe_indexed_content = function_exists('wp_strip_all_tags') ? wp_strip_all_tags((string) $rec['text']) : (string) $rec['text'];
            $handler_instance->_log_vector_data_source_entry([
                'vector_store_id' => $target_index_name,
                'vector_store_name' => $target_index_name,
                'status' => 'indexed',
                'message' => sprintf('File chunk embedded (chunk %d/%d) for %s', $rec['index'] + 1, $rec['total'], sanitize_file_name($file_data['name'])),
                'embedding_provider' => $embedding_provider_key,
                'embedding_model' => $embedding_model,
                'indexed_content' => $safe_indexed_content,
                'file_id' => $rec['id'],
                'batch_id' => $batch_id,
                'post_title' => sanitize_file_name($file_data['name']),
                'source_type_for_log' => 'file_upload_global_form',
            ]);
        }
    }

    return [
        'message' => __('File content embedded and upserted to Pinecone successfully.', 'gpt3-ai-content-generator'),
        'result' => $upsert_result,
    ];
}
