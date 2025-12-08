<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/chat/triggers/actions/AIPKit_Placeholder_Processor.php
// Status: MODIFIED

namespace WPAICG\Lib\Chat\Triggers\Actions; // UPDATED Namespace

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * AIPKit_Placeholder_Processor
 *
 * Handles replacing placeholders in string templates with context data.
 * MODIFIED: Added $mode parameter for context-specific placeholder replacement (display vs. log_summary).
 */
class AIPKit_Placeholder_Processor {

    const LOG_SNIPPET_LENGTH = 75; // Max length for snippets in log_summary mode

    /**
     * Replaces placeholders in a string with values from context_data.
     * Example placeholders: {{user_message_text}}, {{user_name}}, {{bot_name}}.
     *
     * @param string $template The string template with placeholders.
     * @param array  $context_data Associative array of context data.
     * @param string $mode 'display' (default) for user-facing output, 'log_summary' for concise log messages.
     * @return string The processed string.
     */
    public static function process(string $template, array $context_data, string $mode = 'display'): string {
        $processed_string = $template;

        // Define available placeholders and their corresponding context_data keys
        $user_message_raw = $context_data['user_message_text'] ?? '';
        $submitted_data_json_raw = $context_data['submitted_data_json'] ?? null; // This is already JSON string from store_form_submission

        $placeholder_map = [
            '{{user_id}}'           => $context_data['user_id'] ?? 'guest',
            '{{bot_id}}'            => $context_data['bot_id'] ?? 'unknown',
            '{{bot_name}}'          => $context_data['bot_settings']['name'] ?? 'Chatbot',
            '{{post_id}}'           => $context_data['post_id'] ?? '0',
            '{{post_title}}'        => (isset($context_data['post_id']) && $context_data['post_id'] > 0) ? get_the_title($context_data['post_id']) : '',
            '{{user_ip}}'           => $context_data['client_ip'] ?? '', // Already anonymized if addon active
            '{{form_id}}'           => $context_data['form_id'] ?? '', // For store_form_submission
            '{{form_submission_summary}}' => $context_data['form_submission_summary'] ?? '', // For store_form_submission
        ];

        // Special handling for user_name
        if (isset($context_data['user_id']) && $context_data['user_id'] > 0) {
            $user_info = get_userdata($context_data['user_id']);
            $placeholder_map['{{user_name}}'] = $user_info ? $user_info->display_name : 'User';
        } else {
            $placeholder_map['{{user_name}}'] = 'Guest';
        }

        // Mode-specific handling for sensitive/long placeholders
        if ($mode === 'log_summary') {
            $placeholder_map['{{user_message_text}}'] = '[User Message Snippet: ' . mb_substr($user_message_raw, 0, self::LOG_SNIPPET_LENGTH) . (mb_strlen($user_message_raw) > self::LOG_SNIPPET_LENGTH ? '...' : '') . ']';
            // submitted_data_json is handled by form_submission_summary now if that placeholder is used
            // If {{submitted_data_json}} is still directly in template for logging, it should be redacted.
            $placeholder_map['{{submitted_data_json}}'] = '[Form Data Submitted - See Details]';
        } else { // 'display' mode
            $placeholder_map['{{user_message_text}}'] = $user_message_raw;
            $placeholder_map['{{submitted_data_json}}'] = $submitted_data_json_raw ?? '[Form Data]';
        }


        foreach ($placeholder_map as $placeholder => $value) {
            // For log_summary, value is already a summary or safe. For display, it's raw and needs escaping.
            $replacement_value = ($mode === 'log_summary') ? (string) $value : esc_html((string) $value);
            $processed_string = str_replace($placeholder, $replacement_value, $processed_string);
        }

        return $processed_string;
    }
}