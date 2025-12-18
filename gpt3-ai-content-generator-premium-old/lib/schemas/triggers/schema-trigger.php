<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/schemas/triggers/schema-trigger.php
// Status: NEW FILE

/**
 * AIPKit Chatbot Triggers - Trigger Schema Definition
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

return [
    'type' => 'object',
    'properties' => [
        'id' => [
            'type' => 'string',
            'format' => 'uuid',
            'description' => 'Unique identifier for the trigger.',
            'required' => true,
        ],
        'name' => [
            'type' => 'string',
            'description' => 'A user-friendly name for the trigger.',
            'required' => true,
        ],
        'event_name' => [
            'type' => 'string',
            'description' => 'The name of the event that this trigger listens for. Must match one of the defined EventSchema names.',
            'required' => true,
        ],
        'conditions' => [
            'type' => 'array',
            'description' => 'An array of Condition Objects. All conditions must be met for the action to execute.',
            'items' => ['$ref' => '#/components/schemas/ConditionSchema'],
            'default' => [],
        ],
        'action' => [
            'type' => 'object',
            'properties' => [
                'type' => ['type' => 'string', 'required' => true],
                'payload' => ['type' => 'object', 'required' => true, 'additionalProperties' => true],
                 'value' => [ 'type' => ['string', 'integer', 'boolean', 'null']],
            ],
            'required' => ['type', 'payload'],
            'description' => 'The Action Object to execute if conditions are met.',
            'required' => true, // The action itself is required
        ],
        'priority' => [
            'type' => 'integer',
            'description' => 'Execution priority (lower numbers execute first). Default 10.',
            'default' => 10,
        ],
        'is_active' => [
            'type' => 'boolean',
            'description' => 'Whether the trigger is currently active.',
            'default' => true,
        ],
        'description' => [
            'type' => 'string',
            'description' => 'Optional description for the trigger.'
        ]
    ],
    'required' => ['id', 'name', 'event_name', 'action', 'priority', 'is_active'],
];