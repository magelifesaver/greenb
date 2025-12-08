<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/views/settings/partials/settings-advanced-moderation.php
// Status: NEW FILE

/**
 * Partial: OpenAI Moderation Settings (Pro Feature)
 * Renders the fields ONLY when the feature should be available.
 */
if (!defined('ABSPATH')) exit;

// Variables passed from parent (settings/index.php):
// $openai_moderation_enabled, $openai_moderation_message, $placeholder_openai_message
?>
<!-- OpenAI Moderation Settings -->
<h5><?php esc_html_e('OpenAI Moderation', 'gpt3-ai-content-generator'); ?></h5>
<div class="aipkit_form-group">
    <label class="aipkit_form-label aipkit_checkbox-label" for="aipkit_openai_moderation_enabled">
        <input
            type="checkbox"
            id="aipkit_openai_moderation_enabled"
            name="openai_moderation_enabled"
            class="aipkit_toggle_switch aipkit_autosave_trigger"
            value="1"
            <?php checked($openai_moderation_enabled, '1'); ?>
        >
        <?php esc_html_e('Enable', 'gpt3-ai-content-generator'); ?>
    </label>
</div>
<div class="aipkit_form-group">
    <label
        class="aipkit_form-label"
        for="aipkit_openai_moderation_message"
    >
        <?php esc_html_e('Notification Message', 'gpt3-ai-content-generator'); ?>
    </label>
    <input
        type="text"
        id="aipkit_openai_moderation_message"
        name="openai_moderation_message"
        class="aipkit_form-input aipkit_autosave_trigger"
        value="<?php echo esc_attr($openai_moderation_message); ?>"
        placeholder="<?php echo esc_attr($placeholder_openai_message); ?>"
    />
</div>
<!-- End OpenAI Moderation Settings -->