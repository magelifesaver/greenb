<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/cron/event-processor/trigger/trigger-content-enhancement-task.php
// Status: MODIFIED

namespace WPAICG\AutoGPT\Cron\EventProcessor\Trigger;

use WP_Query;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Queues existing content for enhancement based on task configuration.
 *
 * @param int $task_id The ID of the task.
 * @param array $task_config The configuration of the task.
 * @param string|null $last_run_time The last time the task was run.
 * @return void
 */
function trigger_content_enhancement_task_logic(int $task_id, array $task_config, ?string $last_run_time = null): void
{
    global $wpdb;
    $queue_table_name = $wpdb->prefix . 'aipkit_automated_task_queue';

    $post_types = $task_config['post_types'] ?? ['post'];
    if (empty($post_types)) {
        return;
    }

    $args = [
        'post_type'      => $post_types,
        'post_status'    => $task_config['post_statuses'] ?? ['publish'],
        'posts_per_page' => -1, // Get all of them for this pass
        'fields'         => 'ids',
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ];

    if ($last_run_time) {
        $args['date_query'] = [
            [
                'column' => 'post_modified_gmt',
                'after'  => $last_run_time,
                'inclusive' => false,
            ],
        ];
    }

    // --- FIX START: Use tax_query for better cross-post-type compatibility ---
    $tax_queries = [];
    if (!empty($task_config['post_categories'])) {
        $tax_queries[] = [
            'taxonomy' => 'category', // Standard 'post' category taxonomy
            'field'    => 'term_id',
            'terms'    => $task_config['post_categories'],
            'operator' => 'IN',
        ];
    }
    // If other taxonomies were supported, they would be added here.
    if (!empty($tax_queries)) {
        if (count($tax_queries) > 1) {
            $tax_queries['relation'] = 'AND';
        }
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- Reason: The meta/tax query is essential for the feature's functionality. Its performance impact is considered acceptable as the query is highly specific, paginated, cached, or runs in a non-critical admin/cron context.
        $args['tax_query'] = $tax_queries;
    }
    // --- FIX END ---


    if (!empty($task_config['post_authors'])) {
        $args['author__in'] = $task_config['post_authors'];
    }


    $query = new WP_Query($args);
    $queued_count = 0;
    $skipped_count = 0;


    if ($query->have_posts()) {
        foreach ($query->posts as $post_id) {
            // Check if this post is already in the queue for this task
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Reason: Direct query to a custom table. Caches will be invalidated.
            $existing_item = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$queue_table_name} WHERE task_id = %d AND target_identifier = %s",
                $task_id,
                $post_id
            ));

            if ($existing_item) {
                $skipped_count++;
                continue;
            }

            // The item_config for enhancement tasks is just the main task config
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Reason: Direct insert to a custom table.
            $inserted = $wpdb->insert(
                $queue_table_name,
                [
                    'task_id'           => $task_id,
                    'target_identifier' => $post_id,
                    'task_type'         => 'enhance_existing_content',
                    'item_config'       => wp_json_encode($task_config),
                    'status'            => 'pending',
                    'added_at'          => current_time('mysql', 1),
                ],
                ['%d', '%d', '%s', '%s', '%s', '%s']
            );
            if ($inserted) {
                $queued_count++;
            }
        }
    }
}