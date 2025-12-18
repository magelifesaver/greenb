<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/core/ajax-processor/frontend-chat/class-chat-ai-request-runner.php
// Status: MODIFIED

namespace WPAICG\Chat\Core\AjaxProcessor\FrontendChat;

use WPAICG\Chat\Core\AIService;
use WPAICG\Core\AIPKit_Instruction_Manager; // For building final instructions
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ChatAIRequestRunner
{
    private $ai_service;

    public function __construct(AIService $ai_service)
    {
        $this->ai_service = $ai_service;
    }

    /**
     * Makes the AI call using AIService.
     * MODIFIED: Added parameters for Pinecone & Qdrant context.
     *
     * @param string $user_message_text_for_ai
     * @param array $bot_settings
     * @param array $history_for_ai
     * @param string $system_instruction_for_ai
     * @param int $post_id
     * @param string|null $frontend_previous_openai_response_id
     * @param bool $frontend_openai_web_search_active
     * @param bool $frontend_google_search_grounding_active
     * @param array|null $image_inputs_for_service
     * @param string|null $frontend_active_openai_vs_id_from_context Optional active OpenAI Vector Store ID from frontend.
     * @param string|null $frontend_active_pinecone_index_name Optional active Pinecone index name.
     * @param string|null $frontend_active_pinecone_namespace Optional active Pinecone namespace.
     * @param string|null $frontend_active_qdrant_collection_name Optional active Qdrant collection name.
     * @param string|null $frontend_active_qdrant_file_upload_context_id Optional active Qdrant file context ID.
     * @return array|WP_Error AI service result or WP_Error.
     */
    public function run_ai_request(
        string $user_message_text_for_ai,
        array $bot_settings,
        array $history_for_ai,
        string $system_instruction_for_ai,
        int $post_id,
        ?string $frontend_previous_openai_response_id,
        bool $frontend_openai_web_search_active,
        bool $frontend_google_search_grounding_active,
        ?array $image_inputs_for_service,
        ?string $frontend_active_openai_vs_id_from_context = null,
        ?string $frontend_active_pinecone_index_name = null,
        ?string $frontend_active_pinecone_namespace = null,
        ?string $frontend_active_qdrant_collection_name = null,
        ?string $frontend_active_qdrant_file_upload_context_id = null
    ): array|WP_Error {

        $result = $this->ai_service->generate_response(
            $user_message_text_for_ai,
            array_merge($bot_settings, ['bot_id' => $bot_settings['bot_id']]), // ensure bot_id is present for generate_response
            $history_for_ai,
            $post_id,
            $frontend_previous_openai_response_id,
            $frontend_openai_web_search_active,
            $frontend_google_search_grounding_active,
            $image_inputs_for_service,
            $frontend_active_openai_vs_id_from_context,
            $frontend_active_pinecone_index_name,
            $frontend_active_pinecone_namespace,
            $frontend_active_qdrant_collection_name,
            $frontend_active_qdrant_file_upload_context_id
        );

        return $result;
    }
}
