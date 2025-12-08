<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/cron/event-processor/trigger/module/queue-writer.php
// Status: MODIFIED

namespace WPAICG\AutoGPT\Cron\EventProcessor\Trigger\Modules;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Checks if an item with the given identifier already exists for the task.
 * @param int $task_id
 * @param string $target_identifier
 * @return bool
 */
function is_duplicate_topic_logic(int $task_id, string $target_identifier): bool
{
    global $wpdb;
    $queue_table_name = $wpdb->prefix . 'aipkit_automated_task_queue';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: Direct query to a custom table. Caches will be invalidated.
    $existing_item = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$queue_table_name} WHERE task_id = %d AND target_identifier = %s",
        $task_id,
        $target_identifier
    ));
    return (bool) $existing_item;
}

/**
 * Generates a consistent target identifier for an item.
 * @param mixed  $item_data (string or array)
 * @param int    $task_id
 * @param int    $index
 * @return string
 */
function generate_target_identifier_logic($item_data, int $task_id, int $index): string
{
    if (is_array($item_data) && !empty($item_data['guid'])) {
        return $item_data['guid'];
    }
    if (is_array($item_data) && !empty($item_data['link'])) {
        return $item_data['link'];
    }
    $base_identifier = is_string($item_data) ? $item_data : ($item_data['title'] ?? ($item_data['topic'] ?? ''));
    return 'cw_scheduled_' . $task_id . '_' . sanitize_title(substr($base_identifier, 0, 50)) . '_' . time() . '_' . $index;
}

/**
 * Prepares the specific item_config by merging item-specific data with the main task config.
 * @param mixed $item_data (string or array)
 * @param array $task_config
 * @param array $scraped_contexts
 * @return array
 */
function prepare_item_config_logic($item_data, array $task_config, array $scraped_contexts): array
{
    $item_specific_config = $task_config;
    $is_structured_item = is_array($item_data);

    if ($is_structured_item) {
        $topic = $item_data['topic'] ?? ($item_data['title'] ?? '');
        $inline_keywords = $item_data['keywords'] ?? '';
        $category_id = $item_data['category'] ?? null;
        $author_login = $item_data['author'] ?? null;
        $post_type_slug = $item_data['post_type'] ?? null;

        $item_specific_config['content_title'] = $topic;
        $item_specific_config['inline_keywords'] = $inline_keywords;

        if (isset($item_data['description'])) {
            $item_specific_config['rss_description'] = $item_data['description'];
        }
        if (isset($item_data['link'])) {
            $item_specific_config['source_url'] = $item_data['link'];
        }
        if (isset($item_data['guid'])) {
            $item_specific_config['rss_item_guid'] = $item_data['guid'];
        }

        $link = $item_data['link'] ?? md5($topic);
        if (isset($scraped_contexts[$link])) {
            $item_specific_config['url_content_context'] = $scraped_contexts[$link];
            $item_specific_config['source_url'] = $link;
        }

        if (isset($item_data['row_index'])) {
            $item_specific_config['gsheets_row_index'] = $item_data['row_index'];
        }
        if (isset($task_config['gsheets_sheet_id'])) {
            $item_specific_config['gsheets_sheet_id'] = $task_config['gsheets_sheet_id'];
        }

        if ($post_type_slug && post_type_exists($post_type_slug)) {
            $item_specific_config['post_type'] = $post_type_slug;
        }

        if ($category_id && is_numeric($category_id)) {
            $item_specific_config['post_categories'] = [absint($category_id)];
        }

        if ($author_login) {
            $user = get_user_by('login', $author_login);
            if ($user) {
                $post_type = $item_specific_config['post_type'] ?? 'post';
                $post_type_object = get_post_type_object($post_type);
                if ($post_type_object && user_can($user->ID, $post_type_object->cap->create_posts)) {
                    $item_specific_config['post_author'] = $user->ID;
                }
            }
        }
    } else {
        $parts = array_map('trim', explode('|', $item_data));
        $item_specific_config['content_title'] = $parts[0] ?? '';
        $item_specific_config['inline_keywords'] = $parts[1] ?? '';
    }
    return $item_specific_config;
}

/**
 * Inserts a single prepared item into the queue.
 * @param int    $task_id
 * @param string $target_identifier
 * @param array  $item_config
 * @return bool True on success
 */
function insert_topic_into_queue_logic(int $task_id, string $target_identifier, array $item_config): bool
{
    global $wpdb;
    $queue_table_name = $wpdb->prefix . 'aipkit_automated_task_queue';
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Direct query to a custom table.
    $inserted = $wpdb->insert(
        $queue_table_name,
        [
            'task_id' => $task_id,
            'target_identifier' => $target_identifier,
            'task_type' => 'content_writing',
            'item_config' => wp_json_encode($item_config),
            'status' => 'pending',
            'added_at' => current_time('mysql', 1)
        ],
        ['%d', '%s', '%s', '%s', '%s', '%s']
    );
    return (bool) $inserted;
}
