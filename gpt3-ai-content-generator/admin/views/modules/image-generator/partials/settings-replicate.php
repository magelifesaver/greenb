<?php
/**
 * Partial: Image Generator Settings - Replicate Provider
 * Settings specific to the Replicate image generation provider.
 */
if (!defined('ABSPATH')) {
    exit;
}

// Variables required from parent settings-image-generator.php:
// $settings_data (array containing replicate settings)
$replicate_settings = $settings_data['replicate'] ?? [];
$disable_safety_checker = $replicate_settings['disable_safety_checker'] ?? true;
?>
<p class="aipkit_form-help">
    <?php esc_html_e('Configure settings specific to Replicate image generation. By default, the safety checker is disabled to avoid false positives that may block legitimate prompts.', 'gpt3-ai-content-generator'); ?>
</p>

<div class="aipkit_form-group">
    <label class="aipkit_form-label aipkit_checkbox-label">
        <input 
            type="checkbox" 
            id="aipkit_replicate_disable_safety_checker" 
            name="replicate_disable_safety_checker" 
            class="aipkit_form-input aipkit_settings_input" 
            value="1" 
            <?php checked($disable_safety_checker, true); ?>
        >
        <span class="aipkit_checkbox-text">
            <?php esc_html_e('Disable Safety Checker', 'gpt3-ai-content-generator'); ?>
        </span>
    </label>
    <div class="aipkit_form-help">
        <?php esc_html_e('When enabled, this disables Replicate\'s built-in safety checker to prevent false positives that may block legitimate image generation prompts. Only disable this if you have other content moderation measures in place.', 'gpt3-ai-content-generator'); ?>
    </div>
</div> 