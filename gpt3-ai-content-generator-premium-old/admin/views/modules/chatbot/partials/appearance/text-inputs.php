<?php

/**
 * Partial: Appearance - Text Inputs (Greeting, Footer, Placeholder)
 */
if (!defined('ABSPATH')) {
    exit;
}

// Variables available from parent script:
// $bot_id, $bot_settings

$saved_greeting     = isset($bot_settings['greeting']) ? $bot_settings['greeting'] : '';
$saved_footer_text  = isset($bot_settings['footer_text']) ? $bot_settings['footer_text'] : '';
$saved_placeholder  = isset($bot_settings['input_placeholder']) ? $bot_settings['input_placeholder'] : __('Type your message...', 'gpt3-ai-content-generator');
?>

<!-- Custom Texts: Greeting, Placeholder, Footer, Typing Text (modern grid layout) -->
<div class="aipkit_settings_grid">
    <!-- Greeting -->
    <div class="aipkit_form-group aipkit_form-col">
        <label
            class="aipkit_form-label"
            for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_greeting"
        >
            <?php esc_html_e('Greeting', 'gpt3-ai-content-generator'); ?>
        </label>
        <input
            type="text"
            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_greeting"
            name="greeting"
            class="aipkit_form-input"
            value="<?php echo esc_attr($saved_greeting); ?>"
            placeholder="<?php esc_attr_e('Hello! How can I help?', 'gpt3-ai-content-generator'); ?>"
        />
        <div class="aipkit_form-help">
            <?php esc_html_e('Shown at the start of the chat.', 'gpt3-ai-content-generator'); ?>
        </div>
    </div>

    <!-- Placeholder -->
    <div class="aipkit_form-group aipkit_form-col">
        <label
            class="aipkit_form-label"
            for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_input_placeholder"
        >
            <?php esc_html_e('Placeholder', 'gpt3-ai-content-generator'); ?>
        </label>
        <input
            type="text"
            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_input_placeholder"
            name="input_placeholder"
            class="aipkit_form-input"
            value="<?php echo esc_attr($saved_placeholder); ?>"
            placeholder="<?php esc_attr_e('Type your message...', 'gpt3-ai-content-generator'); ?>"
        />
        <div class="aipkit_form-help">
            <?php esc_html_e('Shown in the input before typing.', 'gpt3-ai-content-generator'); ?>
        </div>
    </div>

    <!-- Footer -->
    <div class="aipkit_form-group aipkit_form-col">
        <label
            class="aipkit_form-label"
            for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_footer_text"
        >
            <?php esc_html_e('Footer', 'gpt3-ai-content-generator'); ?>
        </label>
        <input
            type="text"
            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_footer_text"
            name="footer_text"
            class="aipkit_form-input"
            value="<?php echo esc_attr($saved_footer_text); ?>"
            placeholder="<?php esc_attr_e('Powered by AI', 'gpt3-ai-content-generator'); ?>"
        />
        <div class="aipkit_form-help">
            <?php esc_html_e('Appears below the chat area.', 'gpt3-ai-content-generator'); ?>
        </div>
    </div>

    <?php // Include Typing Text input inline so it aligns next to Footer ?>
    <?php include __DIR__ . '/typing-indicator.php'; ?>
</div>
