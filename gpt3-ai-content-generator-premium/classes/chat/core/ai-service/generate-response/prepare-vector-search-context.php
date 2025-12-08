<?php

// File: classes/chat/core/ai-service/generate-response/prepare-vector-search-context.php
// Status: MODIFIED

namespace WPAICG\Chat\Core\AIService\GenerateResponse;

// No direct use statements needed here if dependencies are passed.
// The function `build_vector_search_context_logic` is in a different namespace
// and will be called with its FQN.

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Prepares the vector search context for non-streaming chat interactions.
 *
 * This function serves as a wrapper for the centralized vector context building logic,
 * specifically for use in non-streaming chat scenarios.
 *
 * @param \WPAICG\Core\AIPKit_AI_Caller|null $ai_caller Instance of AI Caller, or null.
 * @param \WPAICG\Vector\AIPKit_Vector_Store_Manager|null $vector_store_manager Instance of Vector Store Manager, or null.
 * @param string $user_message The user's current message.
 * @param array  $bot_settings The settings of the current bot.
 * @param string $main_provider The main AI provider being used for the chat.
 * @param string|null $frontend_active_openai_vs_id Optional active OpenAI Vector Store ID from frontend.
 * @param string|null $frontend_active_pinecone_index_name Optional active Pinecone index name from frontend.
 * @param string|null $frontend_active_pinecone_namespace Optional active Pinecone namespace from frontend.
 * @param string|null $frontend_active_qdrant_collection_name Optional active Qdrant collection name.
 * @param string|null $frontend_active_qdrant_file_upload_context_id Optional active Qdrant file context ID.
 * @param array|null &$vector_search_scores_output Optional reference to capture vector search scores for logging.
 * @return string The formatted context string from vector searches, or an empty string.
 */
function prepare_vector_search_context_logic(
    ?\WPAICG\Core\AIPKit_AI_Caller $ai_caller,
    ?\WPAICG\Vector\AIPKit_Vector_Store_Manager $vector_store_manager,
    string $user_message,
    array $bot_settings,
    string $main_provider,
    ?string $frontend_active_openai_vs_id = null,
    ?string $frontend_active_pinecone_index_name = null,
    ?string $frontend_active_pinecone_namespace = null,
    ?string $frontend_active_qdrant_collection_name = null,
    ?string $frontend_active_qdrant_file_upload_context_id = null,
    ?array &$vector_search_scores_output = null
): string {
    if (!$ai_caller || !$vector_store_manager) {
        return "";
    }

    // Call the centralized logic function
    return \WPAICG\Core\Stream\Vector\build_vector_search_context_logic(
        $ai_caller,
        $vector_store_manager,
        $user_message,
        $bot_settings,
        $main_provider,
        $frontend_active_openai_vs_id,
        $frontend_active_pinecone_index_name,
        $frontend_active_pinecone_namespace,
        $frontend_active_qdrant_collection_name,
        $frontend_active_qdrant_file_upload_context_id,
        $vector_search_scores_output
    );
}
