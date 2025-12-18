<?php

// File: classes/chat/core/ajax-processor/frontend-chat/class-chat-context-builder.php
// Status: MODIFIED

namespace WPAICG\Chat\Core\AjaxProcessor\FrontendChat;

use WPAICG\Chat\Storage\BotStorage;
use WPAICG\Chat\Core\AIService; // For determine_provider_model
use WPAICG\AIPKit\Addons\AIPKit_IP_Anonymization;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ChatContextBuilder
{
    private $bot_storage;
    private $ai_service_for_helper; // For determine_provider_model

    public function __construct(BotStorage $bot_storage)
    {
        $this->bot_storage = $bot_storage;
        // AIService is needed for determine_provider_model
        if (!class_exists(AIService::class)) {
            $ai_service_path = WPAICG_PLUGIN_DIR . 'classes/chat/core/ai_service.php';
            if (file_exists($ai_service_path)) {
                require_once $ai_service_path;
            }
        }
        if (class_exists(AIService::class)) {
            $this->ai_service_for_helper = new AIService();
        } else {
            $this->ai_service_for_helper = null;
        }
    }

    /**
     * Builds the initial context for processing a chat message.
     * MODIFIED: Includes active_pinecone_index_name and active_pinecone_namespace in the context.
     * MODIFIED: Includes active_qdrant_collection_name and active_qdrant_file_upload_context_id.
     *
     * @param array $validated_data Data from ChatMessageValidator.
     * @param string|null $client_ip The client's IP.
     * @param int $post_id The current post ID.
     * @param string|null $frontend_active_openai_vs_id Optional active OpenAI Vector Store ID from frontend.
     * @return array Context data.
     */
    public function build_context(array $validated_data, ?string $client_ip, int $post_id, ?string $frontend_active_openai_vs_id = null): array
    {
        $bot_id = $validated_data['bot_id'];
        $user_id = $validated_data['user_id'];
        $user_message_text = $validated_data['user_message_text'];
        $session_id = $validated_data['session_id'];
        $conversation_uuid = $validated_data['conversation_uuid'];
        $active_pinecone_index_name = $validated_data['active_pinecone_index_name'] ?? null;
        $active_pinecone_namespace = $validated_data['active_pinecone_namespace'] ?? null;
        // --- ADDED: Get Qdrant context from validated data ---
        $active_qdrant_collection_name = $validated_data['active_qdrant_collection_name'] ?? null;
        $active_qdrant_file_upload_context_id = $validated_data['active_qdrant_file_upload_context_id'] ?? null;
        // --- END ADDED ---


        $bot_settings = $this->bot_storage->get_chatbot_settings($bot_id);
        $is_guest     = ($user_id === 0);
        $user_wp_role = !$is_guest ? implode(', ', wp_get_current_user()->roles) : null;

        $provider_model_info = [];
        if (function_exists('\WPAICG\Chat\Core\AIService\determine_provider_model')) {
            $provider_model_info = \WPAICG\Chat\Core\AIService\determine_provider_model($this->ai_service_for_helper, $bot_settings);
        } else {
            $provider_model_info = ['provider' => $bot_settings['provider'] ?? null, 'model' => $bot_settings['model'] ?? null];
        }

        $current_provider = $provider_model_info['provider'];
        $current_model_id = $provider_model_info['model'];

        $base_log_data = [
            'bot_id' => $bot_id, 'user_id' => $user_id ?: null, 'session_id' => $session_id,
            'conversation_uuid' => $conversation_uuid, 'module' => 'chat', 'is_guest' => $is_guest,
            'role' => $user_wp_role,
            'ip_address' => class_exists(AIPKit_IP_Anonymization::class) ? AIPKit_IP_Anonymization::maybe_anonymize($client_ip) : $client_ip,
            'form_id' => null, // Not applicable for chat context
            'user_message_id_from_client' => $validated_data['client_user_message_id'] ?? null,
        ];

        return [
            'bot_id'             => $bot_id,
            'user_id'            => $user_id,
            'session_id'         => $session_id,
            'conversation_uuid'  => $conversation_uuid,
            'user_message_text'  => $user_message_text,
            'bot_settings'       => $bot_settings,
            'client_ip'          => $client_ip,
            'post_id'            => $post_id,
            'is_guest'           => $is_guest,
            'user_wp_role'       => $user_wp_role,
            'system_instruction' => $bot_settings['instructions'] ?? '',
            'current_provider'   => $current_provider,
            'current_model_id'   => $current_model_id,
            'base_log_data'      => $base_log_data,
            'frontend_active_openai_vs_id' => $frontend_active_openai_vs_id,
            'active_pinecone_index_name' => $active_pinecone_index_name,
            'active_pinecone_namespace'  => $active_pinecone_namespace,
            // --- ADDED: Include Qdrant context in the built context ---
            'active_qdrant_collection_name' => $active_qdrant_collection_name,
            'active_qdrant_file_upload_context_id' => $active_qdrant_file_upload_context_id,
            // --- END ADDED ---
        ];
    }
}
