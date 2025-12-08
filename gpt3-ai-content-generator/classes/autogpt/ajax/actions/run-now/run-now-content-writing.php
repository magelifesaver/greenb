<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/ajax/actions/run-now/run-now-content-writing.php
// Status: MODIFIED

namespace WPAICG\AutoGPT\Ajax\Actions\RunNow;

// NEW: Use the shared modular logic
use WPAICG\AutoGPT\Cron\EventProcessor\Trigger\Modules as ContentWritingModules;
use WP_Error;

// NEW: Require all the shared modular files
$modules_path = WPAICG_PLUGIN_DIR . 'classes/autogpt/cron/event-processor/trigger/module/';
require_once $modules_path . 'topic-filter-utils.php';
require_once $modules_path . 'queue-writer.php';
require_once $modules_path . 'rss-task-generator.php';
require_once $modules_path . 'gsheets-task-generator.php';
require_once $modules_path . 'url-task-generator.php';
require_once $modules_path . 'manual-task-generator.php';
require_once $modules_path . 'parse-schedule-utils.php';


if (!defined('ABSPATH')) {
    exit;
}

/**
 * Queues items for a "Run Now" action on a content writing task.
 * This function now acts as an orchestrator, delegating logic to modular components.
 *
 * @param int $task_id The ID of the task.
 * @param array $task_config The configuration of the task.
 * @return true|WP_Error True on success, WP_Error if no titles are found.
 */
function run_now_content_writing_logic(int $task_id, array $task_config): bool|WP_Error
{
    $generation_mode = $task_config['cw_generation_mode'] ?? 'single';
    $topics_to_queue = [];
    $scraped_contexts = [];

    // 1. Generate items based on the generation mode
    switch ($generation_mode) {
        case 'rss':
            // For a "Run Now" action on RSS, we pass null to get all recent items, not just since last run.
            $topics_to_queue = ContentWritingModules\rss_mode_generate_items_logic($task_id, $task_config, null);
            break;
        case 'gsheets':
            $topics_to_queue = ContentWritingModules\gsheets_mode_generate_items_logic($task_id, $task_config);
            break;
        case 'url':
            $result = ContentWritingModules\url_mode_generate_items_logic($task_id, $task_config);
            if (!is_wp_error($result)) {
                $topics_to_queue = $result['topics'];
                $scraped_contexts = $result['contexts'];
            } else {
                $topics_to_queue = $result; // Pass the WP_Error object
            }
            break;
        default: // 'single', 'bulk', 'csv'
            $topics_to_queue = ContentWritingModules\manual_mode_generate_items_logic($task_config);
            break;
    }

    if (is_wp_error($topics_to_queue)) {
        return $topics_to_queue;
    }
    if (empty($topics_to_queue)) {
        return new WP_Error('no_titles_to_queue', __('No new or valid items found in the source to generate content for.', 'gpt3-ai-content-generator'), ['status' => 400]);
    }

    // 2. Loop through generated items and queue them
    $queued_count = 0;
    $item_index = 0;
    foreach ($topics_to_queue as $index => $item_data) {
        $item_config = ContentWritingModules\prepare_item_config_logic($item_data, $task_config, $scraped_contexts);
        if (empty($item_config['content_title'])) {
            continue;
        }

        // Unified scheduling helper
        $scheduled_gmt_time = ContentWritingModules\compute_item_schedule_gmt_logic($item_data, $task_config, $item_index, $generation_mode);
        if ($scheduled_gmt_time) {
            $item_config['scheduled_gmt_time'] = $scheduled_gmt_time;
        }

        $target_identifier = ContentWritingModules\generate_target_identifier_logic($item_data, $task_id, $index);
        if ($generation_mode !== 'bulk' && $generation_mode !== 'csv' && $generation_mode !== 'single') {
            if (ContentWritingModules\is_duplicate_topic_logic($task_id, $target_identifier)) {
                continue;
            }
        }

        if (ContentWritingModules\insert_topic_into_queue_logic($task_id, $target_identifier, $item_config)) {
            $queued_count++;
            $item_index++;
        }
    }

    return true;
}
