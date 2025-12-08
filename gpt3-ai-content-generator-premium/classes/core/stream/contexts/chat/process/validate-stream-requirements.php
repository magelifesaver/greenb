<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/contexts/chat/process/validate-stream-requirements.php
// Status: NEW FILE

namespace WPAICG\Core\Stream\Contexts\Chat\Process;

use WPAICG\Chat\Admin\AdminSetup;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Validates the essential requirements for a chat stream request.
 *
 * @param int         $bot_id            The ID of the chatbot.
 * @param string      $conversation_uuid The UUID of the conversation.
 * @param int|null    $user_id           The ID of the logged-in user, or null for guests.
 * @param string|null $session_id        The session ID for guests.
 * @param string      $user_message_text The user's text message.
 * @param array|null  $image_inputs      Processed image input data.
 * @return true|WP_Error True if validation passes, WP_Error otherwise.
 */
function validate_stream_requirements_logic(
    int $bot_id,
    string $conversation_uuid,
    ?int $user_id,
    ?string $session_id,
    string $user_message_text,
    ?array $image_inputs
): bool|WP_Error {
    // Ensure AdminSetup is loaded for POST_TYPE constant
    if (!class_exists(AdminSetup::class)) {
        $admin_setup_path = WPAICG_PLUGIN_DIR . 'classes/chat/admin/chat_admin_setup.php';
        if (file_exists($admin_setup_path)) {
            require_once $admin_setup_path;
        } else {
            return new WP_Error(
                'dependency_missing_validator_logic',
                __('Chat system component (AdminSetup) missing.', 'gpt3-ai-content-generator'),
                ['status' => 500, 'failed_module' => 'chat_stream_context', 'failed_operation' => 'load_admin_setup']
            );
        }
    }

    if (empty($bot_id) || get_post_type($bot_id) !== AdminSetup::POST_TYPE || get_post_status($bot_id) !== 'publish') {
        return new WP_Error('invalid_bot_id_stream_req', __('Invalid chatbot specified.', 'gpt3-ai-content-generator'), ['status' => 400, 'failed_module' => 'chat_stream_context', 'failed_operation' => 'validate_bot_id']);
    }
    if (empty($conversation_uuid)) {
        return new WP_Error('missing_conversation_uuid_stream_req', __('Conversation ID is missing.', 'gpt3-ai-content-generator'), ['status' => 400, 'failed_module' => 'chat_stream_context', 'failed_operation' => 'validate_conv_uuid']);
    }
    if (!$user_id && empty($session_id)) {
        return new WP_Error('missing_session_id_stream_req', __('Session ID is missing for guest.', 'gpt3-ai-content-generator'), ['status' => 400, 'failed_module' => 'chat_stream_context', 'failed_operation' => 'validate_session_id']);
    }
    if (empty($user_message_text) && empty($image_inputs)) {
        return new WP_Error('empty_content_stream_req', __('Message or image cannot be empty.', 'gpt3-ai-content-generator'), ['status' => 400, 'failed_module' => 'chat_stream_context', 'failed_operation' => 'validate_content']);
    }
    return true;
}
