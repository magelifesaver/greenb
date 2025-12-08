<?php

// File: classes/chat/core/ajax-processor/frontend-chat/class-chat-message-validator.php
// Status: MODIFIED

namespace WPAICG\Chat\Core\AjaxProcessor\FrontendChat;

use WPAICG\Chat\Admin\AdminSetup;
use WPAICG\Core\TokenManager\AIPKit_Token_Manager;
use WPAICG\Core\AIPKit_Content_Moderator;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ChatMessageValidator
{
    private $token_manager;

    public function __construct(AIPKit_Token_Manager $token_manager)
    {
        $this->token_manager = $token_manager;
    }

    /**
     * Validates the initial chat message request.
     * MODIFIED: Extracts and validates active_pinecone_index_name and active_pinecone_namespace.
     * MODIFIED: Extracts and validates active_qdrant_collection_name and active_qdrant_file_upload_context_id.
     * MODIFIED: Changed sanitization of user_message_text to wp_strip_all_tags(wp_unslash(...)) instead of sanitize_textarea_field.
     *
     * @param array $post_data The $_POST data.
     * @param string|null $client_ip The client's IP address.
     * @param array $bot_settings Bot settings.
     * @return array|WP_Error Validated data or WP_Error on failure.
     */
    public function validate(array $post_data, ?string $client_ip, array $bot_settings): array|WP_Error
    {
        // 1. Nonce Check (already done by BaseAjaxHandler in the calling method)

        // 2. Basic Parameter Validation
        $bot_id            = isset($post_data['bot_id']) ? absint($post_data['bot_id']) : 0;
        // --- MODIFIED SANITIZATION for user_message_text ---
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is checked in BaseAjaxHandler
        $raw_user_message = isset($_POST['message']) ? wp_unslash($_POST['message']) : '';
        // Custom sanitization for code content - preserve code structure while ensuring security
        $user_message_text = wp_check_invalid_utf8($raw_user_message);
        $user_message_text = str_replace(chr(0), '', $user_message_text); // Remove null bytes
        $user_message_text = trim($user_message_text);
        // --- END MODIFICATION ---
        $session_id        = isset($post_data['session_id']) ? sanitize_text_field(wp_unslash($post_data['session_id'])) : '';
        $conversation_uuid = isset($post_data['conversation_uuid']) ? sanitize_key($post_data['conversation_uuid']) : '';
        $image_inputs_json = isset($post_data['image_inputs']) ? wp_unslash($post_data['image_inputs']) : null;
        $has_image_input   = !empty($image_inputs_json);
        $active_pinecone_index_name = isset($post_data['active_pinecone_index_name']) ? sanitize_text_field(wp_unslash($post_data['active_pinecone_index_name'])) : null;
        $active_pinecone_namespace = isset($post_data['active_pinecone_namespace']) ? sanitize_text_field(wp_unslash($post_data['active_pinecone_namespace'])) : null;
        $active_qdrant_collection_name = isset($post_data['active_qdrant_collection_name']) ? sanitize_text_field(wp_unslash($post_data['active_qdrant_collection_name'])) : null;
        $active_qdrant_file_upload_context_id = isset($post_data['active_qdrant_file_upload_context_id']) ? sanitize_text_field(wp_unslash($post_data['active_qdrant_file_upload_context_id'])) : null;


        if (empty($bot_id)) {
            return new WP_Error('missing_bot_id', __('Bot ID is required.', 'gpt3-ai-content-generator'), ['status' => 400]);
        }
        if (empty($conversation_uuid)) {
            return new WP_Error('missing_conv_uuid', __('Conversation ID is required.', 'gpt3-ai-content-generator'), ['status' => 400]);
        }
        $user_id = get_current_user_id();
        if (!$user_id && empty($session_id)) {
            return new WP_Error('missing_session_id', __('Session ID is missing for guest.', 'gpt3-ai-content-generator'), ['status' => 400]);
        }
        if (empty($user_message_text) && !$has_image_input) {
            return new WP_Error('empty_content', __('Message or image cannot be empty.', 'gpt3-ai-content-generator'), ['status' => 400]);
        }

        if (($active_pinecone_index_name && !$active_pinecone_namespace) || (!$active_pinecone_index_name && $active_pinecone_namespace)) {
            return new WP_Error('incomplete_pinecone_context', __('Both Pinecone index name and namespace must be provided if one is set.', 'gpt3-ai-content-generator'), ['status' => 400]);
        }
        if (($active_qdrant_collection_name && !$active_qdrant_file_upload_context_id) || (!$active_qdrant_collection_name && $active_qdrant_file_upload_context_id)) {
            return new WP_Error('incomplete_qdrant_context', __('Both Qdrant collection name and file context ID must be provided if one is set.', 'gpt3-ai-content-generator'), ['status' => 400]);
        }


        // 3. Bot Post Validation
        if (!class_exists(AdminSetup::class)) {
            return new WP_Error('dependency_missing_validator', 'Admin Setup component missing for bot validation.');
        }
        if (get_post_type($bot_id) !== AdminSetup::POST_TYPE || get_post_status($bot_id) !== 'publish') {
            return new WP_Error('invalid_chatbot_validator', __('Invalid chatbot specified.', 'gpt3-ai-content-generator'), ['status' => 400]);
        }

        // 4. Token Limit Check
        $token_check_result = $this->token_manager->check_and_reset_tokens($user_id ?: null, $session_id, $bot_id);
        if (is_wp_error($token_check_result)) {
            return new WP_Error($token_check_result->get_error_code(), $token_check_result->get_error_message(), ['status' => 429]);
        }

        // 5. Content Moderation (Text only for now)
        if (!empty($user_message_text) && class_exists(AIPKit_Content_Moderator::class)) {
            $moderation_context = ['client_ip' => $client_ip, 'bot_settings' => $bot_settings];
            $moderation_check = AIPKit_Content_Moderator::check_content($user_message_text, $moderation_context);
            if (is_wp_error($moderation_check)) {
                return new WP_Error($moderation_check->get_error_code(), $moderation_check->get_error_message(), ['status' => $moderation_check->get_error_data()['status'] ?? 400]);
            }
        }

        return [
            'bot_id'            => $bot_id,
            'user_id'           => $user_id,
            'user_message_text' => $user_message_text,
            'session_id'        => $session_id,
            'conversation_uuid' => $conversation_uuid,
            'image_inputs_json' => $image_inputs_json,
            'client_user_message_id' => isset($post_data['user_client_message_id']) ? sanitize_key($post_data['user_client_message_id']) : null,
            'active_pinecone_index_name' => $active_pinecone_index_name,
            'active_pinecone_namespace'  => $active_pinecone_namespace,
            'active_qdrant_collection_name' => $active_qdrant_collection_name,
            'active_qdrant_file_upload_context_id' => $active_qdrant_file_upload_context_id,
        ];
    }
}
