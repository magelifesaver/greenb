<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/storage/logger/update-existing-log.php
// Status: NEW FILE

namespace WPAICG\Chat\Storage\LoggerMethods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Updates an existing conversation log row with a new message.
 *
 * @param \wpdb $wpdb WordPress database object.
 * @param string $table_name The name of the log table.
 * @param array $existing_log_row The existing log row data from DB.
 * @param array $new_message The new message object to add.
 * @param int $current_timestamp The current timestamp for the message.
 * @param string|null $ip_to_store Anonymized IP address.
 * @param string|null $user_wp_role User's WordPress role.
 * @return array|false ['log_id' => int, 'message_id' => string] on success, false on failure.
 */
function update_existing_log_logic(
    \wpdb $wpdb,
    string $table_name,
    array $existing_log_row,
    array $new_message,
    int $current_timestamp,
    ?string $ip_to_store,
    ?string $user_wp_role
): array|false {
    $log_id = absint($existing_log_row['id']);
    $messages_json = $existing_log_row['messages'] ?? null;
    $conversation_data = $messages_json ? json_decode($messages_json, true) : null;

    if (!is_array($conversation_data) || !isset($conversation_data['parent_id']) || !isset($conversation_data['messages'])) {
        $parent_id = generate_parent_id_logic(); // Call namespaced function
        $messages_array = [];
    } else {
        $parent_id = $conversation_data['parent_id'];
        $messages_array = $conversation_data['messages'];
         if (!is_array($messages_array)) $messages_array = []; // Ensure it's an array
    }

    $messages_array[] = $new_message;

    $updated_conversation_data = [
        'parent_id' => $parent_id,
        'messages' => $messages_array,
    ];

    $update_data_fields = [
        'messages'         => wp_json_encode($updated_conversation_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'message_count'    => count($messages_array),
        'last_message_ts'  => $current_timestamp,
        'updated_at'       => current_time('mysql', 1),
        'ip_address'       => $ip_to_store, // This might be null
        'user_wp_role'     => $user_wp_role ? sanitize_text_field($user_wp_role) : null, // This might be null
    ];
    $update_formats_map = ['%s', '%d', '%d', '%s', '%s', '%s']; // Corresponds to update_data_fields order

    $data_to_update = [];
    $formats_to_use = [];
    foreach ($update_data_fields as $key => $value) {
        // Include key if it's explicitly not null, OR if it's one of the keys that *can* be null
        if ($value !== null || in_array($key, ['ip_address', 'user_wp_role'])) {
             $data_to_update[$key] = $value;
             $key_index = array_search($key, array_keys($update_data_fields));
             if ($key_index !== false) {
                 $formats_to_use[] = $update_formats_map[$key_index];
             }
        }
    }

    if (empty($data_to_update)) {
        // Nothing changed except potentially the messages array itself if no other metadata was updated
        // This path should ideally not be taken if we always update 'messages' and 'message_count'
        return ['log_id' => $log_id, 'message_id' => $new_message['message_id']];
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Reason: Direct update to a custom table is necessary. Cache is invalidated by the calling function.
    $updated = $wpdb->update(
        $table_name,
        $data_to_update,
        ['id' => $log_id],
        $formats_to_use,
        ['%d'] // WHERE format for id
    );

    if ($updated === false) {
        return false;
    }

    // --- ADDED: Invalidate cache after update ---
    if (isset($existing_log_row['conversation_uuid'])) {
        $conversation_uuid = $existing_log_row['conversation_uuid'];
        $cache_group = 'aipkit_chat_logs';
        wp_cache_delete('conv_history_' . $conversation_uuid, $cache_group);
        wp_cache_delete('conv_full_log_' . $conversation_uuid, $cache_group);
        wp_cache_delete('conv_meta_' . $conversation_uuid, $cache_group);
        // Invalidate the list cache as well, as it contains summary data
        $user_id_for_list = $existing_log_row['user_id'] ?: null;
        $session_id_for_list = $existing_log_row['session_id'] ?: null;
        $bot_id_for_list = $existing_log_row['bot_id'] ?: 0;
        $cache_key_identifier = $user_id_for_list ? "user_{$user_id_for_list}" : "guest_{$session_id_for_list}";
        $list_cache_key = "conv_list_{$bot_id_for_list}_{$cache_key_identifier}";
        wp_cache_delete($list_cache_key, $cache_group);
    }
    // --- END: Invalidate cache ---

    return ['log_id' => $log_id, 'message_id' => $new_message['message_id']];
}