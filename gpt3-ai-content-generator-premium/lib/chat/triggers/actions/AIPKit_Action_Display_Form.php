<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/chat/triggers/actions/AIPKit_Action_Display_Form.php
// Status: NEW FILE

namespace WPAICG\Lib\Chat\Triggers\Actions;

use WPAICG\Lib\Chat\Triggers\Actions\AIPKit_Placeholder_Processor;
use WP_Error;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Action_Display_Form
 *
 * Handles the 'display_form' trigger action.
 * Prepares the form definition for rendering on the frontend.
 */
class AIPKit_Action_Display_Form {

    /**
     * Executes the 'display_form' action.
     *
     * @param array $payload Payload containing 'form_id', 'title', 'elements', 'submit_button_text'.
     * @param array $context_data Contextual data for placeholder replacement.
     * @param int   $bot_id Bot ID (unused in this specific action handler but part of the interface).
     * @return array|WP_Error Action result containing the processed form definition, or WP_Error on failure.
     */
    public function execute(array $payload, array $context_data, int $bot_id): array|WP_Error {
        $form_definition_template = $payload ?? [];

        // Require elements, but form_id can be auto-generated if missing
        if (empty($form_definition_template['elements']) || !is_array($form_definition_template['elements'])) {
            return new WP_Error(
                'invalid_form_payload',
                __('Invalid or missing form elements in display_form action payload.', 'gpt3-ai-content-generator')
            );
        }

        $processed_form_definition = $form_definition_template; // Start with a copy

        // Auto-generate a unique form_id if not provided
        if (empty($processed_form_definition['form_id']) || !is_string($processed_form_definition['form_id'])) {
            $rand = function_exists('wp_generate_password') ? wp_generate_password(6, false, false) : substr(bin2hex(random_bytes(4)), 0, 6);
            $processed_form_definition['form_id'] = 'form_' . time() . '_' . strtolower($rand);
        }

        // Ensure PlaceholderProcessor class exists
        $placeholder_processor_exists = class_exists(AIPKit_Placeholder_Processor::class);
        
        $process_fn = $placeholder_processor_exists ? [AIPKit_Placeholder_Processor::class, 'process'] : function($text, $ctx) { return $text; };

        // Process placeholders in top-level form definition fields
        if (isset($processed_form_definition['title'])) {
            $processed_form_definition['title'] = call_user_func($process_fn, $processed_form_definition['title'], $context_data);
        }
        if (isset($processed_form_definition['submit_button_text'])) {
            $processed_form_definition['submit_button_text'] = call_user_func($process_fn, $processed_form_definition['submit_button_text'], $context_data);
        }

        // Process placeholders within form elements
        if (isset($processed_form_definition['elements']) && is_array($processed_form_definition['elements'])) {
            foreach ($processed_form_definition['elements'] as &$element) {
                if (isset($element['label'])) {
                    $element['label'] = call_user_func($process_fn, $element['label'], $context_data);
                }
                if (isset($element['placeholder'])) {
                    $element['placeholder'] = call_user_func($process_fn, $element['placeholder'], $context_data);
                }
                if (isset($element['help_text'])) {
                    $element['help_text'] = call_user_func($process_fn, $element['help_text'], $context_data);
                }
                // Process placeholders in options for select, radio_group, checkbox_group
                if (isset($element['options']) && is_array($element['options'])) {
                    foreach ($element['options'] as &$option) {
                        if (isset($option['text'])) {
                            $option['text'] = call_user_func($process_fn, $option['text'], $context_data);
                        }
                        // Note: 'value' in options is usually not processed for placeholders as it's for backend logic
                    }
                    unset($option); // Unset reference
                }
                 // Process default_value if it's a string
                if (isset($element['default_value']) && is_string($element['default_value'])) {
                    $element['default_value'] = call_user_func($process_fn, $element['default_value'], $context_data);
                }
            }
            unset($element); // Unset reference
        }

        return [
            'type'              => 'display_form', // Signals frontend to render a form
            'form_definition'   => $processed_form_definition,
        ];
    }
}
