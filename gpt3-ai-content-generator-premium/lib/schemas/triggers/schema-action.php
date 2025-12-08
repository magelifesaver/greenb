<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/schemas/triggers/schema-action.php
// Status: MODIFIED

/**
 * AIPKit Chatbot Triggers - Action Schema Definition
 * UPDATED: Added 'display_form' action type and its payload definition.
 * UPDATED: Added 'store_form_submission' action type and its payload definition.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

return [
    'type' => 'object',
    'properties' => [
        'type' => [
            'type' => 'string',
            'description' => 'The type of action to perform.',
            'enum' => [
                'bot_reply',        // Send a predefined message from the bot.
                'inject_context',   // Modify context being sent to the AI.
                'block_message',    // Prevent the user\'s message from reaching the AI.
                'call_webhook',     // Make an HTTP request to an external URL.
                'set_variable',     // Set a variable in user_meta or current bot_context.
                'display_form',     // Display a form in the chat interface.
                'store_form_submission', // NEW: Store submitted form data to the chat log.
            ],
            'required' => true,
        ],
        'payload' => [
            'type' => 'object',
            'description' => 'Action-specific configuration data.',
            'properties' => [
                // For 'bot_reply'
                'message' => ['type' => 'string', 'description' => 'The message text for the bot to send. Supports {{placeholders}}.'],
                'stop_processing_ai' => ['type' => 'boolean', 'description' => 'If true, the original user message will not be sent to the AI after this reply.'],

                // For 'inject_context'
                'placement' => [
                    'type' => 'string',
                    'enum' => ['system_instruction_prepend', 'system_instruction_append', 'history_prepend'],
                    'description' => 'Where to inject the content.',
                ],
                'content' => ['type' => 'string', 'description' => 'The content to inject. Supports {{placeholders}}.'],

                // For 'block_message'
                'reason' => ['type' => 'string', 'description' => 'Optional user-facing message explaining why their message was blocked.'],

                // For 'call_webhook'
                'endpoint_url' => ['type' => 'string', 'format' => 'uri', 'description' => 'The URL to call.'],
                'http_method' => ['type' => 'string', 'enum' => ['GET', 'POST', 'PUT', 'DELETE'], 'default' => 'POST'],
                'headers' => ['type' => 'object', 'description' => 'Key-value pairs for HTTP headers. Values support {{placeholders}}.'],
                'body_template' => ['type' => 'string', 'description' => 'JSON string template for the request body. Supports {{placeholders}}.'],
                'timeout_seconds' => ['type' => 'integer', 'default' => 10, 'minimum' => 1, 'maximum' => 30],

                // For 'set_variable'
                'scope' => ['type' => 'string', 'enum' => ['user_meta', 'bot_context'], 'description' => 'Where to set the variable.'],
                'key' => ['type' => 'string', 'description' => 'The key/name of the variable.'],
                // 'value' for 'set_variable' is at the top level of the action object itself.

                // For 'display_form'
                'form_id' => ['type' => 'string', 'description' => 'Auto-generated unique identifier for this form instance (hidden in UI).'],
                'title' => ['type' => 'string', 'description' => 'Optional title to display above the form.'],
                'elements' => [
                    'type' => 'array',
                    'description' => 'An array of form element objects.',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'id' => ['type' => 'string', 'description' => 'Unique ID for this field within the form.'],
                            'type' => [
                                'type' => 'string',
                                'description' => 'Element type.',
                                'enum' => ["text_input", "textarea", "dropdown", "label", "heading", "checkbox_group", "radio_group"]
                            ],
                            'label' => ['type' => 'string', 'description' => 'Display label for the element.'],
                            'placeholder' => ['type' => 'string', 'description' => 'Placeholder text for input/textarea.'],
                            'options' => [
                                'type' => 'array',
                                'description' => 'Array of options for dropdown, radio_group, checkbox_group.',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'value' => ['type' => 'string'],
                                        'text' => ['type' => 'string']
                                    ],
                                    'required' => ['value', 'text']
                                ]
                            ],
                            'required' => ['type' => 'boolean', 'description' => 'If the field is mandatory.'],
                            'default_value' => ['type' => ['string', 'array'], 'description' => 'Default value for the field (string or array for checkbox_group).'],
                            'help_text' => ['type' => 'string', 'description' => 'Small help text below the field.']
                        ],
                        'required' => ['id', 'type']
                    ]
                ],
                'submit_button_text' => ['type' => 'string', 'description' => 'Text for the form\'s submit button (e.g., "Submit Query").', 'default' => 'Submit'],

                // NEW: For 'store_form_submission'
                'log_message_format' => ['type' => 'string', 'description' => 'Optional format for the log message. Placeholders: {{form_id}}, {{user_name}}, {{form_submission_summary}}.', 'default' => "Form '{{form_id}}' submitted by {{user_name}}. Summary: {{form_submission_summary}}"],
            ],
            'if' => ['properties' => ['type' => ['const' => 'bot_reply']]],
            'then' => ['required' => ['message']],
            'else if' => ['properties' => ['type' => ['const' => 'inject_context']]],
            'then' => ['required' => ['placement', 'content']],
            'else if' => ['properties' => ['type' => ['const' => 'call_webhook']]],
            'then' => ['required' => ['endpoint_url', 'http_method']],
            'else if' => ['properties' => ['type' => ['const' => 'set_variable']]],
            'then' => ['required' => ['scope', 'key', 'value']], // Value is defined top-level in action object
            'else if' => ['properties' => ['type' => ['const' => 'display_form']]],
            'then' => ['required' => ['elements']],
            // NEW: else if for store_form_submission
            'else if' => ['properties' => ['type' => ['const' => 'store_form_submission']]],
            'then' => [/* 'log_message_format' is optional with a default */],
        ],
        'value' => [ // This 'value' is specifically for 'set_variable' action.
            'type' => ['string', 'integer', 'boolean', 'null'],
            'description' => 'The value to set for the variable (for set_variable action). Supports {{placeholders}}.'
        ],
    ],
    'required' => ['type', 'payload'],
];
