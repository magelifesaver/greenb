<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/chatbot/partials/accordion-triggers.php
// Status: MODIFIED

/**
 * Partial: Chatbot Triggers Accordion Content
 */
if (!defined('ABSPATH')) {
    exit;
}

// Variables available from parent script:
// $bot_id, $bot_settings

$triggers_json = $bot_settings['triggers_json'] ?? '[]'; // Default to an empty JSON array string

?>
<div class="aipkit_accordion" data-section="triggers">
    <div class="aipkit_accordion-header">
        <span class="dashicons dashicons-arrow-right-alt2"></span>
        <?php esc_html_e('Triggers (Beta)', 'gpt3-ai-content-generator'); ?>
    </div>
    <div class="aipkit_accordion-content">
        <p class="aipkit_form-help">
            <?php esc_html_e('Configure automated triggers based on events, conditions, and actions. Enter the configuration as a JSON array of trigger objects.', 'gpt3-ai-content-generator'); ?>
            <a href="https://aipower.org/docs/chatbot-triggers/" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Learn More', 'gpt3-ai-content-generator'); ?></a>
        </p>
        <div class="aipkit_form-group">
            <label
                class="aipkit_form-label"
                for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_triggers_json"
            >
                <?php esc_html_e('Triggers JSON Configuration', 'gpt3-ai-content-generator'); ?>
            </label>
            <textarea
                id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_triggers_json"
                name="triggers_json" <?php // Name used in FormData by chat-admin-saver.js and sanitize-settings-logic.php ?>
                class="aipkit_form-input"
                rows="10"
                placeholder="<?php esc_attr_e('e.g., [{ "id": "greeting_trigger", "name": "Welcome Message", ... }]', 'gpt3-ai-content-generator'); ?>"
                style="font-family: monospace; font-size: 12px; white-space: pre; overflow-wrap: normal; overflow-x: scroll;"
            ><?php echo esc_textarea($triggers_json); ?></textarea>
            <div class="aipkit_form-help">
                <?php esc_html_e('Refer to the documentation for the correct JSON schema and examples. Invalid JSON will be ignored or reset to an empty array on save.', 'gpt3-ai-content-generator'); ?>
            </div>
        </div>
    </div>
</div>