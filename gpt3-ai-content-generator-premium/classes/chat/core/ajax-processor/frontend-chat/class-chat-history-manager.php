<?php
// File: classes/chat/core/ajax-processor/frontend-chat/class-chat-history-manager.php
// Status: NEW FILE

namespace WPAICG\Chat\Core\AjaxProcessor\FrontendChat;

use WPAICG\Chat\Storage\LogStorage;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class ChatHistoryManager {

    private $log_storage;

    public function __construct(LogStorage $log_storage) {
        $this->log_storage = $log_storage;
    }

    /**
     * Loads and limits conversation history.
     *
     * @param int|null $user_id
     * @param string|null $session_id
     * @param int $bot_id
     * @param string $conversation_uuid
     * @param array $bot_settings
     * @return array Limited history.
     */
    public function get_limited_history(?int $user_id, ?string $session_id, int $bot_id, string $conversation_uuid, array $bot_settings): array {
        $history = $this->log_storage->get_conversation_thread_history($user_id, $session_id, $bot_id, $conversation_uuid);

        // Add current user message to history *before* limiting, as per instruction
        // (Note: This is slightly different from the original stream logic, where the current message was added *after* limiting.
        // For standard AJAX, it's conventional to include the current user message as part of the history sent to the AI.)
        // The log_user_message in ResponseLogger will ensure it's in the DB already, and get_conversation_thread_history should fetch it.
        // So, no explicit addition here is needed if get_conversation_thread_history is correct.

        $max_msgs = isset($bot_settings['max_messages']) ? absint($bot_settings['max_messages']) : 15;
        $max_msgs = max(1, $max_msgs); // Ensure at least 1 message
        if (count($history) > $max_msgs) {
            $history = array_slice($history, -$max_msgs);
        }
        return $history;
    }
}