<?php

/**
 * Partial: Appearance - Conversation Starters Textarea
 */
if (!defined('ABSPATH')) {
    exit;
}

// Variables available from parent script:
// $bot_id, $bot_settings, $starters_addon_active

$enable_conversation_starters = isset($bot_settings['enable_conversation_starters'])
    ? $bot_settings['enable_conversation_starters']
    : \WPAICG\Chat\Storage\BotSettingsManager::DEFAULT_ENABLE_CONVERSATION_STARTERS;
$conversation_starters = isset($bot_settings['conversation_starters']) ? $bot_settings['conversation_starters'] : [];
$conversation_starters_text = implode("\n", $conversation_starters);

?>
<!-- 5) Conversation Starters Textarea: toggled instantly -->
<?php if ($starters_addon_active): ?>
    <div
        class="aipkit_form-group aipkit_starters_conditional_row"
        style="display: <?php echo ($enable_conversation_starters === '1') ? 'block' : 'none'; ?>;"
    >
        <label
            class="aipkit_form-label"
            for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_conversation_starters"
        >
            <?php esc_html_e('Starter Prompts (max 6)', 'gpt3-ai-content-generator'); ?>
        </label>
        <textarea
            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_conversation_starters"
            name="conversation_starters"
            class="aipkit_form-input"
            rows="4"
        ><?php echo esc_textarea($conversation_starters_text); ?></textarea>
        <div class="aipkit_form-help">
            <?php esc_html_e('Enter one prompt per line. Users can click them to start a conversation. Defaults are used if empty.', 'gpt3-ai-content-generator'); ?>
        </div>
    </div>
<?php endif; ?>