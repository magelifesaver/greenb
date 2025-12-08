<?php

/**
 * Partial: AI Config - Stream Mode Toggle
 */
if (!defined('ABSPATH')) {
    exit;
}

// Variables required from parent script (accordion-ai-config.php):
// $bot_id, $bot_settings

$saved_stream_enabled = isset($bot_settings['stream_enabled']) ? $bot_settings['stream_enabled'] : '0'; // Default disabled

?>
<!-- Stream Mode Checkbox -->
<div class="aipkit_form-group">
     <label
        class="aipkit_form-label aipkit_checkbox-label"
        for="aipkit_bot_<?php echo esc_attr($bot_id); ?>_stream_enabled"
    >
        <input
            type="checkbox"
            id="aipkit_bot_<?php echo esc_attr($bot_id); ?>_stream_enabled"
            name="stream_enabled"
            class="aipkit_toggle_switch"
            value="1"
            <?php checked($saved_stream_enabled, '1'); ?>
        >
        <?php esc_html_e('Stream Mode', 'gpt3-ai-content-generator'); ?>
    </label>
    <div class="aipkit_form-help">
         <?php esc_html_e('Users see the response appear word by word. Recommended for a faster feel.', 'gpt3-ai-content-generator'); ?>
    </div>
</div>