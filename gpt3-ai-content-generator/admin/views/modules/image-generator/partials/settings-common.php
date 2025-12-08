<?php
// File: /Applications/MAMP/htdocs/wordpress/wp-content/plugins/gpt3-ai-content-generator/admin/views/modules/image-generator/partials/settings-common.php
// NEW FILE

/**
 * Partial: Image Generator Settings - Common Settings
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Variables required from parent settings-image-generator.php:
// $settings_data (array containing common settings)

$custom_css = $settings_data['common']['custom_css'] ?? '';

$default_css_template = "/* --- AIPKit Image Generator Custom CSS Example (Dark Theme Base) --- */
.aipkit_image_generator_public_wrapper.aipkit-theme-custom {
    background-color: #1f2937; /* Dark grey background */
    border: 1px solid #4b5563; /* Slightly lighter border */
    color: #e5e7eb; /* Light text */
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    margin: 1em 0;
    padding: 20px;
}

.aipkit_image_generator_public_wrapper.aipkit-theme-custom .aipkit_image_generator_input_bar {
    background-color: #374151; /* Slightly lighter than container bg */
    border: 1px solid #4b5563;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* ... Add more rules as needed ... */
";

?>
<!-- Custom CSS Section -->
<p class="aipkit_form-help">
    <?php esc_html_e('Add your custom CSS rules here to style the shortcode when using [aipkit_image_generator theme="custom"]. This allows for full control over the generator\'s appearance on the frontend.', 'gpt3-ai-content-generator'); ?>
    <br>
    <em><?php esc_html_e('Example CSS (based on dark theme) is provided below. Edit or replace it with your own styles.', 'gpt3-ai-content-generator'); ?></em>
</p>
 <div class="aipkit_form-group">
     <label class="aipkit_form-label" for="aipkit_image_generator_custom_css"><?php esc_html_e('Custom CSS Rules', 'gpt3-ai-content-generator'); ?></label>
    <textarea
        id="aipkit_image_generator_custom_css"
        name="custom_css"
        class="aipkit_form-input aipkit_settings_input" <?php // Added aipkit_settings_input for JS save ?>
        rows="15"
        style="font-family: monospace; font-size: 12px; white-space: pre; overflow-x: auto;"
        placeholder="<?php echo esc_attr($default_css_template); ?>"
    ><?php echo esc_textarea($custom_css ?: $default_css_template); // Show default if saved is empty ?></textarea>
</div>