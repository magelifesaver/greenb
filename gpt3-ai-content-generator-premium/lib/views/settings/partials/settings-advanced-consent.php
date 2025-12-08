<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/lib/views/settings/partials/settings-advanced-consent.php
// Status: NEW FILE

/**
 * Partial: Consent Compliance Settings (Pro Feature)
 * Renders the fields ONLY when the feature should be available.
 */
if (!defined('ABSPATH')) exit;

// Variables passed from parent (settings/index.php):
// $saved_consent_title, $placeholder_consent_title, $saved_consent_message,
// $placeholder_consent_message, $saved_consent_button, $placeholder_consent_button
?>
<!-- Consent Compliance Settings -->
<div class="aipkit_form-group">
    <label
        class="aipkit_form-label"
        for="aipkit_consent_title"
    >
        <?php esc_html_e('Consent Box Title', 'gpt3-ai-content-generator'); ?>
    </label>
    <input
        type="text"
        id="aipkit_consent_title"
        name="consent_title"
        class="aipkit_form-input aipkit_autosave_trigger"
        value="<?php echo esc_attr($saved_consent_title); ?>"
        placeholder="<?php echo esc_attr($placeholder_consent_title); ?>"
    />
</div>

<div class="aipkit_form-group">
    <label
        class="aipkit_form-label"
        for="aipkit_consent_message"
    >
        <?php esc_html_e('Consent Box Message', 'gpt3-ai-content-generator'); ?>
    </label>
    <textarea
        id="aipkit_consent_message"
        name="consent_message"
        class="aipkit_form-input aipkit_autosave_trigger"
        rows="4"
        placeholder="<?php echo esc_attr($placeholder_consent_message); ?>"
    ><?php echo wp_kses_post($saved_consent_message); ?></textarea>
</div>

<div class="aipkit_form-group">
    <label
        class="aipkit_form-label"
        for="aipkit_consent_button"
    >
        <?php esc_html_e('Consent Button Text', 'gpt3-ai-content-generator'); ?>
    </label>
    <input
        type="text"
        id="aipkit_consent_button"
        name="consent_button"
        class="aipkit_form-input aipkit_autosave_trigger"
        value="<?php echo esc_attr($saved_consent_button); ?>"
        placeholder="<?php echo esc_attr($placeholder_consent_button); ?>"
    />
</div>
<!-- End Consent Compliance Settings -->