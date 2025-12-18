<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/schemas/triggers/schema-components.php
// Status: NEW FILE

/**
 * AIPKit Chatbot Triggers - Components Schema Definition
 * This file will define the 'components.schemas' part, which references the other schema files.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// The individual schema files (EventSchema, ConditionSchema, etc.) already return the schema array.
// This file is for the conceptual 'components' wrapper if it were a single JSON file.
// In PHP, the main trigger-schemas.php will construct this structure.
return [
    'schemas' => [
        // References to individual schemas will be populated by the main trigger-schemas.php file.
        // 'EventSchema' => require __DIR__ . '/schema-event.php',
        // 'ConditionSchema' => require __DIR__ . '/schema-condition.php',
        // ... and so on
    ],
];