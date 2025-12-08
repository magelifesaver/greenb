<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/storage/reader/get-conversation-thread-history.php
// Status: MODIFIED

namespace WPAICG\Chat\Storage\ReaderMethods;

use WPAICG\Chat\Storage\ConversationReader; // To access instance methods if needed (getters)

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the get_conversation_thread_history method of ConversationReader.
 * Retrieves the conversation history (array of messages) for a specific conversation thread.
 * Handles the new JSON structure. Includes feedback, usage, openai_response_id, and used_previous_response_id.
 * MODIFIED: Filters out system messages with event_sub_type 'trigger_log'.
 *
 * @param ConversationReader $readerInstance The instance of the ConversationReader class.
 * @param int|null $user_id The user ID (null for guests).
 * @param string|null $session_id The guest UUID (null for logged-in users).
 * @param int $bot_id The bot ID.
 * @param string $conversation_uuid The specific conversation thread UUID.
 * @return array The array of messages [{message_id, role, content, timestamp, provider?, model?, feedback?, usage?, openai_response_id?, used_previous_response_id?}, ...].
 */
function get_conversation_thread_history_logic(
    ConversationReader $readerInstance,
    ?int $user_id,
    ?string $session_id,
    int $bot_id,
    string $conversation_uuid
): array {
    if (empty($bot_id) || empty($conversation_uuid) || (!$user_id && empty($session_id))) {
        return [];
    }

    $wpdb = $readerInstance->get_wpdb();
    $table_name = $readerInstance->get_table_name();

    // --- ADDED: Caching logic ---
    $cache_key = 'conv_history_' . $conversation_uuid;
    $cache_group = 'aipkit_chat_logs';
    $messages_json = wp_cache_get($cache_key, $cache_group);

    if (false === $messages_json) {
        $where_sql = "bot_id = %d AND conversation_uuid = %s AND ";
        $params = [$bot_id, $conversation_uuid];
        if ($user_id) {
            $where_sql .= "user_id = %d";
            $params[] = $user_id;
        } else {
            $where_sql .= "(user_id IS NULL AND session_id = %s AND is_guest = 1)";
            $params[] = $session_id;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Reason: This is a prepared query with parameters.
        $messages_json = $wpdb->get_var($wpdb->prepare("SELECT messages FROM {$table_name} WHERE {$where_sql} LIMIT 1", $params));
        wp_cache_set($cache_key, $messages_json, $cache_group, HOUR_IN_SECONDS);
    }
    // --- END: Caching logic ---

    if (empty($messages_json)) {
        return [];
    }

    $conversation_data = json_decode($messages_json, true);
    $messages_array = null;

    // Check if it's the new structure or the old simple array
    if (is_array($conversation_data) && isset($conversation_data['parent_id']) && isset($conversation_data['messages']) && is_array($conversation_data['messages'])) {
        $messages_array = $conversation_data['messages'];
    } elseif (is_array($conversation_data)) { // Assume old structure (simple array) for backward compatibility
        $messages_array = $conversation_data;
    } else {
        return [];
    }

    $filtered_messages = [];
    foreach ($messages_array as $msg) {
        // --- MODIFICATION: Filter out system trigger logs ---
        if (isset($msg['role']) && $msg['role'] === 'system' && isset($msg['event_sub_type']) && $msg['event_sub_type'] === 'trigger_log') {
            continue; // Skip this message
        }
        // --- END MODIFICATION ---

        if (isset($msg['timestamp'])) {
            $msg['timestamp'] = (int)$msg['timestamp'];
        }
        if (!isset($msg['message_id'])) {
            $msg['message_id'] = generate_message_id_logic(); // Call namespaced function
        }
        $filtered_messages[] = $msg;
    }

    return $filtered_messages;
}