<?php

// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/classes/core/stream/contexts/ai-forms/process/build-prompt.php
// Status: MODIFIED

namespace WPAICG\Core\Stream\Contexts\AIForms\Process;

use WP_Error;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Builds the final prompt string from the template and submitted data.
 *
 * @param array $form_config The configuration of the form.
 * @param array $submitted_fields The sanitized submitted data.
 * @return string|WP_Error The final prompt string or WP_Error if template is missing.
 */
function build_prompt_logic(array $form_config, array $submitted_fields): string|WP_Error
{
    $prompt_template = $form_config['prompt_template'] ?? '';
    $form_structure = $form_config['structure'] ?? [];

    if (empty($prompt_template)) {
        return new WP_Error('missing_template', __('Form prompt template is not configured.', 'gpt3-ai-content-generator'), ['status' => 500]);
    }

    $final_prompt = $prompt_template;

    if (!empty($form_structure) && is_array($form_structure)) {
        foreach ($form_structure as $row) {
            if (empty($row['columns']) || !is_array($row['columns'])) {
                continue;
            }
            foreach ($row['columns'] as $column) {
                if (empty($column['elements']) || !is_array($column['elements'])) {
                    continue;
                }
                foreach ($column['elements'] as $element) {
                    $field_id = $element['fieldId'] ?? null;
                    if (!$field_id) { // Skip if element has no fieldId
                        continue;
                    }

                    $placeholder = '{' . $field_id . '}';
                    $value_to_substitute = ''; // Default to empty string if field not submitted

                    if (isset($submitted_fields[$field_id])) {
                        $submitted_value = $submitted_fields[$field_id];
                        $element_type = $element['type'] ?? 'text-input';

                        switch ($element_type) {
                            case 'select':
                            case 'radio-button':
                                $options = $element['options'] ?? [];
                                $found_option_text = false;
                                foreach ($options as $option) {
                                    if (isset($option['value']) && $option['value'] == $submitted_value) { // Use == for loose comparison
                                        $value_to_substitute = $option['text'] ?? $submitted_value;
                                        $found_option_text = true;
                                        break;
                                    }
                                }
                                if (!$found_option_text) {
                                    $value_to_substitute = $submitted_value; // Fallback to raw value if no matching option text found
                                }
                                break;

                            case 'checkbox':
                                // Handle both array and single/comma-separated string value for robustness.
                                $submitted_values_array = [];
                                if (is_array($submitted_value)) {
                                    $submitted_values_array = $submitted_value;
                                } elseif (is_string($submitted_value) && !empty($submitted_value)) {
                                    // This handles both a single value string and a comma-separated string
                                    $submitted_values_array = array_map('trim', explode(',', $submitted_value));
                                }

                                if (!empty($submitted_values_array)) {
                                    $labels_to_substitute = [];
                                    $options = $element['options'] ?? [];
                                    // Loop through submitted values and find their corresponding display text.
                                    foreach ($submitted_values_array as $val) {
                                        $found_text = false;
                                        foreach ($options as $option) {
                                            if (isset($option['value']) && $option['value'] == $val) { // Use == for loose comparison
                                                $labels_to_substitute[] = $option['text'] ?? $val;
                                                $found_text = true;
                                                break;
                                            }
                                        }
                                        if (!$found_text) {
                                            $labels_to_substitute[] = $val; // Fallback to the raw value
                                        }
                                    }
                                    $value_to_substitute = implode(', ', $labels_to_substitute);
                                } else {
                                    // If nothing is selected, substitute with an empty string.
                                    $value_to_substitute = '';
                                }
                                break;

                            default: // 'text-input', 'textarea', 'file-upload' etc.
                                $value_to_substitute = $submitted_value;
                                break;
                        }
                    }

                    // Replace placeholder in the prompt with the determined value.
                    // This will also replace with '' if the field wasn't submitted, effectively removing the placeholder.
                    $final_prompt = str_replace($placeholder, $value_to_substitute, $final_prompt);
                }
            }
        }
    }

    // Handle the legacy/simple case where a single 'user_input' is expected
    // This is less likely with the new structure but good for backward compatibility
    if (empty($form_structure) && isset($submitted_fields['user_input'])) {
        $final_prompt = str_replace('{user_input}', $submitted_fields['user_input'], $final_prompt);
    }

    return $final_prompt;
}