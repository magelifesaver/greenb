<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/autogpt/cron/event-processor/trigger/module/manual-task-generator.php
// Status: NEW FILE

namespace WPAICG\AutoGPT\Cron\EventProcessor\Trigger\Modules;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Generates items to be queued from a textarea input (single, bulk, or csv modes).
 * Each non-empty line can contain pipe-separated columns:
 * 0: topic (required)
 * 1: keywords (optional)
 * 2: category id (optional)
 * 3: author login (optional)
 * 4: post type (optional)
 * 5: schedule datetime (optional; multiple formats supported by parse_schedule_datetime_simple_logic later)
 *
 * Returns an array of mixed item arrays (structured) or strings (legacy) for backward compatibility.
 * Structured arrays enable schedule date usage with schedule_mode=from_input.
 *
 * @param array $task_config The configuration of the task.
 * @return array<int, array|string>
 */
function manual_mode_generate_items_logic(array $task_config): array
{
    $raw = $task_config['content_title'] ?? '';
    if ($raw === '') {
        return [];
    }
    $lines = preg_split('/\r?\n/', $raw);
    $items = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        // Split by pipe only if present; allow legacy plain topic lines.
        if (strpos($line, '|') === false) {
            $items[] = $line; // legacy simple topic
            continue;
        }
        $parts = array_map('trim', explode('|', $line));
        $topic = $parts[0] ?? '';
        if ($topic === '') {
            continue; // skip invalid line
        }
        $item = [
            'topic' => $topic,
        ];
        if (isset($parts[1]) && $parts[1] !== '') {
            $item['keywords'] = $parts[1];
        }
        if (isset($parts[2]) && is_numeric($parts[2])) {
            $item['category'] = $parts[2];
        }
        if (isset($parts[3]) && $parts[3] !== '') {
            $item['author'] = $parts[3];
        }
        if (isset($parts[4]) && $parts[4] !== '') {
            $item['post_type'] = $parts[4];
        }
        if (isset($parts[5]) && $parts[5] !== '') {
            $item['schedule_date'] = $parts[5];
        }
        $items[] = $item;
    }
    return $items;
}
