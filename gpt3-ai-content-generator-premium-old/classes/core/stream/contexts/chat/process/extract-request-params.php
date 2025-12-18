<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/contexts/chat/process/extract-request-params.php
// Status: MODIFIED

namespace WPAICG\Core\Stream\Contexts\Chat\Process;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Extracts and sanitizes request parameters for chat stream processing.
 *
 * @param array $cached_data Data retrieved from the SSE cache.
 * @param array $get_params Original $_GET parameters from the SSE request.
 * @return array An associative array of extracted and sanitized parameters.
 */
function extract_request_params_logic(array $cached_data, array $get_params): array
{
    return [
        'user_id'            => get_current_user_id(),
        'bot_id'             => isset($get_params['bot_id']) ? absint($get_params['bot_id']) : 0,
        'session_id'         => isset($get_params['session_id']) ? sanitize_text_field(wp_unslash($get_params['session_id'])) : '',
        'conversation_uuid'  => isset($get_params['conversation_uuid']) ? sanitize_key($get_params['conversation_uuid']) : '',
        'post_id'            => isset($get_params['post_id']) ? absint($get_params['post_id']) : 0,
        'user_message_text'  => $cached_data['user_message'] ?? '',
        'image_inputs'       => $cached_data['image_inputs'] ?? null,
        'client_ip'          => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : null,
        'frontend_previous_openai_response_id' => isset($get_params['previous_openai_response_id']) ? sanitize_text_field($get_params['previous_openai_response_id']) : null,
        'frontend_openai_web_search_active' => (isset($get_params['frontend_web_search_active']) && $get_params['frontend_web_search_active'] === 'true'),
        'frontend_google_search_grounding_active' => (isset($get_params['frontend_google_search_grounding_active']) && $get_params['frontend_google_search_grounding_active'] === 'true'),
        'client_user_message_id' => $cached_data['client_user_message_id'] ?? null,
        'active_openai_vs_id' => $cached_data['active_openai_vs_id'] ?? ($get_params['active_openai_vs_id'] ?? null),
        'active_pinecone_index_name' => $cached_data['active_pinecone_index_name'] ?? ($get_params['active_pinecone_index_name'] ?? null),
        'active_pinecone_namespace' => $cached_data['active_pinecone_namespace'] ?? ($get_params['active_pinecone_namespace'] ?? null),
        'active_qdrant_collection_name' => $cached_data['active_qdrant_collection_name'] ?? ($get_params['active_qdrant_collection_name'] ?? null),
        'active_qdrant_file_upload_context_id' => $cached_data['active_qdrant_file_upload_context_id'] ?? ($get_params['active_qdrant_file_upload_context_id'] ?? null),
    ];
}
