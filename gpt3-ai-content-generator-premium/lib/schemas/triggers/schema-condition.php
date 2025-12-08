<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/schemas/triggers/schema-condition.php
// Status: NEW FILE

/**
 * AIPKit Chatbot Triggers - Condition Schema Definition
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

return [
    'type' => 'object',
    'properties' => [
        'type' => [
            'type' => 'string',
            'description' => 'The category of the condition.',
            'enum' => [
                'user_context',          // Conditions related to the user (logged_in, role).
                'text_content',          // Conditions related to message text.
                'conversation_state',    // Conditions related to the conversation (message_count).
                'ai_model_context',      // Conditions related to the AI model being used (current_provider, current_model_id).
                'http_context',          // Conditions related to HTTP request (referer, user_agent).
                'post_context',          // Conditions related to the current WordPress post/page.
                'error_context',         // Conditions related to system_error_occurred event
            ],
            'required' => true,
        ],
        'field' => [
            'type' => 'string',
            'description' => 'The specific field to evaluate within the chosen type. Examples: "is_logged_in", "user_role", "user_message_text", "message_count", "current_provider", "current_model_id", "http_referer", "post_id", "post_tags_contain", "error_code", "error_message", "failed_provider".',
            'required' => true,
        ],
        'operator' => [
            'type' => 'string',
            'description' => 'The comparison operator to use.',
            'enum' => [
                // Boolean
                'is_true', 'is_false',
                // String
                'equals', 'not_equals', 'contains', 'not_contains',
                'starts_with', 'ends_with', 'matches_regex', 'is_empty', 'is_not_empty',
                'equals_ignore_case',
                // String/Array
                'is_one_of', 'is_not_one_of', // Value should be an array of strings
                // Numeric
                'greater_than', 'less_than', 'equals_numeric', 'not_equals_numeric',
                'greater_than_or_equals', 'less_than_or_equals',
            ],
            'required' => true,
        ],
        'value' => [
            'type' => ['string', 'integer', 'boolean', 'array', 'null'],
            'description' => 'The value to compare against. Type depends on the field and operator. For "is_one_of", this should be an array.',
        ],
    ],
    'required' => ['type', 'field', 'operator'],
];