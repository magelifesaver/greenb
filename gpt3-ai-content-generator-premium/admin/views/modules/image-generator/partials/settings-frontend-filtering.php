<?php
/**
 * Partial: Image Generator Settings - Frontend Filtering
 */
if (!defined('ABSPATH')) {
    exit;
}

// Variables required from parent settings-image-generator.php:
// $settings_data (array containing frontend_display settings)
$frontend_display_settings = $settings_data['frontend_display'] ?? [];
$allowed_providers_str = $frontend_display_settings['allowed_providers'] ?? '';
$allowed_models_str = $frontend_display_settings['allowed_models'] ?? '';
?>
<p class="aipkit_form-help">
    <?php esc_html_e('Control which AI providers and models are available to users on the frontend Image Generator shortcode.', 'gpt3-ai-content-generator'); ?>
</p>
<div class="aipkit_form-group">
    <label class="aipkit_form-label" for="aipkit_image_gen_frontend_models">
        <?php esc_html_e('Allowed Models', 'gpt3-ai-content-generator'); ?>
    </label>
    <!-- Progressive Enhancement: Hidden original textarea kept for backward compatibility -->
    <textarea
        id="aipkit_image_gen_frontend_models"
        name="frontend_models"
        class="aipkit_form-input aipkit_settings_input"
        style="display:none;" 
        rows="4"
        placeholder="<?php esc_attr_e('Select models below or leave empty for all', 'gpt3-ai-content-generator'); ?>"
    ><?php echo esc_textarea($allowed_models_str); ?></textarea>
    <div id="aipkit_image_gen_models_selector" class="aipkit_models_selector" data-initial-value="<?php echo esc_attr($allowed_models_str); ?>" data-empty-all-selected="<?php echo $allowed_models_str === '' ? '1' : '0'; ?>">
        <div class="aipkit_models_selector-loading">
            <?php esc_html_e('Loading model list…', 'gpt3-ai-content-generator'); ?>
        </div>
    </div>
    <div class="aipkit_form-help">
        <?php esc_html_e('Pick specific models to show on the frontend. Leave everything unselected to allow ALL models. Use "All models" toggle per provider to avoid listing them individually.', 'gpt3-ai-content-generator'); ?>
    </div>
</div>