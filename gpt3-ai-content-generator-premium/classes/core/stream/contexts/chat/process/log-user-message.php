<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/contexts/chat/process/log-user-message.php
// Status: MODIFIED

namespace WPAICG\Core\Stream\Contexts\Chat\Process;

use WPAICG\Chat\Storage\LogStorage;
use WPAICG\AIPKit\Addons\AIPKit_IP_Anonymization;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
* Logs the initial user message for a chat stream.
*
* @param LogStorage $log_storage Instance of LogStorage.
* @param array $base_log_data Base log data (bot_id, user_id, session_id, conversation_uuid, module, is_guest, role, ip_address, bot_message_id FOR BOT, user_message_id_from_client FOR USER).
* @param string $user_message_text The user's text message.
* @param array|null $image_inputs Processed image input data.
* @param int $request_timestamp The timestamp of the request.
* @return array|WP_Error Result from LogStorage::log_message or WP_Error on failure.
*/
function log_user_message_logic(
    LogStorage $log_storage,
    array $base_log_data,
    string $user_message_text,
    ?array $image_inputs,
    int $request_timestamp
): array|WP_Error {
    if (!class_exists(AIPKit_IP_Anonymization::class)) {
        $ip_anon_path = WPAICG_PLUGIN_DIR . 'classes/addons/class-aipkit-ip-anonymization.php';
        if (file_exists($ip_anon_path)) {
            require_once $ip_anon_path;
        }
    }

    $log_user_data = [
        'bot_id'            => $base_log_data['bot_id'],
        'user_id'           => $base_log_data['user_id'],
        'session_id'        => $base_log_data['session_id'],
        'conversation_uuid' => $base_log_data['conversation_uuid'],
        'module'            => $base_log_data['module'],
        'is_guest'          => $base_log_data['is_guest'],
        'role'              => $base_log_data['role'],
        'ip_address'        => isset($base_log_data['ip_address']) && class_exists(AIPKit_IP_Anonymization::class) ? AIPKit_IP_Anonymization::maybe_anonymize($base_log_data['ip_address']) : ($base_log_data['ip_address'] ?? null),
        'message_role'      => 'user',
        'message_content'   => $user_message_text,
        'timestamp'         => $request_timestamp,
        'message_id'        => $base_log_data['user_message_id_from_client'] ?? null,
    ];
    unset($log_user_data['bot_message_id'], $log_user_data['user_message_id_from_client']);


    if (!empty($image_inputs)) {
        $log_user_data['response_data'] = ['type' => 'user_image_upload', 'images' => $image_inputs];
    }

    $user_log_result = $log_storage->log_message($log_user_data);

    if ($user_log_result === false) {
        return new WP_Error('user_log_failed_logic', __('Failed to log user message.', 'gpt3-ai-content-generator'), ['status' => 500]);
    }
    return $user_log_result; // Contains ['log_id', 'message_id', 'is_new_session']
}
