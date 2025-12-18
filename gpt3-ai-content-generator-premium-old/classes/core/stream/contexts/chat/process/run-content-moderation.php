<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/contexts/chat/process/run-content-moderation.php
// Status: MODIFIED

namespace WPAICG\Core\Stream\Contexts\Chat\Process;

use WPAICG\Core\AIPKit_Content_Moderator;
use WPAICG\Chat\Storage\LogStorage;
use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage;
use WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Manager;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Performs content moderation checks (banned IPs, words, OpenAI moderation).
 * Dispatches a 'system_error_occurred' trigger if moderation fails.
 *
 * @param string $user_message_text The user's text message.
 * @param string|null $client_ip The client's IP address.
 * @param array $bot_settings Settings for the current bot.
 * @param LogStorage|null $log_storage Instance of LogStorage for triggers.
 * @param int $bot_id The ID of the current bot.
 * @param int|null $user_id The ID of the user, or null for guests.
 * @param string|null $session_id The session ID for guests.
 * @return true|WP_Error True if moderation passes, WP_Error otherwise.
 */
function run_content_moderation_logic(
    string $user_message_text,
    ?string $client_ip,
    array $bot_settings,
    ?LogStorage $log_storage,
    int $bot_id,
    ?int $user_id,
    ?string $session_id
): bool|WP_Error {

    if (empty($user_message_text)) {
        return true; // If no text (e.g., only image upload), skip text moderation.
    }

    if (!class_exists(AIPKit_Content_Moderator::class)) {
        return true; // Fail open if moderator class is missing.
    }

    $moderation_context = ['client_ip' => $client_ip, 'bot_settings' => $bot_settings];
    $moderation_check = AIPKit_Content_Moderator::check_content($user_message_text, $moderation_context);

    if (is_wp_error($moderation_check)) {
        $trigger_storage_class = '\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Storage';
        $trigger_manager_class = '\WPAICG\Lib\Chat\Triggers\AIPKit_Trigger_Manager';
        $triggers_addon_active = false;
        if (class_exists('\WPAICG\aipkit_dashboard')) {
            $triggers_addon_active = \WPAICG\aipkit_dashboard::is_addon_active('triggers');
        }

        if ($triggers_addon_active && class_exists($trigger_manager_class) && class_exists($trigger_storage_class)) {
            // Only proceed if log storage is available for the trigger manager
            if ($log_storage) {
                $error_data = $moderation_check->get_error_data() ?: [];
                $error_event_context = [
                    'error_code'    => $moderation_check->get_error_code(),
                    'error_message' => $moderation_check->get_error_message(),
                    'bot_id'        => $bot_id,
                    'user_id'       => $user_id ?: null,
                    'session_id'    => $session_id,
                    'module'        => 'chat_content_moderation',
                    'operation'     => 'check_user_message',
                    'status_code'   => is_array($error_data) && isset($error_data['status']) ? (int)$error_data['status'] : 400,
                ];
                try {
                    $trigger_storage = new $trigger_storage_class();
                    $trigger_manager = new $trigger_manager_class($trigger_storage, $log_storage);
                    $trigger_manager->process_event($bot_id, 'system_error_occurred', $error_event_context);
                } catch (\Exception $e) {
                     // Exception is caught and ignored to prevent fatal errors.
                }
            }
        }
        // Return the original WP_Error, ensuring status code is preserved
        $status_code_from_error = is_array($moderation_check->get_error_data()) && isset($moderation_check->get_error_data()['status'])
                                  ? (int)$moderation_check->get_error_data()['status']
                                  : 400;
        return new WP_Error($moderation_check->get_error_code(), $moderation_check->get_error_message(), ['status' => $status_code_from_error]);
    }
    return true; // Moderation passed
}