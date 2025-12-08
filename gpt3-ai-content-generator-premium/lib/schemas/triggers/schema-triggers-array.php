<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/schemas/triggers/schema-triggers-array.php
// Status: NEW FILE

/**
 * AIPKit Chatbot Triggers - Triggers Array Schema Definition
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

return [
    'type' => 'array',
    'items' => ['$ref' => '#/components/schemas/TriggerSchema'],
];