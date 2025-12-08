<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/storage/logger/insert-new-log.php
// Status: MODIFIED

namespace WPAICG\Chat\Storage\LoggerMethods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Inserts a new conversation log row.
 *
 * @param \wpdb $wpdb WordPress database object.
 * @param string $table_name The name of the log table.
 * @param int|null $bot_id
 * @param int|null $user_id
 * @param string|null $session_id
 * @param string $conversation_uuid
 * @param string $module
 * @param int $is_guest
 * @param array $new_message The first message object.
 * @param int $current_timestamp The current timestamp for the message.
 * @param string|null $ip_to_store Anonymized IP address.
 * @param string|null $user_wp_role User's WordPress role.
 * @return array|false ['log_id' => int, 'message_id' => string, 'is_new_session' => true] on success, false on failure.
 */
function insert_new_log_logic(
    \wpdb $wpdb,
    string $table_name,
    ?int $bot_id,
    ?int $user_id,
    ?string $session_id,
    string $conversation_uuid,
    string $module,
    int $is_guest,
    array $new_message,
    int $current_timestamp,
    ?string $ip_to_store,
    ?string $user_wp_role
): array|false {
    $parent_id = generate_parent_id_logic(); // Call namespaced function
    $messages_array = [$new_message];

    $conversation_data = [
         'parent_id' => $parent_id,
         'messages' => $messages_array,
    ];

    $insert_data_fields = [
        'bot_id'            => $bot_id,
        'user_id'           => $user_id,
        'session_id'        => $session_id,
        'conversation_uuid' => $conversation_uuid,
        'module'            => $module,
        'is_guest'          => $is_guest,
        'messages'          => wp_json_encode($conversation_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        'message_count'     => 1,
        'first_message_ts'  => $current_timestamp,
        'last_message_ts'   => $current_timestamp,
        'ip_address'        => $ip_to_store, // This might be null
        'user_wp_role'      => $user_wp_role ? sanitize_text_field($user_wp_role) : null, // This might be null
        'created_at'        => current_time('mysql', 1),
        'updated_at'        => current_time('mysql', 1),
    ];
    $formats_map = ['%d', '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s'];

    $data_to_insert = [];
    $formats_to_use = [];
    foreach ($insert_data_fields as $key => $value) {
        // Always include the key in data_to_insert, even if null.
        // The format string will determine how $wpdb->prepare handles NULLs.
        $data_to_insert[$key] = $value;
        $key_index = array_search($key, array_keys($insert_data_fields));
        if ($key_index !== false) {
            $formats_to_use[] = $formats_map[$key_index];
        }
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Necessary insert operation into a custom table.
    $inserted = $wpdb->insert($table_name, $data_to_insert, $formats_to_use);

    if ($inserted === false) {
        return false;
    }
    $new_log_id = $wpdb->insert_id;
    return ['log_id' => $new_log_id, 'message_id' => $new_message['message_id'], 'is_new_session' => true];
}