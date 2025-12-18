<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/content-writer/ajax/actions/init-stream/log-stream-init.php

namespace WPAICG\ContentWriter\Ajax\Actions\InitStream;

use WPAICG\ContentWriter\Ajax\Actions\AIPKit_Content_Writer_Init_Stream_Action;
use WPAICG\AIPKit\Addons\AIPKit_IP_Anonymization;

if (!defined('ABSPATH')) {
    exit;
}

/**
* Logs the successful stream initialization request.
*
* @param AIPKit_Content_Writer_Init_Stream_Action $handler The handler instance.
* @param array $cached_data The data that was just cached.
* @return void
*/
function log_stream_init_logic(AIPKit_Content_Writer_Init_Stream_Action $handler, array $cached_data): void
{
    if ($handler->log_storage) {
        $client_ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : null;

        $handler->log_storage->log_message([
        'bot_id' => null,
        'user_id' => get_current_user_id(),
        'session_id' => null,
        'conversation_uuid' => $cached_data['conversation_uuid'],
        'module' => 'content_writer',
        'is_guest' => 0,
        'role' => implode(', ', wp_get_current_user()->roles),
        'ip_address' => class_exists(AIPKit_IP_Anonymization::class) ? AIPKit_IP_Anonymization::maybe_anonymize($client_ip) : $client_ip,
        'message_role' => 'user',
        'message_content' => "Content Writer Request: " . esc_html($cached_data['initial_request_details']['title'] ?? 'Untitled'),
        'timestamp' => time(),
        'ai_provider' => $cached_data['provider'],
        'ai_model' => $cached_data['model'],
        'request_payload' => $cached_data['initial_request_details']
        ]);
    }
}
