<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/chat/storage/logger/build-where-clauses.php
// Status: NEW FILE

namespace WPAICG\Chat\Storage\LoggerMethods;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Builds WHERE clauses and parameters for finding an existing conversation log row.
 *
 * @param string $conversation_uuid
 * @param string $module
 * @param int|null $bot_id
 * @param int|null $user_id
 * @param string|null $session_id
 * @return array ['where_sql' => string, 'params' => array]
 */
function build_where_clauses_logic(
    string $conversation_uuid,
    string $module,
    ?int $bot_id,
    ?int $user_id,
    ?string $session_id
): array {
    $where_clauses = ["conversation_uuid = %s", "module = %s"];
    $params = [$conversation_uuid, $module];

    if ($bot_id !== null) {
        $where_clauses[] = "bot_id = %d";
        $params[] = $bot_id;
    } else {
        $where_clauses[] = "bot_id IS NULL";
    }

    if ($user_id) {
        $where_clauses[] = "user_id = %d";
        $params[] = $user_id;
    } else {
        // Ensure session_id is not empty for guest condition
        if (empty($session_id)) {
            // This case should ideally be caught by validation in log_message
            // but adding a safeguard here.
            // Fallback to a condition that won't match anything safely or throw an error.
            // For now, let it proceed, log_message should have caught it.
            $where_clauses[] = "1=0"; // Will not match
        } else {
            $where_clauses[] = "(user_id IS NULL AND session_id = %s AND is_guest = 1)";
            $params[] = $session_id;
        }
    }
    return ['where_sql' => implode(" AND ", $where_clauses), 'params' => $params];
}