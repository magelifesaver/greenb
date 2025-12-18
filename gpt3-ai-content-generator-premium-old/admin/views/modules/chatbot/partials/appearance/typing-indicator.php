<?php
/**
 * Partial: Appearance - Typing Indicator Customization
 */
if (!defined('ABSPATH')) {
    exit;
}

// Variables available from parent script:
// $bot_id, $bot_settings

$custom_typing_text = isset($bot_settings['custom_typing_text']) ? $bot_settings['custom_typing_text'] : '';
?>

<div class="aipkit_form-group aipkit_form-col">
    <label class="aipkit_form-label" for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_custom_typing_text">
        <?php esc_html_e('Typing text', 'gpt3-ai-content-generator'); ?>
    </label>
    <input
        type="text"
        id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_custom_typing_text"
        name="custom_typing_text"
        class="aipkit_form-input"
        value="<?php echo esc_attr($custom_typing_text); ?>"
        placeholder="<?php esc_attr_e('Thinking', 'gpt3-ai-content-generator'); ?>"
    />
    <div class="aipkit_form-help">
        <?php esc_html_e('Leave empty to use the standard animated dots.', 'gpt3-ai-content-generator'); ?>
    </div>
</div>
