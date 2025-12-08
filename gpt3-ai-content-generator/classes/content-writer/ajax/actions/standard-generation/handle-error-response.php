<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/standard-generation/handle-error-response.php
// Status: NEW FILE

namespace WPAICG\ContentWriter\Ajax\Actions\StandardGeneration;

use WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Standard_Generation_Action;
use WPAICG\AIPKit\Addons\AIPKit_IP_Anonymization;
use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handles an error response from the AI call by logging it and sending a JSON error.
 *
 * @param AIPKit_Content_Writer_Standard_Generation_Action $handler The handler instance.
 * @param WP_Error $error The error object returned from the AI call.
 * @param array $validated_params The validated parameters from the request.
 * @param string $conversation_uuid The UUID of this interaction.
 * @return void
 */
function handle_error_response_logic(AIPKit_Content_Writer_Standard_Generation_Action $handler, WP_Error $error, array $validated_params, string $conversation_uuid): void
{
    if ($handler->log_storage) {
        $error_data = $error->get_error_data() ?? [];
        $request_payload_log_on_error = is_array($error_data) ? ($error_data['request_payload_log'] ?? null) : null;

        $handler->log_storage->log_message([
            'bot_id'            => null,
            'user_id'           => get_current_user_id(),
            'session_id'        => null,
            'conversation_uuid' => $conversation_uuid,
            'module'            => 'content_writer',
            'is_guest'          => 0,
            'role'              => implode(', ', wp_get_current_user()->roles),
            'ip_address'        => AIPKit_IP_Anonymization::maybe_anonymize(isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : null),
            'message_role'      => 'bot',
            'message_content'   => "Error generating content (AJAX): " . $error->get_error_message(),
            'timestamp'         => time(),
            'ai_provider'       => $validated_params['provider'],
            'ai_model'          => $validated_params['model'],
            'request_payload'   => $request_payload_log_on_error,
        ]);
    }
    $handler->send_wp_error($error);
}
