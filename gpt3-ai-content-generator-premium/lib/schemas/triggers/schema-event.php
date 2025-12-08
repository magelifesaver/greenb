<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/schemas/triggers/schema-event.php
// Status: MODIFIED

/**
 * AIPKit Chatbot Triggers - Event Schema Definition
 * UPDATED: Added 'form_submitted' event and its parameters.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

return [
    'type' => 'object',
    'properties' => [
        'name' => [
            'type' => 'string',
            'description' => 'The name of the event.',
            'enum' => [
                'user_message_received', // Fired when a user sends a message (before AI processing).
                                         // Context: user_id, session_id, bot_id, bot_settings, user_message_text, current_history, client_ip, post_id, message_count, system_instruction, user_roles, current_provider, current_model_id
                'session_started',       // Fired when a new chat session begins (with the first user message).
                                         // Context: user_id, session_id, bot_id, bot_settings, client_ip, post_id, user_message_text (first message), system_instruction, user_roles, current_provider, current_model_id
                'system_error_occurred', // Fired when an internal AI processing error occurs.
                                         // Context: error_code, error_message, bot_id, user_id, session_id, module, operation, failed_provider, failed_model
                'form_submitted',        // NEW: Fired when a dynamically displayed form is submitted.
                                         // Context: bot_id, form_id, submitted_data, user_id, session_id, conversation_uuid, client_ip, post_id, bot_settings, user_roles, current_provider, current_model_id
                // 'ai_response_generated' // Future: Fired after AI generates a response (for modification).
            ],
            'required' => true,
        ],
        'params' => [
            'type' => 'object',
            'description' => 'Event-specific parameters that might be configured with the event in a trigger. Context for conditions is passed dynamically.',
            'properties' => [
                // Example for a hypothetical future event:
                // 'page_url_matches' => [
                // 'if' => ['properties' => ['name' => ['const' => 'page_visit']]],
                // 'then' => ['type' => 'string', 'format' => 'uri']
                // ],
                // --- NEW: Parameters specifically for 'form_submitted' event ---
                'form_id_is' => [ // This param allows pre-filtering by form_id in the trigger itself.
                                 // The actual `form_id` for conditions will come from dynamic context.
                    'if' => ['properties' => ['name' => ['const' => 'form_submitted']]],
                    'then' => [
                        'type' => 'string',
                        'description' => 'Optional: Trigger only if the submitted form_id matches this value. Conditions can also check `form_id` from context.',
                    ],
                ],
                // The actual submitted_data fields will be accessed via context, e.g., context_data.submitted_data.field_name
            ],
            'additionalProperties' => true, // Allow other params for extensibility
        ],
    ],
    'required' => ['name'],
];