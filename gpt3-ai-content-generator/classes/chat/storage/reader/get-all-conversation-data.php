<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/storage/reader/get-all-conversation-data.php
// Status: MODIFIED

namespace WPAICG\Chat\Storage\ReaderMethods;

use WPAICG\Chat\Storage\ConversationReader; // To access instance methods (getters)
use WPAICG\Chat\Storage\LogQueryHelper;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Logic for the get_all_conversation_data method of ConversationReader.
 * Retrieves summary data for all distinct conversations for a user/session and bot.
 * Handles the new JSON structure to extract the title.
 *
 * @param ConversationReader $readerInstance The instance of the ConversationReader class.
 * @param int|null $user_id The user ID (null for guests).
 * @param string|null $session_id The guest UUID (null for logged-in users).
 * @param int $bot_id The bot ID.
 * @return array|null An array of conversation summaries or null on error.
 */
function get_all_conversation_data_logic(
    ConversationReader $readerInstance,
    ?int $user_id,
    ?string $session_id,
    int $bot_id
): ?array {
    if (empty($bot_id) || (!$user_id && empty($session_id))) {
        return null;
    }

    $wpdb = $readerInstance->get_wpdb();
    $table_name = $readerInstance->get_table_name();
    $query_helper = $readerInstance->get_query_helper();

    // --- ADDED: Caching logic ---
    $cache_key_identifier = $user_id ? "user_{$user_id}" : "guest_{$session_id}";
    $cache_key = "conv_list_{$bot_id}_{$cache_key_identifier}";
    $cache_group = 'aipkit_chat_logs';
    $summaries = wp_cache_get($cache_key, $cache_group);

    if (false === $summaries) {
        $filters = ['bot_id' => $bot_id];
        if ($user_id) {
            $filters['user_id'] = $user_id;
        } else {
            $filters['session_id'] = $session_id;
            $filters['user_id'] = null; // Explicitly set user_id to null for guest session_id queries
        }

        $query_parts = $query_helper->build_conversation_query_parts($filters, 'last_message_ts', 'DESC', 0, 0, true);
        $query = "SELECT {$query_parts['select_sql']} FROM {$table_name} {$query_parts['join_sql']} WHERE {$query_parts['where_sql']} ORDER BY {$query_parts['orderby']} {$query_parts['order']}";
        if (!empty($query_parts['params'])) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Reason: This is a prepared query with parameters.
            $query = $wpdb->prepare($query, $query_parts['params']);
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared -- Reason: This is a prepared query with parameters.
        $summaries = $wpdb->get_results($query, ARRAY_A);
        wp_cache_set($cache_key, $summaries, $cache_group, MINUTE_IN_SECONDS * 5); // Cache for 5 minutes
    }
    // --- END: Caching logic ---


    if ($summaries === null) {
        return null;
    }
    if (empty($summaries)) {
        return [];
    }

    $conversation_list = [];
    foreach ($summaries as $summary) {
        $conv_uuid = $summary['conversation_uuid'];
        $timestamp = (int)$summary['last_message_ts'];
        $title = $conv_uuid; // Default title

        $conversation_data = json_decode($summary['messages'] ?? '[]', true);
        $messages_array = null;
        // Check for new structure
        if (is_array($conversation_data) && isset($conversation_data['messages']) && is_array($conversation_data['messages'])) {
            $messages_array = $conversation_data['messages'];
        } elseif (is_array($conversation_data)) { // Backward compatibility for old structure
            $messages_array = $conversation_data;
        }

        if (is_array($messages_array)) {
            foreach ($messages_array as $msg) { // Find first user message
                if (($msg['role'] ?? '') === 'user' && !empty($msg['content'])) {
                    $title = wp_trim_words($msg['content'], 5, '...');
                    break;
                }
            }
            if ($title === $conv_uuid && !empty($messages_array[0]['content'])) { // Fallback to first message
                $title = wp_trim_words($messages_array[0]['content'], 5, '...');
            }
        }

        $conversation_list[] = ['id' => $conv_uuid, 'title' => $title, 'timestamp' => $timestamp];
    }

    usort($conversation_list, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);
    return $conversation_list;
}