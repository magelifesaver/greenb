<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/schemas/triggers/trigger-schemas.php
// Status: MODIFIED

/**
 * AIPKit Chatbot Triggers - JSON Schema Definitions (Main Importer)
 *
 * This file now imports modularized schema definitions and merges them
 * into a single array structure that mimics a complete JSON schema file.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

$event_schema = require __DIR__ . '/schema-event.php';
$condition_schema = require __DIR__ . '/schema-condition.php';
$action_schema = require __DIR__ . '/schema-action.php';
$trigger_schema = require __DIR__ . '/schema-trigger.php';
$triggers_array_schema = require __DIR__ . '/schema-triggers-array.php';
// The schema-components.php file is not directly used here as we build the structure manually.

return [
    'EventSchema' => $event_schema,
    'ConditionSchema' => $condition_schema,
    'ActionSchema' => $action_schema,
    'TriggerSchema' => $trigger_schema,
    'TriggersArraySchema' => $triggers_array_schema,
    'components' => [
        'schemas' => [
            'EventSchema' => $event_schema, // For $ref consistency if ever used
            'ConditionSchema' => $condition_schema,
            'ActionSchema' => $action_schema,
            'TriggerSchema' => $trigger_schema,
            // TriggersArraySchema is usually the top-level schema, not typically referenced internally.
        ],
    ],
];